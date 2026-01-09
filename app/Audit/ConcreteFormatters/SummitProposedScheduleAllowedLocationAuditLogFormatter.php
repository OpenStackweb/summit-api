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
use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleAllowedLocation;
use Illuminate\Support\Facades\Log;

class SummitProposedScheduleAllowedLocationAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitProposedScheduleAllowedLocation) {
            return null;
        }

        try {
            $track = $subject->getTrack();
            $track_name = $track ? ($track->getTitle() ?? 'Unknown Track') : 'Unknown Track';
            $location = $subject->getLocation();
            $location_name = $location ? ($location->getName() ?? 'Unknown Location') : 'Unknown Location';
            $id = $subject->getId() ?? 'unknown';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Proposed Schedule Allowed Location (%d) Track '%s' Location '%s' created by user %s",
                        $id,
                        $track_name,
                        $location_name,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Proposed Schedule Allowed Location (%d) Track '%s' Location '%s' updated: %s by user %s",
                        $id,
                        $track_name,
                        $location_name,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Proposed Schedule Allowed Location (%d) Track '%s' Location '%s' was deleted by user %s",
                        $id,
                        $track_name,
                        $location_name,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SummitProposedScheduleAllowedLocationAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
