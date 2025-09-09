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
use App\Services\Model\ISponsorUserSyncService;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\main\IGroupRepository;
use models\main\IMemberRepository;
use models\summit\ISummitRepository;
use models\summit\Summit;
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

    /**
     * SponsorUserSyncService constructor.
     * @param ISummitRepository $summit_repository
     * @param IMemberRepository $member_repository
     * @param IGroupRepository $group_repository
     * @param ISummitSponsorService $summit_sponsor_service
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        IMemberRepository $member_repository,
        IGroupRepository $group_repository,
        ISummitSponsorService $summit_sponsor_service,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->summit_repository = $summit_repository;
        $this->member_repository = $member_repository;
        $this->group_repository = $group_repository;
        $this->summit_sponsor_service = $summit_sponsor_service;
    }

    /**
     * @param int $summit_id
     * @param int $user_id
     * @return array
     * @throws EntityNotFoundException
     */
    public function validateParams(int $summit_id, int $user_id): array
    {
        $summit = $this->summit_repository->getById($summit_id);
        if (!$summit instanceof Summit) {
            throw new EntityNotFoundException("Summit {$summit_id} not found");
        }

        $member = $this->member_repository->getByExternalId($user_id);
        if (is_null($member)) {
            throw new EntityNotFoundException("Member with id {$user_id} not found");
        }
        return array($summit, $member);
    }

    /**
     * @inheritDoc
     */
    public function addSponsorUser(int $summit_id, int $sponsor_id, int $user_id): void
    {
        try {
            Log::debug(
                "SponsorUserSyncService::addSponsorUser summit {$summit_id} sponsor {$sponsor_id} user_id {$user_id}");

            list($summit, $member) = $this->validateParams($summit_id, $user_id);

            Log::debug(
                "SponsorUserSyncService::addSponsorUser summit {$summit->getName()} member {$member->getEmail()}");

            $this->summit_sponsor_service->addSponsorUser($summit, $sponsor_id, $member->getId());

            Log::info(
                "SponsorUserSyncService::addSponsorUser member {$member->getId()} successfully added to sponsor {$sponsor_id}");
        }  catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    /**
     * @inheritDoc
     */
    public function removeSponsorUser(int $summit_id, int $user_id, ?int $sponsor_id = null): void
    {
        try {
            Log::debug(
                "SponsorUserSyncService::removeSponsorUser summit {$summit_id} sponsor {$sponsor_id} user_id {$user_id}");

            list($summit, $member) = $this->validateParams($summit_id, $user_id);

            Log::debug(
                "SponsorUserSyncService::removeSponsorUser summit {$summit->getName()} member {$member->getEmail()}");

            if (is_null($sponsor_id)) {
                foreach ($member->getSponsorMemberships() as $sponsor_membership) {
                    $sponsor_id = $sponsor_membership->getId();
                    $this->summit_sponsor_service->removeSponsorUser($summit, $sponsor_id, $member->getId());

                    Log::info(
                        "SponsorUserSyncService::removeSponsorUser: member {$member->getId()} successfully removed from summit {$summit->getId()} for sponsor {$sponsor_id}"
                    );
                }
            } else {
                $this->summit_sponsor_service->removeSponsorUser($summit, $sponsor_id, $member->getId());
                Log::info(
                    "SponsorUserSyncService::removeSponsorUser: member {$member->getId()} successfully removed from to summit {$summit_id} for sponsor {$sponsor_id}");
            }
        }  catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    /**
     * @inheritDoc
     */
    public function addSponsorUserToGroup(int $user_id, string $group_slug): void
    {
        $this->tx_service->transaction(function () use ($user_id, $group_slug) {
            $member = $this->member_repository->getByExternalId($user_id);
            if (is_null($member)) {
                throw new EntityNotFoundException("Member with id {$user_id} not found");
            }
            if (!$member->belongsToGroup($group_slug)) {
                $group = $this->group_repository->getBySlug($group_slug);
                if (is_null($group)) {
                    throw new EntityNotFoundException("Group {$group_slug} not found");
                }
                $member->add2Group($group);
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function removeSponsorUserFromGroup(int $user_id, string $group_slug): void
    {
         $this->tx_service->transaction(function () use ($user_id, $group_slug) {
            $member = $this->member_repository->getByExternalId($user_id);
            if (is_null($member)) {
                throw new EntityNotFoundException("Member with id {$user_id} not found");
            }
            if ($member->belongsToGroup($group_slug)) {
                $group = $this->group_repository->getBySlug($group_slug);
                if (is_null($group)) {
                    throw new EntityNotFoundException("Group {$group_slug} not found");
                }
                $member->removeFromGroup($group);
            }
        });
    }
}