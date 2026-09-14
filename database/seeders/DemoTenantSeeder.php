<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $platform = User::query()->updateOrCreate(
            ['email' => 'platform@daksa.test', 'tenant_id' => null],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make('password'),
                'is_platform_admin' => true,
            ]
        );

        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => 'demo-co'],
            [
                'name' => 'Demo Company',
                'status' => 'active',
                'trial_ends_at' => now()->addDays(14),
            ]
        );

        $plan = Plan::query()->where('code', 'business')->firstOrFail();

        Subscription::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
            ],
            [
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
            ]
        );

        TenantContext::set($tenant);

        $adminRole = Role::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Admin'],
            ['is_system' => true]
        );

        $salesRole = Role::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Sales'],
            ['is_system' => false]
        );

        $enabledModules = $tenant->enabledModuleCodes();

        $allPermissionIds = Permission::query()
            ->whereIn('module_code', $enabledModules)
            ->pluck('id')
            ->all();

        $adminRole->permissions()->sync($allPermissionIds);

        $salesPermissionIds = Permission::query()
            ->whereIn('module_code', ['partners', 'sales'])
            ->whereIn('action', ['view', 'create', 'update', 'confirm', 'send'])
            ->pluck('id')
            ->all();

        $salesRole->syncPermissionsWithinPlan($salesPermissionIds, $enabledModules);

        $tenantAdmin = User::query()->updateOrCreate(
            ['email' => 'admin@demo.test', 'tenant_id' => $tenant->id],
            [
                'name' => 'Tenant Admin',
                'password' => Hash::make('password'),
                'is_platform_admin' => false,
            ]
        );

        $salesUser = User::query()->updateOrCreate(
            ['email' => 'sales@demo.test', 'tenant_id' => $tenant->id],
            [
                'name' => 'Sales User',
                'password' => Hash::make('password'),
                'is_platform_admin' => false,
            ]
        );

        $tenantAdmin->roles()->sync([$adminRole->id]);
        $salesUser->roles()->sync([$salesRole->id]);

        TenantContext::clear();

        $this->command?->info('Demo accounts:');
        $this->command?->line("  Platform: {$platform->email} / password");
        $this->command?->line("  Tenant admin: {$tenantAdmin->email} / password");
        $this->command?->line("  Sales user: {$salesUser->email} / password");
    }
}
