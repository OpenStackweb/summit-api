<?php

namespace App\Audit\ConcreteFormatters\PresentationFormatters;

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
use models\summit\PresentationSpeakerSummitAssistanceConfirmationRequest;
use Illuminate\Support\Facades\Log;

class PresentationSpeakerSummitAssistanceConfirmationAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof PresentationSpeakerSummitAssistanceConfirmationRequest) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            
            $speaker = $subject->getSpeaker();
            $speaker_name = $speaker ? sprintf("%s %s", $speaker->getFirstName() ?? '', $speaker->getLastName() ?? '') : 'Unknown Speaker';
            $speaker_email = $speaker ? ($speaker->getEmail() ?? 'unknown') : 'unknown';
            $speaker_name = trim($speaker_name) ?: $speaker_email;
            
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Speaker Assistance Confirmation (%d) for '%s' on Summit '%s' created by user %s",
                        $id,
                        $speaker_name,
                        $summit_name,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Speaker Assistance Confirmation (%d) for '%s' on Summit '%s' updated: %s by user %s",
                        $id,
                        $speaker_name,
                        $summit_name,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Speaker Assistance Confirmation (%d) for '%s' on Summit '%s' was deleted by user %s",
                        $id,
                        $speaker_name,
                        $summit_name,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("PresentationSpeakerSummitAssistanceConfirmationAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
