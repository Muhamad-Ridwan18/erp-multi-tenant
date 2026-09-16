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
use App\Models\Warehouse;
use App\Services\StockService;
use App\Services\TenantProvisioner;
use App\Support\TenantDatabaseManager;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MvpModulesTest extends TestCase
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

    public function test_admin_can_create_customer_product_confirm_order_and_deliver(): void
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
        $this->assertSame('no', $order->delivery_status);
        $this->assertSame(10, $product->stock_qty, 'Confirming reserves nothing; stock leaves on delivery.');
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/sales/orders/'.$order->id.'/deliver')
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $order->refresh();
        $product->refresh();
        $this->assertSame('full', $order->delivery_status);
        $this->assertSame(7, $product->stock_qty);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $productId,
            'type' => 'delivery',
            'quantity' => -3,
            'balance_after' => 7,
        ], 'tenant');
        app(TenantDatabaseManager::class)->disconnect();
    }

    public function test_delivery_fails_when_stock_insufficient(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);

        $customer = Customer::query()->create(['name' => 'Buyer']);
        $product = Product::query()->create([
            'sku' => 'LOW',
            'name' => 'Low stock',
            'unit' => 'pcs',
            'price' => 1000,
            'stock_qty' => 0,
            'is_active' => true,
        ]);
        app(StockService::class)->adjust($product, 1, $this->admin, 'Opening stock');

        $order = SalesOrder::query()->create([
            'number' => 'SO-TEST-0001',
            'customer_id' => $customer->id,
            'status' => 'confirmed',
            'subtotal' => 5000,
            'confirmed_at' => now(),
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
            ->post('http://acme.localhost/sales/orders/'.$order->id.'/deliver')
            ->assertRedirect()
            ->assertSessionHasErrors('order');

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $this->assertSame('no', $order->fresh()->delivery_status);
        $this->assertSame(1, $product->fresh()->stock_qty);
        app(TenantDatabaseManager::class)->disconnect();
    }

    public function test_warehouse_creation_adds_a_stock_location(): void
    {
        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/inventory/warehouses', [
                'code' => 'WH02',
                'name' => 'Second Warehouse',
                'is_active' => '1',
            ])
            ->assertRedirect(route('tenant.warehouses.index'));

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $warehouse = Warehouse::query()->where('code', 'WH02')->firstOrFail();
        $this->assertDatabaseHas('locations', [
            'warehouse_id' => $warehouse->id,
            'code' => 'STOCK',
            'type' => 'internal',
        ], 'tenant');
        app(TenantDatabaseManager::class)->disconnect();
    }

    public function test_new_inventory_and_finance_pages_render(): void
    {
        $pages = [
            'inventory/products/create',
            'inventory/warehouses',
            'inventory/warehouses/create',
            'inventory/operations',
            'inventory/operations?type=receipt',
            'finance/invoices/create',
            'finance/bills/create',
            'finance/taxes',
            'settings/categories',
        ];

        foreach ($pages as $page) {
            $this->onTenantHost()
                ->actingAs($this->admin)
                ->get('http://acme.localhost/'.$page)
                ->assertOk();
        }
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
