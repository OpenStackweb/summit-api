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
use models\summit\RSVP;


class RSVPAuditLogFormatter extends AbstractAuditLogFormatter
{
    /**
     * {@inheritDoc}
     */
    public function format($subject, array $change_set): ?string
    {
        // Validar que es una entidad RSVP
        if (!$subject instanceof RSVP) {
            return null;
        }

        try {
            $eventTitle = $subject->getEvent()?->getTitle() ?? 'Unknown Event';
            $ownerEmail = $subject->getOwner()?->getEmail() ?? 'Unknown Member';
            $ownerId = $subject->getOwner()?->getId() ?? 'unknown';
            $id = $subject->getId() ?? 'unknown';
            $status = $subject->getStatus() ?? 'Unknown';
            $seatType = $subject->getSeatType() ?? 'Unknown';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "RSVP created for event '%s' (ID: %s) by member %s (ID: %s) - Status: %s, Seat Type: %s, by user %s",
                        $eventTitle,
                        $subject->getEvent()?->getId() ?? 'unknown',
                        $ownerEmail,
                        $ownerId,
                        $status,
                        $seatType,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "RSVP (ID: %s) for event '%s' updated: %s by user %s",
                        $id,
                        $eventTitle,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "RSVP (ID: %s) deleted for event '%s' by member %s (ID: %s) - Final Status: %s, Seat Type: %s by user %s",
                        $id,
                        $eventTitle,
                        $ownerEmail,
                        $ownerId,
                        $status,
                        $seatType,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("RSVPAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
