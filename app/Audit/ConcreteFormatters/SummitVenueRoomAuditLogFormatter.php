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
use models\summit\SummitVenueRoom;
use Illuminate\Support\Facades\Log;

class SummitVenueRoomAuditLogFormatter extends AbstractAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitVenueRoom) {
            return null;
        }

        try {
            $name = $subject->getName() ?? 'Unknown Room';
            $id = $subject->getId() ?? 'unknown';
            
            $venue = $subject->getVenue();
            $venue_name = $venue ? ($venue->getName() ?? 'Unknown Venue') : 'Unknown Venue';
            
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Venue Room '%s' (%d) created in Venue '%s' for Summit '%s' by user %s",
                        $name,
                        $id,
                        $venue_name,
                        $summit_name,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Venue Room '%s' (%d) in Venue '%s' for Summit '%s' updated: %s by user %s",
                        $name,
                        $id,
                        $venue_name,
                        $summit_name,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Venue Room '%s' (%d) in Venue '%s' for Summit '%s' was deleted by user %s",
                        $name,
                        $id,
                        $venue_name,
                        $summit_name,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SummitVenueRoomAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
