<?php namespace Tests\Integration;
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

use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Group;
use services\utils\RedisCacheService;
use Tests\CreatesApplication;
use Tests\TestCase;

/**
 * Integration tests for RedisCacheService::addSingleValue and
 * RedisCacheService::deleteIfValueMatches.
 *
 * These tests require a live Redis instance and verify properties that
 * mocks cannot exercise:
 *
 *  1. Driver compatibility — the variadic SET NX EX form works with the
 *     configured Predis/PhpRedis driver.  If the driver is switched to
 *     PhpRedis, set() returns false on an NX-miss (not null), which would
 *     silently break the `!== null` check; this test catches that regression.
 *
 *  2. Atomicity — key and TTL are written in a single command; there is no
 *     window where the key exists without a TTL.  Verified by reading TTL
 *     immediately after addSingleValue returns.
 *
 *  3. Ownership — deleteIfValueMatches (the Lua compare-and-delete the lock's
 *     ownership guarantee rests on) only deletes the key when the token
 *     matches, and never touches a key it does not own. A broken script or a
 *     driver change breaking the eval($lua, 1, $key, $value) signature would
 *     silently no-op every release, holding all locks to full TTL.
 *
 */
#[Group("integration")]
final class RedisCacheServiceAddSingleValueTest extends TestCase
{
    use CreatesApplication;

    private const TEST_KEY = 'test:add_single_value:lock';
    private const TTL      = 30;

    private RedisCacheService $service;
    private mixed             $redis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redis   = Redis::connection();
        $this->service = new RedisCacheService();
        // Start clean regardless of any leftover from a previous failed run.
        $this->redis->del(self::TEST_KEY);
    }

    protected function tearDown(): void
    {
        $this->redis->del(self::TEST_KEY);
        parent::tearDown();
    }

    /**
     * First call must succeed and leave a TTL on the key.
     * Second call on the same key must return false (NX semantics).
     */
    public function testAddSingleValueSetsKeyWithTtlAndNxSemanticsHold(): void
    {
        $token = bin2hex(random_bytes(16));

        $acquired = $this->service->addSingleValue(self::TEST_KEY, $token, self::TTL);
        $this->assertTrue($acquired, 'first addSingleValue must return true');

        // Atomicity: TTL must already be set — no gap between key write and expire.
        $ttl = (int)$this->redis->ttl(self::TEST_KEY);
        $this->assertGreaterThanOrEqual(1, $ttl, 'key must have a positive TTL immediately after addSingleValue');
        $this->assertLessThanOrEqual(self::TTL, $ttl, 'TTL must not exceed the requested lifetime');

        // NX semantics: a second call while the key still exists must fail.
        $again = $this->service->addSingleValue(self::TEST_KEY, bin2hex(random_bytes(16)), self::TTL);
        $this->assertFalse($again, 'addSingleValue must return false when key already exists (NX)');
    }

    /**
     * After the key is deleted the lock can be re-acquired, confirming the
     * return-value contract holds across both the true and false branches.
     */
    public function testAddSingleValueReturnsTrueAfterKeyIsDeleted(): void
    {
        $token = bin2hex(random_bytes(16));

        $this->assertTrue($this->service->addSingleValue(self::TEST_KEY, $token, self::TTL));
        $this->redis->del(self::TEST_KEY);
        $this->assertTrue(
            $this->service->addSingleValue(self::TEST_KEY, bin2hex(random_bytes(16)), self::TTL),
            'addSingleValue must return true once the key has been removed'
        );
    }

    /**
     * A release with the matching ownership token must delete the key.
     */
    public function testDeleteIfValueMatchesDeletesKeyOnMatch(): void
    {
        $token = bin2hex(random_bytes(16));
        $this->redis->set(self::TEST_KEY, $token, 'EX', self::TTL);

        $released = $this->service->deleteIfValueMatches(self::TEST_KEY, $token);

        $this->assertTrue($released, 'deleteIfValueMatches must return true when the token matches');
        $this->assertSame(0, (int)$this->redis->exists(self::TEST_KEY), 'key must be gone after a matching release');
    }

    /**
     * A release with a stale/foreign token must leave the key untouched —
     * this is the ownership guarantee the whole lock relies on.
     */
    public function testDeleteIfValueMatchesLeavesKeyIntactOnMismatch(): void
    {
        $token = bin2hex(random_bytes(16));
        $this->redis->set(self::TEST_KEY, $token, 'EX', self::TTL);

        $released = $this->service->deleteIfValueMatches(self::TEST_KEY, bin2hex(random_bytes(16)));

        $this->assertFalse($released, 'deleteIfValueMatches must return false when the token does not match');
        $this->assertSame($token, $this->redis->get(self::TEST_KEY), 'key must survive a non-matching release attempt');
    }

    /**
     * incCounter shares the same SET...NX miss-detection as addSingleValue:
     * the first call must create the counter at 1, and the second call must
     * increment it rather than mistaking an NX-miss for a fresh creation.
     */
    public function testIncCounterIncrementsExistingCounterInsteadOfResetting(): void
    {
        $first  = $this->service->incCounter(self::TEST_KEY, self::TTL);
        $second = $this->service->incCounter(self::TEST_KEY, self::TTL);

        $this->assertSame(1, $first, 'first incCounter call must create the counter at 1');
        $this->assertSame(2, $second, 'second incCounter call must increment the existing counter, not reset it');
        $this->assertSame('2', $this->redis->get(self::TEST_KEY));
        $this->assertGreaterThan(0, (int)$this->redis->ttl(self::TEST_KEY));
    }
}
