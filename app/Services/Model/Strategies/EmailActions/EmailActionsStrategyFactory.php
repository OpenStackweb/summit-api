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
use App\Jobs\Emails\SummitAttendeeAllTicketsEditionEmail;
use App\Jobs\Emails\SummitAttendeeRegistrationIncompleteReminderEmail;
use App\Jobs\Emails\SummitAttendeeTicketRegenerateHashEmail;

/**
 * Class IEmailActionsStrategyFactory
 * @package App\Services\Model\Strategies
 */
final class EmailActionsStrategyFactory implements IEmailActionsStrategyFactory
{
    /**
     * @param String $flow_event
     * @return AbstractEmailAction|null
     */
    public function build(String $flow_event): ?AbstractEmailAction {
        switch ($flow_event) {
            case SummitAttendeeTicketRegenerateHashEmail::EVENT_SLUG:
            case InviteAttendeeTicketEditionMail::EVENT_SLUG:
                return new SummitAttendeeTicketEmailStrategy($flow_event);
            case SummitAttendeeAllTicketsEditionEmail::EVENT_SLUG:
                return new SummitAttendeeAllCurrentTicketsEmailStrategy($flow_event);
            case SummitAttendeeRegistrationIncompleteReminderEmail::EVENT_SLUG:
                return new SummitAttendeeRegistrationIncompleteReminderStrategy($flow_event);
            default:
                return null;
        }
    }
}