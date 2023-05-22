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
        ISummitAttendeeTicketRepository $repository,
        Summit $summit,
        $ticket_criteria
    )
    {
        parent::__construct($repository, $summit, $ticket_criteria);
        $this->feed = $feed;
        $this->service = $service;
    }

    /**
     * @return SummitAttendeeTicket|null
     */
    public function find(): ?SummitAttendeeTicket
    {
        if(empty($this->ticket_criteria)) return null;
        if($this->feed->isValidQRCode($this->ticket_criteria)){
            $res = $this->feed->getAttendeeByQRCode($this->ticket_criteria);
            if(!count($res)) return null;

            $attendee = $this->service->ingestExternalAttendee
            (

                $this->summit->getId(),
                1,
                $res
            );
            return $attendee->getTickets()->first();
        }
        else if(filter_var($this->ticket_criteria, FILTER_VALIDATE_EMAIL)){
            $res = $this->feed->getAttendeeByEmail($this->ticket_criteria);
            if(!count($res)) return null;

            $attendee = $this->service->ingestExternalAttendee
            (

                $this->summit->getId(),
                1,
                $res
            );
            return $attendee->getTickets()->first();
        }
        return null;
    }
}