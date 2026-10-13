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

use App\Exceptions\Handler;
use App\Http\Middleware\RateLimitMiddleware;
use App\Models\ResourceServer\IApiEndpointRepository;
use App\Models\ResourceServer\IEndpointRateLimitByIPRepository;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Class RateLimitMiddlewareTest
 * Each rate limited route must own its counter and window per client IP.
 */
final class RateLimitMiddlewareTest extends TestCase
{
    private const NOTIFY_URI = '/api/v1/summits/1/presentations/1/submission-period/reopen/notify';
    private const APPLY_URI = '/api/v1/summits/1/promo-codes/ABC/apply';

    private function buildMiddleware(): RateLimitMiddleware
    {
        return new RateLimitMiddleware(
            $this->createMock(IApiEndpointRepository::class),
            $this->createMock(IEndpointRateLimitByIPRepository::class),
            new RateLimiter(new Repository(new ArrayStore()))
        );
    }

    private function buildRequest(string $method, string $uri, string $ip): Request
    {
        $request = Request::create($uri, $method, [], [], [], ['REMOTE_ADDR' => $ip]);
        $request->setRouteResolver(fn() => Route::getRoutes()->match($request));
        return $request;
    }

    private function next(): \Closure
    {
        return fn($request) => new JsonResponse(['ok' => true], 200);
    }

    public function test_handle_counter_of_one_route_does_not_block_another_route_for_same_ip(): void
    {
        $middleware = $this->buildMiddleware();

        // exhaust the notify budget (30 per 3600 seconds) from one IP
        for ($i = 0; $i < 30; $i++) {
            $middleware->handle($this->buildRequest('PUT', self::NOTIFY_URI, '10.0.0.1'), $this->next(), 30, 3600);
        }

        // a different route from the same IP has its own budget
        $response = $middleware->handle($this->buildRequest('GET', self::APPLY_URI, '10.0.0.1'), $this->next(), 25, 1);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_handle_same_route_still_enforces_its_own_limit(): void
    {
        $middleware = $this->buildMiddleware();

        for ($i = 0; $i < 25; $i++) {
            $middleware->handle($this->buildRequest('GET', self::APPLY_URI, '10.0.0.1'), $this->next(), 25, 60);
        }

        $this->expectException(ThrottleRequestsException::class);
        $middleware->handle($this->buildRequest('GET', self::APPLY_URI, '10.0.0.1'), $this->next(), 25, 60);
    }

    public function test_handle_429_response_is_not_masked_as_server_error(): void
    {
        config(['app.debug' => false]);

        $exception = new ThrottleRequestsException('Too Many Attempts.', null, ['Retry-After' => 60]);
        $response = (new Handler($this->app))->render(
            Request::create(self::APPLY_URI, 'GET'),
            $exception
        );

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('Too Many Requests', json_decode($response->getContent(), true)['message']);
        $this->assertSame('60', $response->headers->get('Retry-After'));
    }
}
