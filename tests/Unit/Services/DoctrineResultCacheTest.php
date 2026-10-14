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

use App\Models\Foundation\Main\IGroup;
use models\summit\SummitAttendee;
use Tests\InsertMemberTestData;
use Tests\InsertSummitTestData;
use Tests\TestCase;

/**
 * Class DoctrineResultCacheTest
 * The result cache of the model entity manager goes to the doctrine_redis store, whose connection
 * (doctrine_cache) must exist: Symfony's cache adapter swallows the "connection not configured"
 * exception, so a missing connection shows up only as a cache that never stores anything.
 */
class DoctrineResultCacheTest extends TestCase
{
    use InsertSummitTestData;
    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    public function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    private function resultCache()
    {
        return self::$em->getConfiguration()->getResultCache();
    }

    private function badgeFeaturesKey(int $attendee_id): string
    {
        return sprintf("ATTENDEES_%s_BADGE_FEATURES", $attendee_id);
    }

    public function testResultCacheSavesAndServesAnItem()
    {
        $cache = $this->resultCache();
        $key = 'result-cache-test';
        $cache->deleteItem($key);

        $item = $cache->getItem($key);
        $this->assertFalse($item->isHit());
        $item->set('value')->expiresAfter(60);
        $this->assertTrue($cache->save($item), 'the result cache must be able to store an item');

        $this->assertTrue($cache->getItem($key)->isHit());
        $this->assertSame('value', $cache->getItem($key)->get());

        $cache->deleteItem($key);
        $this->assertFalse($cache->getItem($key)->isHit());
    }

    public function testAllowedBadgeFeaturesAreCachedAndEvicted()
    {
        $attendee_id = self::$summit->getAttendees()[0]->getId();
        $attendee = self::$em->find(SummitAttendee::class, $attendee_id);
        $cache = $this->resultCache();
        $key = $this->badgeFeaturesKey($attendee_id);
        $cache->deleteItem($key);

        $attendee->getAllowedBadgeFeatures();
        $this->assertTrue($cache->getItem($key)->isHit(), 'getAllowedBadgeFeatures must leave its entry in the result cache');

        $attendee->evictAllowedBadgeFeaturesCache();
        $this->assertFalse($cache->getItem($key)->isHit(), 'evictAllowedBadgeFeaturesCache must remove the entry');
    }
}
