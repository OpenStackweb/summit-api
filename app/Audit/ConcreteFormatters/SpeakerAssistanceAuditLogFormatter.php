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
use models\summit\PresentationSpeakerSummitAssistanceConfirmationRequest;
use Illuminate\Support\Facades\Log;

class SpeakerAssistanceAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof PresentationSpeakerSummitAssistanceConfirmationRequest) {
            return null;
        }

        try {
            $speaker = $subject->getSpeaker();
            $speaker_name = $speaker ? sprintf("%s %s", $speaker->getFirstName() ?? '', $speaker->getLastName() ?? '') : 'Unknown';
            $speaker_email = $speaker ? ($speaker->getEmail() ?? 'unknown') : 'unknown';
            $speaker_name = trim($speaker_name) ?: $speaker_email;
            $speaker_id = $speaker ? ($speaker->getId() ?? 'unknown') : 'unknown';
            
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            
            $is_confirmed = $subject->isConfirmed();
            $is_registered = $subject->isRegistered();

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    $status = $is_confirmed ? 'confirmed' : 'pending';
                    $registration_status = $is_registered ? 'registered' : 'unregistered';
                    return sprintf(
                        "Speaker assistance created for '%s' (%s) on Summit '%s' [confirmation: %s, registration: %s] by user %s",
                        $speaker_name,
                        $speaker_id,
                        $summit_name,
                        $status,
                        $registration_status,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Speaker assistance for '%s' (%s) on Summit '%s' updated: %s by user %s",
                        $speaker_name,
                        $speaker_id,
                        $summit_name,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    $status = $is_confirmed ? 'confirmed' : 'pending';
                    $registration_status = $is_registered ? 'registered' : 'unregistered';
                    return sprintf(
                        "Speaker assistance for '%s' (%s) on Summit '%s' [confirmation: %s, registration: %s] was deleted by user %s",
                        $speaker_name,
                        $speaker_id,
                        $summit_name,
                        $status,
                        $registration_status,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SpeakerAssistanceAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
