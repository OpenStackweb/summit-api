# OpenTelemetry Test Suite for Guzzle HTTP Client

This test suite verifies that OpenTelemetry works correctly with the Guzzle HTTP client to propagate tracing headers, including Cloudflare's `Cf-Ray` header.

## Overview

The tests verify that the `GuzzleTraceMiddleware` works correctly by:
1. **Adding OpenTelemetry tracing headers**: Verifies that `traceparent` and `baggage` headers are automatically added to outgoing HTTP requests.
2. **Propagating baggage context**: When `baggage` contains `cf-ray` and `user-agent` values, they are correctly propagated in the `baggage` header of outgoing requests.
3. **Handling requests without baggage**: Ensures the middleware still adds tracing headers (`traceparent`) even when no baggage is configured.
4. **Supporting different `cf-ray` formats**: Tests with various `cf-ray` value formats to ensure compatibility.
5. **Maintaining context across multiple requests**: Verifies that tracing context is preserved across sequential HTTP requests within the same span.

## Created Files

### Tests
- `tests/OpenTelemetry/GuzzleTracingTest.php`: Unit tests that mock HTTP requests to verify middleware behavior

### Support
- `tests/OpenTelemetry/OpenTelemetryTestCase.php`: Base test case that sets up OpenTelemetry environment and registers the testing ServiceProvider
- `tests/Support/OpenTelemetryTestingServiceProvider.php`: ServiceProvider to configure OpenTelemetry in testing environment
- `tests/OpenTelemetry/Traits/SetsUpOpenTelemetryEnvironment.php`: Trait for environment setup in tests that don't extend the base case

## Testing Environment Configuration

### OpenTelemetryTestCase.php
The base test case (`tests/OpenTelemetry/OpenTelemetryTestCase.php`) programmatically sets up OpenTelemetry environment variables:
```php
putenv('OTEL_SERVICE_ENABLED=true');
putenv('OTEL_SERVICE_NAME=summit-api-test');
putenv('OTEL_PROPAGATORS=tracecontext,baggage');
putenv('OTEL_INSTRUMENTATION_HTTP_CLIENT=true');
// ... more OTEL variables ...
```

### Alternative: SetsUpOpenTelemetryEnvironment Trait
Available trait (`tests/OpenTelemetry/Traits/SetsUpOpenTelemetryEnvironment.php`) provides the same environment setup for tests that don't extend `OpenTelemetryTestCase`.

### OpenTelemetryTestingServiceProvider
The ServiceProvider (`tests/Support/OpenTelemetryTestingServiceProvider.php`) is automatically registered by `OpenTelemetryTestCase` and provides:
- `TracerInterface` binding with NoopSpanProcessor for testing
- Propagators configuration (TraceContext and Baggage)
- Laravel OpenTelemetry `Tracer` facade integration

The tests use `$this->app->make(TracerInterface::class)` to get the tracer instance configured by this ServiceProvider.

## Running Tests

```bash
# Run entire OTEL test suite
docker-compose exec app vendor/bin/phpunit --testsuite="OTEL"

# Run only Guzzle tracing tests
docker-compose exec app vendor/bin/phpunit tests/OpenTelemetry/GuzzleTracingTest.php

# Run a specific test
docker-compose exec app vendor/bin/phpunit --filter="testGuzzleTraceMiddlewareAddsHeaders"
```

## Included Tests

1. **testGuzzleTraceMiddlewareAddsHeaders**: Main test that verifies the middleware adds both `traceparent` and `baggage` headers, and that baggage correctly contains `cf-ray` and `user-agent` values from the active baggage context.
2. **testGuzzleWithoutBaggage**: Verifies that when no baggage is configured, the middleware still adds `traceparent` headers but doesn't include `cf-ray` in baggage.
3. **testGuzzleWithDifferentCfRayValues**: Uses a data provider to test the middleware with various `cf-ray` value formats (different datacenters and hex values).
4. **testMultipleRequestsWithTracing**: Verifies that tracing context and baggage are maintained across multiple sequential HTTP requests within the same span.

## Test Implementation Details

The tests use mocked HTTP handlers to simulate real HTTP requests:
- **MockHandler**: Provides predefined responses without making actual HTTP calls
- **History Middleware**: Captures outgoing requests with their headers for verification
- **Active Spans**: Creates OpenTelemetry spans to provide the required tracing context
- **Baggage Configuration**: Manually sets baggage values (`cf-ray`, `user-agent`) to simulate real middleware behavior

## Test Verifications

Each test performs the following verifications:
- **Header Presence**: Confirms that `traceparent` headers are added to all outgoing requests
- **Baggage Propagation**: When baggage is configured, verifies that `baggage` headers contain the expected `cf-ray` values
- **Header Format**: Validates that baggage headers follow the correct format (`cf-ray`=value,`user-agent`=value)
- **Context Isolation**: Ensures that tests without baggage don't leak `cf-ray` values into headers
- **Custom Headers Preserved**: Verifies that existing request headers are maintained alongside tracing headers
- **Mock Response Handling**: Confirms that mocked responses are properly returned

## Technical Implementation Notes

- **Middleware Order**: Critical that `GuzzleTraceMiddleware` is added BEFORE the History middleware to ensure headers are captured after being added by the trace middleware
- **Active Span Requirement**: An active OpenTelemetry span must be present for the `GuzzleTraceMiddleware` to function properly
- **Baggage Scope Management**: Baggage must be activated and properly scoped to ensure values are available during request processing
- **MockHandler Setup**: Tests use `MockHandler` with predefined responses to avoid actual HTTP calls while testing middleware behavior
- **ServiceProvider Integration**: The test suite uses `OpenTelemetryTestingServiceProvider` to configure TracerInterface and propagators in the test environment

These tests verify that the `GuzzleTraceMiddleware` correctly integrates with OpenTelemetry's tracing system to add proper headers to outgoing HTTP requests.
