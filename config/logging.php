<?php

use Monolog\Handler\StreamHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'stderr'],
        ],
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' =>  env('LOG_LEVEL', 'error'),
        ],
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' =>  env('LOG_LEVEL', 'error'),
            'days' => 7,
        ],
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],
        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'level' =>  env('LOG_LEVEL', 'error'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],
        'stdout' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'level' =>  env('LOG_LEVEL', 'error'),
            'with' => [
                'stream' => 'php://stdout',
            ],
        ],
        'syslog' => [
            'driver' => 'syslog',
            'level' =>  env('LOG_LEVEL', 'error'),
        ],
        'errorlog' => [
            'driver' => 'errorlog',
            'level' =>  env('LOG_LEVEL', 'error'),
        ],
        'otlp' => [
            'driver' => 'monolog',
            'handler' => \Keepsuit\LaravelOpenTelemetry\Support\OpenTelemetryMonologHandler::class,
            'level' => 'debug',
        ]
    ],

];
