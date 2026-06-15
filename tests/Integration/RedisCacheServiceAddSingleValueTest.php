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
 * Integration tests for RedisCacheService::addSingleValue.
 *
 * These tests require a live Redis instance and verify two properties that
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
}
