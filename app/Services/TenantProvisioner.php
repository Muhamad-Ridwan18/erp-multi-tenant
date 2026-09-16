<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantDatabaseManager;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\PermissionCatalogSeeder;
use Illuminate\Support\Facades\DB;

class TenantProvisioner
{
    public function __construct(
        protected TenantDatabaseManager $databases,
        protected TenantSslProvisioner $ssl,
    ) {}

    /**
     * @param  array{name: string, slug: string, status: string, plan_id: int, admin_name?: string|null, admin_email?: string|null, admin_password?: string|null}  $data
     */
    public function provision(array $data): Tenant
    {
        $database = config('tenancy.database_prefix').$data['slug'];

        $tenant = DB::connection('central')->transaction(function () use ($data, $database) {
            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'database' => $database,
                'status' => $data['status'],
                'trial_ends_at' => $data['status'] === 'trial' ? now()->addDays(14) : null,
            ]);

            $this->activatePlan($tenant, (int) $data['plan_id']);

            return $tenant;
        });

        $this->databases->createDatabase($tenant);
        $this->databases->migrate($tenant);
        $this->databases->connect($tenant);

        try {
            (new PermissionCatalogSeeder)->run();
            (new MasterDataSeeder)->run();

            if (! empty($data['admin_email'])) {
                $this->createAdmin(
                    $tenant,
                    $data['admin_name'] ?? 'Admin',
                    $data['admin_email'],
                    $data['admin_password'] ?? 'password'
                );
            }
        } finally {
            $this->databases->disconnect();
        }

        $this->ssl->sync();

        return $tenant->fresh(['activeSubscription.plan']);
    }

    public function activatePlan(Tenant $tenant, int $planId): void
    {
        Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled', 'ends_at' => now()]);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $planId,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);
    }

    public function createAdmin(Tenant $tenant, string $name, string $email, string $password): User
    {
        $this->databases->connect($tenant);

        $adminRole = Role::query()->firstOrCreate(
            ['name' => 'Admin'],
            ['is_system' => true]
        );

        $enabledModules = $tenant->enabledModuleCodes();
        $permissionIds = Permission::query()
            ->whereIn('module_code', $enabledModules)
            ->pluck('id')
            ->all();

        $adminRole->permissions()->sync($permissionIds);

        $admin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
            ]
        );

        $admin->roles()->sync([$adminRole->id]);

        return $admin;
    }

    public function migrateExisting(Tenant $tenant): void
    {
        if (! $this->databases->databaseExists($tenant)) {
            $this->databases->createDatabase($tenant);
        }

        $this->databases->migrate($tenant);
    }
}
