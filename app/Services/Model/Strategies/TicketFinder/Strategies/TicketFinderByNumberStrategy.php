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

use App\Services\Model\Strategies\TicketFinder\ITicketFinderStrategy;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\Summit;
use models\summit\SummitAttendeeTicket;

/**
 * Class TicketFinderByNumberStrategy
 * @package App\Services\Model\Strategies\TicketFinder\Strategies
 */
final class TicketFinderByNumberStrategy
    extends AbstractTicketFinderStrategy
    implements ITicketFinderStrategy
{
    private $ticket_attendee_email;
    /**
     * @param ISummitAttendeeTicketRepository $repository
     * @param Summit $summit
     * @param $ticket_criteria
     * @param string|null $ticket_attendee_email
     */
    public function __construct
    (
        ISummitAttendeeTicketRepository $repository,
        Summit $summit,
        $ticket_criteria,
        ?string $ticket_attendee_email = null
    )
    {
        parent::__construct($repository, $summit, $ticket_criteria);
        $this->ticket_attendee_email = $ticket_attendee_email;
    }

    /**
     * @return SummitAttendeeTicket|null
     * @throws ValidationException
     */
    public function find(): ?SummitAttendeeTicket
    {
        $ticket = $this->repository->getByNumber(strval($this->ticket_criteria));
        if(!empty($this->ticket_attendee_email) && !is_null($ticket)){
            if(!$ticket->hasOwner()) {
                Log::warning
                (
                    sprintf
                    (
                        "TicketFinderByNumberStrategy::find ticket %s has no owner but QR is assigned to %s",
                        $ticket->getId(),
                        $this->ticket_attendee_email
                    )
                );

                throw new
                ValidationException
                (
                    "Your ticket has been reassigned to someone else. Please see the help desk for assistance."
                );
            }

            $owner = $ticket->getOwner();
            if($owner->getEmail() != $this->ticket_attendee_email){
                Log::warning
                (
                    sprintf
                    (
                        "TicketFinderByNumberStrategy::find ticket %s has owner %s but QR is assigned to %s",
                        $ticket->getId(),
                        $owner->getEmail(),
                        $this->ticket_attendee_email
                    )
                );
                throw new
                ValidationException
                (
                    "Your ticket has been reassigned to someone else. Please see the help desk for assistance."
                );
            }
        }
        return $ticket;
    }
}