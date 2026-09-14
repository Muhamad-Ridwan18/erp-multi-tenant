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

];
