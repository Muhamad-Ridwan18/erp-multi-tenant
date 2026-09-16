<?php

namespace Tests\Feature;

use App\Models\BillOfMaterial;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\ManufacturingOrder;
use App\Models\Plan;
use App\Models\Product;
use App\Models\StockOperation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\StockService;
use App\Services\TenantProvisioner;
use App\Support\TenantDatabaseManager;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Wave2ModulesTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            ModuleSeeder::class,
            PlanSeeder::class,
        ]);

        $this->tenant = app(TenantProvisioner::class)->provision([
            'name' => 'Acme',
            'slug' => 'acme',
            'status' => 'active',
            'plan_id' => Plan::query()->where('code', 'business')->value('id'),
            'admin_name' => 'Tenant Admin',
            'admin_email' => 'admin@acme.test',
            'admin_password' => 'password',
        ]);

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $this->admin = User::query()->where('email', 'admin@acme.test')->firstOrFail();
        app(TenantDatabaseManager::class)->disconnect();
    }

    protected function onTenantHost(): static
    {
        return $this->withServerVariables([
            'HTTP_HOST' => 'acme.localhost',
            'SERVER_NAME' => 'acme.localhost',
        ]);
    }

    public function test_bom_mo_produce_consumes_components_and_adds_finished_goods(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);

        $component = Product::query()->create([
            'sku' => 'RAW-1',
            'name' => 'Raw',
            'barcode' => '111',
            'unit' => 'pcs',
            'price' => 1000,
            'stock_qty' => 0,
            'is_active' => true,
            'type' => 'goods',
        ]);
        $finished = Product::query()->create([
            'sku' => 'FG-1',
            'name' => 'Finished',
            'barcode' => '222',
            'unit' => 'pcs',
            'price' => 5000,
            'stock_qty' => 0,
            'is_active' => true,
            'type' => 'goods',
        ]);

        app(StockService::class)->adjust($component, 10, $this->admin, 'seed');

        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/manufacturing/boms', [
                'code' => 'BOM-FG',
                'product_id' => $finished->id,
                'quantity' => 1,
                'lines' => [
                    ['product_id' => $component->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $bom = BillOfMaterial::query()->where('code', 'BOM-FG')->firstOrFail();
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/manufacturing/orders', [
                'product_id' => $finished->id,
                'bill_of_material_id' => $bom->id,
                'quantity' => 3,
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $mo = ManufacturingOrder::query()->where('product_id', $finished->id)->firstOrFail();
        $this->assertSame('draft', $mo->status);
        $this->assertSame(6, $mo->components()->sum('quantity'));
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/manufacturing/orders/'.$mo->id.'/produce', [
                'quantity' => 3,
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $mo->refresh();
        $component->refresh();
        $finished->refresh();
        $this->assertSame('done', $mo->status);
        $this->assertSame(3, $mo->qty_produced);
        $this->assertSame(4, $component->stock_qty);
        $this->assertSame(3, $finished->stock_qty);
        $this->assertTrue(StockOperation::query()->where('manufacturing_order_id', $mo->id)->exists());
        app(TenantDatabaseManager::class)->disconnect();
    }

    public function test_scrap_and_barcode_lookup(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);
        $product = Product::query()->create([
            'sku' => 'SCR-1',
            'name' => 'Scrapable',
            'barcode' => '999888',
            'unit' => 'pcs',
            'price' => 1000,
            'stock_qty' => 0,
            'is_active' => true,
            'type' => 'goods',
        ]);
        app(StockService::class)->adjust($product, 5, $this->admin, 'seed');
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->getJson('http://acme.localhost/inventory/barcode/lookup?code=999888')
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('product.sku', 'SCR-1');

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/inventory/scraps', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $product->refresh();
        $this->assertSame(3, $product->stock_qty);
        $this->assertTrue(StockOperation::query()->where('type', 'scrap')->exists());
        app(TenantDatabaseManager::class)->disconnect();
    }

    public function test_credit_note_reverses_posted_invoice(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);
        $customer = Customer::query()->create(['name' => 'Buyer']);
        $product = Product::query()->create([
            'sku' => 'INV-P',
            'name' => 'Item',
            'unit' => 'pcs',
            'price' => 10000,
            'stock_qty' => 10,
            'is_active' => true,
        ]);
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/finance/invoices', [
                'customer_id' => $customer->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'description' => 'Item',
                        'quantity' => 1,
                        'unit_price' => 10000,
                        'discount_percent' => 0,
                        'tax_percent' => 0,
                    ],
                ],
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $invoice = Invoice::query()->latest('id')->firstOrFail();
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/finance/invoices/'.$invoice->id.'/post')
            ->assertRedirect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/finance/invoices/'.$invoice->id.'/credit-note')
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $invoice->refresh();
        $credit = Invoice::query()->where('reversed_invoice_id', $invoice->id)->firstOrFail();
        $this->assertSame('reversed', $invoice->status);
        $this->assertSame('out_refund', $credit->move_type);
        $this->assertSame('posted', $credit->status);
        $this->assertTrue(JournalEntry::query()->where('document_id', $credit->id)->exists());
        app(TenantDatabaseManager::class)->disconnect();
    }
}
