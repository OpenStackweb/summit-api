<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api_v1.php',
        channels: __DIR__.'/../routes/channels.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Register additional route files
            Route::middleware('api')
                 ->prefix('api/v2')
                 ->group(base_path('routes/api_v2.php'));

            Route::middleware('public_api')
                 ->group(base_path('routes/public_api.php'));

            Route::middleware('well_known')
                 ->group(base_path('routes/well_known.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global HTTP middleware stack (from your old $middleware array)
        $middleware->use([
            // \App\Http\Middleware\TrustProxies::class, // commented as in original
            \App\Http\Middleware\CheckForMaintenanceMode::class,
            \App\Http\Middleware\TrackRequestMiddleware::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            // \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class, // commented as in original
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\SecurityHTTPHeadersWriterMiddleware::class,
            \App\Http\Middleware\ParseMultipartFormDataInputForNonPostRequests::class,
            \App\Http\Middleware\DoctrineMiddleware::class,
            \App\Http\Middleware\RequestScopedCacheMiddleware::class,
        ]);

        // Configure the 'web' middleware group (from your old $middlewareGroups['web'])
        $middleware->web([
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class, // commented as in original
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Configure the 'api' middleware group (from your old $middlewareGroups['api'])
        $middleware->group('api', [
            'ssl',
            'oauth2.protected',
            'etags'
        ]);

        // Configure additional middleware groups
        $middleware->group('public_api', [
            'ssl',
            'rate.limit:10000,1', // 10000 request per minute
            'etags'
        ]);

        $middleware->group('well_known', [
            'ssl',
        ]);

        // Configure middleware aliases (from your old $routeMiddleware)
        $middleware->alias([
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'oauth2.protected' => \App\Http\Middleware\OAuth2BearerAccessTokenRequestValidator::class,
            'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
            'etags' => \App\Http\Middleware\ETagsMiddleware::class,
            'cache' => \App\Http\Middleware\CacheMiddleware::class,
            'ssl' => \App\Http\Middleware\SSLMiddleware::class,
            'auth.user' => \App\Http\Middleware\UserAuthEndpoint::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
