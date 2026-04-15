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
    // 6. Member::addSponsorPermission — concurrency
    // -------------------------------------------------------------------------

    /**
     * Concurrent calls to addSponsorPermission for the same (member, sponsor, slug)
     * must not introduce duplicate entries in the Permissions JSON array.
     * The SELECT … FOR UPDATE row lock serialises the writers so that the
     * second caller reads the committed value and IF(JSON_CONTAINS(…)) is a no-op.
     */
    public function testConcurrentAddSponsorPermissionProducesNoDuplicates(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl_fork() is not available in this environment');
        }

        $sponsor_id  = self::$sponsors[0]->getId();
        $member_id   = self::$member->getId();
        $concurrency = 5;

        // Flush and disconnect the parent before forking so children each
        // get a clean connection — inherited sockets are not fork-safe.
        self::$em->flush();
        self::$em->clear();
        self::$em->getConnection()->close();

        $pids = [];
        for ($i = 0; $i < $concurrency; $i++) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                $this->fail('pcntl_fork() failed');
            }
            if ($pid === 0) {
                // Child: DBAL auto-reconnects on the first query after close().
                try {
                    $conn = self::$em->getConnection();
                    $conn->beginTransaction();
                    $member = self::$member_repository->find($member_id);
                    $member->addSponsorPermission($sponsor_id, IGroup::Sponsors);
                    $conn->commit();
                    exit(0);
                } catch (\Throwable $e) {
                    exit(1);
                }
            }
            $pids[] = $pid;
        }

        // Parent: wait for all children and collect exit codes.
        $failed = 0;
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
            if (pcntl_wexitstatus($status) !== 0) {
                $failed++;
            }
        }

        // Reconnect the parent for the assertion query.
        self::$em->getConnection()->close();

        $raw = self::$em->getConnection()->executeQuery(
            'SELECT Permissions FROM Sponsor_Users WHERE SponsorID = ? AND MemberID = ?',
            [$sponsor_id, $member_id]
        )->fetchOne();

        $permissions = json_decode($raw, true) ?? [];
        $occurrences = array_filter($permissions, fn($p) => $p === IGroup::Sponsors);

        $this->assertSame(0, $failed, 'One or more concurrent workers exited with an error.');
        $this->assertCount(
            1,
            $occurrences,
            'Concurrent addSponsorPermission calls must not produce duplicate slugs in Permissions.'
        );
    }

    // -------------------------------------------------------------------------
    // 7. Member::removeSponsorPermission — concurrency
    // -------------------------------------------------------------------------

    /**
     * Concurrent calls to removeSponsorPermission for the same (member, sponsor, slug)
     * must leave the slug completely absent from the Permissions JSON array.
     * The pre-loaded Permissions intentionally contains duplicate slugs to verify
     * that the JSON_ARRAYAGG-based remove eliminates all occurrences in one shot
     * and that concurrent workers do not leave stale entries behind.
     */
    public function testConcurrentRemoveSponsorPermissionLeavesNoStaleEntries(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl_fork() is not available in this environment');
        }

        $sponsor_id  = self::$sponsors[0]->getId();
        $member_id   = self::$member->getId();
        $concurrency = 5;

        // Seed Permissions with duplicate slugs to exercise the remove-all path.
        self::$em->getConnection()->executeStatement(
            'UPDATE Sponsor_Users SET Permissions = ? WHERE SponsorID = ? AND MemberID = ?',
            [
                json_encode([IGroup::Sponsors, IGroup::Sponsors, IGroup::Sponsors]),
                $sponsor_id,
                $member_id,
            ]
        );

        self::$em->flush();
        self::$em->clear();
        self::$em->getConnection()->close();

        $pids = [];
        for ($i = 0; $i < $concurrency; $i++) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                $this->fail('pcntl_fork() failed');
            }
            if ($pid === 0) {
                try {
                    $conn = self::$em->getConnection();
                    $conn->beginTransaction();
                    $member = self::$member_repository->find($member_id);
                    $member->removeSponsorPermission($sponsor_id, IGroup::Sponsors);
                    $conn->commit();
                    exit(0);
                } catch (\Throwable $e) {
                    exit(1);
                }
            }
            $pids[] = $pid;
        }

        $failed = 0;
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
            if (pcntl_wexitstatus($status) !== 0) {
                $failed++;
            }
        }

        self::$em->getConnection()->close();

        $raw = self::$em->getConnection()->executeQuery(
            'SELECT Permissions FROM Sponsor_Users WHERE SponsorID = ? AND MemberID = ?',
            [$sponsor_id, $member_id]
        )->fetchOne();

        $permissions = json_decode($raw, true) ?? [];
        $occurrences = array_filter($permissions, fn($p) => $p === IGroup::Sponsors);

        $this->assertSame(0, $failed, 'One or more concurrent workers exited with an error.');
        $this->assertCount(
            0,
            $occurrences,
            'Concurrent removeSponsorPermission calls must leave no stale slug occurrences in Permissions.'
        );
    }

    // -------------------------------------------------------------------------
    // 8. Sponsor::addUser — multi-sponsor membership
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

    // -------------------------------------------------------------------------
    // 9. Member::addSponsorPermission — retry after eager row creation
    // -------------------------------------------------------------------------

    public function testAddSponsorPermissionReturnsOneWhenPermissionAlreadyPresent(): void
    {
        $sponsor_id = self::$sponsors[0]->getId();
        $member_id  = self::$member->getId();

        // Seed Permissions with the slug that will be re-added — this produces the
        // "no rows changed" MySQL response that previously caused the false 0 return.
        $this->setPermissions($sponsor_id, $member_id, IGroup::Sponsors);
        self::$em->clear();

        $member = self::$member_repository->find($member_id);
        $result = $member->addSponsorPermission($sponsor_id, IGroup::Sponsors);

        $this->assertSame(
            1,
            $result,
            'addSponsorPermission must return 1 when the row exists, even if the slug was already present ' .
            '(old code returned 0 here, triggering eager creation and ultimately a RuntimeException).'
        );
    }

    /**
     * End-to-end simulation of the eager-creation retry path in
     * SponsorUserSyncService::addSponsorUserToGroup.
     *
     * Sequence:
     *   1. No Sponsor_Users row → addSponsorPermission returns 0.
     *   2. The service creates the row via Sponsor::addUser (eager creation).
     *   3. Retry → addSponsorPermission returns 1 and writes the permission.
     *
     * Before the fix, step 3 returned 0 when the initial call also returned 0 because
     * the row already existed with the permission set, causing a RuntimeException.
     * This test ensures the retry succeeds whenever the row is present after creation.
     */
    public function testAddSponsorPermissionRetrySucceedsAfterEagerRowCreation(): void
    {
        $sponsor_id = self::$sponsors[0]->getId();
        $member_id  = self::$member->getId();

        // Remove the existing Sponsor_Users entry to simulate the race-condition scenario
        // where the group event arrives before the membership event.
        self::$em->getConnection()->executeStatement(
            'DELETE FROM Sponsor_Users WHERE SponsorID = ? AND MemberID = ?',
            [$sponsor_id, $member_id]
        );
        self::$em->clear();

        $member = self::$member_repository->find($member_id);

        // Step 1 — first call: row does not exist → must return 0.
        $firstResult = $member->addSponsorPermission($sponsor_id, IGroup::Sponsors);
        $this->assertSame(0, $firstResult, 'First call must return 0 when no Sponsor_Users row exists.');

        // Step 2 — eager creation: SponsorUserSyncService calls Sponsor::addUser to
        // insert the row, then flushes so the INSERT is visible within the transaction.
        self::$em->clear();
        $sponsor = self::$em->find(Sponsor::class, $sponsor_id);
        $member  = self::$member_repository->find($member_id);
        $sponsor->addUser($member);
        self::$em->flush();
        self::$em->clear();

        // Step 3 — retry: row now exists → must return 1 and write the permission.
        $member      = self::$member_repository->find($member_id);
        $retryResult = $member->addSponsorPermission($sponsor_id, IGroup::Sponsors);
        $this->assertSame(1, $retryResult, 'Retry must return 1 after the Sponsor_Users row has been created.');

        $raw = self::$em->getConnection()->executeQuery(
            'SELECT Permissions FROM Sponsor_Users WHERE SponsorID = ? AND MemberID = ?',
            [$sponsor_id, $member_id]
        )->fetchOne();

        $permissions = json_decode($raw, true) ?? [];
        $this->assertContains(
            IGroup::Sponsors,
            $permissions,
            'The Sponsors slug must be present in Permissions after a successful retry.'
        );
    }
}
