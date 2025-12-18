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
use models\summit\SummitEventAttendanceMetric;
use Illuminate\Support\Facades\Log;

class SummitEventAttendanceMetricAuditLogFormatter extends AbstractAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitEventAttendanceMetric) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            
            $event = $subject->getEvent();
            $event_title = $event ? ($event->getTitle() ?? 'Unknown Event') : 'Unknown Event';
            
            $member = $subject->getMember();
            $member_name = $member ? sprintf("%s %s", $member->getFirstName(), $member->getLastName()) : 'Unknown Member';
            

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Attendance Metric (%d) for member '%s' at event '%s' created by user %s",
                        $id,
                        $member_name,
                        $event_title,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Attendance Metric (%d) for event '%s' updated: %s by user %s",
                        $id,
                        $event_title,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Attendance Metric (%d) for member '%s' at event '%s' was deleted by user %s",
                        $id,
                        $member_name,
                        $event_title,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SummitEventAttendanceMetricAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
