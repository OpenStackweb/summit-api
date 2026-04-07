<?php namespace Tests\Unit\Entities;
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
use App\Models\Foundation\Main\Strategies\SponsorMemberSummitStrategy;
use models\summit\Sponsor;
use Tests\InsertMemberTestData;
use Tests\InsertSummitTestData;
use Tests\TestCase;

/**
 * Class SponsorPermissionTrackingTest
 *
 * Integration tests that verify all entity-level methods filter by the
 * Sponsor_Users.Permissions JSON column.
 *
 * Covered:
 * 1. Member::hasSponsorMembershipsFor — NULL Permissions → false; Sponsors slug → true; External slug → true
 * 2. Member::getSponsorMembershipIds — excludes rows without a matching Permissions entry
 * 3. SponsorMemberSummitStrategy::getAllAllowedSummitIds — filtered by Permissions
 * 4. SponsorMemberSummitStrategy::isSummitAllowed — filtered by Permissions
 * 5. Member::getActiveSummitsSponsorMemberships — excludes sponsors without Permissions entry
 * 6. Sponsor::addUser — member can belong to multiple sponsors within the same summit
 *
 * @package Tests\Unit\Entities
 */
class SponsorPermissionTrackingTest extends TestCase
{
    use InsertSummitTestData;
    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::Sponsors);
        self::insertSummitTestData();

        // Create a Sponsor_Users row for sponsors[0] with NULL Permissions.
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

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Sets Permissions to a JSON array containing a single slug for the given
     * (SponsorID, MemberID) row.
     */
    private function setPermissions(int $sponsor_id, int $member_id, string $slug): void
    {
        self::$em->getConnection()->executeStatement(
            'UPDATE Sponsor_Users SET Permissions = JSON_ARRAY(?) WHERE SponsorID = ? AND MemberID = ?',
            [$slug, $sponsor_id, $member_id]
        );
    }

    /**
     * Inserts a raw Sponsor_Users row bypassing entity validation, with NULL Permissions.
     */
    private function insertRawSponsorUser(int $sponsor_id, int $member_id): void
    {
        self::$em->getConnection()->executeStatement(
            'INSERT INTO Sponsor_Users (SponsorID, MemberID) VALUES (?, ?)',
            [$sponsor_id, $member_id]
        );
    }

    // -------------------------------------------------------------------------
    // 1. Member::hasSponsorMembershipsFor
    // -------------------------------------------------------------------------

    /**
     * With NULL Permissions the member is in Sponsor_Users but has not been
     * granted any group — hasSponsorMembershipsFor must return false.
     */
    public function testHasSponsorMembershipsForReturnsFalseWhenPermissionsNull(): void
    {
        $member = self::$member_repository->find(self::$member->getId());
        // Permissions column is NULL (set up that way in setUp via addUser).
        $this->assertFalse($member->hasSponsorMembershipsFor(self::$summit));
    }

    /**
     * With the Sponsors slug in Permissions, hasSponsorMembershipsFor must return true.
     */
    public function testHasSponsorMembershipsForReturnsTrueForSponsorsSlug(): void
    {
        $this->setPermissions(self::$sponsors[0]->getId(), self::$member->getId(), IGroup::Sponsors);

        $member = self::$member_repository->find(self::$member->getId());
        $this->assertTrue($member->hasSponsorMembershipsFor(self::$summit));
    }

    /**
     * With the SponsorExternalUsers slug in Permissions, hasSponsorMembershipsFor must return true.
     * The member is already in IGroup::Sponsors (set up via insertMemberTestData), so the
     * isSponsorUser() guard passes even though we are testing the external slug path in the SQL.
     */
    public function testHasSponsorMembershipsForReturnsTrueForExternalUsersSlug(): void
    {
        $this->setPermissions(self::$sponsors[0]->getId(), self::$member->getId(), IGroup::SponsorExternalUsers);

        $member = self::$member_repository->find(self::$member->getId());
        $this->assertTrue($member->hasSponsorMembershipsFor(self::$summit));
    }

    // -------------------------------------------------------------------------
    // 2. Member::getSponsorMembershipIds
    // -------------------------------------------------------------------------

    /**
     * Only sponsor IDs whose Permissions JSON contains a recognised slug
     * should be returned. A row with NULL Permissions must be excluded.
     */
    public function testGetSponsorMembershipIdsExcludesRowsWithoutPermission(): void
    {
        $member_id   = self::$member->getId();
        $sponsor0_id = self::$sponsors[0]->getId();
        $sponsor1_id = self::$sponsors[1]->getId();

        // Give sponsor0 a valid permission; sponsor1 has NULL Permissions.
        $this->setPermissions($sponsor0_id, $member_id, IGroup::Sponsors);
        $this->insertRawSponsorUser($sponsor1_id, $member_id);
        // sponsor1 deliberately left with NULL Permissions.

        $member = self::$member_repository->find($member_id);
        $ids = $member->getSponsorMembershipIds(self::$summit);

        $this->assertContains($sponsor0_id, $ids);
        $this->assertNotContains($sponsor1_id, $ids);
    }

    // -------------------------------------------------------------------------
    // 3 & 4. SponsorMemberSummitStrategy
    // -------------------------------------------------------------------------

    /**
     * getAllAllowedSummitIds must not include a summit when Permissions is NULL.
     */
    public function testGetAllAllowedSummitIdsExcludesWhenPermissionsNull(): void
    {
        $strategy = new SponsorMemberSummitStrategy(self::$member->getId());
        $ids = $strategy->getAllAllowedSummitIds();

        $this->assertNotContains(self::$summit->getId(), $ids);
    }

    /**
     * getAllAllowedSummitIds must include a summit when Permissions contains a recognised slug.
     */
    public function testGetAllAllowedSummitIdsIncludesWhenPermissionsSet(): void
    {
        $this->setPermissions(self::$sponsors[0]->getId(), self::$member->getId(), IGroup::Sponsors);

        $strategy = new SponsorMemberSummitStrategy(self::$member->getId());
        $ids = $strategy->getAllAllowedSummitIds();

        $this->assertContains(self::$summit->getId(), $ids);
    }

    /**
     * isSummitAllowed must return false when Permissions is NULL.
     */
    public function testIsSummitAllowedReturnsFalseWhenPermissionsNull(): void
    {
        $strategy = new SponsorMemberSummitStrategy(self::$member->getId());
        $this->assertFalse($strategy->isSummitAllowed(self::$summit));
    }

    /**
     * isSummitAllowed must return true when Permissions contains a recognised slug.
     */
    public function testIsSummitAllowedReturnsTrueWhenPermissionsSet(): void
    {
        $this->setPermissions(self::$sponsors[0]->getId(), self::$member->getId(), IGroup::Sponsors);

        $strategy = new SponsorMemberSummitStrategy(self::$member->getId());
        $this->assertTrue($strategy->isSummitAllowed(self::$summit));
    }

    // -------------------------------------------------------------------------
    // 5. Member::getActiveSummitsSponsorMemberships
    // -------------------------------------------------------------------------

    /**
     * A sponsor whose Permissions column is NULL must not appear in the result.
     */
    public function testGetActiveSummitsSponsorMembershipsExcludesWithoutPermission(): void
    {
        // Permissions is NULL after setUp — no permission granted.
        $member = self::$member_repository->find(self::$member->getId());
        $memberships = $member->getActiveSummitsSponsorMemberships();

        $ids = array_map(fn(Sponsor $s) => $s->getId(), $memberships);
        $this->assertNotContains(self::$sponsors[0]->getId(), $ids);
    }

    /**
     * A sponsor with a valid Permissions entry must appear in the result,
     * provided the summit has not ended.
     */
    public function testGetActiveSummitsSponsorMembershipsIncludesWithPermission(): void
    {
        $this->setPermissions(self::$sponsors[0]->getId(), self::$member->getId(), IGroup::Sponsors);

        $member = self::$member_repository->find(self::$member->getId());
        $memberships = $member->getActiveSummitsSponsorMemberships();

        $ids = array_map(fn(Sponsor $s) => $s->getId(), $memberships);
        $this->assertContains(self::$sponsors[0]->getId(), $ids);
    }

    // -------------------------------------------------------------------------
    // 6. Sponsor::addUser — multi-sponsor membership
    // -------------------------------------------------------------------------

    /**
     * A member may be added to more than one sponsor that belongs to the same
     * summit. The previous single-sponsor-per-summit restriction has been
     * removed and must not throw.
     */
    public function testAddUserAllowsMemberInMultipleSponsorsForSameSummit(): void
    {
        // sponsors[0] was already linked in setUp; link sponsors[1] to the same member.
        $sponsor1 = self::$em->find(Sponsor::class, self::$sponsors[1]->getId());
        $member   = self::$member_repository->find(self::$member->getId());

        $sponsor1->addUser($member);
        self::$em->flush();
        self::$em->clear();

        $sponsor1 = self::$em->find(Sponsor::class, self::$sponsors[1]->getId());
        $memberIds = array_map(fn($m) => $m->getId(), $sponsor1->getMembers()->toArray());
        $this->assertContains(self::$member->getId(), $memberIds);
    }
}
