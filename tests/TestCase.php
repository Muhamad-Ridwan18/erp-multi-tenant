<?php

namespace Tests;

use App\Support\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Windows holds the SQLite file open after the test that migrated it,
        // so a leftover database cannot be reliably truncated or deleted.
        // Giving every test its own prefix keeps provisioning from replaying
        // migrations over a schema an earlier test left behind.
        config(['tenancy.database_prefix' => 'daksa_t_'.Str::lower(Str::random(10)).'_']);
    }

    protected function tearDown(): void
    {
        $this->deleteTenantDatabaseFiles();

        parent::tearDown();
    }

    /**
     * Best effort cleanup so a finished run does not leave databases behind.
     */
    protected function deleteTenantDatabaseFiles(): void
    {
        TenantContext::clear();
        DB::purge('tenant');

        $directory = database_path('tenants');

        File::ensureDirectoryExists($directory);

        // The tenant connection points here until a tenant is configured.
        if (! File::exists($directory.'/default.sqlite')) {
            File::put($directory.'/default.sqlite', '');
        }

        foreach (File::glob($directory.'/daksa_t_*') as $file) {
            if (File::isFile($file)) {
                @unlink($file);
            }
        }
    }
}
