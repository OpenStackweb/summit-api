<?php namespace Tests\Unit\Models;
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
use Doctrine\DBAL\Logging\SQLLogger;
use LaravelDoctrine\ORM\Facades\Registry;
use models\main\Group;
use models\main\Member;
use models\utils\SilverstripeBaseModel;
use Tests\InsertMemberTestData;
use Tests\TestCase;

/**
 * Class MemberGroupMembershipCacheTest
 *
 * Member::isAdmin(), isSummitAdmin(), isRegistrationAdmin(), isTester(), isTrackChairAdmin() and
 * isOnGroup() answer from the request-scoped $groupMembershipCache after the first lookup per
 * group code, instead of running one membership query per call. The queries are counted straight
 * off the connection, the only way to tell "queried once" from "queried N times, same result".
 *
 * @package Tests\Unit\Models
 */
final class MemberGroupMembershipCacheTest extends TestCase
{
    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        // self::$member is on TrackChairs (not an admin), self::$member2 on SummitAdministrators
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$em->clear();
    }

    protected function tearDown(): void
    {
        self::clearMemberTestData();
        parent::tearDown();
    }

    private function loadMember(int $id): Member
    {
        return self::$em->find(Member::class, $id);
    }

    /**
     * Runs $fn and returns how many membership queries (Group_Members) it issued.
     */
    private function countMembershipQueries(callable $fn): int
    {
        $captured_sql = [];
        $logger = new class($captured_sql) implements SQLLogger {
            private array $sink;

            public function __construct(array &$sink)
            {
                $this->sink = &$sink;
            }

            public function startQuery($sql, ?array $params = null, ?array $types = null)
            {
                $this->sink[] = $sql;
            }

            public function stopQuery()
            {
            }
        };

        $connection = Registry::getManager(SilverstripeBaseModel::EntityManager)->getConnection();
        $previous_logger = $connection->getConfiguration()->getSQLLogger();
        $connection->getConfiguration()->setSQLLogger($logger);

        try {
            $fn();
        } finally {
            $connection->getConfiguration()->setSQLLogger($previous_logger);
        }

        return count(array_filter($captured_sql, fn($sql) => stripos($sql, 'Group_Members') !== false));
    }

    public function testRepeatedIsAdminOnNonAdminIssuesAtMostTwoQueries(): void
    {
        $member = $this->loadMember(self::$member->getId());

        $queries = $this->countMembershipQueries(function () use ($member) {
            for ($i = 0; $i < 10; $i++) {
                $this->assertFalse($member->isAdmin(true));
            }
        });

        // SuperAdmins + Administrators, once each
        $this->assertSame(2, $queries);
    }

    public function testIsOnGroupIssuesAtMostThreeQueries(): void
    {
        $member = $this->loadMember(self::$member->getId());

        $queries = $this->countMembershipQueries(function () use ($member) {
            for ($i = 0; $i < 10; $i++) {
                $this->assertTrue($member->isOnGroup(IGroup::TrackChairs, true));
            }
        });

        // SuperAdmins + Administrators + TrackChairs
        $this->assertSame(3, $queries);
    }

    public function testIsOnGroupForAdminShortCircuitsWithoutSecondLookup(): void
    {
        // setUp cleared the unit of work: re-attach the fixtures before swapping the member's group
        self::$group = self::$em->find(Group::class, self::$group->getId());
        self::$member = self::$em->find(Member::class, self::$member->getId());
        self::setMemberDefaultGroup(IGroup::Administrators);
        self::$em->clear();
        $member = $this->loadMember(self::$member->getId());

        $queries = $this->countMembershipQueries(function () use ($member) {
            $this->assertTrue($member->isOnGroup(IGroup::Testers, true));
        });

        // SuperAdmins (miss) + Administrators (hit); the requested group is never looked up
        $this->assertSame(2, $queries);
    }

    public function testMemberInGroupAndNotInGroup(): void
    {
        $member = $this->loadMember(self::$member->getId());
        $summit_admin = $this->loadMember(self::$member2->getId());

        $this->assertFalse($member->isSummitAdmin());
        $this->assertFalse($member->isRegistrationAdmin());
        $this->assertFalse($member->isTester());
        $this->assertFalse($member->isTrackChairAdmin());
        $this->assertTrue($summit_admin->isSummitAdmin());
        $this->assertFalse($summit_admin->isAdmin(true));
    }

    public function testGroupByCodeAnswersNegativeFromCache(): void
    {
        $member = $this->loadMember(self::$member->getId());
        $this->assertFalse($member->belongsToGroup(IGroup::Testers));

        $queries = $this->countMembershipQueries(function () use ($member) {
            $this->assertNull($member->getGroupByCode(IGroup::Testers));
        });

        $this->assertSame(0, $queries);
    }

    public function testGroupByCodeFillsTheCache(): void
    {
        $member = $this->loadMember(self::$member->getId());

        $group = $member->getGroupByCode(IGroup::TrackChairs);
        $this->assertNotNull($group);
        $this->assertSame(IGroup::TrackChairs, $group->getCode());

        $queries = $this->countMembershipQueries(function () use ($member) {
            $this->assertTrue($member->belongsToGroup(IGroup::TrackChairs));
        });

        $this->assertSame(0, $queries);
    }

    public function testUnflushedGroupMutationsAreVisibleToGroupChecks(): void
    {
        $member = $this->loadMember(self::$member->getId());
        $this->assertFalse($member->belongsToGroup(IGroup::Testers));

        $group = new Group();
        $group->setCode(IGroup::Testers);
        $group->setTitle(IGroup::Testers);
        self::$em->persist($group);

        // not flushed: the raw SQL lookup alone could not see this membership
        $member->add2Group($group);
        $this->assertTrue($member->isOnGroup(IGroup::Testers, true));

        $member->removeFromGroup($group);
        $this->assertFalse($member->belongsToGroup(IGroup::Testers));
    }
}
