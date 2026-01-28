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
use models\summit\SummitBookableVenueRoomAttributeType;
use Illuminate\Support\Facades\Log;

class SummitBookableVenueRoomAttributeTypeAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitBookableVenueRoomAttributeType) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $type = $subject->getType() ?? 'Unknown Type';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    $summit_id = $subject->getSummitId() ?? 'unknown';
                    return sprintf(
                        "Summit Bookable Venue Room Attribute Type (%s) '%s' created for summit %s by user %s",
                        $id,
                        $type,
                        $summit_id,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Summit Bookable Venue Room Attribute Type (%s) '%s' updated: %s by user %s",
                        $id,
                        $type,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Summit Bookable Venue Room Attribute Type (%s) '%s' was deleted by user %s",
                        $id,
                        $type,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SummitBookableVenueRoomAttributeTypeAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
