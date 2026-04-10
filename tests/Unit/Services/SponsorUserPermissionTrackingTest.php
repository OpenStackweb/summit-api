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
use App\Services\Model\ISponsorUserSyncService;
use Tests\InsertMemberTestData;
use Tests\InsertSummitTestData;
use Tests\TestCase;

/**
 * Class SponsorUserPermissionTrackingTest
 *
 * Integration tests for per-sponsor permission tracking in the
 * Sponsor_Users.Permissions JSON column.
 *
 * Verifies that:
 * - addSponsorUserToGroup writes the group slug into the JSON column and
 *   adds the member to the global group when not already a member.
 * - Calling addSponsorUserToGroup twice does not create duplicate entries.
 * - removeSponsorUserFromGroup removes the entry from the JSON column and
 *   removes the member from the global group when no other sponsor still
 *   holds the permission.
 * - removeSponsorUserFromGroup removes the entry from the JSON column but
 *   retains the global group when another sponsor still holds the permission.
 *
 * @package Tests\Unit\Services
 */
class SponsorUserPermissionTrackingTest extends TestCase
{
    use InsertSummitTestData;
    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::Sponsors);
        self::insertSummitTestData();

        // Create the Sponsor_Users row for sponsors[0] so permission updates
        // have a row to target. Member is already in IGroup::Sponsors group.
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

    private function getService(): ISponsorUserSyncService
    {
        return app(ISponsorUserSyncService::class);
    }

    /**
     * Returns the decoded Permissions JSON array for a given (SponsorID, MemberID)
     * row in Sponsor_Users, or an empty array when the column is NULL.
     */
    private function getPermissions(int $sponsor_id, int $member_id): array
    {
        $conn = self::$em->getConnection();
        $raw = $conn->executeQuery(
            'SELECT Permissions FROM Sponsor_Users WHERE SponsorID = ? AND MemberID = ?',
            [$sponsor_id, $member_id]
        )->fetchOne();

        if (empty($raw)) {
            return [];
        }
        return json_decode($raw, true) ?? [];
    }

    // -------------------------------------------------------------------------
    // addSponsorUserToGroup
    // -------------------------------------------------------------------------

    /**
     * MQ race: the group event arrives before the membership event, so there is
     * no Sponsor_Users row yet when addSponsorUserToGroup is called.
     * The service must create the row eagerly, flush the UoW so the INSERT is
     * visible to the raw SQL retry, and then successfully write the permission.
     */
    public function testAddSponsorUserToGroupEagerlyCreatesRowAndWritesPermissionOnRetry(): void
    {
        // sponsors[1] has no Sponsor_Users row — the member is not yet a user
        // of this sponsor, simulating the race condition.
        $sponsor_id  = self::$sponsors[1]->getId();
        $member_id   = self::$member->getId();
        $external_id = self::$member->getUserExternalId();
        $summit_id   = self::$summit->getId();

        $conn = self::$em->getConnection();

        // Confirm no row exists before the call.
        $exists = $conn->executeQuery(
            'SELECT COUNT(*) FROM Sponsor_Users WHERE SponsorID = ? AND MemberID = ?',
            [$sponsor_id, $member_id]
        )->fetchOne();
        $this->assertEquals(0, (int)$exists, 'Pre-condition: no Sponsor_Users row should exist');

        $this->getService()->addSponsorUserToGroup($external_id, IGroup::Sponsors, $sponsor_id, $summit_id);

        // The row must have been created and the permission written.
        $this->assertContains(IGroup::Sponsors, $this->getPermissions($sponsor_id, $member_id));
    }

    /**
     * The group slug must be written into the Sponsor_Users.Permissions JSON
     * column for the correct (SponsorID, MemberID) row.
     */
    public function testAddSponsorUserToGroupWritesPermissionToJsonColumn(): void
    {
        $sponsor_id = self::$sponsors[0]->getId();
        $member_id  = self::$member->getId();

        $this->getService()->addSponsorUserToGroup(
            self::$member->getUserExternalId(),
            IGroup::Sponsors,
            $sponsor_id,
            self::$summit->getId()
        );

        $this->assertContains(IGroup::Sponsors, $this->getPermissions($sponsor_id, $member_id));
    }

    /**
     * Calling addSponsorUserToGroup twice for the same sponsor must not
     * produce duplicate entries in the JSON array.
     */
    public function testAddSponsorUserToGroupIsIdempotent(): void
    {
        $sponsor_id  = self::$sponsors[0]->getId();
        $member_id   = self::$member->getId();
        $external_id = self::$member->getUserExternalId();
        $summit_id   = self::$summit->getId();

        $service = $this->getService();
        $service->addSponsorUserToGroup($external_id, IGroup::Sponsors, $sponsor_id, $summit_id);
        $service->addSponsorUserToGroup($external_id, IGroup::Sponsors, $sponsor_id, $summit_id);

        $occurrences = array_filter(
            $this->getPermissions($sponsor_id, $member_id),
            fn($p) => $p === IGroup::Sponsors
        );
        $this->assertCount(1, $occurrences);
    }

    // -------------------------------------------------------------------------
    // removeSponsorUserFromGroup
    // -------------------------------------------------------------------------

    /**
     * When this is the last sponsor holding the permission, removing it must
     * clear the JSON entry and also remove the member from the global group.
     */
    public function testRemoveSponsorUserFromGroupRemovesGlobalGroupWhenLastSponsor(): void
    {
        $external_id = self::$member->getUserExternalId();
        $sponsor_id  = self::$sponsors[0]->getId();
        $member_id   = self::$member->getId();
        $summit_id   = self::$summit->getId();

        $service = $this->getService();

        // Write permission first so there is something to remove.
        $service->addSponsorUserToGroup($external_id, IGroup::Sponsors, $sponsor_id, $summit_id);
        $this->assertContains(IGroup::Sponsors, $this->getPermissions($sponsor_id, $member_id));

        // Doctrine ORM 3 EXTRA_LAZY PersistentCollection::removeElement() delegates to
        // parent::removeElement() on the in-memory ArrayCollection first. If the collection
        // is still uninitialized (addSponsorUserToGroup leaves it that way), that call
        // returns false and changed() is never called, so the flush issues no DELETE.
        // Force-initialize through the same model EM so removeFromGroup works correctly.
        self::$em->find(\models\main\Member::class, $member_id)->getGroups()->toArray();

        // Remove — no other sponsor holds the permission.
        $service->removeSponsorUserFromGroup($external_id, IGroup::Sponsors, $sponsor_id, $summit_id);

        // JSON entry must be gone.
        $this->assertNotContains(IGroup::Sponsors, $this->getPermissions($sponsor_id, $member_id));

        // Global group must have been removed too.
        self::$em->clear();
        $member = self::$member_repository->find($member_id);
        $this->assertFalse($member->belongsToGroup(IGroup::Sponsors));
    }

    /**
     * When another sponsor still holds the same permission, removing it for
     * one sponsor must only clear that sponsor's JSON entry — the member must
     * retain the global group.
     */
    public function testRemoveSponsorUserFromGroupRetainsGlobalGroupWhenAnotherSponsorHoldsPermission(): void
    {
        $external_id = self::$member->getUserExternalId();
        $sponsor0_id = self::$sponsors[0]->getId();
        $sponsor1_id = self::$sponsors[1]->getId();
        $member_id   = self::$member->getId();
        $summit_id   = self::$summit->getId();

        // Create a second Sponsor_Users row so sponsor1 can also hold the permission.
        // Inserted via raw SQL to bypass Sponsor::addUser's single-sponsor-per-summit guard,
        // which is a service-layer concern unrelated to permission tracking.
        self::$em->getConnection()->executeStatement(
            'INSERT INTO Sponsor_Users (SponsorID, MemberID) VALUES (?, ?)',
            [$sponsor1_id, $member_id]
        );

        $service = $this->getService();

        // Grant permission to both sponsors.
        $service->addSponsorUserToGroup($external_id, IGroup::Sponsors, $sponsor0_id, $summit_id);
        $service->addSponsorUserToGroup($external_id, IGroup::Sponsors, $sponsor1_id, $summit_id);

        // Same EXTRA_LAZY initialization as in the single-sponsor removal test.
        self::$em->find(\models\main\Member::class, $member_id)->getGroups()->toArray();

        // Remove permission only from sponsor0.
        $service->removeSponsorUserFromGroup($external_id, IGroup::Sponsors, $sponsor0_id, $summit_id);

        // sponsor0's JSON entry must be cleared.
        $this->assertNotContains(IGroup::Sponsors, $this->getPermissions($sponsor0_id, $member_id));

        // sponsor1's JSON entry must still be present.
        $this->assertContains(IGroup::Sponsors, $this->getPermissions($sponsor1_id, $member_id));

        // Global group must be retained because sponsor1 still holds the permission.
        self::$em->clear();
        $member = self::$member_repository->find($member_id);
        $this->assertTrue($member->belongsToGroup(IGroup::Sponsors));
    }
}
