<?php namespace App\Jobs;
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

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeTicket;

/**
 * Class SendAttendeeInvitationEmail
 * @package App\Jobs
 */
final class SendAttendeeInvitationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $ticket_id;


    /**
     * @param int $ticket_id
     */
    public function __construct(int $ticket_id)
    {
        Log::debug(sprintf("SendAttendeeInvitationEmail::constructor ticket_id %s", $ticket_id));
        $this->ticket_id = $ticket_id;
    }

    /**
     * @param ISummitAttendeeTicketRepository $ticketRepository
     * @param ISummitAttendeeRepository $attendeeRepository
     * @return void
     * @throws EntityNotFoundException
     */
    public function handle
    (
        ISummitAttendeeTicketRepository $ticketRepository,
        ISummitAttendeeRepository $attendeeRepository
    )
    {
        Log::debug(sprintf( "SendAttendeeInvitationEmail::handle ticket_id %s", $this->ticket_id));

        try {
            $ticket = $ticketRepository->getByIdRefreshed($this->ticket_id);

            if (!$ticket instanceof SummitAttendeeTicket) {

                throw new EntityNotFoundException(sprintf("Ticket %s not found.", $this->ticket_id));
            }

            if (!$ticket->hasOwner()) {

                Log::warning
                (
                    sprintf
                    (
                        "SendAttendeeInvitationEmail::handle ticket %s has no owner",
                        $this->ticket_id
                    )
                );

                return;
            }

            $attendee = $ticket->getOwner();

            $attendee = $attendeeRepository->getByIdRefreshed($attendee->getId());

            if(!$attendee instanceof SummitAttendee){
                Log::warning
                (

                        "SendAttendeeInvitationEmail::handle attendee not found",
                );
                return;
            }

            Log::debug
            (
                sprintf
                (
                    "SendAttendeeInvitationEmail::handle sendInvitationEmail to attendee %s (%s) status %s",
                    $attendee->getEmail(),
                    $attendee->getId(),
                    $attendee->getStatus()
                )
            );

            $attendee->sendInvitationEmailWithoutDelay($ticket);
        }
        catch (\Exception $ex){
            Log::error($ex);
            throw $ex;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }

}