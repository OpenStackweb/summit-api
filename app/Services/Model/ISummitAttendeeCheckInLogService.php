<?php namespace services\model;
/**
 * Copyright 2026 OpenStack Foundation
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
use models\main\Member;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeCheckInLog;

/**
 * Interface ISummitAttendeeCheckInLogService
 * @package services\model
 */
interface ISummitAttendeeCheckInLogService
{
    /**
     * Appends a check in/out record for the attendee. Must be called inside the same transaction
     * that changes the attendee check in state and only when the state really changed.
     * Actor, client id, ip and user agent are resolved from the current request context
     * (the actor can be overridden, i.e. badge printing where the requestor is known).
     * @param SummitAttendee $attendee
     * @param string $action one of SummitAttendeeCheckInLog::AllowedActions
     * @param string $source one of SummitAttendeeCheckInLog::AllowedSources
     * @param string|null $reason
     * @param Member|null $actor
     * @return SummitAttendeeCheckInLog
     */
    public function log
    (
        SummitAttendee $attendee,
        string         $action,
        string         $source,
        ?string        $reason = null,
        ?Member        $actor = null
    ): SummitAttendeeCheckInLog;
}
