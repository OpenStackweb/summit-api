<?php
use Libs\Utils\Doctrine\EscapingQuoteStrategy;

return [

    /*
    |--------------------------------------------------------------------------
    | Entity Mangers
    |--------------------------------------------------------------------------
    |
    | Configure your Entity Managers here. You can set a different connection
    | and driver per manager and configure events and filters. Change the
    | paths setting to the appropriate path and replace App namespace
    | by your own namespace.
    |
    | Available meta drivers: attributes|fluent|annotations|yaml|xml|config|static_php|php
    |
    | Available connections: mysql|oracle|pgsql|sqlite|sqlsrv
    | (Connections can be configured in the database config)
    |
    | --> Warning: Proxy auto generation should only be enabled in dev!
    |
    */
    'managers'                  => [
        'config' => [
            'dev'        => env('APP_DEBUG', true),
            'quote_strategy' => EscapingQuoteStrategy::class,
            'meta'       => env('DOCTRINE_METADATA', 'attributes'),
            'connection' => env('DB_CONNECTION', 'config'),
            'namespaces' => [
                'App'
            ],
            'paths'      => [
                base_path('app/Models/ResourceServer')
            ],
            'repository' => Doctrine\ORM\EntityRepository::class,
            'proxies'    => [
                'namespace'     => 'Proxies',
                'path'          => storage_path('proxies'),
                'auto_generate' => env('DOCTRINE_PROXY_AUTOGENERATE', false)
            ],
            /*
            |--------------------------------------------------------------------------
            | Doctrine events
            |--------------------------------------------------------------------------
            |
            | The listener array expects the key to be a Doctrine event
            | e.g. Doctrine\ORM\Events::onFlush
            |
            */
            'events'     => [
                'listeners'   => [],
                'subscribers' => []
            ],
            'filters'    => [],
            /*
            |--------------------------------------------------------------------------
            | Doctrine mapping types
            |--------------------------------------------------------------------------
            |
            | Link a Database Type to a Local Doctrine Type
            |
            | Using 'enum' => 'string' is the same of:
            | $doctrineManager->extendAll(function (\Doctrine\ORM\Configuration $configuration,
            |         \Doctrine\DBAL\Connection $connection,
            |         \Doctrine\Common\EventManager $eventManager) {
            |     $connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
            | });
            |
            | References:
            | http://doctrine-orm.readthedocs.org/en/latest/cookbook/custom-mapping-types.html
            | http://doctrine-dbal.readthedocs.org/en/latest/reference/types.html#custom-mapping-types
            | http://doctrine-orm.readthedocs.org/en/latest/cookbook/advanced-field-value-conversion-using-custom-mapping-types.html
            | http://doctrine-orm.readthedocs.org/en/latest/reference/basic-mapping.html#reference-mapping-types
            | http://symfony.com/doc/current/cookbook/doctrine/dbal.html#registering-custom-mapping-types-in-the-schematool
            |--------------------------------------------------------------------------
            */
            'mapping_types'              => [
                'enum' => 'string'
            ],
            /**
             * References:
             * https://www.doctrine-project.org/projects/doctrine-dbal/en/current/reference/architecture.html#middlewares
             */
            'middlewares' => array_filter([
                env('DOCTRINE_LOGGING', false) ? Doctrine\DBAL\Logging\Middleware::class : null,
            ]),
        ],
        'model' => [
            'dev'        => env('APP_DEBUG'),
            'meta'       => env('DOCTRINE_METADATA', 'attributes'),
            'quote_strategy' => EscapingQuoteStrategy::class,
            'connection' => 'model',
            'namespaces' => [
                'App'
            ],
            'paths'      => [
                base_path('app/Models/Foundation')
            ],
            'repository' => Doctrine\ORM\EntityRepository::class,
            'proxies'    => [
                'namespace'     => 'Proxies',
                'path'          => storage_path('proxies'),
                'auto_generate' => env('DOCTRINE_PROXY_AUTOGENERATE', false)
            ],
            /*
            |--------------------------------------------------------------------------
            | Doctrine events
            |--------------------------------------------------------------------------
            |
            | The listener array expects the key to be a Doctrine event
            | e.g. Doctrine\ORM\Events::onFlush
            |
            */
            'events'     => [
                'listeners'   => [
                    Doctrine\ORM\Events::onFlush => App\Audit\AuditEventListener::class
                ],
                'subscribers' => []
            ],
            'filters'    => [],
            /*
            |--------------------------------------------------------------------------
            | Doctrine mapping types
            |--------------------------------------------------------------------------
            |
            | Link a Database Type to a Local Doctrine Type
            |
            | Using 'enum' => 'string' is the same of:
            | $doctrineManager->extendAll(function (\Doctrine\ORM\Configuration $configuration,
            |         \Doctrine\DBAL\Connection $connection,
            |         \Doctrine\Common\EventManager $eventManager) {
            |     $connection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
            | });
            |
            | References:
            | http://doctrine-orm.readthedocs.org/en/latest/cookbook/custom-mapping-types.html
            | http://doctrine-dbal.readthedocs.org/en/latest/reference/types.html#custom-mapping-types
            | http://doctrine-orm.readthedocs.org/en/latest/cookbook/advanced-field-value-conversion-using-custom-mapping-types.html
            | http://doctrine-orm.readthedocs.org/en/latest/reference/basic-mapping.html#reference-mapping-types
            | http://symfony.com/doc/current/cookbook/doctrine/dbal.html#registering-custom-mapping-types-in-the-schematool
            |--------------------------------------------------------------------------
            */
            'mapping_types'              => [
                'enum' => 'string'
            ],
            /**
             * References:
             * https://www.doctrine-project.org/projects/doctrine-dbal/en/current/reference/architecture.html#middlewares
             */
            'middlewares' => array_filter([
                env('DOCTRINE_LOGGING', false) ? Doctrine\DBAL\Logging\Middleware::class : null,
            ]),
        ]
    ],
    /*
    |--------------------------------------------------------------------------
    | Doctrine Extensions
    |--------------------------------------------------------------------------
    |
    | Enable/disable Doctrine Extensions by adding or removing them from the list
    |
    | If you want to require custom extensions you will have to require
    | laravel-doctrine/extensions in your composer.json
    |
    */
    'extensions'                => [
        //LaravelDoctrine\ORM\Extensions\TablePrefix\TablePrefixExtension::class,
        //LaravelDoctrine\Extensions\Timestamps\TimestampableExtension::class,
        //LaravelDoctrine\Extensions\SoftDeletes\SoftDeleteableExtension::class,
        //LaravelDoctrine\Extensions\Sluggable\SluggableExtension::class,
        //LaravelDoctrine\Extensions\Sortable\SortableExtension::class,
        //LaravelDoctrine\Extensions\Tree\TreeExtension::class,
        //LaravelDoctrine\Extensions\Loggable\LoggableExtension::class,
        //LaravelDoctrine\Extensions\Blameable\BlameableExtension::class,
        //LaravelDoctrine\Extensions\IpTraceable\IpTraceableExtension::class,
        //LaravelDoctrine\Extensions\Translatable\TranslatableExtension::class
    ],
    /*
    |--------------------------------------------------------------------------
    | Doctrine custom types
    |--------------------------------------------------------------------------
    |
    | Create a custom or override a Doctrine Type
    |--------------------------------------------------------------------------
    */
    'custom_types'              => [
        'CarbonDate'       => DoctrineExtensions\Types\CarbonDateType::class,
        'CarbonDateTime'   => DoctrineExtensions\Types\CarbonDateTimeType::class,
        'CarbonDateTimeTz' => DoctrineExtensions\Types\CarbonDateTimeTzType::class,
        'CarbonTime'       => DoctrineExtensions\Types\CarbonTimeType::class
    ],
    /*
    |--------------------------------------------------------------------------
    | DQL custom datetime functions
    |--------------------------------------------------------------------------
    */
    'custom_datetime_functions' => [
        'DATEADD'           => DoctrineExtensions\Query\Mysql\DateAdd::class,
        'DATEDIFF'          => DoctrineExtensions\Query\Mysql\DateDiff::class,
        'UTC_TIMESTAMP'     => DoctrineExtensions\Query\Mysql\UtcTimestamp::class,
        'DATE'              =>  DoctrineExtensions\Query\Mysql\Date::class,
        'DATE_FORMAT'       => DoctrineExtensions\Query\Mysql\DateFormat::class,
        'DATESUB'           => DoctrineExtensions\Query\Mysql\DateSub::class,
        'DAY'               => DoctrineExtensions\Query\Mysql\Day::class,
        'DAYNAME'           => DoctrineExtensions\Query\Mysql\DayName::class,
        'FROM_UNIXTIME'     => DoctrineExtensions\Query\Mysql\FromUnixtime::class,
        'HOUR'              => DoctrineExtensions\Query\Mysql\Hour::class,
        'LAST_DAY'          => DoctrineExtensions\Query\Mysql\LastDay::class,
        'MINUTE'            => DoctrineExtensions\Query\Mysql\Minute::class,
        'MONTH'             => DoctrineExtensions\Query\Mysql\Month::class,
        'MONTHNAME'         => DoctrineExtensions\Query\Mysql\MonthName::class,
        'SECOND'            => DoctrineExtensions\Query\Mysql\Second::class,
        'STRTODATE'         => DoctrineExtensions\Query\Mysql\StrToDate::class,
        'TIME'              => DoctrineExtensions\Query\Mysql\Time::class,
        'TIMESTAMPADD'      => DoctrineExtensions\Query\Mysql\TimestampAdd::class,
        'TIMESTAMPDIFF'     => DoctrineExtensions\Query\Mysql\TimestampDiff::class,
        'WEEK'              => DoctrineExtensions\Query\Mysql\Week::class,
        'WEEKDAY'           => DoctrineExtensions\Query\Mysql\WeekDay::class,
        'YEAR'              => DoctrineExtensions\Query\Mysql\Year::class,
        'REVIEW_STATUS'     => \App\Utils\CustomDBFunctions\ReviewStatus::class,
        'SUMMIT_ORDER_FINAL_AMOUNT' => \App\Utils\CustomDBFunctions\SummitOrderFinalAmount::class,
    ],
    /*
    |--------------------------------------------------------------------------
    | DQL custom numeric functions
    |--------------------------------------------------------------------------
    */
    'custom_numeric_functions'  => [
        "rand" => DoctrineExtensions\Query\Mysql\Rand::class,
    ],
    /*
    |--------------------------------------------------------------------------
    | DQL custom string functions
    |--------------------------------------------------------------------------
    */
    'custom_string_functions'   => [
        'FIELD' => DoctrineExtensions\Query\Mysql\Field::class,
    ],
    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Configure meta-data, query and result caching here.
    | Optionally you can enable second level caching.
    |
    | Available: acp|array|file|memcached|redis|void
    |
    */
    'cache'                     => [
        'default'                => env('DOCTRINE_CACHE', 'redis'),
        'namespace'              => "summit_api",
        'second_level'           => [
            'enabled'                => true,
            'region_lifetime'        => 180,
            'region_lock_lifetime'   => 60,
            'regions'                => [
                'summit_event_feedback_region' => [
                    'lifetime'      => 300,
                    'lock_lifetime' => 60
                ],
                'current_summit_region' => [
                    'lifetime'      => 600,
                    'lock_lifetime' => 60
                ],
                'resource_server_region' => [
                    'lifetime'      => 3600,
                    'lock_lifetime' => 60
                ],
                'summit_type_region' => [
                    'lifetime'      => 1800,
                    'lock_lifetime' => 60
                ],
                'summit_ticket_type_region' => [
                    'lifetime'      => 1800,
                    'lock_lifetime' => 60
                ],
                'summit_event_type_region' => [
                    'lifetime'      => 1800,
                    'lock_lifetime' => 60
                ],
                'summit_presentation_category_region' => [
                    'lifetime'      => 1800,
                    'lock_lifetime' => 60
                ],
            ],
            'log_enabled'  => true,
            'file_lock_region_directory' => '/tmp'
        ],
        'metadata'         => [
            'driver'       => env('DOCTRINE_METADATA_CACHE', "apc"),
            'namespace'    => null,
        ],
        'query'            => [
            'driver'       => env('DOCTRINE_QUERY_CACHE', "apc"),
            'namespace'    => null,
        ],
        'result'           => [
            'driver'       => env('DOCTRINE_RESULT_CACHE', env('DOCTRINE_CACHE', 'redis')),
            'namespace'    => 'res',
            'store'        => env('DOCTRINE_QUERY_CACHE_STORE', 'doctrine_redis'),
            'lifetime'     => 3600, // 1 hour
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Gedmo extensions
    |--------------------------------------------------------------------------
    |
    | Settings for Gedmo extensions
    | If you want to use this you will have to require
    | laravel-doctrine/extensions in your composer.json
    |
    */
    'gedmo'                     => [
        'all_mappings' => false
    ]
];
