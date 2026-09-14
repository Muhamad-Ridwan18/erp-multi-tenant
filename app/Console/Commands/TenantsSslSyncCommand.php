<?php

namespace App\Console\Commands;

use App\Services\TenantSslProvisioner;
use Illuminate\Console\Command;

class TenantsSslSyncCommand extends Command
{
    protected $signature = 'tenants:ssl-sync';

    protected $description = 'Expand Let\'s Encrypt certificate to cover all tenant subdomains';

    public function handle(TenantSslProvisioner $ssl): int
    {
        if (! config('tenancy.ssl_auto')) {
            $this->warn('TENANCY_SSL_AUTO is disabled.');

            return self::SUCCESS;
        }

        $this->info('Syncing SSL certificates for tenant subdomains...');

        if (! $ssl->sync()) {
            $this->error('SSL sync failed. Check storage/logs/laravel.log');

            return self::FAILURE;
        }

        $this->info('SSL sync completed.');

        return self::SUCCESS;
    }
}
