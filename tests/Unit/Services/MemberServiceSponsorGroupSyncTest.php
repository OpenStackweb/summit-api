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
use App\Services\Model\IMemberService;
use Illuminate\Support\Facades\App;
use models\main\Group;
use Tests\InsertMemberTestData;
use Tests\InsertSummitTestData;
use Tests\TestCase;

/**
 * Class MemberServiceSponsorGroupSyncTest
 *
 * The IDP payload is the source of truth for the sponsor groups: on the full
 * (allow_removals = true) sync a sponsor group missing from the payload is removed
 * locally together with the matching Sponsor_Users access. The additive
 * (per-request, token claim) sync never removes either.
 *
 * @package Tests\Unit\Services
 */
class MemberServiceSponsorGroupSyncTest extends TestCase
{
    use InsertSummitTestData;
    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::Sponsors);
        self::insertSummitTestData();

        // Sponsor_Users row for sponsors[0], permission set by each test.
        self::$sponsors[0]->addUser(self::$member);

        self::$em->flush();
        self::$em->clear();
    }

    public function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    private function setPermissions(int $sponsor_id, int $member_id, array $slugs): void
    {
        self::$em->getConnection()->executeStatement(
            'UPDATE Sponsor_Users SET Permissions = ? WHERE SponsorID = ? AND MemberID = ?',
            [json_encode($slugs), $sponsor_id, $member_id]
        );
    }

    /**
     * @return array|null decoded Permissions of the row, null when the row does not exist
     */
    private function getPermissions(int $sponsor_id, int $member_id): ?array
    {
        $res = self::$em->getConnection()->fetchOne(
            'SELECT Permissions FROM Sponsor_Users WHERE SponsorID = ? AND MemberID = ?',
            [$sponsor_id, $member_id]
        );
        return $res === false ? null : json_decode($res ?? '[]', true);
    }

    private function sync(array $groups, bool $allow_removals): void
    {
        $member = self::$member_repository->find(self::$member->getId());
        App::make(IMemberService::class)->synchronizeGroups($member, $groups, $allow_removals);
        self::$em->clear();
    }

    public function testFullSyncWithoutSponsorsGroupRemovesLocalGroupAndSponsorUsersRow(): void
    {
        $member_id = self::$member->getId();
        $sponsor_id = self::$sponsors[0]->getId();
        $this->setPermissions($sponsor_id, $member_id, [IGroup::Sponsors]);

        $this->sync([], true);

        $member = self::$member_repository->find($member_id);
        $this->assertFalse($member->belongsToGroup(IGroup::Sponsors), "local sponsors group must be removed");
        $this->assertNull($this->getPermissions($sponsor_id, $member_id), "Sponsor_Users row must be removed");
    }

    public function testFullSyncKeepsSponsorsGroupAndSponsorUsersRowWhenPayloadStillHasIt(): void
    {
        $member_id = self::$member->getId();
        $sponsor_id = self::$sponsors[0]->getId();
        $this->setPermissions($sponsor_id, $member_id, [IGroup::Sponsors]);

        $this->sync([IGroup::Sponsors], true);

        $member = self::$member_repository->find($member_id);
        $this->assertTrue($member->belongsToGroup(IGroup::Sponsors));
        $this->assertEquals([IGroup::Sponsors], $this->getPermissions($sponsor_id, $member_id));
    }

    public function testAdditiveSyncNeverRemovesSponsorsGroupNorSponsorUsersRow(): void
    {
        $member_id = self::$member->getId();
        $sponsor_id = self::$sponsors[0]->getId();
        $this->setPermissions($sponsor_id, $member_id, [IGroup::Sponsors]);

        $this->sync([], false);

        $member = self::$member_repository->find($member_id);
        $this->assertTrue($member->belongsToGroup(IGroup::Sponsors));
        $this->assertEquals([IGroup::Sponsors], $this->getPermissions($sponsor_id, $member_id));
    }

    public function testFullSyncKeepsRowAndOtherSlugsWhenRowHoldsMoreThanTheRemovedGroup(): void
    {
        $member_id = self::$member->getId();
        $sponsor_id = self::$sponsors[0]->getId();
        $this->setPermissions($sponsor_id, $member_id, [IGroup::Sponsors, IGroup::SponsorServices]);

        $this->sync([], true);

        $member = self::$member_repository->find($member_id);
        $this->assertFalse($member->belongsToGroup(IGroup::Sponsors));
        $this->assertEquals(
            [IGroup::SponsorServices],
            $this->getPermissions($sponsor_id, $member_id),
            "only the removed slug must be stripped; the row keeps the other one"
        );
    }

    public function testFullSyncWithoutExternalUsersGroupRemovesLocalGroupAndSponsorUsersRow(): void
    {
        $member_id = self::$member->getId();
        $sponsor_id = self::$sponsors[0]->getId();

        $external = new Group();
        $external->setCode(IGroup::SponsorExternalUsers);
        $external->setTitle(IGroup::SponsorExternalUsers);
        $external->setDescription(IGroup::SponsorExternalUsers);
        $external->setExternal();
        self::$em->persist($external);
        $member = self::$member_repository->find($member_id);
        $member->add2Group($external);
        self::$em->flush();
        self::$em->clear();

        $this->setPermissions($sponsor_id, $member_id, [IGroup::SponsorExternalUsers]);

        try {
            // payload keeps sponsors (so the row's remaining access is judged on its own slug) but drops external users
            $this->sync([IGroup::Sponsors], true);

            $member = self::$member_repository->find($member_id);
            $this->assertTrue($member->belongsToGroup(IGroup::Sponsors));
            $this->assertFalse($member->belongsToGroup(IGroup::SponsorExternalUsers));
            $this->assertNull($this->getPermissions($sponsor_id, $member_id));
        } finally {
            $g = self::$em->getRepository(Group::class)->findOneBy(['code' => IGroup::SponsorExternalUsers]);
            if (!is_null($g)) {
                self::$em->remove($g);
                self::$em->flush();
            }
        }
    }
}
