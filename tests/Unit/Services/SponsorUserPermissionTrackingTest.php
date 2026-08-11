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
     * Replaces the IDP user API with a mock returning $user_data (null = the
     * user does not exist at the IDP) and forces the services that may have
     * been resolved against the real API to rebuild.
     */
    private function mockExternalUserApi(?array $user_data): void
    {
        $api = \Mockery::mock(\App\Services\Apis\IExternalUserApi::class)
            ->shouldIgnoreMissing();
        $api->shouldReceive('getUserById')->andReturn($user_data);
        $this->app->instance(\App\Services\Apis\IExternalUserApi::class, $api);
        $this->app->forgetInstance(\App\Services\Model\IMemberService::class);
        $this->app->forgetInstance(\App\Services\Model\ISponsorUserSyncService::class);
    }

    /**
     * A failed tx_service transaction closes the entity manager and resets it in the
     * registry (see DoctrineTransactionService::transaction), leaving the static one
     * captured at setUp time unusable. Returns a usable manager either way.
     */
    private static function reopenEntityManager(): \Doctrine\ORM\EntityManagerInterface
    {
        if (!self::$em->isOpen()) {
            return \LaravelDoctrine\ORM\Facades\Registry::resetManager(
                \models\utils\SilverstripeBaseModel::EntityManager
            );
        }
        self::$em->clear();
        return self::$em;
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

    /**
     * Whether a Sponsor_Users row exists for the given (SponsorID, MemberID) pair,
     * regardless of its Permissions value.
     */
    private function hasSponsorUserRow(int $sponsor_id, int $member_id): bool
    {
        return (bool)self::$em->getConnection()->executeQuery(
            'SELECT 1 FROM Sponsor_Users WHERE SponsorID = ? AND MemberID = ?',
            [$sponsor_id, $member_id]
        )->fetchOne();
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
     * Brand-new sponsor user: the member exists but does NOT belong to any
     * sponsor group yet - the group grant is exactly what this event delivers.
     * The eager-create path must not fail Sponsor::addUser's group validation
     * (chicken-and-egg: the validation requires the group this handler grants).
     */
    public function testAddSponsorUserToGroupCreatesRowWhenMemberHasNoSponsorGroupYet(): void
    {
        // member2 belongs only to SummitAdministrators - no sponsor group,
        // exactly the state of a brand-new sponsor user's member row.
        $member_id   = self::$member2->getId();
        $external_id = self::$member2->getUserExternalId();
        $sponsor_id  = self::$sponsors[1]->getId(); // no Sponsor_Users row
        $summit_id   = self::$summit->getId();

        // Pre-condition: the member must NOT belong to any sponsor group.
        $this->assertFalse(
            self::$member_repository->find($member_id)->belongsToGroup(IGroup::Sponsors),
            'Pre-condition: member should not belong to the sponsors group'
        );

        $this->getService()->addSponsorUserToGroup(
            $external_id,
            IGroup::Sponsors,
            $sponsor_id,
            $summit_id
        );

        // Row created + permission written...
        $this->assertContains(IGroup::Sponsors, $this->getPermissions($sponsor_id, $member_id));

        // ...and the member ended up in the global group.
        self::$em->clear();
        $this->assertTrue(
            self::$member_repository->find($member_id)->belongsToGroup(IGroup::Sponsors)
        );
    }

    /**
     * The MQ payload's group_slug is attacker/producer-controlled input: the
     * shared broker vhost grants write access to several services, so a forged
     * or buggy auth_user_added_to_group with e.g. 'administrators' must never
     * be granted. Only Sponsor::AllowedMemberGroups may flow through this sync.
     */
    public function testAddSponsorUserToGroupRejectsNonSponsorGroup(): void
    {
        $this->expectException(\models\exceptions\ValidationException::class);

        $this->getService()->addSponsorUserToGroup(
            self::$member->getUserExternalId(),
            IGroup::Administrators,
            self::$sponsors[0]->getId(),
            self::$summit->getId()
        );
    }

    /**
     * Same contract on the removal path: a forged removal event must not be
     * able to strip a member from an arbitrary group like 'administrators'.
     */
    public function testRemoveSponsorUserFromGroupRejectsNonSponsorGroup(): void
    {
        $this->expectException(\models\exceptions\ValidationException::class);

        $this->getService()->removeSponsorUserFromGroup(
            self::$member->getUserExternalId(),
            IGroup::Administrators,
            self::$sponsors[0]->getId(),
            self::$summit->getId()
        );
    }

    /**
     * Builds a second sponsor belonging to summit2 (a DIFFERENT summit than the
     * event summit used by these tests). Caller must clean it up with
     * deleteCrossSummitSponsor() in a finally block.
     */
    private function createCrossSummitSponsor(): \models\summit\Sponsor
    {
        $summit2 = self::$summit_repository->getById(self::$summit2->getId());
        $company = self::$em->find(\models\main\Company::class, self::$companies[1]->getId());

        $other_sponsor = new \models\summit\Sponsor();
        $other_sponsor->setCompany($company);
        $summit2->addSummitSponsor($other_sponsor);
        self::$em->persist($other_sponsor);
        self::$em->flush();

        return $other_sponsor;
    }

    /**
     * Removes the cross-summit sponsor THROUGH the EntityManager. A raw-SQL
     * delete would leave the managed entity dangling in the unit of work,
     * referencing a Summit the teardown is about to remove - the next flush
     * then fails with "new entity found through relationship" and the
     * member/group fixtures leak into the shared test database.
     */
    private function deleteCrossSummitSponsor(\models\summit\Sponsor $sponsor): void
    {
        $managed = self::$em->find(\models\summit\Sponsor::class, $sponsor->getId());
        if (!is_null($managed)) {
            self::$em->remove($managed); // owning side: Sponsor_Users rows go with it
            self::$em->flush();
        }
    }

    /**
     * The producer derives sponsor_id and summit_id from the same AccessRight,
     * so a mismatched pair is a forged or buggy event. When the member already
     * holds a Sponsor_Users row on the foreign sponsor, the permission must NOT
     * be written onto another summit's sponsor row.
     */
    public function testAddSponsorUserToGroupRejectsSponsorFromAnotherSummit(): void
    {
        $member = self::$member_repository->find(self::$member->getId());
        $other_sponsor = $this->createCrossSummitSponsor();
        $other_sponsor->addUser($member); // row on the FOREIGN sponsor exists
        self::$em->flush();

        $member_id        = $member->getId();
        $external_id      = $member->getUserExternalId();
        $other_sponsor_id = $other_sponsor->getId();

        try {
            $thrown = null;
            try {
                // Event claims the FIRST summit but carries summit2's sponsor.
                $this->getService()->addSponsorUserToGroup(
                    $external_id,
                    IGroup::Sponsors,
                    $other_sponsor_id,
                    self::$summit->getId()
                );
            } catch (\models\exceptions\ValidationException $ex) {
                $thrown = $ex;
            }

            $this->assertNotNull($thrown, 'a sponsor from another summit must be rejected');
            $this->assertEmpty(
                $this->getPermissions($other_sponsor_id, $member_id),
                'no permission may be written onto another summit\'s sponsor row'
            );
        } finally {
            $this->deleteCrossSummitSponsor($other_sponsor);
        }
    }

    /**
     * The ownership gate must run BEFORE resolveMember: a rejected event must
     * not provision a member from the IDP as a side effect.
     */
    public function testAddSponsorUserToGroupRejectedCrossSummitEventDoesNotProvisionMember(): void
    {
        $other_sponsor    = $this->createCrossSummitSponsor();
        $other_sponsor_id = $other_sponsor->getId();
        $external_id      = mt_rand(1500000000, 2000000000); // no local Member row
        $email            = sprintf("smarcet+xsummit_%s@gmail.com", str_random(8));

        // The user DOES exist at the IDP - on-demand registration would succeed.
        $this->mockExternalUserApi([
            'id'             => $external_id,
            'email'          => $email,
            'first_name'     => 'Cross',
            'last_name'      => 'Summit',
            'bio'            => '',
            'active'         => true,
            'email_verified' => true,
            'groups'         => [],
            'public_profile_show_photo'            => false,
            'public_profile_show_fullname'         => false,
            'public_profile_show_email'            => false,
            'public_profile_show_telephone_number' => false,
            'public_profile_show_bio'              => false,
            'public_profile_show_social_media_info' => false,
            'public_profile_allow_chat_with_me'    => false,
        ]);

        try {
            $thrown = null;
            try {
                $this->getService()->addSponsorUserToGroup(
                    $external_id,
                    IGroup::Sponsors,
                    $other_sponsor_id,
                    self::$summit->getId()
                );
            } catch (\models\exceptions\ValidationException $ex) {
                $thrown = $ex;
            }

            $this->assertNotNull($thrown, 'a sponsor from another summit must be rejected');
            $this->assertNull(
                self::$member_repository->getByExternalId($external_id),
                'a rejected event must not provision a member from the IDP'
            );
        } finally {
            $this->deleteCrossSummitSponsor($other_sponsor);
            $leftover = self::$member_repository->getByExternalId($external_id);
            if (!is_null($leftover)) {
                self::$em->remove($leftover);
                self::$em->flush();
            }
        }
    }

    /**
     * A removal event carrying a sponsor of ANOTHER summit must be a no-op: it
     * must neither touch the foreign sponsor's permission entry nor strip the
     * member's global group.
     */
    public function testRemoveSponsorUserFromGroupSkipsSponsorFromAnotherSummit(): void
    {
        $member = self::$member_repository->find(self::$member->getId());
        $other_sponsor = $this->createCrossSummitSponsor();
        $other_sponsor->addUser($member);
        self::$em->flush();

        $member_id        = $member->getId();
        $external_id      = $member->getUserExternalId();
        $other_sponsor_id = $other_sponsor->getId();

        try {
            // Legit grant on summit2 writes the permission on the foreign row.
            $this->getService()->addSponsorUserToGroup(
                $external_id, IGroup::Sponsors, $other_sponsor_id, self::$summit2->getId());
            $this->assertContains(IGroup::Sponsors, $this->getPermissions($other_sponsor_id, $member_id));

            // Forged/buggy removal: summit1's event carrying summit2's sponsor.
            $this->getService()->removeSponsorUserFromGroup(
                $external_id, IGroup::Sponsors, $other_sponsor_id, self::$summit->getId());

            self::$em->clear();
            $this->assertContains(
                IGroup::Sponsors,
                $this->getPermissions($other_sponsor_id, $member_id),
                'a cross-summit removal must not touch the foreign sponsor\'s permission entry'
            );
            $this->assertTrue(
                self::$member_repository->find($member_id)->belongsToGroup(IGroup::Sponsors),
                'a cross-summit removal must not strip the global group'
            );
        } finally {
            $this->deleteCrossSummitSponsor($other_sponsor);
        }
    }

    /**
     * A sponsor deleted ENTIRELY must not skip the removal: its Sponsor_Users
     * rows are gone, and running the removal is exactly what recomputes the
     * remaining permission count and strips the global sponsors group when this
     * was the member's last sponsor. Skipping would leave the member with
     * residual show-admin access forever.
     */
    public function testRemoveSponsorUserFromGroupStillCleansUpWhenSponsorWasDeleted(): void
    {
        $member_id   = self::$member->getId();
        $external_id = self::$member->getUserExternalId();

        // Pre-condition: member holds the global group and NO remaining
        // permission entries (the fixture row has a NULL Permissions column).
        $this->assertTrue(
            self::$member_repository->find($member_id)->belongsToGroup(IGroup::Sponsors)
        );

        $this->getService()->removeSponsorUserFromGroup(
            $external_id,
            IGroup::Sponsors,
            PHP_INT_MAX, // sponsor no longer exists anywhere
            self::$summit->getId()
        );

        self::$em->clear();
        $this->assertFalse(
            self::$member_repository->find($member_id)->belongsToGroup(IGroup::Sponsors),
            'the last-sponsor cleanup must still strip the global group when the sponsor row is gone'
        );
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
    // addSponsorUser (membership event)
    // -------------------------------------------------------------------------

    /**
     * When the member does not exist yet in summit-api (brand-new IDP user whose
     * member row has not been synced), the failure must PROPAGATE so the MQ job's
     * retry/failed_jobs machinery applies - not be swallowed and lost silently.
     */
    public function testAddSponsorUserPropagatesErrorWhenMemberDoesNotExist(): void
    {
        // User does not exist locally NOR at the IDP.
        $this->mockExternalUserApi(null);

        $this->expectException(\models\exceptions\EntityNotFoundException::class);

        $this->getService()->addSponsorUser(
            self::$summit->getId(),
            self::$sponsors[1]->getId(),
            PHP_INT_MAX // external user id with no matching Member row
        );
    }

    /**
     * Brand-new IDP user whose Member row was never synced to summit-api
     * (the user never logged in): the sync must register the member on demand
     * from the IDP instead of failing - otherwise both MQ events exhaust their
     * retries against a missing member and the access grant is lost for good.
     */
    public function testAddSponsorUserToGroupRegistersMemberOnDemandWhenMissing(): void
    {
        $external_id = mt_rand(1500000000, 2000000000); // no local Member row
        $sponsor_id  = self::$sponsors[1]->getId();
        $summit_id   = self::$summit->getId();
        $email       = sprintf("smarcet+ondemand_%s@gmail.com", str_random(8));

        $this->mockExternalUserApi([
            'id'             => $external_id,
            'email'          => $email,
            'first_name'     => 'On',
            'last_name'      => 'Demand',
            'bio'            => '',
            'active'         => true,
            'email_verified' => true,
            'groups'         => [],
            'public_profile_show_photo'            => false,
            'public_profile_show_fullname'         => false,
            'public_profile_show_email'            => false,
            'public_profile_show_telephone_number' => false,
            'public_profile_show_bio'              => false,
            'public_profile_show_social_media_info' => false,
            'public_profile_allow_chat_with_me'    => false,
        ]);

        try {
            $this->getService()->addSponsorUserToGroup(
                $external_id,
                IGroup::Sponsors,
                $sponsor_id,
                $summit_id
            );

            // Member must have been registered on demand from the IDP...
            // (clear first: the in-service instance memoizes a pre-grant
            // belongsToGroup(false) in its groupMembershipCache)
            self::$em->clear();
            $member = self::$member_repository->getByExternalId($external_id);
            $this->assertNotNull($member, 'Member should have been registered on demand');

            // ...with the Sponsor_Users row + permission written and the group granted.
            $this->assertContains(IGroup::Sponsors, $this->getPermissions($sponsor_id, $member->getId()));
            $this->assertTrue($member->belongsToGroup(IGroup::Sponsors));
        } finally {
            // This member is created on demand, outside the trait's tearDown scope,
            // so it has to be removed here - and in a finally, or a failing assertion
            // above leaks it into the next test's database. Look it up again instead
            // of reusing $member: the failure may have happened before it was set.
            $leftover = self::$member_repository->getByExternalId($external_id);
            if (!is_null($leftover)) {
                self::$em->remove($leftover);
                self::$em->flush();
            }
        }
    }

    /**
     * On-demand registration must happen OUTSIDE addSponsorUserToGroup's transaction.
     *
     * registerExternalUserById dispatches NewMember / MemberDataUpdatedExternally, whose
     * listeners enqueue MemberAssocSummitOrders, UpdateAttendeeInfo and CleanMemberCacheJob.
     * Those pushes are NOT deferred to commit: afterCommit only works for transactions that
     * Laravel's DatabaseTransactionsManager can see, and this service opens its transaction
     * straight on the Doctrine DBAL connection. So if the member were registered inside the
     * transaction and the transaction later rolled back, the Member row would vanish while
     * the already-queued jobs kept pointing at its id - they would fail forever.
     *
     * Here the group row for an ALLOWED slug does not exist, so the transaction
     * throws AFTER the member was resolved (the allowlist and sponsor-ownership
     * gates both pass, and the missing Group row is only discovered inside the
     * transaction). The member must still be present afterwards.
     */
    public function testAddSponsorUserToGroupKeepsOnDemandMemberWhenTransactionFails(): void
    {
        $external_id = mt_rand(1500000000, 2000000000); // no local Member row
        $sponsor_id  = self::$sponsors[1]->getId(); // real sponsor: passes the ownership gate
        $summit_id   = self::$summit->getId();
        $email       = sprintf("smarcet+rollback_%s@gmail.com", str_random(8));

        // Ensure no Group row exists for the allowed slug used below, so the
        // in-transaction getBySlug lookup fails AFTER resolveMember succeeded.
        // (Fixtures create their own groups per test, so deleting is safe.)
        $conn = self::$em->getConnection();
        $conn->executeStatement(
            'DELETE gm FROM Group_Members gm INNER JOIN `Group` g ON g.ID = gm.GroupID WHERE g.Code = ?',
            [IGroup::SponsorExternalUsers]
        );
        $conn->executeStatement('DELETE FROM `Group` WHERE Code = ?', [IGroup::SponsorExternalUsers]);

        $this->mockExternalUserApi([
            'id'             => $external_id,
            'email'          => $email,
            'first_name'     => 'Roll',
            'last_name'      => 'Back',
            'bio'            => '',
            'active'         => true,
            'email_verified' => true,
            'groups'         => [],
            'public_profile_show_photo'            => false,
            'public_profile_show_fullname'         => false,
            'public_profile_show_email'            => false,
            'public_profile_show_telephone_number' => false,
            'public_profile_show_bio'              => false,
            'public_profile_show_social_media_info' => false,
            'public_profile_allow_chat_with_me'    => false,
        ]);

        try {
            $thrown = null;
            try {
                $this->getService()->addSponsorUserToGroup(
                    $external_id,
                    IGroup::SponsorExternalUsers, // allowed slug with no Group row: throws inside the tx
                    $sponsor_id,
                    $summit_id
                );
            } catch (\models\exceptions\EntityNotFoundException $ex) {
                $thrown = $ex;
            }

            $this->assertNotNull($thrown, 'The missing group row should have failed the transaction');

            // The on-demand member was committed by its own transaction, so the jobs
            // already dispatched for it reference a row that exists.
            self::$em = self::reopenEntityManager();
            $this->assertNotNull(
                self::$member_repository->getByExternalId($external_id),
                'On-demand member must survive the rolled back outer transaction'
            );
        } finally {
            self::$em = self::reopenEntityManager();
            $leftover = self::$member_repository->getByExternalId($external_id);
            if (!is_null($leftover)) {
                self::$em->remove($leftover);
                self::$em->flush();
            }
        }
    }

    /**
     * removeSponsorUser with a null sponsor_id (the auth_user_removed_from_summit
     * event, which carries no sponsor_id) must revoke ONLY the memberships that
     * belong to the summit the event is about.
     *
     * Member::getSponsorMemberships() is a plain ManyToMany to Sponsor with no
     * summit scoping, so it also returns sponsors of other summits. Those do not
     * resolve against $summit and make SummitSponsorService::removeSponsorUser
     * throw "Sponsor not found.", aborting the loop and leaving the memberships
     * of the event's own summit un-revoked.
     */
    public function testRemoveSponsorUserWithoutSponsorIdOnlyRevokesTheEventSummit(): void
    {
        // setUp() ends with an em->clear(), so the static fixture entities are
        // detached: re-fetch them or Doctrine treats them as new on persist.
        $member  = self::$member_repository->find(self::$member->getId());
        $summit2 = self::$summit_repository->getById(self::$summit2->getId());
        $company = self::$em->find(\models\main\Company::class, self::$companies[1]->getId());

        // A second sponsor, belonging to a DIFFERENT summit, with the same member.
        $other_sponsor = new \models\summit\Sponsor();
        $other_sponsor->setCompany($company);
        $summit2->addSummitSponsor($other_sponsor);
        $other_sponsor->addUser($member);
        self::$em->persist($other_sponsor);
        self::$em->flush();

        $member_id        = $member->getId();
        $external_id      = $member->getUserExternalId();
        $event_sponsor_id = self::$sponsors[0]->getId(); // belongs to self::$summit
        $other_sponsor_id = $other_sponsor->getId();     // belongs to self::$summit2

        // Pre-condition: the member holds a membership in BOTH summits.
        $this->assertTrue($this->hasSponsorUserRow($event_sponsor_id, $member_id));
        $this->assertTrue($this->hasSponsorUserRow($other_sponsor_id, $member_id));

        try {
            // auth_user_removed_from_summit: no sponsor_id, only the summit.
            $this->getService()->removeSponsorUser(
                self::$summit->getId(),
                $external_id,
                null
            );

            self::$em->clear();

            $this->assertFalse(
                $this->hasSponsorUserRow($event_sponsor_id, $member_id),
                'the membership of the event summit must have been revoked'
            );
            $this->assertTrue(
                $this->hasSponsorUserRow($other_sponsor_id, $member_id),
                'the membership of an unrelated summit must be left untouched'
            );
        } finally {
            $conn = self::$em->getConnection();
            $conn->executeStatement('DELETE FROM Sponsor_Users WHERE SponsorID = ?', [$other_sponsor_id]);
            $conn->executeStatement('DELETE FROM Sponsor WHERE ID = ?', [$other_sponsor_id]);
        }
    }

    /**
     * The membership event (auth_user_added_to_sponsor_and_summit) is emitted by
     * sponsor-users-api WITHOUT any companion group event - see _import_user and
     * _notify_approval, neither of which publishes auth_user_added_to_group. So
     * nothing downstream repairs a failure here: if Sponsor::addUser rejects the
     * member for not belonging to an allowed sponsor group, the Sponsor_Users row
     * is never created and the access is lost for good.
     *
     * The producer does add the group at the IDP before publishing, so the IDP is
     * already correct; it is the local copy that is stale. A member that does not
     * exist yet is covered by resolveMember's on-demand registration - this covers
     * the member that DOES exist locally with outdated groups.
     */
    public function testAddSponsorUserRefreshesStaleGroupsFromIdp(): void
    {
        // member2 belongs only to SummitAdministrators - not an allowed sponsor group.
        $member_id   = self::$member2->getId();
        $external_id = self::$member2->getUserExternalId();
        $sponsor_id  = self::$sponsors[1]->getId(); // no Sponsor_Users row yet

        $this->assertFalse(
            self::$member_repository->find($member_id)->belongsToGroup(IGroup::Sponsors),
            'Pre-condition: member must not belong to an allowed sponsor group'
        );
        $this->assertFalse($this->hasSponsorUserRow($sponsor_id, $member_id));

        // The IDP already carries the group (the producer syncs it before publishing).
        $this->mockExternalUserApi([
            'id'             => $external_id,
            'email'          => self::$member2->getEmail(),
            'first_name'     => self::$member2->getFirstName(),
            'last_name'      => self::$member2->getLastName(),
            'bio'            => '',
            'active'         => true,
            'email_verified' => true,
            'groups'         => [IGroup::Sponsors],
            'public_profile_show_photo'            => false,
            'public_profile_show_fullname'         => false,
            'public_profile_show_email'            => false,
            'public_profile_show_telephone_number' => false,
            'public_profile_show_bio'              => false,
            'public_profile_show_social_media_info' => false,
            'public_profile_allow_chat_with_me'    => false,
        ]);

        $this->getService()->addSponsorUser(
            self::$summit->getId(),
            $sponsor_id,
            $external_id
        );

        self::$em->clear();
        $this->assertTrue(
            $this->hasSponsorUserRow($sponsor_id, $member_id),
            'the Sponsor_Users row must have been created after refreshing groups from the IDP'
        );

        $member = self::$member_repository->find($member_id);
        $this->assertTrue(
            $member->belongsToGroup(IGroup::Sponsors),
            'the sponsor group must have been refreshed from the IDP'
        );
        // The refresh must be ADDITIVE: this event only ever grants access, and
        // removals stay owned by the IDP's own user_updated flow. A full
        // authoritative re-sync here would strip locally-held groups absent
        // from the IDP payload (like this one) as a side effect.
        $this->assertTrue(
            $member->belongsToGroup(IGroup::SummitAdministrators),
            'unrelated local groups must not be stripped by the refresh'
        );
    }

    /**
     * A member that was never synced holds no Sponsor_Users row and no group
     * membership, so a revocation event for it has nothing to revoke: it is a
     * no-op, not a failure. Turning it into an exception would burn the job's
     * 3 tries and park a permanently unresolvable entry in failed_jobs.
     */
    public function testRemoveSponsorUserIsNoOpWhenMemberWasNeverSynced(): void
    {
        // User does not exist locally NOR at the IDP.
        $this->mockExternalUserApi(null);

        $this->getService()->removeSponsorUser(
            self::$summit->getId(),
            PHP_INT_MAX, // external user id with no matching Member row
            self::$sponsors[0]->getId()
        );

        $this->assertNull(self::$member_repository->getByExternalId(PHP_INT_MAX));
    }

    /**
     * Revocation must never PROVISION. When the member was never synced but the
     * user does still exist at the IDP, registering it on demand would create a
     * Member row, run a full synchronizeGroups and dispatch NewMember /
     * MemberDataUpdatedExternally jobs - all to then revoke nothing, since a
     * member that did not exist owns no Sponsor_Users row.
     */
    public function testRemoveSponsorUserDoesNotProvisionMemberFromIdp(): void
    {
        $external_id = mt_rand(1500000000, 2000000000); // no local Member row
        $email       = sprintf("smarcet+norevokeprov_%s@gmail.com", str_random(8));

        // The user DOES exist at the IDP - on-demand registration would succeed.
        $this->mockExternalUserApi([
            'id'             => $external_id,
            'email'          => $email,
            'first_name'     => 'No',
            'last_name'      => 'Provision',
            'bio'            => '',
            'active'         => true,
            'email_verified' => true,
            'groups'         => [],
            'public_profile_show_photo'            => false,
            'public_profile_show_fullname'         => false,
            'public_profile_show_email'            => false,
            'public_profile_show_telephone_number' => false,
            'public_profile_show_bio'              => false,
            'public_profile_show_social_media_info' => false,
            'public_profile_allow_chat_with_me'    => false,
        ]);

        try {
            $this->getService()->removeSponsorUser(
                self::$summit->getId(),
                $external_id,
                self::$sponsors[0]->getId()
            );

            self::$em->clear();
            $this->assertNull(
                self::$member_repository->getByExternalId($external_id),
                'a revocation event must not create a Member row'
            );
        } finally {
            $leftover = self::$member_repository->getByExternalId($external_id);
            if (!is_null($leftover)) {
                self::$em->remove($leftover);
                self::$em->flush();
            }
        }
    }

    /**
     * Same contract for the group-scoped revocation entry point.
     */
    public function testRemoveSponsorUserFromGroupDoesNotProvisionMemberFromIdp(): void
    {
        $external_id = mt_rand(1500000000, 2000000000); // no local Member row
        $email       = sprintf("smarcet+norevokegrp_%s@gmail.com", str_random(8));

        $this->mockExternalUserApi([
            'id'             => $external_id,
            'email'          => $email,
            'first_name'     => 'No',
            'last_name'      => 'Provision',
            'bio'            => '',
            'active'         => true,
            'email_verified' => true,
            'groups'         => [],
            'public_profile_show_photo'            => false,
            'public_profile_show_fullname'         => false,
            'public_profile_show_email'            => false,
            'public_profile_show_telephone_number' => false,
            'public_profile_show_bio'              => false,
            'public_profile_show_social_media_info' => false,
            'public_profile_allow_chat_with_me'    => false,
        ]);

        try {
            $this->getService()->removeSponsorUserFromGroup(
                $external_id,
                IGroup::Sponsors,
                self::$sponsors[0]->getId(),
                self::$summit->getId()
            );

            self::$em->clear();
            $this->assertNull(
                self::$member_repository->getByExternalId($external_id),
                'a group revocation event must not create a Member row'
            );
        } finally {
            $leftover = self::$member_repository->getByExternalId($external_id);
            if (!is_null($leftover)) {
                self::$em->remove($leftover);
                self::$em->flush();
            }
        }
    }

    /**
     * sponsor-users-api's metamodel reconciler emits removal events precisely
     * when the sponsor no longer exists here (it reaps the sponsors summit-api
     * stopped returning, routing them through remove_sponsor_show_permissions
     * so the domain events still fire). A missing sponsor is therefore
     * nothing-to-revoke - not a failure that burns the job's retries and parks
     * a permanently unresolvable entry in failed_jobs.
     */
    public function testRemoveSponsorUserIsNoOpWhenSponsorNoLongerExists(): void
    {
        $member_id  = self::$member->getId();
        $sponsor_id = self::$sponsors[0]->getId();

        // Pre-condition: the member holds a membership on this summit.
        $this->assertTrue($this->hasSponsorUserRow($sponsor_id, $member_id));

        // Removal event pointing at a sponsor that no longer exists on the summit.
        $this->getService()->removeSponsorUser(
            self::$summit->getId(),
            self::$member->getUserExternalId(),
            PHP_INT_MAX
        );

        // No exception, and the member's existing membership is untouched.
        $this->assertTrue($this->hasSponsorUserRow($sponsor_id, $member_id));
    }

    /**
     * Skipping an unknown member must not turn removeSponsorUser into a
     * swallow-everything handler: a genuine failure still has to propagate so
     * the MQ job's retry / failed_jobs machinery applies.
     */
    public function testRemoveSponsorUserPropagatesErrorWhenSummitDoesNotExist(): void
    {
        $this->expectException(\models\exceptions\EntityNotFoundException::class);

        $this->getService()->removeSponsorUser(
            PHP_INT_MAX, // no such summit
            self::$member->getUserExternalId(),
            self::$sponsors[0]->getId()
        );
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
