<?php namespace Tests\Unit\Services;
/**
 * Copyright 2026 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

use App\Services\FileSystem\Dropbox\RetryAfterDropboxClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Sleep;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Spatie\Dropbox\TokenProvider;

/**
 * Class RetryAfterDropboxClientTest
 *
 * Unit tests for {@see RetryAfterDropboxClient::contentEndpointRequest()}
 * covering Retry-After aware 429 handling with jitter, max retries, and passthrough.
 *
 * @package Tests\Unit\Services
 */
class RetryAfterDropboxClientTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test successful request passthrough (no 429)
     * Verifies parent behavior works when no rate limit is hit
     */
    public function testContentEndpointRequestSuccessfulPassthrough(): void
    {
        Sleep::fake();

        $httpClient = Mockery::mock(ClientInterface::class);
        $tokenProvider = Mockery::mock(TokenProvider::class);
        $response = Mockery::mock(ResponseInterface::class);

        $tokenProvider->shouldReceive('getToken')->andReturn('test-token');
        $tokenProvider->shouldReceive('isRefreshable')->andReturn(false);

        $httpClient->shouldReceive('request')
            ->once()
            ->andReturn($response);

        $client = new RetryAfterDropboxClient($httpClient, $tokenProvider);
        $result = $client->contentEndpointRequest('/test/endpoint', ['arg' => 'value'], 'body');

        $this->assertSame($response, $result);
        Sleep::assertNeverSlept();
    }

    /**
     * Test 429 with Retry-After header
     * Verifies sleep duration includes Retry-After value + jitter range (100-500ms)
     */
    public function testContentEndpointRequest429WithRetryAfter(): void
    {
        Sleep::fake();

        $httpClient = Mockery::mock(ClientInterface::class);
        $tokenProvider = Mockery::mock(TokenProvider::class);
        $request = Mockery::mock(RequestInterface::class);
        $response429 = Mockery::mock(ResponseInterface::class);
        $responseSuccess = Mockery::mock(ResponseInterface::class);

        $tokenProvider->shouldReceive('getToken')->andReturn('test-token');
        $tokenProvider->shouldReceive('isRefreshable')->andReturn(false);

        $response429->shouldReceive('getStatusCode')->andReturn(429);
        $response429->shouldReceive('getHeaderLine')->with('Retry-After')->andReturn('2');

        $exception = Mockery::mock(ClientException::class);
        $exception->shouldReceive('getResponse')->andReturn($response429);
        $exception->shouldReceive('getRequest')->andReturn($request);

        $httpClient->shouldReceive('request')
            ->once()
            ->andThrow($exception);

        $httpClient->shouldReceive('request')
            ->once()
            ->andReturn($responseSuccess);

        $client = new RetryAfterDropboxClient($httpClient, $tokenProvider);
        $result = $client->contentEndpointRequest('/test/endpoint', ['arg' => 'value'], 'body');

        $this->assertSame($responseSuccess, $result);

        // Verify sleep was called with 2 seconds (2000ms) + jitter (100-500ms)
        // Total range: 2100ms to 2500ms
        Sleep::assertSlept(function ($duration) {
            $millis = $duration->totalMilliseconds;
            return $millis >= 2100 && $millis <= 2500;
        }, 1);
    }

    /**
     * Test 429 max retries exceeded (5 attempts)
     * Verifies exception is rethrown after exhausting all retry attempts
     */
    public function testContentEndpointRequest429MaxRetriesExceeded(): void
    {
        Sleep::fake();

        $httpClient = Mockery::mock(ClientInterface::class);
        $tokenProvider = Mockery::mock(TokenProvider::class);
        $request = Mockery::mock(RequestInterface::class);
        $response429 = Mockery::mock(ResponseInterface::class);

        $tokenProvider->shouldReceive('getToken')->andReturn('test-token');
        $tokenProvider->shouldReceive('isRefreshable')->andReturn(false);

        $response429->shouldReceive('getStatusCode')->andReturn(429);
        $response429->shouldReceive('getHeaderLine')->with('Retry-After')->andReturn('1');

        $exception = Mockery::mock(ClientException::class);
        $exception->shouldReceive('getResponse')->andReturn($response429);
        $exception->shouldReceive('getRequest')->andReturn($request);

        // All 5 attempts fail with 429
        $httpClient->shouldReceive('request')
            ->times(5)
            ->andThrow($exception);

        $client = new RetryAfterDropboxClient($httpClient, $tokenProvider);

        $this->expectException(ClientException::class);
        $client->contentEndpointRequest('/test/endpoint', ['arg' => 'value'], 'body');

        // Verify sleep was called 4 times (not on the 5th/final attempt before throwing)
        Sleep::assertSleptTimes(4);
    }

    /**
     * Test non-429 ClientException passthrough
     * Verifies non-429 errors pass through to parent behavior without retry
     */
    public function testContentEndpointRequestNon429Exception(): void
    {
        Sleep::fake();

        $httpClient = Mockery::mock(ClientInterface::class);
        $tokenProvider = Mockery::mock(TokenProvider::class);
        $request = Mockery::mock(RequestInterface::class);
        $response500 = Mockery::mock(ResponseInterface::class);

        $tokenProvider->shouldReceive('getToken')->andReturn('test-token');
        $tokenProvider->shouldReceive('isRefreshable')->andReturn(false);

        $response500->shouldReceive('getStatusCode')->andReturn(500);

        $exception = Mockery::mock(ClientException::class);
        $exception->shouldReceive('getResponse')->andReturn($response500);
        $exception->shouldReceive('getRequest')->andReturn($request);

        $httpClient->shouldReceive('request')
            ->once()
            ->andThrow($exception);

        $client = new RetryAfterDropboxClient($httpClient, $tokenProvider);

        $this->expectException(ClientException::class);
        $client->contentEndpointRequest('/test/endpoint', ['arg' => 'value'], 'body');

        // Verify no sleep was called (no retry for non-429)
        Sleep::assertNeverSlept();
    }

    /**
     * Test 429 without Retry-After header
     * Verifies default 1 second sleep when Retry-After header is absent
     */
    public function testContentEndpointRequest429WithoutRetryAfter(): void
    {
        Sleep::fake();

        $httpClient = Mockery::mock(ClientInterface::class);
        $tokenProvider = Mockery::mock(TokenProvider::class);
        $request = Mockery::mock(RequestInterface::class);
        $response429 = Mockery::mock(ResponseInterface::class);
        $responseSuccess = Mockery::mock(ResponseInterface::class);

        $tokenProvider->shouldReceive('getToken')->andReturn('test-token');
        $tokenProvider->shouldReceive('isRefreshable')->andReturn(false);

        $response429->shouldReceive('getStatusCode')->andReturn(429);
        $response429->shouldReceive('getHeaderLine')->with('Retry-After')->andReturn(''); // No header

        $exception = Mockery::mock(ClientException::class);
        $exception->shouldReceive('getResponse')->andReturn($response429);
        $exception->shouldReceive('getRequest')->andReturn($request);

        $httpClient->shouldReceive('request')
            ->once()
            ->andThrow($exception);

        $httpClient->shouldReceive('request')
            ->once()
            ->andReturn($responseSuccess);

        $client = new RetryAfterDropboxClient($httpClient, $tokenProvider);
        $result = $client->contentEndpointRequest('/test/endpoint', ['arg' => 'value'], 'body');

        $this->assertSame($responseSuccess, $result);

        // Verify sleep was called with default 1 second (1000ms) + jitter (100-500ms)
        // Total range: 1100ms to 1500ms
        Sleep::assertSlept(function ($duration) {
            $millis = $duration->totalMilliseconds;
            return $millis >= 1100 && $millis <= 1500;
        }, 1);
    }
}
