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
use models\summit\SummitSubmissionInvitation;
use Illuminate\Support\Facades\Log;

class SubmissionInvitationAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitSubmissionInvitation) {
            return null;
        }

        try {
            $email = $subject->getEmail() ?? 'unknown';
            $first_name = $subject->getFirstName() ?? 'Unknown';
            $last_name = $subject->getLastName() ?? '';
            $full_name = trim(sprintf("%s %s", $first_name, $last_name)) ?: 'Unknown';
            $is_sent = $subject->isSent();
            $speaker = $subject->getSpeaker();
            $speaker_name = $speaker ? sprintf("%s %s", $speaker->getFirstName() ?? '', $speaker->getLastName() ?? '') : 'None';
            $speaker_name = trim($speaker_name) ?: 'None';
            $id = $subject->getId() ?? 'unknown';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    $sent_status = $is_sent ? 'sent' : 'not sent';
                    return sprintf(
                        "Submission invitation created for '%s' (%s) with email '%s' [status: %s] by user %s",
                        $full_name,
                        $id,
                        $email,
                        $sent_status,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Submission invitation for '%s' (%s) updated: %s by user %s",
                        $email,
                        $id,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    $sent_status = $is_sent ? 'sent' : 'pending';
                    return sprintf(
                        "Submission invitation for '%s' (%s) with email '%s' [status: %s] was deleted by user %s",
                        $full_name,
                        $id,
                        $email,
                        $sent_status,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SubmissionInvitationAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
