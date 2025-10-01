<?php

namespace Tests\OpenTelemetry\Traits;

/**
 * Trait for setting up OpenTelemetry environment variables in tests
 */
trait SetsUpOpenTelemetryEnvironment
{
    /**
     * Set up OpenTelemetry environment variables before tests
     */
    protected function setUpOpenTelemetryEnvironment(): void
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

    /**
     * Clean up OpenTelemetry environment variables after tests
     */
    protected function tearDownOpenTelemetryEnvironment(): void
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
