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
use App\Models\Foundation\Summit\Factories\SponsorUserInfoGrantFactory;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgeRepository;
use App\Services\Model\AbstractService;
use App\Services\Model\ISponsorUserInfoGrantService;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\ISponsorUserInfoGrantRepository;
use models\summit\SponsorBadgeScan;
use models\summit\SponsorUserInfoGrant;
use models\summit\Summit;
use models\summit\SummitAttendeeBadge;
/**
 * Class SponsorUserInfoGrantService
 * @package App\Services\Model\Imp
 */
final class SponsorUserInfoGrantService
    extends AbstractService
    implements ISponsorUserInfoGrantService
{

    /**
     * @var ISponsorUserInfoGrantRepository
     */
    private $repository;

    /**
     * @var ISummitAttendeeBadgeRepository
     */
    private $badge_repository;

    /**
     * SponsorBadgeScanService constructor.
     * @param ISponsorUserInfoGrantRepository $repository
     * @param ISummitAttendeeBadgeRepository $badge_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISponsorUserInfoGrantRepository $repository,
        ISummitAttendeeBadgeRepository $badge_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
        $this->badge_repository = $badge_repository;
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param Member $current_member
     * @return SponsorUserInfoGrant
     * @throws \Exception
     */
    public function addGrant(Summit $summit, int $sponsor_id, Member $current_member):SponsorUserInfoGrant {
        return $this->tx_service->transaction(function() use($summit, $sponsor_id, $current_member){
            $sponsor = $summit->getSummitSponsorById($sponsor_id);
            if(is_null($sponsor)){
                throw new EntityNotFoundException(sprintf("Sponsor not found."));
            }
            if($sponsor->hasGrant($current_member)){
                Log::warning(
                    sprintf
                    (
                        "User %s already gave grant to sponsor %s",
                        $current_member->getEmail(),
                        $sponsor_id
                    )
                );
                return $sponsor->getGrant($current_member);
            }
            $grant = SponsorUserInfoGrantFactory::build(['class_name' => SponsorUserInfoGrant::ClassName]);
            $grant->setAllowedUser($current_member);
            $sponsor->addUserInfoGrant($grant);
            return $grant;
        });
    }

    /**
     * @param Summit $summit
     * @param Member $current_member
     * @param array $data
     * @return SponsorBadgeScan
     * @throws \Exception
     */
    public function addBadgeScan(Summit $summit, Member $current_member, array $data): SponsorBadgeScan
    {
        return $this->tx_service->transaction(function() use($summit, $current_member, $data){

            $qr_code         = trim($data['qr_code']);
            $fields          = SummitAttendeeBadge::parseQRCode($qr_code);
            $prefix          = $fields['prefix'];
            $scan_date_epoch = intval($data['scan_date']);
            $scan_date       = new \DateTime("@$scan_date_epoch");
            $begin_date      = $summit->getBeginDate();
            $end_date        = $summit->getEndDate();

            if(!($scan_date >= $begin_date && $scan_date <= $end_date))
                throw new ValidationException("scan_date is does not belong to summit period.");

            if($summit->getBadgeQRPrefix() != $prefix)
                throw new ValidationException
                (
                    sprintf
                    (
                        "%s qr code is not valid for summit %s",
                        $qr_code,
                        $summit->getId()
                    )
                );

            $ticket_number = $fields['ticket_number'];

            $badge = $this->badge_repository->getBadgeByTicketNumber($ticket_number);

            if(is_null($badge))
                throw new EntityNotFoundException("badge not found");

            $sponsor = $current_member->getSponsorBySummit($summit);

            if(is_null($sponsor))
                throw new ValidationException("Current member does not belongs to any summit sponsor.");

            $scan = SponsorUserInfoGrantFactory::build(['class_name' => SponsorBadgeScan::ClassName]);
            $scan->setScanDate($scan_date);
            $scan->setQRCode($qr_code);
            $scan->setUser($current_member);
            $scan->setBadge($badge);
            $scan->setNotes(isset($data['notes'])? trim($data['notes']): "");
            $sponsor->addUserInfoGrant($scan);

            return $scan;
        });
    }

    /**
     * @param Summit $summit
     * @param Member $current_member
     * @param int $scan_id
     * @param array $data
     * @return SponsorBadgeScan
     * @throws \Exception
     */
    public function updateBadgeScan(Summit $summit, Member $current_member, int $scan_id, array $data): SponsorBadgeScan
    {
        return $this->tx_service->transaction(function() use($summit, $current_member, $scan_id, $data){
            $sponsor = $current_member->getSponsorBySummit($summit);

            if(is_null($sponsor))
                throw new ValidationException("Current member does not belongs to any summit sponsor.");

            $scan = $sponsor->getUserInfoGrantById($scan_id);

            if(is_null($scan) || !$scan instanceof SponsorBadgeScan){
                throw new EntityNotFoundException("Scan not found.");
            }

            if(isset($data['notes'])){
                $scan->setNotes(trim($data['notes']));
            }

            return $scan;
        });
    }

    /**
     * @param Summit $summit
     * @param Member $current_member
     * @param int $scan_id
     * @return SponsorBadgeScan
     * @throws \Exception
     */
    public function getBadgeScan(Summit $summit, Member $current_member, int $scan_id): SponsorBadgeScan
    {
        return $this->tx_service->transaction(function() use($summit, $current_member, $scan_id){
            $sponsor = $current_member->getSponsorBySummit($summit);

            if(is_null($sponsor))
                throw new ValidationException("Current member does not belongs to any summit sponsor.");

            $scan = $sponsor->getUserInfoGrantById($scan_id);

            if(is_null($scan) || !$scan instanceof SponsorBadgeScan){
                throw new EntityNotFoundException("Scan not found.");
            }

            return $scan;
        });
    }
}