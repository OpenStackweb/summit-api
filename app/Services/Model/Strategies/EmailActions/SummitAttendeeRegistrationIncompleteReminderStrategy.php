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

use App\Jobs\Emails\SummitAttendeeRegistrationIncompleteReminderEmail;
use Illuminate\Support\Facades\Log;
use models\summit\SummitAttendee;

class SummitAttendeeRegistrationIncompleteReminderStrategy extends AbstractEmailAction
{
    /**
     * SummitAttendeeRegistrationIncompleteReminderStrategy constructor.
     * @param String $flow_event
     */
    public function __construct(String $flow_event)
    {
        parent::__construct($flow_event);
    }

    public function process(SummitAttendee $attendee)
    {
        if (!$attendee->isComplete()) {
            Log::debug
            (
                sprintf
                (
                    "SummitAttendeeRegistrationIncompleteReminderStrategy::sending reminder to attendee %s - flow event %s",
                    $attendee->getEmail(),
                    $this->flow_event
                )
            );
            SummitAttendeeRegistrationIncompleteReminderEmail::dispatch($attendee);
        } else {
            Log::debug
            (
                sprintf
                (
                    "SummitAttendeeRegistrationIncompleteReminderStrategy::nothing to send due to attendee %s status is complete",
                    $attendee->getEmail()
                )
            );
        }
    }
}