<?php

namespace Tests\OpenTelemetry;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use Keepsuit\LaravelOpenTelemetry\Support\HttpClient\GuzzleTraceMiddleware;
use OpenTelemetry\API\Baggage\Baggage;
use OpenTelemetry\API\Trace\TracerInterface;
use Mockery;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
class GuzzleTracingTest extends OpenTelemetryTestCase
{
    private array $capturedRequests = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Clear captured requests
        $this->capturedRequests = [];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that verifies the GuzzleTrace middleware works correctly
     * Simulates real behavior without depending on global OpenTelemetry configuration
     *
     * @return void
     */
    public function testGuzzleTraceMiddlewareAddsHeaders(): void
    {
        // Configure baggage with cf-ray simulating the input middleware
        $cfRayValue = '8a2e036cae2822-SJC';
        $userAgent = 'TestUserAgent/1.0';

        // Create an active span to have tracing context
        $tracer = $this->app->make(TracerInterface::class);
        $span = $tracer->spanBuilder('test-http-request')->startSpan();
        $spanScope = $span->activate();

        try {
            // Configure baggage in the active span context
            $baggage = Baggage::getCurrent()
                ->toBuilder()
                ->set('cf-ray', $cfRayValue)
                ->set('user_agent', $userAgent)
                ->build();

            $baggageScope = $baggage->activate();

            try {
                // Create a mock handler to capture requests
                $mockHandler = new MockHandler([
                    new Response(200, [], '{"success": true}')
                ]);

                // Middleware to capture sent headers
                $historyMiddleware = Middleware::history($this->capturedRequests);

                // Create handler stack with tracing middleware
                $handlerStack = HandlerStack::create($mockHandler);

                // IMPORTANT: First add tracing middleware, THEN history
                // This ensures history captures the request after headers are added
                $handlerStack->push(GuzzleTraceMiddleware::make());

                // History middleware goes AFTER to capture the final request with headers
                $handlerStack->push($historyMiddleware);                // Create Guzzle client
                $client = new Client(['handler' => $handlerStack]);

                // Make the request
                $response = $client->post('http://example.com/api/test', [
                    'json' => ['test' => 'data'],
                    'headers' => [
                        'X-Custom-Header' => 'custom-value'
                    ]
                ]);

                // Verify the response was successful
                $this->assertEquals(200, $response->getStatusCode());

                // Verify exactly one request was captured
                $this->assertCount(1, $this->capturedRequests);

                $transaction = $this->capturedRequests[0];
                $request = $transaction['request'];
                $headers = [];

                // Convert headers to associative array for easy verification
                foreach ($request->getHeaders() as $name => $values) {
                    $headers[strtolower($name)] = $values;
                }

                // Verify tracing headers were added
                $this->assertTrue(isset($headers['traceparent']), "Request must have traceparent header");
                $this->assertTrue(isset($headers['baggage']), "Request must have baggage header");

                // If there's baggage, verify it contains cf-ray
                if (isset($headers['baggage'])) {
                    $baggageHeader = $headers['baggage'][0];
                    $this->assertStringContainsString(
                        "cf-ray=$cfRayValue",
                        $baggageHeader,
                        'Baggage header must contain cf-ray value'
                    );
                }

                // Verify custom headers are also present
                $this->assertArrayHasKey('x-custom-header', $headers);
                $this->assertEquals('custom-value', $headers['x-custom-header'][0]);

            } finally {
                $baggageScope->detach();
            }
        } finally {
            $span->end();
            $spanScope->detach();
        }
    }

    /**
     * Test that verifies behavior when no baggage is configured
     *
     * @return void
     */
    public function testGuzzleWithoutBaggage(): void
    {
        // Create an active span but WITHOUT configured baggage
        $tracer = $this->app->make(TracerInterface::class);
        $span = $tracer->spanBuilder('test-without-baggage')->startSpan();
        $spanScope = $span->activate();

        try {
            // Create a mock handler
            $mockHandler = new MockHandler([
                new Response(200, [], '{"success": true}')
            ]);

            // Middleware to capture sent headers
            $historyMiddleware = Middleware::history($this->capturedRequests);

            // Create handler stack
            $handlerStack = HandlerStack::create($mockHandler);

            // Important order: trace middleware first, then history
            $handlerStack->push(GuzzleTraceMiddleware::make());
            $handlerStack->push($historyMiddleware);

            // Create Guzzle client
            $client = new Client(['handler' => $handlerStack]);

            // Make request without configured baggage
            $response = $client->get('http://example.com/api/test');

            // Verify response was successful
            $this->assertEquals(200, $response->getStatusCode());

            // Verify request was captured
            $this->assertCount(1, $this->capturedRequests);

            $transaction = $this->capturedRequests[0];
            $request = $transaction['request'];
            $headers = [];

            foreach ($request->getHeaders() as $name => $values) {
                $headers[strtolower($name)] = $values;
            }

            // Must have some tracing header
            $this->assertTrue(isset($headers['traceparent']), "Request must have traceparent header");

            // Should not have baggage with cf-ray if not configured
            if (isset($headers['baggage'])) {
                $baggageHeader = $headers['baggage'][0];
                $this->assertStringNotContainsString('cf-ray=', $baggageHeader);
            }
        } finally {
            $span->end();
            $spanScope->detach();
        }
    }

    /**
     * Test that verifies behavior with different cf-ray values
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cfRayProvider')]
    public function testGuzzleWithDifferentCfRayValues(string $cfRayValue): void
    {
        // Create an active span with specific baggage for this cf-ray
        $tracer = $this->app->make(TracerInterface::class);
        $span = $tracer->spanBuilder('test-different-cfray')->startSpan();
        $spanScope = $span->activate();

        try {
            // Configure baggage with specific cf-ray
            $baggage = Baggage::getCurrent()
                ->toBuilder()
                ->set('cf-ray', $cfRayValue)
                ->set('user_agent', 'test-agent')
                ->build();

            $baggageScope = $baggage->activate();

            try {
                $mockHandler = new MockHandler([
                    new Response(200, [], '{"success": true}')
                ]);

                $historyMiddleware = Middleware::history($this->capturedRequests);
                $handlerStack = HandlerStack::create($mockHandler);

                // Important order: trace middleware first, then history
                $handlerStack->push(GuzzleTraceMiddleware::make());
                $handlerStack->push($historyMiddleware);

                $client = new Client(['handler' => $handlerStack]);

                $response = $client->post('http://example.com/api/test', [
                    'json' => ['cf_ray_test' => $cfRayValue]
                ]);

                $this->assertEquals(200, $response->getStatusCode());
                $this->assertCount(1, $this->capturedRequests);

                $transaction = $this->capturedRequests[0];
                $request = $transaction['request'];
                $headers = [];

                foreach ($request->getHeaders() as $name => $values) {
                    $headers[strtolower($name)] = $values;
                }

                // Verify tracing headers
                $this->assertTrue(isset($headers['traceparent']), "Request must have traceparent header");
                $this->assertTrue(isset($headers['baggage']), "Request must have baggage header");

                // If there's baggage, verify it contains the correct cf-ray
                if (isset($headers['baggage']) && !empty($cfRayValue)) {
                    $baggageHeader = $headers['baggage'][0];
                    $this->assertStringContainsString(
                        "cf-ray=$cfRayValue",
                        $baggageHeader,
                        "Baggage header must contain cf-ray value: $cfRayValue"
                    );
                }
            } finally {
                $baggageScope->detach();
            }
        } finally {
            $span->end();
            $spanScope->detach();
        }
    }

    /**
     * Data provider for different cf-ray values
     */
    public static function cfRayProvider(): array
    {
        return [
            'cf-ray standard format' => ['8a2e036cae2822-SJC'],
            'cf-ray different datacenter' => ['7f1b025bdf1733-LAX'],
            'cf-ray another format' => ['9c3f047def3944-LHR'],
            'cf-ray with different hex' => ['ab4e158efg4055-CDG'],
        ];
    }

    /**
     * Test that verifies multiple requests maintain tracing context
     */
    public function testMultipleRequestsWithTracing(): void
    {
        $cfRayValue = '8a2e036cae2822-SJC';

        // Create an active span with baggage for all requests
        $tracer = $this->app->make(TracerInterface::class);
        $span = $tracer->spanBuilder('test-multiple-requests')->startSpan();
        $spanScope = $span->activate();

        try {
            // Configure baggage for multiple requests
            $baggage = Baggage::getCurrent()
                ->toBuilder()
                ->set('cf-ray', $cfRayValue)
                ->set('user_agent', 'test-agent')
                ->build();

            $baggageScope = $baggage->activate();

            try {
                // Configure mock for multiple responses
                $mockHandler = new MockHandler([
                    new Response(200, [], '{"request": 1}'),
                    new Response(200, [], '{"request": 2}'),
                    new Response(200, [], '{"request": 3}')
                ]);

                $historyMiddleware = Middleware::history($this->capturedRequests);
                $handlerStack = HandlerStack::create($mockHandler);

                // Important order: trace middleware first, then history
                $handlerStack->push(GuzzleTraceMiddleware::make());
                $handlerStack->push($historyMiddleware);

                $client = new Client(['handler' => $handlerStack]);

                // Make multiple requests
                for ($i = 1; $i <= 3; $i++) {
                    $response = $client->get("http://example.com/api/test/$i");
                    $this->assertEquals(200, $response->getStatusCode());
                }

                // Verify all requests were captured
                $this->assertCount(3, $this->capturedRequests);

                // Verify all requests have tracing headers
                foreach ($this->capturedRequests as $index => $transaction) {
                    $request = $transaction['request'];
                    $headers = [];

                    foreach ($request->getHeaders() as $name => $values) {
                        $headers[strtolower($name)] = $values;
                    }

                    // All must have at least tracing headers
                    $this->assertTrue(isset($headers['traceparent']), "Request $index must have traceparent header");
                    $this->assertTrue(isset($headers['baggage']), "Request $index must have baggage header");

                    // Verify baggage contains cf-ray in all requests
                    if (isset($headers['baggage'])) {
                        $baggageHeader = $headers['baggage'][0];
                        $this->assertStringContainsString(
                            "cf-ray=$cfRayValue",
                            $baggageHeader,
                            "Request $index must have cf-ray in baggage"
                        );
                    }
                }
            } finally {
                $baggageScope->detach();
            }
        } finally {
            $span->end();
            $spanScope->detach();
        }
    }

    /**
     * Creates a custom middleware for testing that simulates tracing middleware behavior
     */
    private function createCustomTraceMiddleware(string $cfRayValue, string $userAgent): callable
    {
        return function (callable $handler) use ($cfRayValue, $userAgent) {
            return function ($request, array $options) use ($handler, $cfRayValue, $userAgent) {
                // Simulate adding tracing headers
                $request = $request->withHeader('traceparent', '00-' . str_repeat('0', 32) . '-' . str_repeat('0', 16) . '-01');
                $request = $request->withHeader('baggage', "cf-ray=$cfRayValue,user_agent=" . urlencode($userAgent));

                return $handler($request, $options);
            };
        };
    }
}
