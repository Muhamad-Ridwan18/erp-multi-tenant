<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Throwable;

class TenantDatabaseManager
{
    public function databaseName(Tenant $tenant): string
    {
        return $tenant->database ?: config('tenancy.database_prefix').$tenant->slug;
    }

    public function sqlitePath(string $databaseName): string
    {
        return database_path('tenants/'.$databaseName.'.sqlite');
    }

    public function configure(Tenant $tenant): void
    {
        $databaseName = $this->databaseName($tenant);
        $driver = config('database.connections.central.driver');

        if ($driver === 'sqlite') {
            $path = $this->sqlitePath($databaseName);
            config(['database.connections.tenant.database' => $path]);
        } else {
            config(['database.connections.tenant.database' => $databaseName]);
            config([
                'database.connections.tenant.host' => config('database.connections.central.host'),
                'database.connections.tenant.port' => config('database.connections.central.port'),
                'database.connections.tenant.username' => config('database.connections.central.username'),
                'database.connections.tenant.password' => config('database.connections.central.password'),
                'database.connections.tenant.unix_socket' => config('database.connections.central.unix_socket'),
            ]);
        }

        DB::purge('tenant');
    }

    public function connect(Tenant $tenant): void
    {
        $this->configure($tenant);
        DB::connection('tenant')->getPdo();
        TenantContext::set($tenant);
    }

    public function disconnect(): void
    {
        TenantContext::clear();
        DB::purge('tenant');
    }

    public function createDatabase(Tenant $tenant): void
    {
        $databaseName = $this->databaseName($tenant);
        $driver = config('database.connections.central.driver');

        if ($driver === 'sqlite') {
            File::ensureDirectoryExists(database_path('tenants'));
            $path = $this->sqlitePath($databaseName);
            if (! File::exists($path)) {
                File::put($path, '');
            }

            return;
        }

        $charset = config('database.connections.central.charset', 'utf8mb4');
        $collation = config('database.connections.central.collation', 'utf8mb4_unicode_ci');

        DB::connection('central')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET {$charset} COLLATE {$collation}"
        );
    }

    public function migrate(Tenant $tenant): void
    {
        $this->configure($tenant);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => config('tenancy.tenant_migrations_path'),
            '--force' => true,
        ]);
    }

    public function databaseExists(Tenant $tenant): bool
    {
        $databaseName = $this->databaseName($tenant);
        $driver = config('database.connections.central.driver');

        if ($driver === 'sqlite') {
            return File::exists($this->sqlitePath($databaseName));
        }

        try {
            $result = DB::connection('central')->select(
                'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
                [$databaseName]
            );

            return count($result) > 0;
        } catch (Throwable) {
            return false;
        }
    }

    public function dropDatabase(Tenant $tenant): void
    {
        $databaseName = $this->databaseName($tenant);
        $driver = config('database.connections.central.driver');

        $this->disconnect();

        if ($driver === 'sqlite') {
            $path = $this->sqlitePath($databaseName);
            if (File::exists($path)) {
                File::delete($path);
            }

            return;
        }

        if (! preg_match('/^[a-zA-Z0-9_]+$/', $databaseName)) {
            throw new InvalidArgumentException('Invalid tenant database name.');
        }

        DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$databaseName}`");
    }
}
