<?php namespace Tests;
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
use App\Permissions\IPermissionsManager;
use Illuminate\Support\Facades\App;
use models\exceptions\ValidationException;
use models\main\Member;
use models\main\SummitAdministratorPermissionGroup;
use models\summit\Summit;

/**
 * Members whose only group is summit-room-administrators must be able to join a
 * SummitAdministratorPermissionGroup, and get access only to the summits that group is scoped to.
 * self::$member belongs ONLY to IGroup::SummitRoomAdministrators (see setUp).
 * Class SummitRoomAdministratorPermissionGroupTest
 * @package Tests
 */
final class SummitRoomAdministratorPermissionGroupTest extends TestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    /**
     * @var SummitAdministratorPermissionGroup[]
     */
    private $permission_groups = [];

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::SummitRoomAdministrators);
        self::insertSummitTestData();
    }

    public function tearDown(): void
    {
        $repository = self::$em->getRepository(SummitAdministratorPermissionGroup::class);
        foreach ($this->permission_groups as $group) {
            $group = $repository->find($group->getId());
            if (!is_null($group))
                self::$em->remove($group);
        }
        self::$em->flush();
        $this->permission_groups = [];

        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    private function createPermissionGroupFor(Member $member, Summit $summit): SummitAdministratorPermissionGroup
    {
        $group = new SummitAdministratorPermissionGroup();
        $group->setTitle("TEST_ROOM_ADMINS_" . str_random(16));
        $group->addMember($member);
        $group->addSummit($summit);

        self::$em->persist($group);
        self::$em->flush();

        $this->permission_groups[] = $group;
        return $group;
    }

    public function testRoomAdministratorIsAValidGroup()
    {
        $this->assertTrue(SummitAdministratorPermissionGroup::isValidGroup(IGroup::SummitRoomAdministrators));
    }

    public function testRoomAdministratorCanJoinPermissionGroupAndOnlySeesItsSummit()
    {
        $this->assertFalse(self::$member->isAdmin());
        $this->assertFalse(self::$member->hasAllowedSummits());

        $group = $this->createPermissionGroupFor(self::$member, self::$summit);

        $this->assertEquals([self::$member->getId()], array_map('intval', $group->getMembersIds()));
        $this->assertEquals([self::$summit->getId()], array_map('intval', self::$member->getAllAllowedSummitsIds()));
        $this->assertTrue(self::$member->isSummitAllowed(self::$summit));
        $this->assertFalse(self::$member->isSummitAllowed(self::$summit2));
        $this->assertTrue(self::$member->hasPermissionForOnGroup(self::$summit, IGroup::SummitRoomAdministrators));
        $this->assertFalse(self::$member->hasPermissionForOnGroup(self::$summit2, IGroup::SummitRoomAdministrators));
    }

    public function testRoomAdministratorScopedToAnotherSummitDoesNotSeeIt()
    {
        $this->createPermissionGroupFor(self::$member, self::$summit2);

        $this->assertEquals([self::$summit2->getId()], array_map('intval', self::$member->getAllAllowedSummitsIds()));
        $this->assertFalse(self::$member->isSummitAllowed(self::$summit));
    }

    public function testMemberWithoutAValidGroupIsRejected()
    {
        $member = new Member();
        $member->setEmail(sprintf("test+%s@nodomain.com", str_random(10)));

        $group = new SummitAdministratorPermissionGroup();
        $this->assertFalse($group->canAddMember($member));

        $this->expectException(ValidationException::class);
        $group->addMember($member);
    }

    public function testRoomAdministratorCanOnlyEditEventOccupancy()
    {
        $permissions_manager = App::make(IPermissionsManager::class);

        $this->assertTrue($permissions_manager->canEditFields(self::$member, 'SummitEvent', ['occupancy' => 'FULL']));
        $this->assertFalse($permissions_manager->canEditFields(self::$member, 'SummitEvent', ['title' => 'NEW TITLE']));
        $this->assertFalse($permissions_manager->canEditFields(self::$member, 'SummitEvent', ['occupancy' => 'FULL', 'description' => 'NEW DESCRIPTION']));
    }
}
