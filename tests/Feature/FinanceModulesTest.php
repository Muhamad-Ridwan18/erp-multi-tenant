<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Services\TenantProvisioner;
use App\Support\TenantContext;
use App\Support\TenantDatabaseManager;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FinanceModulesTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists(database_path('tenants'));

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

    protected function tearDown(): void
    {
        TenantContext::clear();
        app(TenantDatabaseManager::class)->disconnect();

        foreach (File::glob(database_path('tenants/*.sqlite')) as $file) {
            File::delete($file);
        }

        parent::tearDown();
    }

    protected function onTenantHost(): static
    {
        return $this->withServerVariables([
            'HTTP_HOST' => 'acme.localhost',
            'SERVER_NAME' => 'acme.localhost',
        ]);
    }

    public function test_invoice_from_confirmed_sales_order_can_be_posted_and_paid(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);

        $customer = Customer::query()->create(['name' => 'Buyer']);
        $product = Product::query()->create([
            'sku' => 'P1',
            'name' => 'Widget',
            'unit' => 'pcs',
            'price' => 10000,
            'stock_qty' => 20,
            'is_active' => true,
        ]);
        $order = SalesOrder::query()->create([
            'number' => 'SO-TEST-0001',
            'customer_id' => $customer->id,
            'status' => 'confirmed',
            'subtotal' => 20000,
            'confirmed_at' => now(),
            'created_by' => $this->admin->id,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 10000,
            'line_total' => 20000,
        ]);

        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/finance/invoices/from-order/'.$order->id)
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $invoice = Invoice::query()->where('sales_order_id', $order->id)->firstOrFail();
        $this->assertSame('draft', $invoice->status);
        $this->assertSame(20000, $invoice->subtotal);
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/finance/invoices/'.$invoice->id.'/post')
            ->assertRedirect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/finance/invoices/'.$invoice->id.'/pay', [
                'amount' => 20000,
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $invoice->refresh();
        $this->assertSame('posted', $invoice->status);
        $this->assertSame(20000, $invoice->amount_paid);
        $this->assertTrue($invoice->isPaid());
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'direction' => 'incoming',
            'amount' => 20000,
        ], 'tenant');
        app(TenantDatabaseManager::class)->disconnect();
    }

    public function test_bill_from_received_purchase_order_can_be_posted_and_paid(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);

        $vendor = Vendor::query()->create(['name' => 'Supplier']);
        $product = Product::query()->create([
            'sku' => 'R1',
            'name' => 'Raw',
            'unit' => 'pcs',
            'price' => 5000,
            'stock_qty' => 10,
            'is_active' => true,
        ]);
        $order = PurchaseOrder::query()->create([
            'number' => 'PO-TEST-0001',
            'vendor_id' => $vendor->id,
            'status' => 'received',
            'subtotal' => 15000,
            'confirmed_at' => now(),
            'received_at' => now(),
            'created_by' => $this->admin->id,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 5000,
            'line_total' => 15000,
        ]);

        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/finance/bills/from-purchase/'.$order->id)
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $bill = Bill::query()->where('purchase_order_id', $order->id)->firstOrFail();
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/finance/bills/'.$bill->id.'/post')
            ->assertRedirect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/finance/bills/'.$bill->id.'/pay', [
                'amount' => 15000,
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $bill->refresh();
        $this->assertTrue($bill->isPaid());
        $this->assertDatabaseHas('payments', [
            'bill_id' => $bill->id,
            'direction' => 'outgoing',
            'amount' => 15000,
        ], 'tenant');
        app(TenantDatabaseManager::class)->disconnect();
    }

    public function test_cannot_invoice_draft_sales_order(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);

        $customer = Customer::query()->create(['name' => 'Buyer']);
        $order = SalesOrder::query()->create([
            'number' => 'SO-DRAFT-1',
            'customer_id' => $customer->id,
            'status' => 'draft',
            'subtotal' => 0,
            'created_by' => $this->admin->id,
        ]);

        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->from('http://acme.localhost/sales/orders/'.$order->id)
            ->post('http://acme.localhost/finance/invoices/from-order/'.$order->id)
            ->assertRedirect()
            ->assertSessionHasErrors('order');
    }

    public function test_user_without_permission_cannot_view_invoices(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);

        $role = Role::query()->create(['name' => 'SalesOnly', 'is_system' => false]);
        $role->permissions()->sync(
            Permission::query()->where('name', 'sales.orders.view')->pluck('id')
        );

        $user = User::query()->create([
            'name' => 'Limited',
            'email' => 'limited@acme.test',
            'password' => 'password',
        ]);
        $user->roles()->sync([$role->id]);

        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($user)
            ->get('http://acme.localhost/finance/invoices')
            ->assertForbidden();
    }
}
