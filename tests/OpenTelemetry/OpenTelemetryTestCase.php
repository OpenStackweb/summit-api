<?php

namespace Tests\OpenTelemetry;

use Tests\TestCase;

/**
 * Base test case for OpenTelemetry tests that handles environment setup
 */
abstract class OpenTelemetryTestCase extends TestCase
{
    protected function setUp(): void
    {
        // Set OpenTelemetry environment variables for testing
        $this->setOpenTelemetryEnvironmentVariables();

        parent::setUp();

        // Register the OpenTelemetry testing ServiceProvider
        $this->app->register(\Tests\Support\OpenTelemetryTestingServiceProvider::class);

        // Enable OpenTelemetry in testing environment
        config(['opentelemetry.enabled' => true]);

        // Configure necessary instrumentations
        config([
            'opentelemetry.instrumentation' => [
                \Keepsuit\LaravelOpenTelemetry\Instrumentation\HttpClientInstrumentation::class => [
                    'enabled' => true,
                    'manual' => false,
                    'allowed_headers' => [],
                    'sensitive_headers' => [],
                ],
            ]
        ]);
    }

    /**
     * Set OpenTelemetry environment variables for testing
     */
    protected function setOpenTelemetryEnvironmentVariables(): void
    {
        putenv('OTEL_SERVICE_ENABLED=true');
        putenv('OTEL_SERVICE_NAME=summit-api-test');
        putenv('OTEL_PROPAGATORS=tracecontext,baggage');
        putenv('OTEL_INSTRUMENTATION_HTTP_CLIENT=true');
        putenv('OTEL_INSTRUMENTATION_REDIS=false');
        putenv('OTEL_INSTRUMENTATION_QUERY=false');
        putenv('OTEL_INSTRUMENTATION_QUEUE=false');
        putenv('OTEL_INSTRUMENTATION_CACHE=false');
        putenv('OTEL_INSTRUMENTATION_EVENT=false');
        putenv('OTEL_INSTRUMENTATION_VIEW=false');
        putenv('OTEL_INSTRUMENTATION_CONSOLE=false');
        putenv('OTEL_TRACES_EXPORTER=none');
        putenv('OTEL_METRICS_EXPORTER=none');
    }

    protected function tearDown(): void
    {
        // Clean up environment variables
        $this->cleanupOpenTelemetryEnvironmentVariables();
        parent::tearDown();
    }

    /**
     * Clean up OpenTelemetry environment variables after testing
     */
    protected function cleanupOpenTelemetryEnvironmentVariables(): void
    {
        putenv('OTEL_SERVICE_ENABLED');
        putenv('OTEL_SERVICE_NAME');
        putenv('OTEL_PROPAGATORS');
        putenv('OTEL_INSTRUMENTATION_HTTP_CLIENT');
        putenv('OTEL_INSTRUMENTATION_REDIS');
        putenv('OTEL_INSTRUMENTATION_QUERY');
        putenv('OTEL_INSTRUMENTATION_QUEUE');
        putenv('OTEL_INSTRUMENTATION_CACHE');
        putenv('OTEL_INSTRUMENTATION_EVENT');
        putenv('OTEL_INSTRUMENTATION_VIEW');
        putenv('OTEL_INSTRUMENTATION_CONSOLE');
        putenv('OTEL_TRACES_EXPORTER');
        putenv('OTEL_METRICS_EXPORTER');
    }
}
