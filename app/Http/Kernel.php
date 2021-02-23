<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        //\App\Http\Middleware\TrustProxies::class,
        \App\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        //\Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\SecurityHTTPHeadersWriterMiddleware::class,
        \App\Http\Middleware\ParseMultipartFormDataInputForNonPostRequests::class,
        \App\Http\Middleware\DoctrineMiddleware::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
        'api' => [
            'ssl',
            'oauth2.protected',
            'rate.limit',
            'etags'
        ],
        'public_api' => [
            'ssl',
            'rate.limit:10000,1', // 10000 request per minute
            'etags'
        ],
        'well_known' => [
            'ssl',
            'rate.limit:1000,1', // 1000 request per minute
        ]
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth'             => \App\Http\Middleware\Authenticate::class,
        'auth.basic'       => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'can'              => \Illuminate\Foundation\Http\Middleware\Authorize::class,
        'guest'            => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle'         => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'oauth2.protected' => \App\Http\Middleware\OAuth2BearerAccessTokenRequestValidator::class,
        'rate.limit'       => \App\Http\Middleware\RateLimitMiddleware::class,
        'etags'            => \App\Http\Middleware\ETagsMiddleware::class,
        'cache'            => \App\Http\Middleware\CacheMiddleware::class,
        'ssl'              => \App\Http\Middleware\SSLMiddleware::class,
        'auth.user'        => \App\Http\Middleware\UserAuthEndpoint::class,
    ];
}
