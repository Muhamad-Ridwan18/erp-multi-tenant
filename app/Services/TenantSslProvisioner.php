<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

class TenantSslProvisioner
{
    public function sync(): bool
    {
        if (! config('tenancy.ssl_auto')) {
            return false;
        }

        $command = (string) config('tenancy.ssl_sync_command');

        if ($command === '') {
            return false;
        }

        try {
            $result = Process::timeout(180)->run($command);

            if (! $result->successful()) {
                Log::error('Tenant SSL sync failed.', [
                    'exit_code' => $result->exitCode(),
                    'output' => $result->output(),
                    'error' => $result->errorOutput(),
                ]);

                return false;
            }

            Log::info('Tenant SSL sync completed.', [
                'tenants' => Tenant::query()->pluck('slug')->all(),
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('Tenant SSL sync exception.', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
