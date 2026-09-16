<?php

/**
 * Global permission catalog for Daksa ERP.
 * Roles pick from this list; plan modules limit what can be assigned.
 */
return [
    'modules' => [
        'procurement' => 'Procurement',
        'inventory' => 'Inventory',
        'finance' => 'Finance',
        'sales' => 'Sales',
        'settings' => 'Settings',
    ],

    'permissions' => [
        // Procurement (ref: Aureus purchases plugin)
        ['module' => 'procurement', 'resource' => 'vendors', 'action' => 'view', 'description' => 'View vendors'],
        ['module' => 'procurement', 'resource' => 'vendors', 'action' => 'create', 'description' => 'Create vendors'],
        ['module' => 'procurement', 'resource' => 'vendors', 'action' => 'update', 'description' => 'Update vendors'],
        ['module' => 'procurement', 'resource' => 'vendors', 'action' => 'delete', 'description' => 'Delete vendors'],
        ['module' => 'procurement', 'resource' => 'orders', 'action' => 'view', 'description' => 'View purchase orders'],
        ['module' => 'procurement', 'resource' => 'orders', 'action' => 'create', 'description' => 'Create purchase orders'],
        ['module' => 'procurement', 'resource' => 'orders', 'action' => 'update', 'description' => 'Update purchase orders'],
        ['module' => 'procurement', 'resource' => 'orders', 'action' => 'delete', 'description' => 'Delete purchase orders'],
        ['module' => 'procurement', 'resource' => 'orders', 'action' => 'confirm', 'description' => 'Confirm purchase orders'],
        ['module' => 'procurement', 'resource' => 'receipts', 'action' => 'view', 'description' => 'View goods receipts'],
        ['module' => 'procurement', 'resource' => 'receipts', 'action' => 'receive', 'description' => 'Receive goods into stock'],

        // Sales (ref: Aureus sales plugin)
        ['module' => 'sales', 'resource' => 'customers', 'action' => 'view', 'description' => 'View customers'],
        ['module' => 'sales', 'resource' => 'customers', 'action' => 'create', 'description' => 'Create customers'],
        ['module' => 'sales', 'resource' => 'customers', 'action' => 'update', 'description' => 'Update customers'],
        ['module' => 'sales', 'resource' => 'customers', 'action' => 'delete', 'description' => 'Delete customers'],

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

        // Finance (ref: Aureus accounts + invoices plugins)
        ['module' => 'finance', 'resource' => 'invoices', 'action' => 'view', 'description' => 'View customer invoices'],
        ['module' => 'finance', 'resource' => 'invoices', 'action' => 'create', 'description' => 'Create customer invoices'],
        ['module' => 'finance', 'resource' => 'invoices', 'action' => 'update', 'description' => 'Update customer invoices'],
        ['module' => 'finance', 'resource' => 'invoices', 'action' => 'delete', 'description' => 'Delete customer invoices'],
        ['module' => 'finance', 'resource' => 'invoices', 'action' => 'post', 'description' => 'Post customer invoices'],
        ['module' => 'finance', 'resource' => 'bills', 'action' => 'view', 'description' => 'View vendor bills'],
        ['module' => 'finance', 'resource' => 'bills', 'action' => 'create', 'description' => 'Create vendor bills'],
        ['module' => 'finance', 'resource' => 'bills', 'action' => 'update', 'description' => 'Update vendor bills'],
        ['module' => 'finance', 'resource' => 'bills', 'action' => 'delete', 'description' => 'Delete vendor bills'],
        ['module' => 'finance', 'resource' => 'bills', 'action' => 'post', 'description' => 'Post vendor bills'],
        ['module' => 'finance', 'resource' => 'payments', 'action' => 'view', 'description' => 'View payments'],
        ['module' => 'finance', 'resource' => 'payments', 'action' => 'create', 'description' => 'Record payments'],

        // Inventory (ref: Aureus inventories plugin)
        ['module' => 'inventory', 'resource' => 'products', 'action' => 'view', 'description' => 'View products'],
        ['module' => 'inventory', 'resource' => 'products', 'action' => 'create', 'description' => 'Create products'],
        ['module' => 'inventory', 'resource' => 'products', 'action' => 'update', 'description' => 'Update products'],
        ['module' => 'inventory', 'resource' => 'products', 'action' => 'delete', 'description' => 'Delete products'],
        ['module' => 'inventory', 'resource' => 'stock', 'action' => 'view', 'description' => 'View stock levels'],
        ['module' => 'inventory', 'resource' => 'stock', 'action' => 'adjust', 'description' => 'Adjust stock'],
        ['module' => 'inventory', 'resource' => 'warehouses', 'action' => 'view', 'description' => 'View warehouses'],
        ['module' => 'inventory', 'resource' => 'warehouses', 'action' => 'manage', 'description' => 'Manage warehouses'],
        ['module' => 'inventory', 'resource' => 'operations', 'action' => 'view', 'description' => 'View stock operations'],
        ['module' => 'inventory', 'resource' => 'operations', 'action' => 'create', 'description' => 'Create stock operations'],
        ['module' => 'inventory', 'resource' => 'operations', 'action' => 'validate', 'description' => 'Validate stock operations'],

        // Finance config
        ['module' => 'finance', 'resource' => 'taxes', 'action' => 'view', 'description' => 'View taxes'],
        ['module' => 'finance', 'resource' => 'taxes', 'action' => 'manage', 'description' => 'Manage taxes'],
        ['module' => 'finance', 'resource' => 'journals', 'action' => 'view', 'description' => 'View journals'],
        ['module' => 'finance', 'resource' => 'accounts', 'action' => 'view', 'description' => 'View chart of accounts'],

        // Settings masters
        ['module' => 'settings', 'resource' => 'uoms', 'action' => 'view', 'description' => 'View units of measure'],
        ['module' => 'settings', 'resource' => 'uoms', 'action' => 'manage', 'description' => 'Manage units of measure'],
        ['module' => 'settings', 'resource' => 'categories', 'action' => 'manage', 'description' => 'Manage product categories'],
        ['module' => 'settings', 'resource' => 'payment_terms', 'action' => 'manage', 'description' => 'Manage payment terms'],

        // Settings (always useful for tenant admins)
        ['module' => 'settings', 'resource' => 'roles', 'action' => 'view', 'description' => 'View roles'],
        ['module' => 'settings', 'resource' => 'roles', 'action' => 'manage', 'description' => 'Manage roles & permissions'],
        ['module' => 'settings', 'resource' => 'users', 'action' => 'view', 'description' => 'View users'],
        ['module' => 'settings', 'resource' => 'users', 'action' => 'manage', 'description' => 'Manage users'],
    ],
];
