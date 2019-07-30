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
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgeRepository;
use App\Services\Model\AbstractService;
use App\Services\Model\ISponsorBadgeScanService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\ISponsorBadgeScanRepository;
use models\summit\SponsorBadgeScan;
use models\summit\Summit;
use models\summit\SummitAttendeeBadge;
/**
 * Class SponsorBadgeScanService
 * @package App\Services\Model\Imp
 */
final class SponsorBadgeScanService
    extends AbstractService
    implements ISponsorBadgeScanService
{

    /**
     * @var ISponsorBadgeScanRepository
     */
    private $repository;

    /**
     * @var ISummitAttendeeBadgeRepository
     */
    private $badge_repository;

    /**
     * SponsorBadgeScanService constructor.
     * @param ISponsorBadgeScanRepository $repository
     * @param ISummitAttendeeBadgeRepository $badge_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISponsorBadgeScanRepository $repository,
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
                throw new ValidationException("scan_date is does not belong to summit period");

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
                throw new ValidationException("current member does not belongs to any summit sponsor");

            $scan = new SponsorBadgeScan();

            $scan->setScanDate($scan_date);
            $scan->setQRCode($qr_code);
            $scan->setUser($current_member);
            $scan->setBadge($badge);
            $sponsor->addBadgeScan($scan);

            return $scan;
        });
    }
}