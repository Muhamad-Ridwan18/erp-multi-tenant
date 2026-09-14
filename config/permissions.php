<?php

/**
 * Global permission catalog for Daksa ERP.
 * Roles pick from this list; plan modules limit what can be assigned.
 */
return [
    'modules' => [
        'partners' => 'Partners',
        'sales' => 'Sales',
        'inventory' => 'Inventory',
        'settings' => 'Settings',
    ],

    'permissions' => [
        // Partners
        ['module' => 'partners', 'resource' => 'customers', 'action' => 'view', 'description' => 'View customers'],
        ['module' => 'partners', 'resource' => 'customers', 'action' => 'create', 'description' => 'Create customers'],
        ['module' => 'partners', 'resource' => 'customers', 'action' => 'update', 'description' => 'Update customers'],
        ['module' => 'partners', 'resource' => 'customers', 'action' => 'delete', 'description' => 'Delete customers'],
        ['module' => 'partners', 'resource' => 'vendors', 'action' => 'view', 'description' => 'View vendors'],
        ['module' => 'partners', 'resource' => 'vendors', 'action' => 'create', 'description' => 'Create vendors'],
        ['module' => 'partners', 'resource' => 'vendors', 'action' => 'update', 'description' => 'Update vendors'],
        ['module' => 'partners', 'resource' => 'vendors', 'action' => 'delete', 'description' => 'Delete vendors'],

        // Sales
        ['module' => 'sales', 'resource' => 'orders', 'action' => 'view', 'description' => 'View sales orders'],
        ['module' => 'sales', 'resource' => 'orders', 'action' => 'create', 'description' => 'Create sales orders'],
        ['module' => 'sales', 'resource' => 'orders', 'action' => 'update', 'description' => 'Update sales orders'],
        ['module' => 'sales', 'resource' => 'orders', 'action' => 'delete', 'description' => 'Delete sales orders'],
        ['module' => 'sales', 'resource' => 'orders', 'action' => 'confirm', 'description' => 'Confirm sales orders'],
        ['module' => 'sales', 'resource' => 'quotations', 'action' => 'view', 'description' => 'View quotations'],
        ['module' => 'sales', 'resource' => 'quotations', 'action' => 'create', 'description' => 'Create quotations'],
        ['module' => 'sales', 'resource' => 'quotations', 'action' => 'update', 'description' => 'Update quotations'],
        ['module' => 'sales', 'resource' => 'quotations', 'action' => 'delete', 'description' => 'Delete quotations'],
        ['module' => 'sales', 'resource' => 'quotations', 'action' => 'send', 'description' => 'Send quotations'],

        // Inventory
        ['module' => 'inventory', 'resource' => 'products', 'action' => 'view', 'description' => 'View products'],
        ['module' => 'inventory', 'resource' => 'products', 'action' => 'create', 'description' => 'Create products'],
        ['module' => 'inventory', 'resource' => 'products', 'action' => 'update', 'description' => 'Update products'],
        ['module' => 'inventory', 'resource' => 'products', 'action' => 'delete', 'description' => 'Delete products'],
        ['module' => 'inventory', 'resource' => 'stock', 'action' => 'view', 'description' => 'View stock levels'],
        ['module' => 'inventory', 'resource' => 'stock', 'action' => 'adjust', 'description' => 'Adjust stock'],

        // Settings (always useful for tenant admins)
        ['module' => 'settings', 'resource' => 'roles', 'action' => 'view', 'description' => 'View roles'],
        ['module' => 'settings', 'resource' => 'roles', 'action' => 'manage', 'description' => 'Manage roles & permissions'],
        ['module' => 'settings', 'resource' => 'users', 'action' => 'view', 'description' => 'View users'],
        ['module' => 'settings', 'resource' => 'users', 'action' => 'manage', 'description' => 'Manage users'],
    ],
];
