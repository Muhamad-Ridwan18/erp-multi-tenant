<?php

namespace Tests\Feature;

use App\Models\BankStatement;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioner;
use App\Support\TenantDatabaseManager;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Wave4ModulesTest extends TestCase
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

    public function test_currency_rate_update_and_invoice_snapshots_fx(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);
        $usd = Currency::query()->where('code', 'USD')->firstOrFail();
        $customer = Customer::query()->create(['name' => 'FX Buyer']);
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->put('http://acme.localhost/finance/currencies/'.$usd->id, [
                'name' => 'US Dollar',
                'symbol' => '$',
                'rate' => 16500,
                'is_active' => 1,
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $usd->refresh();
        $this->assertEquals(16500.0, (float) $usd->rate);
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/finance/invoices', [
                'customer_id' => $customer->id,
                'currency_id' => $usd->id,
                'items' => [[
                    'description' => 'Export',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'discount_percent' => 0,
                    'tax_percent' => 0,
                ]],
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $invoice = Invoice::query()->latest('id')->firstOrFail();
        $this->assertSame($usd->id, $invoice->currency_id);
        $this->assertEquals(16500.0, (float) $invoice->currency_rate);
        $this->assertSame(100, $invoice->grand_total);
        $this->assertSame(1650000, $invoice->amount_company);
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->get('http://acme.localhost/finance/currencies')
            ->assertOk()
            ->assertSee('USD');
    }

    public function test_bank_statement_matches_payment_and_completes(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);
        $customer = Customer::query()->create(['name' => 'Bank Buyer']);
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/finance/invoices', [
                'customer_id' => $customer->id,
                'items' => [[
                    'description' => 'Goods',
                    'quantity' => 1,
                    'unit_price' => 50000,
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
            ->post('http://acme.localhost/finance/invoices/'.$invoice->id.'/pay', [
                'amount' => 50000,
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $payment = Payment::query()->latest('id')->firstOrFail();
        $this->assertFalse($payment->is_reconciled);
        $this->assertSame(50000, $payment->amount_company ?: $payment->amount);
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/finance/bank-statements', [
                'name' => 'March statement',
                'date' => now()->toDateString(),
                'balance_start' => 0,
                'balance_end' => 50000,
                'lines' => [[
                    'date' => now()->toDateString(),
                    'partner_name' => 'Bank Buyer',
                    'label' => 'Incoming',
                    'amount' => 50000,
                ]],
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $statement = BankStatement::query()->latest('id')->firstOrFail();
        $line = $statement->lines()->firstOrFail();
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/finance/bank-statements/'.$statement->id.'/lines/'.$line->id.'/match', [
                'payment_id' => $payment->id,
            ])
            ->assertRedirect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/finance/bank-statements/'.$statement->id.'/complete')
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $statement->refresh();
        $payment->refresh();
        $line->refresh();
        $this->assertSame('done', $statement->status);
        $this->assertTrue($line->is_reconciled);
        $this->assertTrue($payment->is_reconciled);
        $this->assertSame($line->id, $payment->bank_statement_line_id);
        app(TenantDatabaseManager::class)->disconnect();
    }

    public function test_barcode_page_renders_camera_scanner(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);
        Product::query()->create([
            'sku' => 'SCAN-1',
            'name' => 'Scan Me',
            'barcode' => '999888',
            'unit' => 'pcs',
            'price' => 1000,
            'stock_qty' => 0,
            'is_active' => true,
            'type' => 'goods',
        ]);
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->get('http://acme.localhost/inventory/barcode')
            ->assertOk()
            ->assertSee('html5-qrcode')
            ->assertSee('Camera scanner');
    }
}
