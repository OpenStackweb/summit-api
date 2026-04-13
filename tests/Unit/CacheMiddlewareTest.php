<?php namespace Tests\Unit;
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

use App\Http\Middleware\CacheMiddleware;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use models\oauth2\IResourceServerContext;
use Tests\TestCase;

/**
 * Class CacheMiddlewareTest
 * Tests for CacheMiddleware lock timeout handling
 */
final class CacheMiddlewareTest extends TestCase
{
    /**
     * Test that LockTimeoutException is caught and handled gracefully
     * When block() throws LockTimeoutException, the middleware should:
     * 1. Log a WARNING (not propagate the exception as ERROR)
     * 2. Execute the handler without the lock
     * 3. Return a valid response
     *
     * @return void
     */
    public function test_lock_timeout_exception_handled_gracefully()
    {
        // Arrange: Create a GET request
        $request = Request::create('/api/v1/summits/123', 'GET');

        // Mock the resource server context (no user for this test)
        $context = $this->createMock(IResourceServerContext::class);
        $context->method('getCurrentUser')->willReturn(null);

        // Create the middleware instance
        $middleware = new CacheMiddleware($context);

        // Mock the next handler to return a JSON response
        $expectedData = ['data' => 'test_response'];
        $next = function ($req) use ($expectedData) {
            return new JsonResponse($expectedData, 200);
        };

        // Mock Cache facade behavior
        $mockCache = $this->createMock(\Illuminate\Contracts\Cache\Repository::class);
        $mockLock = $this->createMock(\Illuminate\Contracts\Cache\Lock::class);

        // First get() returns null (cache miss)
        $mockCache->expects($this->once())
            ->method('get')
            ->willReturn(null);

        // Lock is created
        $mockCache->expects($this->once())
            ->method('lock')
            ->willReturn($mockLock);

        // block() throws LockTimeoutException (simulating timeout)
        $mockLock->expects($this->once())
            ->method('block')
            ->willThrowException(new LockTimeoutException());

        // Lock release is attempted in finally block
        $mockLock->expects($this->once())
            ->method('release');

        // Cache put should be called (caching the 200 response)
        $mockCache->expects($this->once())
            ->method('put');

        Cache::shouldReceive('store')
            ->once()
            ->andReturn($mockCache);

        // Expect WARNING log (not ERROR)
        Log::shouldReceive('warning')
            ->once()
            ->with('CacheMiddleware: lock timeout, executing handler without lock', $this->anything());

        Log::shouldReceive('debug')
            ->zeroOrMoreTimes();

        // Act: Handle the request through the middleware
        $response = $middleware->handle($request, $next, 60);

        // Assert: Response is valid, not a 500 error
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedData, $response->getData(true));
        $this->assertEquals('MISS', $response->headers->get('X-Cache-Result'));
    }
}
