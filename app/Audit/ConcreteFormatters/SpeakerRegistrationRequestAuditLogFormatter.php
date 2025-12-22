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
use models\summit\SpeakerRegistrationRequest;
use Illuminate\Support\Facades\Log;

class SpeakerRegistrationRequestAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SpeakerRegistrationRequest) {
            return null;
        }

        try {
            $email = $subject->getEmail() ?? 'unknown';
            $speaker = $subject->getSpeaker();
            $speaker_name = $speaker ? sprintf("%s %s", $speaker->getFirstName() ?? '', $speaker->getLastName() ?? '') : 'Unknown';
            $speaker_name = trim($speaker_name) ?: 'Unknown';
            $is_confirmed = $subject->isConfirmed();

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Speaker registration request created for email '%s' by user %s",
                        $email,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Speaker registration request for '%s' updated: %s by user %s",
                        $email,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    $status = $is_confirmed ? 'confirmed' : 'pending';
                    return sprintf(
                        "Speaker registration request for email '%s' (status: %s) was deleted by user %s",
                        $email,
                        $status,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SpeakerRegistrationRequestAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
