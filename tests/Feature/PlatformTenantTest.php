<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PermissionCatalogSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTenantTest extends TestCase
{
    use RefreshDatabase;

    protected User $platform;

    protected User $tenantUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            ModuleSeeder::class,
            PermissionCatalogSeeder::class,
            PlanSeeder::class,
        ]);

        $this->platform = User::factory()->platformAdmin()->create([
            'email' => 'platform@daksa.test',
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Existing Co',
            'slug' => 'existing-co',
            'status' => 'active',
        ]);

        $this->tenantUser = User::factory()->forTenant($tenant->id)->create([
            'email' => 'user@existing.test',
        ]);
    }

    public function test_non_platform_user_cannot_access_tenants(): void
    {
        $this->actingAs($this->tenantUser)
            ->get(route('platform.tenants.index'))
            ->assertForbidden();
    }

    public function test_platform_admin_can_create_tenant_with_admin(): void
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

        $response->assertRedirect(route('platform.tenants.show', $tenant));

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'plan_id' => $planId,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => 'admin@newco.test',
        ]);

        $this->assertDatabaseHas('roles', [
            'tenant_id' => $tenant->id,
            'name' => 'Admin',
            'is_system' => true,
        ]);
    }

    public function test_platform_admin_can_suspend_tenant(): void
    {
        $tenant = Tenant::query()->where('slug', 'existing-co')->firstOrFail();

        $this->actingAs($this->platform)
            ->patch(route('platform.tenants.suspend', $tenant))
            ->assertRedirect();

        $this->assertSame('suspended', $tenant->fresh()->status);
    }

    public function test_platform_admin_can_change_plan(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Plan Co',
            'slug' => 'plan-co',
            'status' => 'active',
        ]);

        $starter = Plan::query()->where('code', 'starter')->firstOrFail();
        $business = Plan::query()->where('code', 'business')->firstOrFail();

        $tenant->subscriptions()->create([
            'plan_id' => $starter->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);

        $this->actingAs($this->platform)
            ->put(route('platform.tenants.update', $tenant), [
                'name' => 'Plan Co',
                'slug' => 'plan-co',
                'status' => 'active',
                'plan_id' => $business->id,
            ])
            ->assertRedirect(route('platform.tenants.show', $tenant));

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'plan_id' => $starter->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'plan_id' => $business->id,
            'status' => 'active',
        ]);
    }
}
