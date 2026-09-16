<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioner;
use App\Support\TenantDatabaseManager;
use Database\Seeders\ModuleSeeder;
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

        $databases = app(TenantDatabaseManager::class);
        $databases->connect($this->tenant);

        $salesRole = Role::query()->create([
            'name' => 'Sales',
            'is_system' => false,
        ]);

        $salesRole->permissions()->sync(
            Permission::query()->where('name', 'sales.orders.view')->pluck('id')
        );

        $this->admin = User::query()->where('email', 'admin@acme.test')->firstOrFail();

        $this->sales = User::query()->create([
            'name' => 'Sales User',
            'email' => 'sales@acme.test',
            'password' => 'password',
        ]);
        $this->sales->roles()->sync([$salesRole->id]);

        $databases->disconnect();
    }

    protected function onTenantHost(): static
    {
        return $this->withServerVariables([
            'HTTP_HOST' => 'acme.localhost',
            'SERVER_NAME' => 'acme.localhost',
        ]);
    }

    public function test_sales_user_cannot_manage_roles(): void
    {
        $this->onTenantHost()
            ->actingAs($this->sales)
            ->get('http://acme.localhost/settings/roles')
            ->assertForbidden();
    }

    public function test_admin_can_create_role_with_unique_name(): void
    {
        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/settings/roles', ['name' => 'Warehouse'])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $this->assertDatabaseHas('roles', ['name' => 'Warehouse'], 'tenant');

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->post('http://acme.localhost/settings/roles', ['name' => 'Warehouse'])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_assign_permissions_and_users(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);

        $role = Role::query()->create([
            'name' => 'Ops',
            'is_system' => false,
        ]);

        $permissionId = Permission::query()->where('name', 'sales.orders.confirm')->value('id');
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->put('http://acme.localhost/settings/roles/'.$role->id, [
                'name' => 'Ops',
                'permissions' => [$permissionId],
                'users' => [$this->sales->id],
            ])
            ->assertRedirect();

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $role = Role::query()->findOrFail($role->id);
        $this->assertTrue($role->permissions->contains('id', $permissionId));
        $this->assertTrue($role->users->contains('id', $this->sales->id));
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);
        $system = Role::query()->where('name', 'Admin')->firstOrFail();
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->delete('http://acme.localhost/settings/roles/'.$system->id)
            ->assertForbidden();
    }

    public function test_custom_role_can_be_deleted(): void
    {
        app(TenantDatabaseManager::class)->connect($this->tenant);
        $role = Role::query()->where('name', 'Sales')->firstOrFail();
        app(TenantDatabaseManager::class)->disconnect();

        $this->onTenantHost()
            ->actingAs($this->admin)
            ->delete('http://acme.localhost/settings/roles/'.$role->id)
            ->assertRedirect('http://acme.localhost/settings/roles');

        app(TenantDatabaseManager::class)->connect($this->tenant);
        $this->assertDatabaseMissing('roles', ['id' => $role->id], 'tenant');
    }
}
