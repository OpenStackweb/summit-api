<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | By default, database results will be returned as instances of the PHP
    | stdClass object; however, you may desire to retrieve records in an
    | array format for simplicity. Here you can tweak the fetch style.
    |
    */

    'fetch' => PDO::FETCH_CLASS,

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'config'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        // config DB
        'config' => array_merge(
            [
                'driver' => 'mysql',
                'host' => env('DB_HOST'),
                'database' => env('DB_DATABASE'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
                'port' => env('DB_PORT', 3306),
                'charset' => env('DB_CHARSET', 'utf8'),
                'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
                'prefix' => env('DB_PREFIX', ''),
            ],
            !empty(env('DB_MYSQL_ATTR_SSL_CA', '')) ?
                [
                    'options' => [
                        PDO::MYSQL_ATTR_SSL_CA => env('DB_MYSQL_ATTR_SSL_CA', ''),
                    ],
                    'driverOptions' => [
                        PDO::MYSQL_ATTR_SSL_CA => env('DB_MYSQL_ATTR_SSL_CA', ''),
                    ],
                ] : []),
        // Model DB
        'model' => array_merge(
            [
                    'driver' => 'mysql',
                    'host' => env('SS_DB_HOST'),
                    'database' => env('SS_DATABASE'),
                    'username' => env('SS_DB_USERNAME'),
                    'password' => env('SS_DB_PASSWORD'),
                    'port' => env('SS_DB_PORT', 3306),
                    'charset' => env('SS_DB_CHARSET', 'utf8'),
                    'collation' => env('SS_DB_COLLATION', 'utf8_unicode_ci'),
                    'prefix' => env('SS_DB_PREFIX', ''),
            ],
            !empty(env('DB_MYSQL_ATTR_SSL_CA', '')) ?
            [
                'options' => [
                    PDO::MYSQL_ATTR_SSL_CA => env('SS_DB_MYSQL_ATTR_SSL_CA', null),
                ],
                'driverOptions' => [
                    PDO::MYSQL_ATTR_SSL_CA => env('SS_DB_MYSQL_ATTR_SSL_CA', null),
                ],
            ]:[]
        ),

    ],

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

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'predis'),
        /*
         * @see https://github.com/predis/predis/wiki/Connection-Parameters
         */
        'cluster' => false,

        'default' => [
            'host' => env('REDIS_HOST2'),
            'port' => env('REDIS_PORT'),
            'database' => env('REDIS_DEFAULT_DATABASE', 0),
            'password' => env('REDIS_PASSWORD'),
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'read_write_timeout' => env('REDIS_READ_WRITE_TIMEOUT', -1),
            'timeout' => env('REDIS_TIMEOUT', 30),
        ],

        'cache' => [
            'host' => env('REDIS_HOST2'),
            'port' => env('REDIS_PORT'),
            'database' => env('REDIS_CACHE_DATABASE', 0),
            'password' => env('REDIS_PASSWORD'),
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'read_write_timeout' => env('REDIS_READ_WRITE_TIMEOUT', -1),
            'timeout' => env('REDIS_TIMEOUT', 30),
        ],

        'session' => [
            'host' => env('REDIS_HOST2'),
            'port' => env('REDIS_PORT'),
            'database' => env('REDIS_SESSION_DATABASE', 1),
            'password' => env('REDIS_PASSWORD'),
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'read_write_timeout' => env('REDIS_READ_WRITE_TIMEOUT', -1),
            'timeout' => env('REDIS_TIMEOUT', 30),
        ],

        'worker' => [
            'host' => env('REDIS_HOST2'),
            'port' => env('REDIS_PORT'),
            'database' => env('REDIS_WORKER_DATABASE', 2),
            'password' => env('REDIS_PASSWORD'),
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'read_write_timeout' => env('REDIS_READ_WRITE_TIMEOUT', -1),
            'timeout' => env('REDIS_TIMEOUT', 30),
        ],

    ],
    'allow_disabled_pk' => env('DB_ALLOW_DISABLED_PK', false),
];
