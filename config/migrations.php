<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Entity Manager Migrations Configuration
    |--------------------------------------------------------------------------
    |
    | Each entity manager can have a custom migration configuration. Provide
    | the name of the entity manager as the key, then duplicate the settings.
    | This will allow generating custom migrations per EM instance and not have
    | collisions when executing them.
    |
    */
    'config' => [
        /*
        |--------------------------------------------------------------------------
        | Migration Repository Table
        |--------------------------------------------------------------------------
        |
        | This table keeps track of all the migrations that have already run for
        | your application. Using this information, we can determine which of
        | the migrations on disk haven't actually been run in the database.
        |
        */
        'table'     => 'DoctrineMigration',
        /*
        |--------------------------------------------------------------------------
        | Migration Directory
        |--------------------------------------------------------------------------
        |
        | This directory is where all migrations will be stored for this entity
        | manager. Use different directories for each entity manager.
        |
        */
        'directory' => sprintf("%s/%s", database_path('migrations')  ,"config"),
        /*
        |--------------------------------------------------------------------------
        | Migration Namespace
        |--------------------------------------------------------------------------
        |
        | This namespace will be used on all migrations. To prevent collisions, add
        | the entity manager name (connection name).
        |
        */
        'namespace' => 'Database\\Migrations\\Config',
        /*
        |--------------------------------------------------------------------------
        | Migration Repository Table
        |--------------------------------------------------------------------------
        |
        | Tables which are filtered by Regular Expression. You optionally
        | exclude or limit to certain tables. The default will
        | filter all tables.
        |
        */
        'schema'    => [
            'filter' => '/^(?!password_resets|failed_jobs).*$/'
        ]
    ],
    'model_write' => [
        /*
        |--------------------------------------------------------------------------
        | Migration Repository Table
        |--------------------------------------------------------------------------
        |
        | This table keeps track of all the migrations that have already run for
        | your application. Using this information, we can determine which of
        | the migrations on disk haven't actually been run in the database.
        |
        */
        'table'     => 'DoctrineMigration',
        /*
        |--------------------------------------------------------------------------
        | Migration Directory
        |--------------------------------------------------------------------------
        |
        | This directory is where all migrations will be stored for this entity
        | manager. Use different directories for each entity manager.
        |
        */
        'directory' => sprintf("%s/%s", database_path('migrations')  ,"model"),
        /*
        |--------------------------------------------------------------------------
        | Migration Namespace
        |--------------------------------------------------------------------------
        |
        | This namespace will be used on all migrations. To prevent collisions, add
        | the entity manager name (connection name).
        |
        */
        'namespace' => 'Database\\Migrations\\Model',
        /*
        |--------------------------------------------------------------------------
        | Migration Repository Table
        |--------------------------------------------------------------------------
        |
        | Tables which are filtered by Regular Expression. You optionally
        | exclude or limit to certain tables. The default will
        | filter all tables.
        |
        */
        'schema'    => [
            'filter' => '/^(?!password_resets|failed_jobs).*$/'
        ]
    ],
];
