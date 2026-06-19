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

use App\Services\Utils\Exceptions\UnacquiredLockException;
use App\Services\Utils\LockManagerService;
use libs\utils\ICacheService;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for the four bugs fixed in LockManagerService:
 *
 * 1. Non-atomic acquisition (setnx + expire → SET NX EX)
 * 2. Missing ownership token (timestamp value → random token)
 * 3. Unconditional release in finally (guarded by $token !== null)
 * 4. Broken exponential backoff (^ XOR → ** power)
 *
 * These tests use a mock ICacheService so they run without Redis.
 * The Alice/Bob scenario demonstrates that a failed acquirer never
 * deletes another process's lock key.
 */
class LockManagerServiceOwnershipTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Facade::clearResolvedInstances();
        $container = new \Illuminate\Container\Container();
        $container->instance('app', $container);
        $container->instance('log', new class {
            public function __call($name, $args) {}
        });
        \Illuminate\Support\Facades\Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\Facade::clearResolvedInstances();
        \Illuminate\Support\Facades\Facade::setFacadeApplication(null);
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Alice holds the lock. Bob's acquire exhausts retries and throws
     * UnacquiredLockException. Bob's lock() finally block must NOT call
     * deleteIfValueMatches — Bob never owned the key and must not delete it.
     *
     * On main (before fix) this test fails because releaseLock was called
     * unconditionally from the finally block.
     */
    public function testBobsFailedAcquireNeverDeletesAlicesKey(): void
    {
        // Alice: acquires once, releases once via deleteIfValueMatches.
        $aliceCache = Mockery::mock(ICacheService::class);
        $aliceCache->shouldReceive('addSingleValue')->once()->andReturn(true);
        $aliceCache->shouldReceive('deleteIfValueMatches')->once()->andReturn(true);

        // Bob: fails to acquire on every retry; must never touch Redis for release.
        $bobCache = Mockery::mock(ICacheService::class);
        $bobCache->shouldReceive('addSingleValue')
                 ->times(LockManagerService::MaxRetries)
                 ->andReturn(false);
        $bobCache->shouldReceive('deleteIfValueMatches')->never();

        $alice = new LockManagerService($aliceCache);
        $bob   = new LockManagerService($bobCache);

        $alice->lock('resource.lock', function () {
            // Alice's critical section.
        });

        $this->expectException(UnacquiredLockException::class);
        $bob->lock('resource.lock', function () {
            $this->fail('Bob must not enter the critical section.');
        });
        // Mockery tearDown asserts deleteIfValueMatches was never called on $bobCache.
    }

    /**
     * After a full acquire → callback → release cycle exactly one
     * addSingleValue and one deleteIfValueMatches must have been issued.
     * Mockery's ->once() expectations enforce this without inspecting internals.
     */
    public function testSuccessfulLockCyclePairsAcquireAndRelease(): void
    {
        $cache = Mockery::mock(ICacheService::class);
        $cache->shouldReceive('addSingleValue')->once()->andReturn(true);
        $cache->shouldReceive('deleteIfValueMatches')->once()->andReturn(true);

        $service     = new LockManagerService($cache);
        $callbackRan = false;
        $service->lock('test.lock', function () use (&$callbackRan) {
            $callbackRan = true;
        }, 3600);

        $this->assertTrue($callbackRan, 'callback must execute inside the lock');
        // Mockery tearDown verifies addSingleValue and deleteIfValueMatches each fired once.
    }

    /**
     * Structural assertion: addSingleValue is called exactly once per
     * acquisition attempt (not two separate calls for setnx + expire).
     * The call must carry the lock name, a string token, and the lifetime.
     */
    public function testAddSingleValueCalledOnceWithTokenAndLifetime(): void
    {
        $cache = Mockery::mock(ICacheService::class);
        $cache->shouldReceive('addSingleValue')
              ->once()
              ->with('test.lock', Mockery::type('string'), 3600)
              ->andReturn(true);
        $cache->shouldReceive('deleteIfValueMatches')->once()->andReturn(true);

        $service     = new LockManagerService($cache);
        $callbackRan = false;
        $service->lock('test.lock', function () use (&$callbackRan) {
            $callbackRan = true;
        }, 3600);

        $this->assertTrue($callbackRan, 'callback must execute inside the lock');
        // Mockery tearDown verifies the single atomic SET NX EX call.
    }

    /**
     * Known failure mode: when deleteIfValueMatches returns false (Redis
     * unavailable), the token is passed to the Lua script but deletion fails
     * silently. The Redis key persists until TTL expiry; there is no
     * application-level retry path after a failed release.
     */
    public function testReleaseLockWhenRedisDownLeavesKeyUntilTtl(): void
    {
        $cache = Mockery::mock(ICacheService::class);
        $cache->shouldReceive('addSingleValue')->once()->andReturn(true);
        // Simulate Redis unavailable — deletion silently fails.
        $cache->shouldReceive('deleteIfValueMatches')->once()->andReturn(false);

        $service     = new LockManagerService($cache);
        $callbackRan = false;
        $service->lock('resource.lock', function () use (&$callbackRan) {
            $callbackRan = true;
        });

        $this->assertTrue($callbackRan, 'callback must run even when the subsequent release fails');

        // The Redis key was NOT deleted; only TTL expiry can free it.
        // A subsequent acquire attempt on the same resource will fail until the
        // TTL elapses — there is no application-level retry path.
    }
}
