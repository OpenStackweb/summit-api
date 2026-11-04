<?php namespace Tests\Repositories;
/*
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
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeBadge;
use Tests\InsertOrdersTestData;
use Tests\InsertSummitTestData;
use Tests\ProtectedApiTestCase;

/**
 * The tickets per badge feature type counts include the features a badge inherits from its badge type,
 * the same semantics as SummitAttendeeBadge::getAllFeatures.
 *
 * Fixture (feature F1 is granted at badge level, F2 by the badge type, F3 at both levels):
 *  ticket 0: default badge type, F1 on the badge
 *  ticket 1: badge type 2 (F2, F3), nothing on the badge, owner checked in
 *  ticket 2: badge type 2 (F2, F3), F3 on the badge, owner checked in
 *  ticket 3: badge type 2 (F2, F3), F1 on the badge, owner NOT checked in
 *
 * @package Tests\Repositories
 */
final class RegistrationStatsBadgeFeatureTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;
    use InsertOrdersTestData;

    private Summit $stats_summit;

    protected function setUp(): void
    {
        $this->current_group = IGroup::TrackChairs;
        parent::setUp();
        self::insertSummitTestData();
        self::InsertOrdersTestData();

        [$f1, $f2, $f3] = self::$badge_features;
        self::$badge_type_2->addBadgeFeatureType($f2);
        self::$badge_type_2->addBadgeFeatureType($f3);

        $layout = [
            [self::$default_badge_type, [$f1]],
            [self::$badge_type_2, []],
            [self::$badge_type_2, [$f3]],
            [self::$badge_type_2, [$f1]],
        ];

        $checked_in = [];
        foreach ($layout as $i => [$type, $features]) {
            $ticket = self::$summit_orders[$i]->getTickets()->first();
            $badge = new SummitAttendeeBadge();
            $badge->setType($type);
            $badge->setTicket($ticket);
            foreach ($features as $f) {
                $badge->addFeature($f);
            }
            $ticket->setBadge($badge);

            if ($i === 0) continue;
            $attendee = new SummitAttendee();
            $attendee->setEmail(sprintf("badge-feature+%s@test.com", $i));
            $attendee->setFirstName("Attendee");
            $attendee->setSurname(sprintf("%s", $i));
            $attendee->addTicket($ticket);
            self::$summit->addAttendee($attendee);
            self::$em->persist($attendee);
            self::$em->flush();
            if ($i !== 3) $checked_in[] = $attendee->getId();
        }
        self::$em->flush();

        DB::table('SummitAttendee')->whereIn('ID', $checked_in)->update(['SummitHallCheckedIn' => 1]);

        $id = self::$summit->getId();
        self::$em->clear();
        $this->stats_summit = self::$em->find(Summit::class, $id);
    }

    protected function tearDown(): void
    {
        self::clearOrdersTestData();
        self::clearSummitTestData();
        parent::tearDown();
    }

    private static function byType(array $rows): array
    {
        $res = array_column($rows, 'qty', 'type');
        $res = array_map('intval', $res);
        ksort($res);
        return $res;
    }

    public function testSoldQtyIncludesFeaturesInheritedFromTheBadgeType(): void
    {
        $this->assertSame(
            ['FEATURE 1' => 2, 'FEATURE 2' => 3, 'FEATURE 3' => 3],
            self::byType($this->stats_summit->getActiveTicketsPerBadgeFeatureType())
        );
    }

    public function testCheckinQtyIncludesFeaturesInheritedFromTheBadgeType(): void
    {
        $this->assertSame(
            ['FEATURE 2' => 2, 'FEATURE 3' => 2],
            self::byType($this->stats_summit->getAttendeesCheckinPerBadgeFeatureType())
        );
    }

    public function testDateFilterAppliesToBothBranches(): void
    {
        $future = new DateTime('+1 year', new DateTimeZone('UTC'));
        $this->assertSame([], $this->stats_summit->getActiveTicketsPerBadgeFeatureType($future));
        $this->assertSame([], $this->stats_summit->getAttendeesCheckinPerBadgeFeatureType($future));

        $past = new DateTime('-1 year', new DateTimeZone('UTC'));
        $this->assertSame(
            ['FEATURE 1' => 2, 'FEATURE 2' => 3, 'FEATURE 3' => 3],
            self::byType($this->stats_summit->getActiveTicketsPerBadgeFeatureType($past))
        );
    }
}
