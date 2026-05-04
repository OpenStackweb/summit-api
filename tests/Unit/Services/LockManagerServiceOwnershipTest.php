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
use ReflectionClass;

/**
 * Regression tests for the four bugs fixed in LockManagerService:
 *
 * 1. Non-atomic acquisition (setnx + expire → SET NX EX)
 * 2. Missing ownership token (timestamp value → random token)
 * 3. Unconditional release in finally (guarded by $acquired flag)
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
     * Calling releaseLock on a name that was never acquired must be a
     * complete no-op — no Redis command issued, no exception thrown.
     */
    public function testReleaseLockWithoutAcquireIsNoOp(): void
    {
        $cache = Mockery::mock(ICacheService::class);
        $cache->shouldReceive('deleteIfValueMatches')->never();
        $cache->shouldReceive('delete')->never();

        $service = new LockManagerService($cache);
        $service->releaseLock('never.acquired.lock');

        // Tokens map must still be empty — the no-op must not corrupt state.
        $ref  = new ReflectionClass($service);
        $prop = $ref->getProperty('tokens');
        $prop->setAccessible(true);
        $this->assertEmpty($prop->getValue($service));
    }

    /**
     * After a full acquire → callback → release cycle the internal tokens
     * map must be empty — no token leak that could cause a future
     * releaseLock call to issue a stale deleteIfValueMatches.
     */
    public function testTokensClearedAfterSuccessfulLockCycle(): void
    {
        $cache = Mockery::mock(ICacheService::class);
        $cache->shouldReceive('addSingleValue')->once()->andReturn(true);
        $cache->shouldReceive('deleteIfValueMatches')->once()->andReturn(true);

        $service = new LockManagerService($cache);
        $service->lock('test.lock', function () {}, 3600);

        $ref  = new ReflectionClass($service);
        $prop = $ref->getProperty('tokens');
        $prop->setAccessible(true);
        $this->assertEmpty($prop->getValue($service), 'tokens map must be empty after release');
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

        $service = new LockManagerService($cache);
        $service->lock('test.lock', function () {}, 3600);

        // Tokens cleared — confirms the single addSingleValue call was paired
        // with exactly one deleteIfValueMatches (not a separate expire call).
        $ref  = new ReflectionClass($service);
        $prop = $ref->getProperty('tokens');
        $prop->setAccessible(true);
        $this->assertEmpty($prop->getValue($service));
    }
}
