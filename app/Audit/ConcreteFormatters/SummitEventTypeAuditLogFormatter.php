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
use models\summit\SummitEventType;
use Illuminate\Support\Facades\Log;

class SummitEventTypeAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitEventType) {
            return null;
        }

        try {
            $type = $subject->getType() ?? 'Unknown Type';
            $id = $subject->getId() ?? 'unknown';
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            $color = $subject->getColor() ?? 'N/A';
            $is_default = $subject->isDefault() ? 'yes' : 'no';
            $is_private = $subject->isPrivate() ? 'yes' : 'no';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:

                    return sprintf(
                        "Event Type '%s' (%d) created for Summit '%s' with color '%s', default: %s, private: %s by user %s",
                        $type,
                        $id,
                        $summit_name,
                        $color,
                        $is_default,
                        $is_private,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Event Type '%s' (%d) for Summit '%s' updated: %s by user %s",
                        $type,
                        $id,
                        $summit_name,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Event Type '%s' (%d) for Summit '%s' with color '%s' was deleted by user %s",
                        $type,
                        $id,
                        $summit_name,
                        $color,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SummitEventTypeAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
