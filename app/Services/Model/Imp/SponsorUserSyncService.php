<?php namespace App\Services\Model\Imp;
/**
 * Copyright 2025 OpenStack Foundation
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

use App\Services\Apis\IExternalUserApi;
use App\Services\Model\AbstractService;
use App\Services\Model\IMemberService;
use App\Services\Model\ISponsorUserSyncService;
use App\Services\Utils\Exceptions\UnacquiredLockException;
use App\Services\Utils\ILockManagerService;
use Illuminate\Support\Facades\Log;
use LaravelDoctrine\ORM\Facades\Registry;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IGroupRepository;
use models\main\IMemberRepository;
use models\main\Member;
use App\Models\Foundation\Summit\Repositories\ISponsorRepository;
use models\summit\ISummitRepository;
use models\summit\Sponsor;
use models\summit\Summit;
use models\utils\SilverstripeBaseModel;
use services\model\ISummitSponsorService;

/**
 * Class SponsorUserInfoGrantService
 * @package App\Services\Model\Imp
 */
final class SponsorUserSyncService
    extends AbstractService
    implements ISponsorUserSyncService
{
    private ISummitRepository $summit_repository;

    private IMemberRepository $member_repository;

    private IGroupRepository $group_repository;

    private ISummitSponsorService $summit_sponsor_service;

    private IMemberService $member_service;

    private IExternalUserApi $external_user_api;

    private ISponsorRepository $sponsor_repository;

    private ILockManagerService $lock_service;

    /**
     * Lifetime, in seconds, for the sponsor_user.*.ext_*.lock held around
     * addSponsorUser / addSponsorUserToGroup. Both paths resolve the member
     * INSIDE the lock, which for a brand-new user makes a synchronous IDP
     * call (registerExternalUserById) bounded by curl.timeout (60s default) -
     * so the lifetime must exceed that, or a slow IDP dissolves the lock right
     * as the competing MQ job's first retry lands (RetryBackoff starts at 30s).
     * It must also stay below the job's LAST retry (+150s with tries=3 and
     * backoff '30,120'), so a worker killed mid-callback leaves a lock that
     * expires before the final retry rather than burning it too.
     */
    private const SponsorUserLockLifetime = 120;

    /**
     * SponsorUserSyncService constructor.
     * @param ISummitRepository $summit_repository
     * @param IMemberRepository $member_repository
     * @param IGroupRepository $group_repository
     * @param ISummitSponsorService $summit_sponsor_service
     * @param IMemberService $member_service
     * @param IExternalUserApi $external_user_api
     * @param ISponsorRepository $sponsor_repository
     * @param ITransactionService $tx_service
     * @param ILockManagerService $lock_service
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        IMemberRepository $member_repository,
        IGroupRepository $group_repository,
        ISummitSponsorService $summit_sponsor_service,
        IMemberService $member_service,
        IExternalUserApi $external_user_api,
        ISponsorRepository $sponsor_repository,
        ITransactionService $tx_service,
        ILockManagerService $lock_service
    )
    {
        parent::__construct($tx_service);
        $this->summit_repository = $summit_repository;
        $this->member_repository = $member_repository;
        $this->group_repository = $group_repository;
        $this->summit_sponsor_service = $summit_sponsor_service;
        $this->member_service = $member_service;
        $this->external_user_api = $external_user_api;
        $this->sponsor_repository = $sponsor_repository;
        $this->lock_service = $lock_service;
    }

    /**
     * Lock key guarding a (sponsor, external user) pair against the two MQ
     * consumers (AddSponsorMemberMQJob / UpdateSponsorMemberGroupsMQJob) that
     * can both observe a missing Sponsor_Users row and both insert one.
     * Built from ids already present on both entry points' signatures - it
     * must NOT depend on the local MemberID, since resolving that is part of
     * what the lock protects.
     *
     * @param int $sponsor_id
     * @param int $user_id external (IDP) user id
     * @return string
     */
    private function sponsorUserLockKey(int $sponsor_id, int $user_id): string
    {
        return "sponsor_user.{$sponsor_id}.ext_{$user_id}.lock";
    }

    /**
     * Resolves the local Member for an IDP user id, registering it on demand
     * from the IDP when it was never synced (brand-new user that has not
     * logged in yet). Throws EntityNotFoundException when the user does not
     * exist at the IDP either.
     *
     * @param int $user_id external (IDP) user id
     * @return Member
     * @throws EntityNotFoundException
     */
    private function resolveMember(int $user_id): Member
    {
        $member = $this->member_repository->getByExternalId($user_id);
        if (!is_null($member)) return $member;

        Log::warning(
            "SponsorUserSyncService::resolveMember member with external id {$user_id} not found locally - registering on demand from IDP");

        return $this->member_service->registerExternalUserById($user_id);
    }

    /**
     * Looks up the local Member WITHOUT registering it on demand. Revocation
     * paths use this: a member that was never synced owns no Sponsor_Users row
     * and no group membership, so there is nothing to revoke and provisioning
     * one from the IDP would be a pure side effect (Member row, full
     * synchronizeGroups, NewMember / MemberDataUpdatedExternally jobs).
     *
     * @param int $user_id external (IDP) user id
     * @return Member|null null when the member was never synced
     */
    private function findMember(int $user_id): ?Member
    {
        return $this->member_repository->getByExternalId($user_id);
    }

    /**
     * Sponsor::addUser rejects a member that belongs to none of its
     * AllowedMemberGroups. sponsor-users-api grants that group at the IDP before
     * publishing the membership event (_sync_user_groups), but summit-api only
     * learns about it through the IDP's own user-updated event, which races this
     * one. resolveMember covers the member that does not exist yet - this covers
     * the member that exists with a stale local group set: re-read the groups
     * from the IDP, which is the source of truth, instead of failing.
     *
     * Nothing downstream would repair that failure: the producers of
     * auth_user_added_to_sponsor_and_summit (_import_user, _notify_approval) emit
     * no companion group event, so no eager-create path ever runs and the access
     * is lost once the job exhausts its tries.
     *
     * The sync is ADDITIVE (allow_removals = false) on purpose: this event only
     * ever GRANTS access, and removals stay owned by the IDP's own user_updated
     * flow (PublishUserUpdated). A full authoritative re-sync here would strip
     * locally-held groups absent from the IDP payload as a side effect of a
     * sponsor-membership event.
     *
     * @param Member $member
     * @param int $user_id external (IDP) user id
     * @return Member the same member, with its groups refreshed when stale
     * @throws \Exception
     */
    private function ensureSponsorGroupMembership(Member $member, int $user_id): Member
    {
        foreach (Sponsor::AllowedMemberGroups as $group_slug) {
            if ($member->belongsToGroup($group_slug)) return $member;
        }

        Log::warning(
            "SponsorUserSyncService::ensureSponsorGroupMembership member {$member->getId()} belongs to none of the allowed sponsor groups - refreshing groups from the IDP");

        $user_data = $this->external_user_api->getUserById($user_id);
        if (is_null($user_data)) {
            throw new EntityNotFoundException(
                "SponsorUserSyncService::ensureSponsorGroupMembership user {$user_id} does not exist at the IDP");
        }

        return $this->member_service->synchronizeGroups($member, $user_data['groups'] ?? [], false);
    }

    /**
     * The MQ payload's group_slug is producer-controlled input arriving over a
     * broker vhost several services can write to: without this gate a forged or
     * buggy auth_user_added_to_group / auth_user_removed_from_group event could
     * grant - or strip - membership of an arbitrary group like administrators.
     *
     * @param string $group_slug
     * @throws ValidationException
     */
    private function assertAllowedSponsorGroup(string $group_slug): void
    {
        if (!in_array($group_slug, Sponsor::AllowedMemberGroups, true)) {
            throw new ValidationException(
                sprintf(
                    "Group %s is not an allowed sponsor group (%s).",
                    $group_slug,
                    implode(', ', Sponsor::AllowedMemberGroups)
                )
            );
        }
    }

    /**
     * @param int $summit_id
     * @return Summit
     * @throws EntityNotFoundException
     */
    private function resolveSummit(int $summit_id): Summit
    {
        $summit = $this->summit_repository->getById($summit_id);
        if (!$summit instanceof Summit) {
            throw new EntityNotFoundException("Summit {$summit_id} not found");
        }
        return $summit;
    }

    /**
     * @param int $summit_id
     * @param int $user_id
     * @return array
     * @throws EntityNotFoundException
     */
    public function validateParams(int $summit_id, int $user_id): array
    {
        return array($this->resolveSummit($summit_id), $this->resolveMember($user_id));
    }

    /**
     * @inheritDoc
     * @throws UnacquiredLockException propagated on purpose - same as any
     * other failure here, it must burn one of the MQ job's retries rather
     * than be swallowed (see the note below).
     */
    public function addSponsorUser(int $summit_id, int $sponsor_id, int $user_id): void
    {
        // Do NOT swallow failures here: the MQ job (tries = 3) needs the
        // exception to apply its retry / failed_jobs machinery. A swallowed
        // failure loses the membership event silently. This also applies to
        // UnacquiredLockException from the lock below.
        Log::debug(
            "SponsorUserSyncService::addSponsorUser summit {$summit_id} sponsor {$sponsor_id} user_id {$user_id}");

        // summit_sponsor_service->addSponsorUser already opens and commits its
        // own transaction, so no transaction is needed at this level: by the
        // time the lock callback returns, the insert is committed and visible.
        $this->lock_service->lock(
            $this->sponsorUserLockKey($sponsor_id, $user_id),
            function () use ($summit_id, $sponsor_id, $user_id) {

                list($summit, $member) = $this->validateParams($summit_id, $user_id);

                Log::debug(
                    "SponsorUserSyncService::addSponsorUser summit {$summit->getName()} member {$member->getEmail()}");

                $member = $this->ensureSponsorGroupMembership($member, $user_id);

                $this->summit_sponsor_service->addSponsorUser($summit, $sponsor_id, $member->getId());

                Log::info(
                    "SponsorUserSyncService::addSponsorUser member {$member->getId()} successfully added to sponsor {$sponsor_id}");
            },
            self::SponsorUserLockLifetime
        );
    }

    /**
     * @inheritDoc
     */
    public function removeSponsorUser(int $summit_id, int $user_id, ?int $sponsor_id = null): void
    {
        // Do NOT swallow failures here: a lost removal event silently leaves
        // the user with access they should have lost. Propagate so the MQ job
        // (tries = 3) applies its retry / failed_jobs machinery.
        Log::debug(
            "SponsorUserSyncService::removeSponsorUser summit {$summit_id} sponsor {$sponsor_id} user_id {$user_id}");

        $summit = $this->resolveSummit($summit_id);

        // Revocation must not provision (see findMember). Skipping is also what
        // keeps a deleted IDP user from parking an unresolvable entry in
        // failed_jobs: sponsor-users-api emits a removal event per access right
        // when a user is deleted, and by then the local Member is gone too.
        $member = $this->findMember($user_id);
        if (is_null($member)) {
            Log::warning(
                "SponsorUserSyncService::removeSponsorUser member with external id {$user_id} was never synced - nothing to revoke, skipping");
            return;
        }

        Log::debug(
            "SponsorUserSyncService::removeSponsorUser summit {$summit->getName()} member {$member->getEmail()}");

        if (is_null($sponsor_id)) {
            // getSponsorMemberships() is a plain ManyToMany to Sponsor with no summit
            // scoping, so it also yields sponsors of OTHER summits. Those do not resolve
            // against $summit and would make removeSponsorUser throw "Sponsor not found.",
            // aborting the loop and leaving this summit's own memberships un-revoked.
            foreach ($member->getSponsorMemberships() as $sponsor_membership) {
                if ($sponsor_membership->getSummit()->getId() !== $summit->getId()) continue;

                $current_sponsor_id = $sponsor_membership->getId();
                $this->summit_sponsor_service->removeSponsorUser($summit, $current_sponsor_id, $member->getId());

                Log::info(
                    "SponsorUserSyncService::removeSponsorUser: member {$member->getId()} successfully removed from summit {$summit->getId()} for sponsor {$current_sponsor_id}"
                );
            }
        } else {
            // sponsor-users-api's metamodel reconciler emits this event precisely
            // when the sponsor no longer exists here (it reaps sponsors summit-api
            // stopped returning, routing them through remove_sponsor_show_permissions
            // so the domain events still fire). Nothing left to revoke - not a
            // failure to burn retries on and park in failed_jobs.
            if (is_null($summit->getSummitSponsorById($sponsor_id))) {
                Log::warning(
                    "SponsorUserSyncService::removeSponsorUser sponsor {$sponsor_id} no longer exists on summit {$summit->getId()} - nothing to revoke, skipping");
                return;
            }
            $this->summit_sponsor_service->removeSponsorUser($summit, $sponsor_id, $member->getId());
            Log::info(
                "SponsorUserSyncService::removeSponsorUser: member {$member->getId()} successfully removed from summit {$summit->getId()} for sponsor {$sponsor_id}");
        }
    }

    /**
     * @inheritDoc
     * @throws UnacquiredLockException propagated on purpose, same as any
     * other failure in this path (see addSponsorUser).
     */
    public function addSponsorUserToGroup(int $user_id, string $group_slug, int $sponsor_id, int $summit_id): void
    {
        Log::debug(
            "SponsorUserSyncService::addSponsorUserToGroup user_id {$user_id} group_slug {$group_slug} sponsor_id {$sponsor_id} summit_id {$summit_id}");

        // Gate BEFORE resolveMember: a rejected slug must not provision a member
        // from the IDP as a side effect.
        $this->assertAllowedSponsorGroup($group_slug);

        // The producer derives sponsor_id and summit_id from the same AccessRight,
        // so a mismatched pair is a forged or buggy event - and a deleted sponsor
        // leaves nothing to grant onto. Fail here, BEFORE resolveMember (same
        // no-side-effect rule as above) and loudly (failed_jobs), rather than ever
        // writing onto another summit's sponsor row.
        $summit = $this->resolveSummit($summit_id);
        if (is_null($summit->getSummitSponsorById($sponsor_id))) {
            throw new ValidationException(
                "Sponsor {$sponsor_id} does not belong to summit {$summit_id}.");
        }

        // Lock held OUTSIDE the transaction below: releasing it before commit
        // would let a waiting AddSponsorMemberMQJob/UpdateSponsorMemberGroupsMQJob
        // observe the row as still-missing and insert its own duplicate.
        $this->lock_service->lock(
            $this->sponsorUserLockKey($sponsor_id, $user_id),
            function () use ($user_id, $group_slug, $sponsor_id, $summit_id) {

                // Resolve (and, if needed, register from the IDP) OUTSIDE the transaction below,
                // but INSIDE the lock - resolving the local MemberID is part of what the lock
                // protects. registerExternalUserById opens its own transaction and dispatches
                // NewMember / MemberDataUpdatedExternally right after it. Those jobs are pushed
                // immediately: afterCommit only defers dispatch for Eloquent-managed transactions,
                // and this service uses the Doctrine DBAL connection directly. Keeping the
                // registration outside the transaction guarantees the Member row is committed
                // before any job references its id.
                $member_id = $this->resolveMember($user_id)->getId();

                $this->tx_service->transaction(function () use ($member_id, $group_slug, $sponsor_id, $summit_id) {

                    // Re-load inside the transaction: the tx service may have reset the entity
                    // manager, which would leave an entity resolved outside it detached.
                    $member = $this->member_repository->getById($member_id);
                    if (!$member instanceof Member) {
                        throw new EntityNotFoundException("Member with id {$member_id} not found");
                    }

                    // Grant the global group FIRST: Sponsor::addUser (reached through the
                    // eager-create path below) validates the member already belongs to a
                    // sponsor group, and for a brand-new sponsor user this very event is
                    // what delivers that group.
                    if (!$member->belongsToGroup($group_slug)) {
                        $group = $this->group_repository->getBySlug($group_slug);
                        if (is_null($group)) {
                            throw new EntityNotFoundException("Group {$group_slug} not found");
                        }
                        $member->add2Group($group);
                    }

                    // Add permission entry to the Sponsor_Users JSON column for this sponsor-member pair.
                    // If the row does not exist yet (MQ ordering race: group event arrived before membership
                    // event), create it eagerly so the permission is never silently dropped.
                    if ($member->addSponsorPermission($sponsor_id, $group_slug) === 0) {
                        Log::warning(
                            "SponsorUserSyncService::addSponsorUserToGroup no Sponsor_Users row found for " .
                            "member {$member->getId()} / sponsor {$sponsor_id} — creating it eagerly");

                        $summit = $this->summit_repository->getById($summit_id);
                        if (!$summit instanceof Summit) {
                            throw new EntityNotFoundException("Summit {$summit_id} not found");
                        }

                        $this->summit_sponsor_service->addSponsorUser($summit, $sponsor_id, $member->getId());

                        // Flush the UoW so the INSERT is visible to the raw SQL retry
                        // on the same connection within the active transaction.
                        Registry::getManager(SilverstripeBaseModel::EntityManager)->flush();

                        // Retry now that the row exists.
                        $retryResult = $member->addSponsorPermission($sponsor_id, $group_slug);
                        if ($retryResult === 0) {
                            throw new \RuntimeException(
                                "Failed to write permission after eager Sponsor_Users creation " .
                                "for member {$member->getId()} / sponsor {$sponsor_id}"
                            );
                        }
                    }

                    Log::info(
                        "SponsorUserSyncService::addSponsorUserToGroup member {$member->getId()} added to group {$group_slug} via sponsor {$sponsor_id}");
                });
            },
            self::SponsorUserLockLifetime
        );
    }

    /**
     * @inheritDoc
     */
    public function removeSponsorUserFromGroup(int $user_id, string $group_slug, int $sponsor_id, int $summit_id): void
    {
        Log::debug(
            "SponsorUserSyncService::removeSponsorUserFromGroup user_id {$user_id} group_slug {$group_slug} sponsor_id {$sponsor_id} summit_id {$summit_id}");

        $this->assertAllowedSponsorGroup($group_slug);

        // Reject only a sponsor that exists on a DIFFERENT summit (forged or buggy
        // event - the producer derives both ids from the same AccessRight). A
        // sponsor deleted ENTIRELY must NOT skip: its Sponsor_Users rows are gone,
        // and running the removal is exactly what recomputes the remaining
        // permission count and strips the global sponsors group when this was the
        // member's last sponsor - skipping would leave residual show-admin access.
        $sponsor = $this->sponsor_repository->getById($sponsor_id);
        if ($sponsor instanceof Sponsor && $sponsor->getSummitId() !== $summit_id) {
            Log::warning(
                "SponsorUserSyncService::removeSponsorUserFromGroup sponsor {$sponsor_id} belongs to summit {$sponsor->getSummitId()}, not event summit {$summit_id} - skipping");
            return;
        }

        // Revocation must not provision (see findMember): a member that was never
        // synced holds no permission entry and no group membership to remove.
        $member = $this->findMember($user_id);
        if (is_null($member)) {
            Log::warning(
                "SponsorUserSyncService::removeSponsorUserFromGroup member with external id {$user_id} was never synced - nothing to revoke, skipping");
            return;
        }
        $member_id = $member->getId();

        $this->tx_service->transaction(function () use ($member_id, $group_slug, $sponsor_id, $summit_id) {

            // Re-load inside the transaction: the tx service may have reset the entity
            // manager, which would leave an entity resolved outside it detached.
            $member = $this->member_repository->getById($member_id);
            if (!$member instanceof Member) {
                throw new EntityNotFoundException("Member with id {$member_id} not found");
            }

            // Remove permission entry from JSON and get remaining sponsor count.
            $remaining = $member->removeSponsorPermission($sponsor_id, $group_slug);

            if ($remaining === 0 && $member->belongsToGroup($group_slug)) {
                $group = $this->group_repository->getBySlug($group_slug);
                if (is_null($group)) {
                    throw new EntityNotFoundException("Group {$group_slug} not found");
                }
                $member->removeFromGroup($group);
                Log::info(
                    "SponsorUserSyncService::removeSponsorUserFromGroup member {$member->getId()} removed from global group {$group_slug}");
            } else {
                Log::info(
                    "SponsorUserSyncService::removeSponsorUserFromGroup member {$member->getId()} retains group {$group_slug} via {$remaining} other sponsor(s)");
            }
        });
    }
}
