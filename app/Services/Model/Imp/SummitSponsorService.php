<?php namespace App\Services;
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
use App\Models\Foundation\Summit\Factories\SponsorFactory;
use App\Models\Foundation\Summit\Repositories\ISponsorshipTypeRepository;
use App\Services\Model\AbstractService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\ICompanyRepository;
use models\main\IMemberRepository;
use models\main\Member;
use models\summit\Sponsor;
use models\summit\Summit;
use services\model\ISummitSponsorService;
/**
 * Class SummitSponsorService
 * @package App\Services\Model
 */
final class SummitSponsorService
    extends AbstractService
    implements ISummitSponsorService
{
    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ICompanyRepository
     */
    private $company_repository;

    /**
     * @var ISponsorshipTypeRepository
     */
    private $sponsorship_type_repository;

    public function __construct
    (
        IMemberRepository $member_repository,
        ICompanyRepository $company_repository,
        ISponsorshipTypeRepository $sponsorship_type_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->member_repository = $member_repository;
        $this->company_repository = $company_repository;
        $this->sponsorship_type_repository = $sponsorship_type_repository;
    }


    /**
     * @param Summit $summit
     * @param array $payload
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSponsor(Summit $summit, array $payload): Sponsor
    {
        return $this->tx_service->transaction(function () use ($summit, $payload) {
            $company_id = intval($payload['company_id']);
            $sponsorship_id = intval($payload['sponsorship_id']);
            $company = $this->company_repository->getById($company_id);
            if (is_null($company))
                throw new EntityNotFoundException("company not found");
            $sponsorship_type = $this->sponsorship_type_repository->getById($sponsorship_id);
            if (is_null($sponsorship_type))
                throw new EntityNotFoundException("sponsorship type not found");

            $former_sponsor = $summit->getSummitSponsorByCompany($company);
            if (!is_null($former_sponsor)) {
                throw new ValidationException("company already is sponsor on summit");
            }

            $payload['company'] = $company;
            $payload['sponsorship'] = $sponsorship_type;
            $sponsor = SponsorFactory::build($payload);

            $summit->addSummitSponsor($sponsor);

            return $sponsor;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param array $payload
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateSponsor(Summit $summit, int $sponsor_id, array $payload): Sponsor
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $payload) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("sponsor not found");
            $company = null;
            $sponsorship_type = null;

            if(isset($payload['company_id'])) {
                $company_id = intval($payload['company_id']);
                $company = $this->company_repository->getById($company_id);
                if (is_null($company))
                    throw new EntityNotFoundException("company not found");
            }

            if(isset($payload['sponsorship_id'])) {
                $sponsorship_id = intval($payload['sponsorship_id']);
                $sponsorship_type = $this->sponsorship_type_repository->getById($sponsorship_id);
                if (is_null($sponsorship_type))
                    throw new EntityNotFoundException("sponsorship type not found");
            }

            if(!is_null($company)) {
                $former_sponsor = $summit->getSummitSponsorByCompany($company);
                if (!is_null($former_sponsor) && $former_sponsor->getId() != $sponsor_id) {
                    throw new ValidationException("company already is sponsor on summit");
                }
            }
            if(!is_null($company))
                $payload['company'] = $company;
            if(!is_null($sponsorship_type))
                $payload['sponsorship'] = $sponsorship_type;
            $sponsor = SponsorFactory::populate($summit_sponsor, $payload);

            if (isset($payload['order']) && intval($payload['order']) != $sponsor->getOrder()) {
                // request to update order
                $summit->recalculateSummitSponsorOrder($sponsor, $payload['order']);
            }

            return $sponsor;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSponsor(Summit $summit, int $sponsor_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $sponsor_id) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("sponsor not found");

            $summit->removeSummitSponsor($summit_sponsor);
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $member_id
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSponsorUser(Summit $summit, int $sponsor_id, int $member_id): Sponsor
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $member_id) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);

            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("sponsor not found");

            $member                    = $this->member_repository->getById($member_id);
            $current_summit_begin_date = $summit->getBeginDate();
            $current_summit_end_date   = $summit->getEndDate();

            if (is_null($member) || !$member instanceof Member)
                throw new EntityNotFoundException("member not found");

            foreach($member->getSponsorMemberships() as $former_sponsor){

                $former_summit            = $former_sponsor->getSummit();
                $former_summit_begin_date = $former_summit->getBeginDate();
                $former_summit_end_date   = $former_summit->getEndDate();

                // check that current summit does not intersect with a former one
                // due a member could be on 2 diff places at same time ...
                // (StartA <= EndB)  and  (EndA >= StartB)

                if($current_summit_begin_date <= $former_summit_end_date && $current_summit_end_date >=$former_summit_begin_date){
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "you can not add member %s as sponsor user on summit %s bc its already sponsor user on another concurrent summit (%s)",
                            $member_id,
                            $summit->getId(),
                            $former_summit->getId()
                        )
                    );
                }
            }

            $summit_sponsor->addUser($member);

            return $summit_sponsor;

        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $member_id
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removeSponsorUser(Summit $summit, int $sponsor_id, int $member_id): Sponsor
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $member_id) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("sponsor not found");

            $member = $this->member_repository->getById($member_id);

            if (is_null($member))
                throw new EntityNotFoundException("member not found");

            $summit_sponsor->removeUser($member);

            return $summit_sponsor;

        });
    }
}