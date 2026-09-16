<?php

namespace Tests\Feature;

use App\Models\BillOfMaterial;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lot;
use App\Models\ManufacturingOrder;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\UnbuildOrder;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\StockService;
use App\Services\TenantProvisioner;
use App\Support\TenantDatabaseManager;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Wave3ModulesTest extends TestCase
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

    public function test_bom_operations_create_work_orders_and_produce_with_lot(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);
        $raw = Product::query()->create([
            'sku' => 'RAW-W3',
            'name' => 'Raw W3',
            'unit' => 'pcs',
            'price' => 1000,
            'stock_qty' => 0,
            'is_active' => true,
            'type' => 'goods',
            'tracking' => 'none',
        ]);
        $fg = Product::query()->create([
            'sku' => 'FG-W3',
            'name' => 'Finished W3',
            'unit' => 'pcs',
            'price' => 5000,
            'stock_qty' => 0,
            'is_active' => true,
            'type' => 'goods',
            'tracking' => 'lot',
        ]);
        app(StockService::class)->adjust($raw, 20, $this->admin, 'seed');
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/manufacturing/boms', [
                'code' => 'BOM-W3',
                'product_id' => $fg->id,
                'quantity' => 1,
                'lines' => [['product_id' => $raw->id, 'quantity' => 2]],
                'operations' => [
                    ['name' => 'Assemble', 'duration_minutes' => 30],
                    ['name' => 'Pack', 'duration_minutes' => 10],
                ],
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $bom = BillOfMaterial::query()->where('code', 'BOM-W3')->firstOrFail();
        $this->assertCount(2, $bom->operations);
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/manufacturing/orders', [
                'product_id' => $fg->id,
                'bill_of_material_id' => $bom->id,
                'quantity' => 2,
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $mo = ManufacturingOrder::query()->where('product_id', $fg->id)->firstOrFail();
        $this->assertCount(2, $mo->workOrders);
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/manufacturing/orders/'.$mo->id.'/produce', [
                'quantity' => 2,
                'lot_name' => 'LOT-A1',
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $mo->refresh();
        $fg->refresh();
        $this->assertSame('done', $mo->status);
        $this->assertSame(2, $fg->stock_qty);
        $this->assertTrue(Lot::query()->where('name', 'LOT-A1')->where('product_id', $fg->id)->exists());
        $this->assertTrue(WorkOrder::query()->where('manufacturing_order_id', $mo->id)->where('status', 'done')->count() === 2);
        app(TenantDatabaseManager::class)->disconnect();
    }

    public function test_unbuild_returns_components_and_aging_page_loads(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);
        $raw = Product::query()->create([
            'sku' => 'RAW-UB',
            'name' => 'Raw UB',
            'unit' => 'pcs',
            'price' => 1000,
            'stock_qty' => 0,
            'is_active' => true,
            'type' => 'goods',
        ]);
        $fg = Product::query()->create([
            'sku' => 'FG-UB',
            'name' => 'Finished UB',
            'unit' => 'pcs',
            'price' => 5000,
            'stock_qty' => 0,
            'is_active' => true,
            'type' => 'goods',
        ]);
        app(StockService::class)->adjust($raw, 10, $this->admin, 'seed');
        app(StockService::class)->adjust($fg, 3, $this->admin, 'seed');
        $bom = BillOfMaterial::query()->create([
            'code' => 'BOM-UB',
            'product_id' => $fg->id,
            'quantity' => 1,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
        $bom->lines()->create(['product_id' => $raw->id, 'quantity' => 2, 'sort' => 0]);
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/manufacturing/unbuilds', [
                'product_id' => $fg->id,
                'bill_of_material_id' => $bom->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $ub = UnbuildOrder::query()->latest('id')->firstOrFail();
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/manufacturing/unbuilds/'.$ub->id.'/validate')
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $raw->refresh();
        $fg->refresh();
        $this->assertSame(2, $fg->stock_qty);
        $this->assertSame(12, $raw->stock_qty);
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->get('http://acme.localhost/finance/aging')
            ->assertOk();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->get('http://acme.localhost/finance/journal-entries')
            ->assertOk();
    }

    public function test_aging_includes_unpaid_posted_invoice(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);
        $customer = Customer::query()->create(['name' => 'Aging Buyer']);
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/finance/invoices', [
                'customer_id' => $customer->id,
                'items' => [[
                    'description' => 'Service',
                    'quantity' => 1,
                    'unit_price' => 100000,
                    'discount_percent' => 0,
                    'tax_percent' => 0,
                ]],
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
            ->get('http://acme.localhost/finance/aging?type=ar')
            ->assertOk()
            ->assertSee($invoice->number);
    }
}
