<?php

namespace Tests\Unit\Services;

use App\Services\FileSystem\Dropbox\DropboxServiceProvider;
use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use GuzzleHttp\BodySummarizer;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

/**
 * Test Dropbox rate limit retry middleware
 */
class DropboxRateLimitRetryTest extends TestCase
{
    /**
     * Test that 429 responses trigger retry with Retry-After header
     */
    public function test_retries_on_429_with_retry_after_header(): void
    {
        // Arrange: Mock handler returns 429 on first call, then 200 on retry
        $mock = new MockHandler([
            new Response(429, ['Retry-After' => '1']),
            new Response(200, [], 'success'),
        ]);

        // Build handler stack matching production setup
        $stack = new HandlerStack($mock);
        $stack->push(Middleware::httpErrors(new BodySummarizer(250)), 'http_errors');
        $stack->push(DropboxServiceProvider::createRateLimitRetryMiddleware(3, 300), 'dropbox_rate_limit');

        $client = new Client(['handler' => $stack]);

        // Act: Make request that will hit 429 then retry
        $startTime = microtime(true);
        $response = $client->request('POST', 'https://content.dropboxapi.com/2/files/upload');
        $duration = microtime(true) - $startTime;

        // Assert: Response succeeded after retry
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success', (string) $response->getBody());

        // Verify delay was applied (should be at least 1 second from Retry-After header)
        $this->assertGreaterThanOrEqual(1.0, $duration, 'Should have waited at least 1 second');

        // Verify both requests were made
        $this->assertCount(0, $mock, 'Should have consumed both mocked responses');
    }

    /**
     * Test that middleware gives up after max retries
     */
    public function test_gives_up_after_max_retries(): void
    {
        // Arrange: Mock handler returns 429 for all attempts (4 total: initial + 3 retries)
        $mock = new MockHandler([
            new Response(429, ['Retry-After' => '0']),
            new Response(429, ['Retry-After' => '0']),
            new Response(429, ['Retry-After' => '0']),
            new Response(429, ['Retry-After' => '0']),
        ]);

        // Build handler stack matching production setup
        $stack = new HandlerStack($mock);
        $stack->push(Middleware::httpErrors(new BodySummarizer(250)), 'http_errors');
        $stack->push(DropboxServiceProvider::createRateLimitRetryMiddleware(3, 300), 'dropbox_rate_limit');

        $client = new Client(['handler' => $stack]);

        // Act & Assert: Should eventually fail with 429
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('429 Too Many Requests');

        $client->request('POST', 'https://content.dropboxapi.com/2/files/upload');
    }

    /**
     * Test that Retry-After header is capped at maxDelay
     */
    public function test_caps_retry_delay_at_max_delay(): void
    {
        // Arrange: Mock returns 429 with 1000 second delay, but maxDelay is 5
        $mock = new MockHandler([
            new Response(429, ['Retry-After' => '1000']),
            new Response(200, [], 'success'),
        ]);

        // Build handler stack matching production setup
        $stack = new HandlerStack($mock);
        $stack->push(Middleware::httpErrors(new BodySummarizer(250)), 'http_errors');
        $stack->push(DropboxServiceProvider::createRateLimitRetryMiddleware(3, 5), 'dropbox_rate_limit');

        $client = new Client(['handler' => $stack]);

        // Act: Make request
        $startTime = microtime(true);
        $response = $client->request('POST', 'https://content.dropboxapi.com/2/files/upload');
        $duration = microtime(true) - $startTime;

        // Assert: Delay should be capped at 5 seconds, not 1000
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertLessThan(10, $duration, 'Should have capped delay at 5 seconds (allowing margin)');
        $this->assertGreaterThanOrEqual(5.0, $duration, 'Should have waited at least 5 seconds');
    }

    /**
     * Test that non-429 responses are not retried
     */
    public function test_does_not_retry_non_429_errors(): void
    {
        // Arrange: Mock returns 500 error
        $mock = new MockHandler([
            new Response(500, [], 'server error'),
        ]);

        // Build handler stack matching production setup
        $stack = new HandlerStack($mock);
        $stack->push(Middleware::httpErrors(new BodySummarizer(250)), 'http_errors');
        $stack->push(DropboxServiceProvider::createRateLimitRetryMiddleware(3, 300), 'dropbox_rate_limit');

        $client = new Client(['handler' => $stack]);

        // Act & Assert: Should throw ServerException without retry
        $this->expectException(\GuzzleHttp\Exception\ServerException::class);
        $this->expectExceptionMessage('500 Internal Server Error');

        $client->request('POST', 'https://content.dropboxapi.com/2/files/upload');

        // Verify only one request was made (no retries)
        $this->assertCount(0, $mock, 'Should have consumed exactly one mocked response');
    }
}
