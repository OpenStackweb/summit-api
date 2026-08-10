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

use App\Services\Model\AbstractService;
use App\Services\Model\IMemberService;
use App\Services\Model\ISponsorUserSyncService;
use Illuminate\Support\Facades\Log;
use LaravelDoctrine\ORM\Facades\Registry;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\main\IGroupRepository;
use models\main\IMemberRepository;
use models\main\Member;
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

    /**
     * SponsorUserSyncService constructor.
     * @param ISummitRepository $summit_repository
     * @param IMemberRepository $member_repository
     * @param IGroupRepository $group_repository
     * @param ISummitSponsorService $summit_sponsor_service
     * @param IMemberService $member_service
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        IMemberRepository $member_repository,
        IGroupRepository $group_repository,
        ISummitSponsorService $summit_sponsor_service,
        IMemberService $member_service,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->summit_repository = $summit_repository;
        $this->member_repository = $member_repository;
        $this->group_repository = $group_repository;
        $this->summit_sponsor_service = $summit_sponsor_service;
        $this->member_service = $member_service;
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
     * the member that exists with a stale local group set: re-read it from the
     * IDP, which is the source of truth, instead of failing.
     *
     * Nothing downstream would repair that failure: the producers of
     * auth_user_added_to_sponsor_and_summit (_import_user, _notify_approval) emit
     * no companion group event, so no eager-create path ever runs and the access
     * is lost once the job exhausts its tries.
     *
     * @param Member $member
     * @param int $user_id external (IDP) user id
     * @return Member the same member, or the refreshed one
     * @throws \Exception
     */
    private function ensureSponsorGroupMembership(Member $member, int $user_id): Member
    {
        foreach (Sponsor::AllowedMemberGroups as $group_slug) {
            if ($member->belongsToGroup($group_slug)) return $member;
        }

        Log::warning(
            "SponsorUserSyncService::ensureSponsorGroupMembership member {$member->getId()} belongs to none of the allowed sponsor groups - refreshing groups from the IDP");

        return $this->member_service->registerExternalUserById($user_id);
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
     */
    public function addSponsorUser(int $summit_id, int $sponsor_id, int $user_id): void
    {
        // Do NOT swallow failures here: the MQ job (tries = 3) needs the
        // exception to apply its retry / failed_jobs machinery. A swallowed
        // failure loses the membership event silently.
        Log::debug(
            "SponsorUserSyncService::addSponsorUser summit {$summit_id} sponsor {$sponsor_id} user_id {$user_id}");

        list($summit, $member) = $this->validateParams($summit_id, $user_id);

        Log::debug(
            "SponsorUserSyncService::addSponsorUser summit {$summit->getName()} member {$member->getEmail()}");

        $member = $this->ensureSponsorGroupMembership($member, $user_id);

        $this->summit_sponsor_service->addSponsorUser($summit, $sponsor_id, $member->getId());

        Log::info(
            "SponsorUserSyncService::addSponsorUser member {$member->getId()} successfully added to sponsor {$sponsor_id}");
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
            $this->summit_sponsor_service->removeSponsorUser($summit, $sponsor_id, $member->getId());
            Log::info(
                "SponsorUserSyncService::removeSponsorUser: member {$member->getId()} successfully removed from summit {$summit->getId()} for sponsor {$sponsor_id}");
        }
    }

    /**
     * @inheritDoc
     */
    public function addSponsorUserToGroup(int $user_id, string $group_slug, int $sponsor_id, int $summit_id): void
    {
        Log::debug(
            "SponsorUserSyncService::addSponsorUserToGroup user_id {$user_id} group_slug {$group_slug} sponsor_id {$sponsor_id} summit_id {$summit_id}");

        // Resolve (and, if needed, register from the IDP) OUTSIDE the transaction below.
        // registerExternalUserById opens its own transaction and dispatches NewMember /
        // MemberDataUpdatedExternally right after it. Those jobs are pushed immediately:
        // afterCommit only defers dispatch for Eloquent-managed transactions, and this
        // service uses the Doctrine DBAL connection directly. Keeping the registration
        // outside guarantees the Member row is committed before any job references its id.
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
    }

    /**
     * @inheritDoc
     */
    public function removeSponsorUserFromGroup(int $user_id, string $group_slug, int $sponsor_id, int $summit_id): void
    {
        Log::debug(
            "SponsorUserSyncService::removeSponsorUserFromGroup user_id {$user_id} group_slug {$group_slug} sponsor_id {$sponsor_id} summit_id {$summit_id}");

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
