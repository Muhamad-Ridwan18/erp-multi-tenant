<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantProvisioner;
use Illuminate\Console\Command;

class TenantsMigrateCommand extends Command
{
    protected $signature = 'tenants:migrate {--tenant= : Tenant slug}';

    protected $description = 'Run tenant migrations for one or all tenants';

    public function handle(TenantProvisioner $provisioner): int
    {
        $query = Tenant::query()->orderBy('slug');

        if ($slug = $this->option('tenant')) {
            $query->where('slug', $slug);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');

            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $this->info("Migrating {$tenant->slug} ({$tenant->database})...");
            $provisioner->migrateExisting($tenant);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
