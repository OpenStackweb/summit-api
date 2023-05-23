<?php namespace App\Services\Model\Strategies\TicketFinder\Strategies;
/*
 * Copyright 2023 OpenStack Foundation
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

use App\Services\Apis\ExternalRegistrationFeeds\IExternalRegistrationFeed;
use App\Services\Model\IRegistrationIngestionService;
use App\Services\Model\Strategies\TicketFinder\ITicketFinderStrategy;
use Illuminate\Support\Facades\Log;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\Summit;
use models\summit\SummitAttendeeTicket;

/**
 * Class TicketFinderByExternalFeedStrategy
 * @package App\Services\Model\Strategies\TicketFinder\Strategies
 */
final class TicketFinderByExternalFeedStrategy
    extends AbstractTicketFinderStrategy
    implements ITicketFinderStrategy
{

    /**
     * @var IRegistrationIngestionService
     */
    private $service;
    /**
     * @var IExternalRegistrationFeed
     */
    private $feed;

    /**
     * @var ISummitAttendeeRepository
     */
    private $attendee_repository;

    /**
     * @param IRegistrationIngestionService $service
     * @param IExternalRegistrationFeed $feed
     * @param ISummitAttendeeTicketRepository $repository
     * @param Summit $summit
     * @param $ticket_criteria
     */
    public function __construct
    (
        IRegistrationIngestionService $service,
        IExternalRegistrationFeed $feed,
        ISummitAttendeeRepository $attendee_repository,
        ISummitAttendeeTicketRepository $repository,
        Summit $summit,
        $ticket_criteria
    )
    {
        parent::__construct($repository, $summit, $ticket_criteria);
        $this->feed = $feed;
        $this->service = $service;
        $this->attendee_repository = $attendee_repository;
    }

    /**
     * @return SummitAttendeeTicket|null
     */
    public function find(): ?SummitAttendeeTicket
    {
        if(empty($this->ticket_criteria)) return null;
        Log::debug(sprintf("TicketFinderByExternalFeedStrategy::find ticket_criteria %s", $this->ticket_criteria));

        if($this->feed->isValidQRCode($this->ticket_criteria)){
            $externalAttendeeId = $this->feed->getExternalUserIdFromQRCode($this->ticket_criteria);
            // check first if we have it locally
            $attendee = $this->attendee_repository->getBySummitAndExternalId
            (
                $this->summit, $externalAttendeeId
            );

            if(is_null($attendee)) {

                Log::debug
                (
                    sprintf
                    (
                        "TicketFinderByExternalFeedStrategy::find attendee %s not found locally, fetching from external feed",
                        $externalAttendeeId
                    )
                );

                $res = $this->feed->getAttendeeByQRCode($this->ticket_criteria);

                if (!count($res)){
                    Log::warning(sprintf("TicketFinderByExternalFeedStrategy::find attendee %s not found on external feed", $externalAttendeeId));
                    return null;
                }

                Log::debug
                (
                    sprintf
                    (
                        "TicketFinderByExternalFeedStrategy::find attendee %s found on external feed payload %s",
                        $externalAttendeeId,
                        json_encode($res)
                    )
                );

                $attendee = $this->service->ingestExternalAttendee
                (

                    $this->summit->getId(),
                    1,
                    $res
                );
            }

            return $attendee->getFirstTicket();
        }
        if(filter_var($this->ticket_criteria, FILTER_VALIDATE_EMAIL)){

            $externalAttendeeEmail = $this->ticket_criteria;
            // check first if we have it locally
            $attendee = $this->attendee_repository->getBySummitAndEmail
            (
                $this->summit, $externalAttendeeEmail
            );

            if(is_null($attendee)) {

                Log::debug
                (
                    sprintf
                    (
                        "TicketFinderByExternalFeedStrategy::find attendee %s not found locally, fetching from external feed",
                        $externalAttendeeEmail
                    )
                );

                $res = $this->feed->getAttendeeByEmail($externalAttendeeEmail);
                if (!count($res)){
                    Log::warning(sprintf("TicketFinderByExternalFeedStrategy::find attendee %s not found on external feed", $externalAttendeeEmail));
                    return null;
                }

                Log::debug
                (
                    sprintf
                    (
                        "TicketFinderByExternalFeedStrategy::find attendee %s found on external feed payload %s",
                        $this->ticket_criteria,
                        json_encode($res)
                    )
                );

                $attendee = $this->service->ingestExternalAttendee
                (

                    $this->summit->getId(),
                    1,
                    $res
                );
            }
            return $attendee->getFirstTicket();
        }
        return null;
    }
}