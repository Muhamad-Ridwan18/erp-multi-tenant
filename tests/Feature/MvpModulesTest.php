<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioner;
use App\Support\TenantContext;
use App\Support\TenantDatabaseManager;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MvpModulesTest extends TestCase
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

    public function test_admin_can_create_customer_product_and_confirm_order(): void
    {
        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/sales/customers', [
                'name' => 'PT Buyer',
                'email' => 'buyer@example.com',
            ])
            ->assertRedirect(route('tenant.customers.index'));

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/inventory/products', [
                'sku' => 'SKU-1',
                'name' => 'Widget',
                'unit' => 'pcs',
                'price' => 15000,
                'stock_qty' => 10,
                'is_active' => '1',
            ])
            ->assertRedirect(route('tenant.products.index'));

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $customerId = Customer::query()->where('name', 'PT Buyer')->value('id');
        $productId = Product::query()->where('sku', 'SKU-1')->value('id');
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/sales/orders', [
                'customer_id' => $customerId,
                'items' => [
                    ['product_id' => $productId, 'quantity' => 3, 'unit_price' => 15000, 'discount_percent' => 0, 'tax_percent' => 10],
                ],
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $order = SalesOrder::query()->where('status', 'draft')->firstOrFail();
        $this->assertSame(45000, $order->subtotal);
        $this->assertSame(4500, $order->tax_total);
        $this->assertSame(49500, $order->grand_total);
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/sales/orders/'.$order->id.'/confirm')
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $order->refresh();
        $product = Product::query()->findOrFail($productId);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame(7, $product->stock_qty);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $productId,
            'type' => 'sale',
            'quantity' => -3,
            'balance_after' => 7,
        ], 'tenant');
        app(TenantDatabaseManager::class)->disconnect();
    }

    public function test_confirm_fails_when_stock_insufficient(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);

        $customer = Customer::query()->create(['name' => 'Buyer']);
        $product = Product::query()->create([
            'sku' => 'LOW',
            'name' => 'Low stock',
            'unit' => 'pcs',
            'price' => 1000,
            'stock_qty' => 1,
            'is_active' => true,
        ]);
        $order = SalesOrder::query()->create([
            'number' => 'SO-TEST-0001',
            'customer_id' => $customer->id,
            'status' => 'draft',
            'subtotal' => 5000,
            'created_by' => $this->admin->id,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 1000,
            'line_total' => 5000,
        ]);

        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->from('http://acme.localhost/sales/orders/'.$order->id)
            ->post('http://acme.localhost/sales/orders/'.$order->id.'/confirm')
            ->assertRedirect()
            ->assertSessionHasErrors('order');

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $this->assertSame('draft', $order->fresh()->status);
        $this->assertSame(1, $product->fresh()->stock_qty);
        app(TenantDatabaseManager::class)->disconnect();
    }

    public function test_user_without_permission_cannot_view_customers(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);

        $role = Role::query()->create(['name' => 'Viewer', 'is_system' => false]);
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
            ->get('http://acme.localhost/sales/customers')
            ->assertForbidden();
    }
}
