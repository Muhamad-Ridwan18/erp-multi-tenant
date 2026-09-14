<?php

namespace App\Support;

class TenantContext
{
    protected static ?\App\Models\Tenant $tenant = null;

    public static function set(?\App\Models\Tenant $tenant): void
    {
        static::$tenant = $tenant;
    }

    public static function get(): ?\App\Models\Tenant
    {
        return static::$tenant;
    }

    public static function id(): ?int
    {
        return static::$tenant?->id;
    }

    public static function check(): bool
    {
        return static::$tenant !== null;
    }

    public static function clear(): void
    {
        static::$tenant = null;
    }

    public static function url(string $path = '/'): string
    {
        $tenant = static::get();
        $baseHost = config('tenancy.base_host');
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';

        if (! $tenant) {
            return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
        }

        $host = $tenant->slug.'.'.$baseHost;
        $path = '/'.ltrim($path, '/');

        return $scheme.'://'.$host.($path === '/' ? '' : rtrim($path, '/'));
    }

    public static function tenantUrl(\App\Models\Tenant $tenant, string $path = '/'): string
    {
        $baseHost = config('tenancy.base_host');
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $host = $tenant->slug.'.'.$baseHost;
        $path = '/'.ltrim($path, '/');

        return $scheme.'://'.$host.($path === '/' ? '' : rtrim($path, '/'));
    }
}
