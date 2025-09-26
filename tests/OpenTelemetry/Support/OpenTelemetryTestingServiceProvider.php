<?php

namespace Tests\OpenTelemetry\Support;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\NoopSpanProcessor;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\API\Common\Instrumentation\Globals;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\Context\Propagation\TextMapPropagatorInterface;
use OpenTelemetry\API\Baggage\Propagation\BaggagePropagator;
use OpenTelemetry\Context\Propagation\MultiTextMapPropagator;
use Keepsuit\LaravelOpenTelemetry\Tracer;
use OpenTelemetry\API\Globals as OTAPIGlobals;

class OpenTelemetryTestingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Siempre registrar durante las pruebas
        $this->registerTracerProvider();
        $this->registerPropagators();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->bind(TracerInterface::class, function ($app) {
            /** @var TracerProvider $tracerProvider */
            $tracerProvider = $app->make(TracerProvider::class);
            return $tracerProvider->getTracer('test-tracer', '1.0.0');
        });
    }

    /**
     * Register the TraceProvider for testing
     */
    protected function registerTracerProvider(): void
    {
        $this->app->singleton(TracerProvider::class, function () {
            $resource = ResourceInfoFactory::emptyResource();

            return TracerProvider::builder()
                ->addSpanProcessor(new NoopSpanProcessor())
                ->setResource($resource)
                ->build();
        });

        // Registrar el Tracer principal que usa el facade
        $this->app->singleton(Tracer::class, function ($app) {
            return new Tracer(
                $app->make(TracerInterface::class),
                $app->make(TextMapPropagatorInterface::class)
            );
        });
    }

    /**
     * Register propagators for testing
     */
    protected function registerPropagators(): void
    {
        $this->app->singleton(TextMapPropagatorInterface::class, function () {
            return new MultiTextMapPropagator([
                new TraceContextPropagator(),
                new BaggagePropagator(),
            ]);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            TracerProvider::class,
            TracerInterface::class,
            \Keepsuit\LaravelOpenTelemetry\Tracer::class,
            TextMapPropagatorInterface::class,
        ];
    }
}
