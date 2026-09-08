<?php namespace App\Services\Model\Strategies\EmailActions;
/*
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

use App\Jobs\Emails\Registration\Attendees\GenericSummitAttendeeEmail;
use App\Services\utils\IEmailExcerptService;
use Illuminate\Support\Facades\Log;
use models\summit\Summit;
use models\summit\SummitAttendee;

/**
 * Class SummitAttendeeGenericEmailStrategy
 * @package App\Services\Model\Strategies\EmailActions
 */
final class SummitAttendeeGenericEmailStrategy extends AbstractEmailAction
{
    /**
     * SummitAttendeeGenericEmailStrategy constructor.
     * @param Summit $summit
     * @param String $flow_event
     */
    public function __construct(Summit $summit, string $flow_event)
    {
        parent::__construct($summit, $flow_event);
    }

    /**
     * @param SummitAttendee $attendee
     * @param string|null $test_email_recipient
     * @param callable|null $onSuccess
     * @param callable|null $onInfo
     * @param callable|null $onError
     * @param int|null $resume_since
     * @return void
     */
    public function process
    (
        SummitAttendee $attendee,
        ?string $test_email_recipient = null,
        callable $onSuccess = null,
        callable $onInfo = null,
        callable $onError = null,
        ?int $resume_since = null
    )
    {
        if ($this->alreadySentSince($attendee, $resume_since)) {
            if (!is_null($onInfo)) {
                $onInfo
                (
                    sprintf
                    (
                        "Attendee %s (%s) already processed by this run before the retry, skipped.",
                        $attendee->getEmail(),
                        $attendee->getId()
                    )
                );
            }
            return;
        }

        Log::debug
        (
            sprintf
            (
                "SummitAttendeeGenericEmailStrategy::sending all tickets to attendee %s - flow event %s",
                $attendee->getEmail(),
                $this->flow_event
            )
        );

        GenericSummitAttendeeEmail::dispatch($attendee, $test_email_recipient);
        $this->recordSent($attendee);

        if (!is_null($onSuccess)) {
            $onSuccess($attendee->getEmail(), IEmailExcerptService::EmailLineType, $this->flow_event);
        }
    }
}
