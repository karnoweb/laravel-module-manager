<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    | Optional prefix for module tables. Example: 'mm_' => mm_modules, mm_module_dependencies
    */
    'table_prefix' => env('MODULE_MANAGER_TABLE_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'modules' => 'modules',
        'dependencies' => 'module_dependencies',
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration
    |--------------------------------------------------------------------------
    | Prefix for published migration filenames to avoid conflicts.
    */
    'migration_prefix' => env('MODULE_MANAGER_MIGRATION_PREFIX', 'module_manager_'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('MODULE_MANAGER_CACHE_ENABLED', true),
        'ttl' => (int) env('MODULE_MANAGER_CACHE_TTL', 3600),
        'prefix' => env('MODULE_MANAGER_CACHE_PREFIX', 'module_manager_'),
        'tags' => ['modules'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Deactivation Behavior
    |--------------------------------------------------------------------------
    | Options: cascade, restrict, none
    */
    'default_deactivation' => env('MODULE_MANAGER_DEFAULT_DEACTIVATION', 'restrict'),

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */
    'events' => [
        'enabled' => env('MODULE_MANAGER_EVENTS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules Definition (Config-driven)
    |--------------------------------------------------------------------------
    | Define modules here. Run `php artisan module:sync` to create/update in database.
    | Keys: key => [ name, description?, group?, icon?, sort_order?, is_active?, is_system?, on_deactivate?, metadata?, parent?, requires?, conflicts?, suggests? ]
    */
    'modules' => [
        // 'core' => [
        //     'name' => 'Core',
        //     'group' => 'general',
        //     'is_active' => true,
        //     'is_system' => true,
        //     'parent' => null,
        //     'requires' => [],
        //     'conflicts' => [],
        //     'suggests' => [],
        // ],
        // 'products' => [
        //     'name' => 'Products',
        //     'description' => 'Product management',
        //     'group' => 'shop',
        //     'icon' => 'fa-box',
        //     'sort_order' => 0,
        //     'is_active' => false,
        //     'is_system' => false,
        //     'on_deactivate' => 'restrict',
        //     'metadata' => [],
        //     'parent' => null,
        //     'requires' => [],
        //     'conflicts' => [],
        //     'suggests' => [],
        // ],
        // 'simple_product' => [
        //     'name' => 'Simple Product',
        //     'group' => 'shop',
        //     'parent' => 'products',
        //     'is_active' => true,
        //     'requires' => ['products'],
        // ],
    ],
];
