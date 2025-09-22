<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Logs\LoggerInterface;
use OpenTelemetry\API\Logs\NoopLogger;

class OpenTelemetryServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    // Registrar inmediatamente un binding para LoggerInterface
    $this->app->bind(LoggerInterface::class, function ($app) {
      // Usar NoopLogger como implementación por defecto
      return new NoopLogger();
    });
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    //
  }
}