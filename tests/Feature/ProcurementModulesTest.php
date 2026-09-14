<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Plan;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Role;
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

class ProcurementModulesTest extends TestCase
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

    public function test_admin_can_create_vendor_po_confirm_and_receive_stock(): void
    {
        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/procurement/vendors', [
                'name' => 'PT Supplier',
                'email' => 'vendor@example.com',
            ])
            ->assertRedirect(route('tenant.vendors.index'));

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $product = Product::query()->create([
            'sku' => 'RAW-1',
            'name' => 'Raw Material',
            'unit' => 'pcs',
            'price' => 5000,
            'stock_qty' => 2,
            'is_active' => true,
        ]);
        $vendorId = Vendor::query()->where('name', 'PT Supplier')->value('id');
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/procurement/purchases', [
                'vendor_id' => $vendorId,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 4000],
                ],
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $order = PurchaseOrder::query()->where('status', 'draft')->firstOrFail();
        $this->assertSame(20000, $order->subtotal);
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/procurement/purchases/'.$order->id.'/confirm')
            ->assertRedirect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/procurement/purchases/'.$order->id.'/receive')
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $order->refresh();
        $product->refresh();
        $this->assertSame('received', $order->status);
        $this->assertSame(7, $product->stock_qty);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'purchase',
            'quantity' => 5,
            'balance_after' => 7,
        ], 'tenant');
        app(TenantDatabaseManager::class)->disconnect();
    }

    public function test_receive_fails_when_order_still_draft(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);

        $vendor = Vendor::query()->create(['name' => 'Vendor']);
        $product = Product::query()->create([
            'sku' => 'X',
            'name' => 'Item',
            'unit' => 'pcs',
            'price' => 1000,
            'stock_qty' => 0,
            'is_active' => true,
        ]);
        $order = PurchaseOrder::query()->create([
            'number' => 'PO-TEST-0001',
            'vendor_id' => $vendor->id,
            'status' => 'draft',
            'subtotal' => 1000,
            'created_by' => $this->admin->id,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'line_total' => 1000,
        ]);

        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->from('http://acme.localhost/procurement/purchases/'.$order->id)
            ->post('http://acme.localhost/procurement/purchases/'.$order->id.'/receive')
            ->assertRedirect()
            ->assertSessionHasErrors('order');

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $this->assertSame('draft', $order->fresh()->status);
        $this->assertSame(0, $product->fresh()->stock_qty);
        app(TenantDatabaseManager::class)->disconnect();
    }

    public function test_user_without_permission_cannot_view_vendors(): void
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
            ->get('http://acme.localhost/procurement/vendors')
            ->assertForbidden();
    }
}
