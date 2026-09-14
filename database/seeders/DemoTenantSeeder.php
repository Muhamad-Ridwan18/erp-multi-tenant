<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioner;
use App\Support\TenantDatabaseManager;
use Illuminate\Database\Seeder;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'platform@daksa.test'],
            [
                'name' => 'Platform Admin',
                'password' => 'password',
                'is_platform_admin' => true,
            ]
        );

        $plan = Plan::query()->where('code', 'business')->firstOrFail();
        $provisioner = app(TenantProvisioner::class);
        $databases = app(TenantDatabaseManager::class);

        $existing = Tenant::query()->where('slug', 'demo')->first();
        if ($existing) {
            $databases->dropDatabase($existing);
            $existing->subscriptions()->delete();
            $existing->delete();
        }

        $tenant = $provisioner->provision([
            'name' => 'Demo Company',
            'slug' => 'demo',
            'status' => 'active',
            'plan_id' => $plan->id,
            'admin_name' => 'Tenant Admin',
            'admin_email' => 'admin@demo.test',
            'admin_password' => 'password',
        ]);

        $databases->connect($tenant);

        try {
            $salesRole = Role::query()->updateOrCreate(
                ['name' => 'Sales'],
                ['is_system' => false]
            );

            $enabledModules = $tenant->enabledModuleCodes();
            $salesPermissionIds = Permission::query()
                ->where('module_code', 'sales')
                ->whereIn('action', ['view', 'create', 'update', 'confirm', 'send'])
                ->pluck('id')
                ->all();

            $salesRole->syncPermissionsWithinPlan($salesPermissionIds, $enabledModules);

            $salesUser = User::query()->updateOrCreate(
                ['email' => 'sales@demo.test'],
                [
                    'name' => 'Sales User',
                    'password' => 'password',
                ]
            );

            $salesUser->roles()->sync([$salesRole->id]);
        } finally {
            $databases->disconnect();
        }

        $this->command?->info('Demo accounts:');
        $this->command?->line('  Platform: platform@daksa.test / password (central domain)');
        $this->command?->line('  Tenant admin: admin@demo.test / password (demo subdomain)');
        $this->command?->line('  Sales user: sales@demo.test / password (demo subdomain)');
        $this->command?->line('  Tenant URL: '.$tenant->domainUrl('/login'));
    }
}
