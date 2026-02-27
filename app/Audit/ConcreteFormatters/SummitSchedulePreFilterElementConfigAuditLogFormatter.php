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
use models\summit\SummitSchedulePreFilterElementConfig;
use Illuminate\Support\Facades\Log;

class SummitSchedulePreFilterElementConfigAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitSchedulePreFilterElementConfig) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $type = $subject->getType() ?? 'Unknown Type';
            
            $config_key = $subject->hasConfig() ? ($subject->getConfig()->getKey() ?? 'Unknown Config') : 'Unknown Config';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Schedule Pre-Filter Element Config (%s) created for Config '%s' with type '%s' by user %s",
                        $id,
                        $config_key,
                        $type,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Schedule Pre-Filter Element Config (%s) for Config '%s' updated: %s by user %s",
                        $id,
                        $config_key,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Schedule Pre-Filter Element Config (%s) for Config '%s' was deleted by user %s",
                        $id,
                        $config_key,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SummitSchedulePreFilterElementConfigAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
