<?php namespace App\Services\Model\Imp;
/**
 * Copyright 2019 OpenStack Foundation
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
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitSponsorService
     */
    private $summit_sponsor_service;


    /**
     * SponsorUserSyncService constructor.
     * @param ISummitRepository $summit_repository
     * @param IMemberRepository $member_repository
     * @param ISummitSponsorService $summit_sponsor_service
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        IMemberRepository $member_repository,
        ISummitSponsorService $summit_sponsor_service,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->summit_repository = $summit_repository;
        $this->member_repository = $member_repository;
        $this->summit_sponsor_service = $summit_sponsor_service;
    }

    /**
     * @inheritDoc
     */
    public function addSponsorUser(int $summit_id, int $sponsor_id, int $user_external_id) {
        try {
            Log::debug(
                "SponsorUserSyncService::addSponsorUser summit {$summit_id} sponsor {$sponsor_id} user_external_id {$user_external_id}");

            $summit = $this->summit_repository->getById($summit_id);
            if (!$summit instanceof Summit) {
                throw new EntityNotFoundException("Summit {$summit_id} not found");
            }

            $member = $this->member_repository->getByExternalId($user_external_id);
            if (is_null($member)) {
                throw new EntityNotFoundException("Member with external id {$user_external_id} not found");
            }

            Log::debug(
                "SponsorUserSyncService::addSponsorUser summit {$summit->getName()} member {$member->getEmail()}");

            $this->summit_sponsor_service->addSponsorUser($summit, $sponsor_id, $member->getId());

            Log::info(
                "SponsorUserSyncService::addSponsorUser member {$member->getId()} successfully added to sponsor {$sponsor_id}");
        }  catch (\Exception $ex) {
            Log::error($ex);
        }
    }
}