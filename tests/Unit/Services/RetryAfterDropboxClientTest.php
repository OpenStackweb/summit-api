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
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Sleep;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Spatie\Dropbox\Client as BaseDropboxClient;
use Spatie\Dropbox\TokenProvider;

/**
 * Class RetryAfterDropboxClientTest
 *
 * Unit tests for {@see RetryAfterDropboxClient::uploadChunk()}
 * covering Retry-After aware 429 handling with jitter, max retries, and passthrough.
 *
 * The previous version of this test exercised contentEndpointRequest() which is
 * inherited from the parent Spatie Client and contains NO custom retry logic.
 * The actual retry-with-sleep logic lives in the overridden uploadChunk() method.
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
     * Create a RetryAfterDropboxClient with a mocked HTTP client and token provider.
     */
    private function createClient(ClientInterface $httpClient, int $maxRetries = 5): RetryAfterDropboxClient
    {
        $tokenProvider = Mockery::mock(TokenProvider::class);
        $tokenProvider->shouldReceive('getToken')->andReturn('test-token');

        return new RetryAfterDropboxClient(
            $tokenProvider,
            $httpClient,
            BaseDropboxClient::MAX_CHUNK_SIZE,
            $maxRetries
        );
    }

    /**
     * Call the protected uploadChunk method via Closure::bind.
     * RetryAfterDropboxClient is final, so we cannot subclass it.
     * uploadChunk takes $stream by reference, so we use a closure to preserve that.
     */
    private function callUploadChunk(RetryAfterDropboxClient $client, StreamInterface &$stream, int $chunkSize = 1024)
    {
        $fn = \Closure::bind(function () use (&$stream, $chunkSize) {
            return $this->uploadChunk(
                BaseDropboxClient::UPLOAD_SESSION_START,
                $stream,
                $chunkSize
            );
        }, $client, RetryAfterDropboxClient::class);

        return $fn();
    }

    /**
     * Build a success response for upload_session/start.
     * Spatie's uploadSessionStart parses JSON with session_id from the response body.
     */
    private function makeSuccessResponse(): Response
    {
        return new Response(200, [], json_encode(['session_id' => 'test-session-123']));
    }

    /**
     * Build a 429 ClientException with an optional Retry-After header.
     */
    private function make429Exception(string $retryAfter = '2'): ClientException
    {
        $request = new Request('POST', 'https://content.dropboxapi.com/2/files/upload_session/start');
        $headers = $retryAfter !== '' ? ['Retry-After' => $retryAfter] : [];
        $response = new Response(429, $headers);
        return new ClientException('Rate limited', $request, $response);
    }

    /**
     * Build a non-429 ClientException (e.g. 403 Forbidden).
     */
    private function make403Exception(): ClientException
    {
        $request = new Request('POST', 'https://content.dropboxapi.com/2/files/upload_session/start');
        $response = new Response(403, [], 'Forbidden');
        return new ClientException('Forbidden', $request, $response);
    }

    /**
     * Test successful upload chunk without any errors.
     * Verifies no sleep is called and a cursor is returned.
     */
    public function testUploadChunkSuccessfulPassthrough(): void
    {
        Sleep::fake();

        $httpClient = Mockery::mock(ClientInterface::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->andReturn($this->makeSuccessResponse());

        $client = $this->createClient($httpClient);
        $stream = Utils::streamFor('test-content');

        $cursor = $this->callUploadChunk($client, $stream);

        $this->assertNotNull($cursor);
        Sleep::assertNeverSlept();
    }

    /**
     * Test 429 with Retry-After header triggers sleep and successful retry.
     * Verifies sleep duration: Retry-After seconds * 1000 + jitter (100-500ms).
     */
    public function testUploadChunk429WithRetryAfterSleepsAndRetries(): void
    {
        Sleep::fake();

        $httpClient = Mockery::mock(ClientInterface::class);
        $httpClient->shouldReceive('request')
            ->once()->ordered()
            ->andThrow($this->make429Exception('2'));
        $httpClient->shouldReceive('request')
            ->once()->ordered()
            ->andReturn($this->makeSuccessResponse());

        $client = $this->createClient($httpClient);
        $stream = Utils::streamFor('test-content');

        $cursor = $this->callUploadChunk($client, $stream);

        $this->assertNotNull($cursor);

        // Verify sleep: 2 seconds (2000ms) + jitter (100-500ms) = 2100-2500ms
        Sleep::assertSlept(function ($duration) {
            $millis = $duration->totalMilliseconds;
            return $millis >= 2100 && $millis <= 2500;
        }, 1);
    }

    /**
     * Test 429 without Retry-After header falls back to DEFAULT_RETRY_AFTER_SECONDS (300).
     */
    public function testUploadChunk429WithoutRetryAfterUsesDefault(): void
    {
        Sleep::fake();

        $httpClient = Mockery::mock(ClientInterface::class);
        $httpClient->shouldReceive('request')
            ->once()->ordered()
            ->andThrow($this->make429Exception(''));
        $httpClient->shouldReceive('request')
            ->once()->ordered()
            ->andReturn($this->makeSuccessResponse());

        $client = $this->createClient($httpClient);
        $stream = Utils::streamFor('test-content');

        $cursor = $this->callUploadChunk($client, $stream);

        $this->assertNotNull($cursor);

        // Default is 300 seconds (300000ms) + jitter (100-500ms) = 300100-300500ms
        Sleep::assertSlept(function ($duration) {
            $millis = $duration->totalMilliseconds;
            return $millis >= 300100 && $millis <= 300500;
        }, 1);
    }

    /**
     * Test 429 max retries exceeded throws exception after all retry attempts.
     * With maxUploadChunkRetries=5, it should attempt 5 times then throw.
     * Sleep should be called 4 times (not on the final throw).
     */
    public function testUploadChunk429MaxRetriesExceededThrowsException(): void
    {
        Sleep::fake();

        $httpClient = Mockery::mock(ClientInterface::class);
        // All 5 attempts fail with 429
        $httpClient->shouldReceive('request')
            ->times(5)
            ->andThrow($this->make429Exception('1'));

        $client = $this->createClient($httpClient, 5);
        $stream = Utils::streamFor('test-content');

        $thrown = false;
        try {
            $this->callUploadChunk($client, $stream);
        } catch (\Exception $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown, 'Expected exception to be thrown after max retries');

        // Sleep called 4 times: attempts 1-4 sleep before retry, attempt 5 throws immediately
        Sleep::assertSleptTimes(4);
    }

    /**
     * Test non-429 error retries without sleep.
     * A 403 should still be retried (up to max retries) but without any sleep.
     */
    public function testUploadChunkNon429RetriesWithoutSleep(): void
    {
        Sleep::fake();

        $httpClient = Mockery::mock(ClientInterface::class);
        $httpClient->shouldReceive('request')
            ->once()->ordered()
            ->andThrow($this->make403Exception());
        $httpClient->shouldReceive('request')
            ->once()->ordered()
            ->andReturn($this->makeSuccessResponse());

        $client = $this->createClient($httpClient);
        $stream = Utils::streamFor('test-content');

        $cursor = $this->callUploadChunk($client, $stream);

        $this->assertNotNull($cursor);
        // No sleep for non-429 errors
        Sleep::assertNeverSlept();
    }
}
