<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Central domains (no tenant context)
    |--------------------------------------------------------------------------
    */
    'central_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TENANCY_CENTRAL_DOMAINS', 'erp.webyouneed.id,localhost,127.0.0.1'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Base host used to build tenant URLs in the UI
    |--------------------------------------------------------------------------
    */
    'base_host' => env('TENANCY_BASE_HOST', 'erp.webyouneed.id'),

    /*
    |--------------------------------------------------------------------------
    | Tenant database naming
    |--------------------------------------------------------------------------
    */
    'database_prefix' => env('TENANCY_DB_PREFIX', 'daksa_t_'),

    /*
    |--------------------------------------------------------------------------
    | Relative path for tenant migrations
    |--------------------------------------------------------------------------
    */
    'tenant_migrations_path' => 'database/migrations/tenant',

    /*
    |--------------------------------------------------------------------------
    | Auto-provision Let's Encrypt certificates for tenant subdomains
    |--------------------------------------------------------------------------
    |
    | When enabled, creating a tenant expands the cert to include
    | {slug}.{base_host}. Requires the sync script + passwordless sudo.
    |
    */
    'ssl_auto' => (bool) env('TENANCY_SSL_AUTO', false),

    'ssl_sync_command' => env('TENANCY_SSL_SYNC_COMMAND', 'sudo /usr/local/bin/daksa-erp-ssl-sync'),

];
