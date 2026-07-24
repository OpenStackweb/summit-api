<?php

namespace App\Audit\ConcreteFormatters;

/**
 * Copyright 2025 OpenStack Foundation
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

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Illuminate\Support\Facades\Log;
use App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation;

class RSVPInvitationAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof RSVPInvitation) {
            return null;
        }

        try {
            $attendeeEmail = $subject->getInvitee()?->getEmail() ?? 'Unknown';
            $attendeeId = $subject->getInvitee()?->getId() ?? 'unknown';
            $eventTitle = $subject->getEvent()?->getTitle() ?? 'Unknown Event';
            $eventId = $subject->getEvent()?->getId() ?? 'unknown';
            $id = $subject->getId() ?? 'unknown';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "RSVP invitation created for attendee '%s' (ID: %s) to event '%s' (ID: %s) by user %s",
                        $attendeeEmail,
                        $attendeeId,
                        $eventTitle,
                        $eventId,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "RSVP invitation (ID: %s) for attendee '%s' to event '%s' updated: %s by user %s",
                        $id,
                        $attendeeEmail,
                        $eventTitle,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "RSVP invitation (ID: %s) deleted for attendee '%s' (ID: %s) to event '%s' (ID: %s) by user %s",
                        $id,
                        $attendeeEmail,
                        $attendeeId,
                        $eventTitle,
                        $eventId,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("RSVPInvitationAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
