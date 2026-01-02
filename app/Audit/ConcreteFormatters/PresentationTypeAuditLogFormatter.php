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
use models\summit\PresentationType;
use Illuminate\Support\Facades\Log;

class PresentationTypeAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof PresentationType) {
            return null;
        }

        try {
            $type_name = $subject->getType() ?? 'Unknown Type';
            $id = $subject->getId() ?? 'unknown';
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            $max_speakers = $subject->getMaxSpeakers() ?? 0;
            $use_speakers = $subject->isUseSpeakers() ? 'yes' : 'no';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Presentation Type '%s' (%d) created for Summit '%s' with max speakers %d, use speakers %s by user %s",
                        $type_name,
                        $id,
                        $summit_name,
                        $max_speakers,
                        $use_speakers,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Presentation Type '%s' (%d) for Summit '%s' updated: %s by user %s",
                        $type_name,
                        $id,
                        $summit_name,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Presentation Type '%s' (%d) for Summit '%s' was deleted by user %s",
                        $type_name,
                        $id,
                        $summit_name,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("PresentationTypeAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
