<?php

$rabbit_port = intval( env('RABBITMQ_PORT', 5671) );
$rabbit_connection = PhpAmqpLib\Connection\AMQPLazyConnection::class;
if($rabbit_port === 5671)
    $rabbit_connection = PhpAmqpLib\Connection\AMQPSSLConnection::class;


return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Driver
    |--------------------------------------------------------------------------
    |
    | The Laravel queue API supports a variety of back-ends via an unified
    | API, giving you convenient access to each back-end using the same
    | syntax for each one. Here you may set the default queue driver.
    |
    | Supported: "null", "sync", "database", "beanstalkd",
    |            "sqs", "redis"
    |
    */

    'default' => env('QUEUE_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    */

    'connections' => [

        'database' => [
            'connection' => env('QUEUE_CONN', ''),
            'database' => env('QUEUE_DATABASE', ''),
            'driver'   => 'database',
            'table'    => 'queue_jobs',
            'queue'    => 'default',
            'expire'   => 60,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'worker',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_RETRY_AFTER', 1800),
            'block_for' => null,
            'after_commit' => false,
        ],
        /* SOURCE API IDP
         * DOMAIN EVENTS:
         *  - PublishUserCreated ( app/Jobs/PublishUserCreated.php )
         *  - PublishUserDeleted ( app/Jobs/PublishUserDeleted.php )
         *  - PublishUserUpdated ( app/Jobs/PublishUserUpdated.php )
        */
        'message_broker' => [
            'driver' => 'rabbitmq',
            'queue' => env('RABBITMQ_QUEUE', ''),
            'connection' => $rabbit_connection,
            'hosts' => [
                [
                    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
                    'port' => $rabbit_port,
                    'user' => env('RABBITMQ_LOGIN', 'guest'),
                    'password' => env('RABBITMQ_PASSWORD', 'guest'),
                    'vhost' => env('RABBITMQ_VHOST', '/'),
                ],
            ],
            'options' => [
                'ssl_options' => [
                    // @see https://www.php.net/manual/en/context.ssl.php
                    'cafile' => env('RABBITMQ_SSL_CAFILE', null),
                    'local_cert' => env('RABBITMQ_SSL_LOCALCERT', null),
                    'local_pk' => env('RABBITMQ_SSL_LOCALKEY', null),
                    'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
                    'passphrase' => env('RABBITMQ_SSL_PASSPHRASE', null),
                ],
                'queue' => [
                    'exchange' =>  env('RABBITMQ_EXCHANGE_NAME'),
                    'exchange_type' =>  env('RABBITMQ_EXCHANGE_TYPE', 'fanout'),
                    'passive' => env('RABBITMQ_QUEUE_PASSIVE', false),
                    'durable' => env('RABBITMQ_QUEUE_DURABLE', true),
                    'exclusive' => env('RABBITMQ_QUEUE_EXCLUSIVE', false),
                    'auto_delete' => env('RABBITMQ_QUEUE_AUTODELETE', true),
                ],
            ],
        ],
        /* SOURCE API sponsor users api
         * DOMAIN EVENTS:
         *  - auth_user_added_to_group
         *  - auth_user_removed_from_group
         *  - auth_user_added_to_sponsor_and_summit
         *  - auth_user_removed_from_sponsor_and_summit
         *  - auth_user_removed_from_summit
        */
        'sponsor_users_sync_consumer' => [
            'driver' => 'rabbitmq',
            'queue' => env('SPONSOR_USERS_QUEUE', 'sponsor-users-api-summit-api-badge-scans-queue'),
            'connection' => $rabbit_connection,
            'hosts' => [
                [
                    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
                    'port' => $rabbit_port,
                    'user' => env('RABBITMQ_LOGIN', 'guest'),
                    'password' => env('RABBITMQ_PASSWORD', 'guest'),
                    'vhost' => env('RABBITMQ_VHOST', '/'),
                ],
            ],
            'options' => [
                'ssl_options' => [
                    // @see https://www.php.net/manual/en/context.ssl.php
                    'cafile' => env('RABBITMQ_SSL_CAFILE', null),
                    'local_cert' => env('RABBITMQ_SSL_LOCALCERT', null),
                    'local_pk' => env('RABBITMQ_SSL_LOCALKEY', null),
                    'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
                    'passphrase' => env('RABBITMQ_SSL_PASSPHRASE', null),
                ],
                'queue' => [
                    'exchange' =>  env('SPONSOR_USERS_EXCHANGE','sponsor-users-api-message-broker'),
                    'passive' => env('RABBITMQ_QUEUE_PASSIVE', false),
                    'durable' => env('RABBITMQ_QUEUE_DURABLE', true),
                    'exclusive' => env('RABBITMQ_QUEUE_EXCLUSIVE', false),
                    'job' => \App\Jobs\SponsorServices\SponsorServicesMQJob::class,
                ],
            ],
        ],
        /* SOURCE API purchases api
         * DOMAIN EVENTS:
         *  - payment_profile_created
         *  - payment_profile_updated
         *  - payment_profile_deleted
        */
        'payments_sync_consumer' => [
            'driver' => 'rabbitmq',
            'queue' => env('PAYMENTS_QUEUE', 'purchases-api-summit-api-payment-profiles-queue'),
            'connection' => $rabbit_connection,
            'hosts' => [
                [
                    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
                    'port' => $rabbit_port,
                    'user' => env('RABBITMQ_LOGIN', 'guest'),
                    'password' => env('RABBITMQ_PASSWORD', 'guest'),
                    'vhost' => env('RABBITMQ_VHOST', '/'),
                ],
            ],
            'options' => [
                'ssl_options' => [
                    // @see https://www.php.net/manual/en/context.ssl.php
                    'cafile' => env('RABBITMQ_SSL_CAFILE', null),
                    'local_cert' => env('RABBITMQ_SSL_LOCALCERT', null),
                    'local_pk' => env('RABBITMQ_SSL_LOCALKEY', null),
                    'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
                    'passphrase' => env('RABBITMQ_SSL_PASSPHRASE', null),
                ],
                'queue' => [
                    'exchange' =>  env('PAYMENTS_EXCHANGE','purchases-api-payments-message-broker'),
                    'passive' => env('RABBITMQ_QUEUE_PASSIVE', false),
                    'durable' => env('RABBITMQ_QUEUE_DURABLE', true),
                    'exclusive' => env('RABBITMQ_QUEUE_EXCLUSIVE', false),
                    'job' => \App\Jobs\Payments\PaymentsMQJob::class,
                ],
            ],
        ],
        /*
         * SOURCE API Summit API
         * Summit Entities Domain Events
         * DOMAIN EVENTS:
         *  - sponsor_created ( see app/Events/SponsorServices/SponsorDomainEvents.php )
         *  - sponsor_updated
         *  - sponsor_deleted
         *  - sponsorship_created
         *  - sponsorship_updated
         *  - sponsorship_removed
         *  - sponsorship_addon_created
         *  - sponsorship_addon_updated
         *  - sponsorship_addon_removed
         *  - summit_created ( see app/Events/SponsorServices/SummitDomainEvents.php )
         *  - summit_updated
         *  - summit_deleted
         *  - summit_media_file_type_created ( see app/Events/SponsorServices/SummitMediaFileTypeDomainEvents.php )
         *  - summit_media_file_type_updated
         *  - summit_media_file_type_deleted
         */
        'domain_events_message_broker' => [
            'driver' => 'rabbitmq',
            'hosts' => [
                [
                    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
                    'port' => $rabbit_port,
                    'user' => env('RABBITMQ_LOGIN', 'guest'),
                    'password' => env('RABBITMQ_PASSWORD', 'guest'),
                    'vhost' => env('RABBITMQ_VHOST', '/'),
                ],
            ],
            'options' => [
                'exchange' => [
                    'name' => env('DOMAIN_EVENTS_EXCHANGE_NAME', 'summit-api-message-broker'),
                    'type' => env('DOMAIN_EVENTS_EXCHANGE_TYPE', 'direct'), // direct, fanout, topic, headers
                    'passive' => false,
                    'durable' => true,
                    'auto_delete' => false,
                ],
                'ssl_options' => [
                    // @see https://www.php.net/manual/en/context.ssl.php
                    'cafile' => env('RABBITMQ_SSL_CAFILE', null),
                    'local_cert' => env('RABBITMQ_SSL_LOCALCERT', null),
                    'local_pk' => env('RABBITMQ_SSL_LOCALKEY', null),
                    'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
                    'passphrase' => env('RABBITMQ_SSL_PASSPHRASE', null),
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'database' => env('QUEUE_CONN', ''),
        'table'   => 'queue_failed_jobs',
    ],

];
