<?php

namespace App\Audit\ConcreteFormatters;

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

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use App\Models\Foundation\Summit\Locations\Banners\ScheduledSummitLocationBanner;
use Illuminate\Support\Facades\Log;

class ScheduledSummitLocationBannerAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof ScheduledSummitLocationBanner) {
            return null;
        }

        try {
            $title = $subject->getTitle() ?? 'Unknown Banner';
            $id = $subject->getId() ?? 0;
            
            $location = $subject->getLocation();
            $location_name = $location ? ($location->getName() ?? 'Unknown Location') : 'Unknown Location';
            
            $summit = $location ? ($location->getSummit() ? ($location->getSummit()->getName() ?? 'Unknown Summit') : 'Unknown Summit') : 'Unknown Summit';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Scheduled Location Banner '%s' (%d) created for Location '%s' in Summit '%s' by user %s",
                        $title,
                        $id,
                        $location_name,
                        $summit,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Scheduled Location Banner '%s' (%d) for Location '%s' in Summit '%s' updated: %s by user %s",
                        $title,
                        $id,
                        $location_name,
                        $summit,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Scheduled Location Banner '%s' (%d) for Location '%s' in Summit '%s' was deleted by user %s",
                        $title,
                        $id,
                        $location_name,
                        $summit,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("ScheduledSummitLocationBannerAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
