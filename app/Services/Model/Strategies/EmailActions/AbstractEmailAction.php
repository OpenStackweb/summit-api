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

use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeAnnouncementEmail;
use models\summit\SummitAttendeeTicket;
use DateTime;

/**
 * Class AbstractEmailAction
 * @package App\Services\Model\Strategies\EmailActions
 */
abstract class AbstractEmailAction
{
    /**
     * @var Summit
     */
    protected $summit;

    /**
     * @var String
     */
    protected $flow_event;

    /**
     * AbstractEmailAction constructor.
     * @param Summit $summit
     * @param String $flow_event
     */
    public function __construct(Summit $summit, String $flow_event)
    {
        $this->summit = $summit;
        $this->flow_event = $flow_event;
    }

    /**
     * @param SummitAttendee $attendee
     * @param string|null $test_email_recipient
     * @param callable|null $onSuccess
     * @param callable|null $onInfo
     * @param callable|null $onError
     * @param int|null $resume_since
     * @return mixed
     */
    public abstract function process
    (
        SummitAttendee $attendee,
        ?string $test_email_recipient = null,
        callable $onSuccess = null,
        callable $onInfo = null,
        callable $onError = null,
        ?int $resume_since = null
    );

    /**
     * Resume-check backing every concrete strategy's skip decision: on a retry (resume_since
     * set), an (attendee[, ticket]) that already has a proof for this flow event written at or
     * after resume_since was already reached by this run before the kill - skip before any side
     * effect. First attempt (resume_since null) always returns false, so this task alone changes
     * no observable behavior until the chunk job that sets resume_since exists.
     *
     * @param SummitAttendee $attendee
     * @param int|null $resume_since
     * @param SummitAttendeeTicket|null $ticket
     * @param string|null $type defaults to $this->flow_event - pass explicitly when the caller's
     *        own flow_event has been transiently mutated (SummitAttendeeTicketEmailStrategy)
     * @return bool
     */
    protected function alreadySentSince(SummitAttendee $attendee, ?int $resume_since, ?SummitAttendeeTicket $ticket = null, ?string $type = null): bool
    {
        if (is_null($resume_since)) return false;
        return $attendee->hasAnnouncementEmailTypeSentSince($this->summit, $type ?? $this->flow_event, new DateTime('@' . $resume_since), $ticket);
    }

    /**
     * Records the sent-proof for an email this strategy actually dispatched. Call after the
     * dispatch, never before - a proof only means "sent", not "about to send".
     *
     * @param SummitAttendee $attendee
     * @param SummitAttendeeTicket|null $ticket
     * @param string|null $type defaults to $this->flow_event - pass explicitly when the caller's
     *        own flow_event has been transiently mutated (SummitAttendeeTicketEmailStrategy)
     * @return void
     */
    protected function recordSent(SummitAttendee $attendee, ?SummitAttendeeTicket $ticket = null, ?string $type = null): void
    {
        $proof = new SummitAttendeeAnnouncementEmail();
        $proof->setType($type ?? $this->flow_event);
        $proof->setSummit($this->summit);
        $attendee->addAnnouncementEmail($proof);
        $proof->setTicket($ticket);
        $proof->markAsSent();
    }
}
