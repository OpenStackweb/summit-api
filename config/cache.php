<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    */

    'default' => env('CACHE_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    */

    'stores' => [

        'apc' => [
            'driver' => 'apc',
        ],

        'array' => [
            'driver' => 'array',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            //'persistent_id' => 'host-cache',
            'sasl'          => [null, null],
            'servers'       => [
                // UNIX socket (fastest)
                [
                    'host' => env('MEMCACHED_SERVER_HOST', '/var/run/memcached/memcached.sock'),
                    'port' => env('MEMCACHED_SERVER_PORT',0),
                    'weight' => env('MEMCACHED_SERVER_WEIGHT',100)
                ],
                // or TCP if you prefer:
                // ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],

        'doctrine_redis' => [
            'driver' => 'redis',
            'connection' => env('DOCTRINE_CACHE_REDIS_CONN', 'doctrine_cache'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing a RAM based store such as APC or Memcached, there might
    | be other applications utilizing the same cache. So, we'll specify a
    | value to get prefixed to all our keys so we can avoid collisions.
    |
    */

    'prefix' => 'laravel',
    'request_scope_cache_store' => env('REQUEST_SCOPE_CACHE_STORE', 'array'),
];
