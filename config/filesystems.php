<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. A "local" driver, as well as a variety of cloud
    | based drivers are available for your choosing. Just store away!
    |
    | Supported: "local", "ftp", "s3", "rackspace"
    |
    */

    'default' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => 's3',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => true,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'visibility' => 'public',
            'throw' => true,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
        ],
        'file_api_disk' => [
            'driver' => 's3',
            'key' => env('FILE_UPLOAD_API_AWS_ACCESS_KEY_ID'),
            'secret' => env('FILE_UPLOAD_API_AWS_SECRET_ACCESS_KEY'),
            'region' => env('FILE_UPLOAD_API_AWS_DEFAULT_REGION'),
            'bucket' => env('FILE_UPLOAD_API_AWS_BUCKET'),
            'url' => env('FILE_UPLOAD_API_AWS_URL'),
            'endpoint' => env('FILE_UPLOAD_API_AWS_ENDPOINT'),
        ],
        'assets' => [
            'driver'                => 'swift',
            'auth_url'              => env('CLOUD_STORAGE_AUTH_URL'),
            'region'                => env('CLOUD_STORAGE_REGION'),
            'app_credential_id'     => env('CLOUD_STORAGE_APP_CREDENTIAL_ID'),
            'app_credential_secret' => env('CLOUD_STORAGE_APP_CREDENTIAL_SECRET'),
            'container'             => env('CLOUD_STORAGE_ASSETS_CONTAINER'),
        ],

        'static_images' => [
            'driver'                => 'swift',
            'auth_url'              => env('CLOUD_STORAGE_AUTH_URL'),
            'region'                => env('CLOUD_STORAGE_REGION'),
            'app_credential_id'     => env('CLOUD_STORAGE_APP_CREDENTIAL_ID'),
            'app_credential_secret' => env('CLOUD_STORAGE_APP_CREDENTIAL_SECRET'),
            'container'             => env('CLOUD_STORAGE_IMAGES_CONTAINER'),
        ],

        'assets_s3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID_ASSETS'),
            'secret' => env('AWS_SECRET_ACCESS_KEY_ASSETS'),
            'region' => env('AWS_DEFAULT_REGION_ASSETS'),
            'bucket' => env('AWS_BUCKET_ASSETS'),
            'url' => env('AWS_URL_ASSETS'),
            'endpoint' => env('AWS_ENDPOINT_ASSETS'),
        ],

        'static_images_s3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID_STATIC_IMAGES'),
            'secret' => env('AWS_SECRET_ACCESS_KEY_STATIC_IMAGES'),
            'region' => env('AWS_DEFAULT_REGION_ASSETS_STATIC_IMAGES'),
            'bucket' => env('AWS_BUCKET_ASSETS_STATIC_IMAGES'),
            'url' => env('AWS_URL_ASSETS_STATIC_IMAGES'),
            'endpoint' => env('AWS_ENDPOINT_ASSETS_STATIC_IMAGES'),
        ],

        'dropbox' => [
            'driver' => 'dropbox',
            'authorization_token' => env('DROPBOX_ACCESS_TOKEN'),
        ],

        'swift' => [
            'driver'                => 'swift',
            'auth_url'              => env('CLOUD_STORAGE_AUTH_URL'),
            'region'                => env('CLOUD_STORAGE_REGION'),
            'app_credential_id'     => env('CLOUD_STORAGE_APP_CREDENTIAL_ID'),
            'app_credential_secret' => env('CLOUD_STORAGE_APP_CREDENTIAL_SECRET'),
            'container'             => env('CLOUD_STORAGE_MEDIA_UPLOADS_CONTAINER'),
        ]

    ],
    'assets_disk' => env('FILESYSTEMS_ASSETS_DISK', 'assets'),
    'static_images_disk' => env('FILESYSTEMS_STATIC_IMAGES_DISK', 'static_images')
];
