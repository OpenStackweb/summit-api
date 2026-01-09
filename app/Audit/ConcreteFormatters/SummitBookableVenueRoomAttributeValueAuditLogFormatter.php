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
use models\summit\SummitBookableVenueRoomAttributeValue;
use Illuminate\Support\Facades\Log;

class SummitBookableVenueRoomAttributeValueAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitBookableVenueRoomAttributeValue) {
            return null;
        }

        try {
            $value = $subject->getValue() ?? 'Unknown Value';
            $type = $subject->getType();
            $type_name = $type ? ($type->getType() ?? 'Unknown Type') : 'Unknown Type';
            $id = $subject->getId() ?? 'unknown';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Summit Bookable Venue Room Attribute Value (%d) '%s' Type '%s' created by user %s",
                        $id,
                        $value,
                        $type_name,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Summit Bookable Venue Room Attribute Value (%d) '%s' updated: %s by user %s",
                        $id,
                        $value,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Summit Bookable Venue Room Attribute Value (%d) '%s' was deleted by user %s",
                        $id,
                        $value,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SummitBookableVenueRoomAttributeValueAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
