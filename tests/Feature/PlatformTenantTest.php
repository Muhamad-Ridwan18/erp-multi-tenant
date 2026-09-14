<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioner;
use App\Support\TenantDatabaseManager;
use App\Support\TenantContext;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PlatformTenantTest extends TestCase
{
    use RefreshDatabase;

    protected User $platform;

    protected function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists(database_path('tenants'));

        $this->seed([
            ModuleSeeder::class,
            PlanSeeder::class,
        ]);

        $this->platform = User::factory()->platformAdmin()->create([
            'email' => 'platform@daksa.test',
        ]);
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

    public function test_non_platform_user_cannot_access_tenants_on_central(): void
    {
        // Tenant users are not on central DB; use a non-admin central user.
        $user = User::factory()->create([
            'email' => 'staff@central.test',
            'is_platform_admin' => false,
        ]);

        $this->actingAs($user)
            ->get(route('platform.tenants.index'))
            ->assertForbidden();
    }

    public function test_platform_admin_can_create_tenant_with_database(): void
    {
        $planId = Plan::query()->where('code', 'starter')->value('id');

        $response = $this->actingAs($this->platform)
            ->post(route('platform.tenants.store'), [
                'name' => 'New Co',
                'slug' => 'new-co',
                'status' => 'active',
                'plan_id' => $planId,
                'admin_name' => 'New Admin',
                'admin_email' => 'admin@newco.test',
                'admin_password' => 'password123',
            ]);

        $tenant = Tenant::query()->where('slug', 'new-co')->first();
        $this->assertNotNull($tenant);
        $this->assertSame('daksa_t_new-co', $tenant->database);
        $response->assertRedirect(route('platform.tenants.show', $tenant));

        $this->assertTrue(app(TenantDatabaseManager::class)->databaseExists($tenant));

        app(TenantDatabaseManager::class)->connect($tenant);
        $this->assertDatabaseHas('users', ['email' => 'admin@newco.test'], 'tenant');
        app(TenantDatabaseManager::class)->disconnect();
    }

    public function test_platform_admin_can_suspend_tenant(): void
    {
        $tenant = app(TenantProvisioner::class)->provision([
            'name' => 'Suspend Co',
            'slug' => 'suspend-co',
            'status' => 'active',
            'plan_id' => Plan::query()->where('code', 'starter')->value('id'),
        ]);

        $this->actingAs($this->platform)
            ->patch(route('platform.tenants.suspend', $tenant))
            ->assertRedirect();

        $this->assertSame('suspended', $tenant->fresh()->status);
    }

    public function test_unknown_subdomain_returns_not_found(): void
    {
        $this->get('http://missing.localhost/login')
            ->assertNotFound();
    }

    public function test_platform_routes_unavailable_on_tenant_host(): void
    {
        $tenant = app(TenantProvisioner::class)->provision([
            'name' => 'Host Co',
            'slug' => 'host-co',
            'status' => 'active',
            'plan_id' => Plan::query()->where('code', 'starter')->value('id'),
            'admin_name' => 'Admin',
            'admin_email' => 'admin@host.test',
            'admin_password' => 'password123',
        ]);

        app(TenantDatabaseManager::class)->connect($tenant);
        $admin = User::query()->where('email', 'admin@host.test')->firstOrFail();

        $this->actingAs($admin)
            ->get('http://host-co.localhost/platform/tenants')
            ->assertNotFound();
    }
}
