<?php namespace App\Services\Model\Strategies\EmailActions;
/**
 * Copyright 2022 OpenStack Foundation
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

use App\Jobs\Emails\InviteAttendeeTicketEditionMail;
use App\Jobs\Emails\SummitAttendeeTicketRegenerateHashEmail;
use Illuminate\Support\Facades\Log;
use models\summit\SummitAttendee;

/**
 * Class SummitAttendeeTicketEmailStrategy
 * @package App\Services\Model\Strategies\EmailActions
 */
class SummitAttendeeTicketEmailStrategy extends AbstractEmailAction
{
    /**
     * SummitAttendeeTicketEmailStrategy constructor.
     * @param String $flow_event
     */
    public function __construct(String $flow_event)
    {
        parent::__construct($flow_event);
    }

    /**
     * @param SummitAttendee $attendee
     * @param string|null $test_email_recipient
     * @return void
     */
    public function process(SummitAttendee $attendee, ?string $test_email_recipient = null)
    {
        foreach ($attendee->getTickets() as $ticket) {
            try {
                if(!$ticket->isActive()) continue;
                if(!$ticket->isPaid()) continue;
                $is_complete = $attendee->isComplete();
                $original_flow_event = $this->flow_event;
                Log::debug
                (
                    sprintf
                    (
                        "SummitAttendeeTicketEmailStrategy::send processing attendee %s - ticket %s - isComplete %b - original flow event %s",
                        $attendee->getEmail(),
                        $ticket->getId(),
                        $attendee->isComplete(),
                        $original_flow_event
                    )
                );
                if ($is_complete) {
                    $this->flow_event = InviteAttendeeTicketEditionMail::EVENT_SLUG;
                    Log::debug
                    (
                        sprintf
                        (
                            "SummitAttendeeTicketEmailStrategy::send changing from %s to %s bc attendee is complete",
                            $original_flow_event,
                            $this->flow_event
                        )
                    );
                }
                // send email
                if ($this->flow_event == SummitAttendeeTicketRegenerateHashEmail::EVENT_SLUG) {
                    $ticket->sendPublicEditEmail($test_email_recipient);
                }

                if ($this->flow_event == InviteAttendeeTicketEditionMail::EVENT_SLUG) {
                    $attendee->sendInvitationEmail($ticket, true, [], $test_email_recipient);
                }
                $this->flow_event = $original_flow_event;

            } catch (\Exception $ex) {
                Log::warning($ex);
            }
        }
    }
}