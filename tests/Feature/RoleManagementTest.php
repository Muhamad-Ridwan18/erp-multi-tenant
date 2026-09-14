<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PermissionCatalogSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $admin;

    protected User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            ModuleSeeder::class,
            PermissionCatalogSeeder::class,
            PlanSeeder::class,
        ]);

        $this->tenant = Tenant::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'status' => 'active',
        ]);

        Subscription::query()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => Plan::query()->where('code', 'business')->value('id'),
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);

        TenantContext::set($this->tenant);

        $adminRole = Role::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin',
            'is_system' => true,
        ]);

        $salesRole = Role::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sales',
            'is_system' => false,
        ]);

        $adminRole->permissions()->sync(
            Permission::query()->whereIn('module_code', $this->tenant->enabledModuleCodes())->pluck('id')
        );

        $salesRole->permissions()->sync(
            Permission::query()->where('name', 'sales.orders.view')->pluck('id')
        );

        $this->admin = User::factory()->forTenant($this->tenant->id)->create([
            'email' => 'admin@acme.test',
        ]);
        $this->admin->roles()->sync([$adminRole->id]);

        $this->sales = User::factory()->forTenant($this->tenant->id)->create([
            'email' => 'sales@acme.test',
        ]);
        $this->sales->roles()->sync([$salesRole->id]);

        TenantContext::clear();
    }

    public function test_sales_user_cannot_manage_roles(): void
    {
        $this->actingAs($this->sales)
            ->get(route('tenant.roles.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_role_with_unique_name(): void
    {
        TenantContext::set($this->tenant);

        $this->actingAs($this->admin)
            ->post(route('tenant.roles.store'), ['name' => 'Warehouse'])
            ->assertRedirect();

        $this->assertDatabaseHas('roles', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Warehouse',
        ]);

        $this->actingAs($this->admin)
            ->post(route('tenant.roles.store'), ['name' => 'Warehouse'])
            ->assertSessionHasErrors('name');

        TenantContext::clear();
    }

    public function test_admin_can_assign_permissions_and_users(): void
    {
        TenantContext::set($this->tenant);

        $role = Role::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Ops',
            'is_system' => false,
        ]);

        $permissionId = Permission::query()->where('name', 'sales.orders.confirm')->value('id');

        $this->actingAs($this->admin)
            ->put(route('tenant.roles.update', $role), [
                'name' => 'Ops',
                'permissions' => [$permissionId],
                'users' => [$this->sales->id],
            ])
            ->assertRedirect();

        $this->assertTrue($role->fresh()->permissions->contains('id', $permissionId));
        $this->assertTrue($role->fresh()->users->contains('id', $this->sales->id));

        TenantContext::clear();
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        TenantContext::set($this->tenant);

        $system = Role::query()->where('name', 'Admin')->firstOrFail();

        $this->actingAs($this->admin)
            ->delete(route('tenant.roles.destroy', $system))
            ->assertForbidden();

        TenantContext::clear();
    }

    public function test_custom_role_can_be_deleted(): void
    {
        TenantContext::set($this->tenant);

        $role = Role::query()->where('name', 'Sales')->firstOrFail();

        $this->actingAs($this->admin)
            ->delete(route('tenant.roles.destroy', $role))
            ->assertRedirect(route('tenant.roles.index'));

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);

        TenantContext::clear();
    }
}
