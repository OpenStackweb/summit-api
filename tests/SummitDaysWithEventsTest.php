<?php namespace Tests;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use models\summit\Summit;

/**
 * Summit::getSummitDaysWithEvents runs one query per summit and applies the per day
 * overlap test in PHP, the result must match the former one COUNT per day implementation.
 *
 * Class SummitDaysWithEventsTest
 */
final class SummitDaysWithEventsTest extends TestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('cache_api_response.summit_dates_with_events_lifetime', 0);
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    /**
     * the former implementation, one COUNT per summit day
     * @return string[]
     */
    private function legacyDaysWithEvents(Summit $summit): array
    {
        $res = [];
        foreach ($summit->getSummitDays() as $day) {
            $begin = (clone $day)->setTime(0, 0, 0)->setTimezone(new \DateTimeZone('UTC'));
            $end = (clone $day)->setTime(23, 59, 59)->setTimezone(new \DateTimeZone('UTC'));
            $count = DB::selectOne(
                "SELECT COUNT(ID) AS QTY FROM SummitEvent WHERE SummitID = ? AND Published = 1 AND StartDate <= ? AND EndDate >= ?",
                [$summit->getId(), $end->format('Y-m-d H:i:s'), $begin->format('Y-m-d H:i:s')]
            )->QTY;
            if ($count > 0) $res[] = $day->format('Y-m-d');
        }
        return $res;
    }

    private function daysWithEvents(Summit $summit): array
    {
        return array_map(function ($day) {
            return $day->format('Y-m-d');
        }, $summit->getSummitDaysWithEvents());
    }

    public function testMatchesPerDayImplementation(): void
    {
        $expected = $this->legacyDaysWithEvents(self::$summit);
        $this->assertNotEmpty($expected);
        $this->assertSame($expected, $this->daysWithEvents(self::$summit));
    }

    public function testEventCrossingDaysCountsOnBothDaysAndDaysWithoutEventsAreSkipped(): void
    {
        $summit_id = self::$summit->getId();
        DB::update("UPDATE SummitEvent SET Published = 0 WHERE SummitID = ?", [$summit_id]);
        $days = self::$summit->getSummitDays();

        // one event from the evening of day 2 to the morning of day 3, summit timezone
        $start = (clone $days[1])->setTime(22, 0, 0)->setTimezone(new \DateTimeZone('UTC'));
        $end = (clone $days[2])->setTime(2, 0, 0)->setTimezone(new \DateTimeZone('UTC'));
        $id = DB::selectOne("SELECT ID FROM SummitEvent WHERE SummitID = ? LIMIT 1", [$summit_id])->ID;
        DB::update(
            "UPDATE SummitEvent SET Published = 1, StartDate = ?, EndDate = ? WHERE ID = ?",
            [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $id]
        );

        $expected = [$days[1]->format('Y-m-d'), $days[2]->format('Y-m-d')];
        $this->assertSame($expected, $this->daysWithEvents(self::$summit));
        $this->assertSame($expected, $this->legacyDaysWithEvents(self::$summit));
    }

    public function testPublishedEventsWithoutDatesAreIgnored(): void
    {
        $summit_id = self::$summit->getId();
        DB::update("UPDATE SummitEvent SET Published = 0 WHERE SummitID = ?", [$summit_id]);
        $days = self::$summit->getSummitDays();
        $end = (clone $days[2])->setTime(2, 0, 0)->setTimezone(new \DateTimeZone('UTC'));
        $id = DB::selectOne("SELECT ID FROM SummitEvent WHERE SummitID = ? LIMIT 1", [$summit_id])->ID;
        DB::update(
            "UPDATE SummitEvent SET Published = 1, StartDate = NULL, EndDate = ? WHERE ID = ?",
            [$end->format('Y-m-d H:i:s'), $id]
        );

        $this->assertSame([], $this->legacyDaysWithEvents(self::$summit));
        $this->assertSame([], $this->daysWithEvents(self::$summit));
    }

    public function testRunsASingleSelectRegardlessOfTheNumberOfDays(): void
    {
        $this->assertGreaterThan(5, count(self::$summit->getSummitDays()));
        $conn = self::$em->getConnection();
        $selects = function () use ($conn): int {
            return intval($conn->fetchAssociative("SHOW SESSION STATUS LIKE 'Com_select'")['Value']);
        };
        $before = $selects();
        $this->daysWithEvents(self::$summit);
        $this->assertSame(1, $selects() - $before);
    }

    public function testCachedListIsServedWithinTheLifetime(): void
    {
        Config::set('cache_api_response.summit_dates_with_events_lifetime', 300);
        Cache::flush();
        $first = $this->daysWithEvents(self::$summit);
        $this->assertNotEmpty($first);
        DB::update("UPDATE SummitEvent SET Published = 0 WHERE SummitID = ?", [self::$summit->getId()]);
        $this->assertSame($first, $this->daysWithEvents(self::$summit));
        Config::set('cache_api_response.summit_dates_with_events_lifetime', 0);
        $this->assertSame([], $this->daysWithEvents(self::$summit));
        Cache::flush();
    }
}
