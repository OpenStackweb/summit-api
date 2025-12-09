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
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

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
                    $changed_fields = [];
                    
                    if (isset($change_set['FirstName']) || isset($change_set['LastName'])) {
                        $changed_fields[] = "name";
                    }
                    if (isset($change_set['Email'])) {
                        $old_email = $change_set['Email'][0] ?? 'N/A';
                        $new_email = $change_set['Email'][1] ?? 'N/A';
                        $changed_fields[] = sprintf("email %s to %s", $old_email, $new_email);
                    }
                    if (isset($change_set['SentDate'])) {
                        $old_status = isset($change_set['SentDate'][0]) && $change_set['SentDate'][0] ? 'sent' : 'not sent';
                        $new_status = isset($change_set['SentDate'][1]) && $change_set['SentDate'][1] ? 'sent' : 'not sent';
                        $changed_fields[] = sprintf("status %s to %s", $old_status, $new_status);
                    }
                    if (isset($change_set['SpeakerID'])) {
                        $changed_fields[] = "speaker";
                    }
                    
                    $fields_str = !empty($changed_fields) ? implode(', ', $changed_fields) : 'properties';
                    return sprintf(
                        "Submission invitation for '%s' (%s) updated (%s changed) by user %s",
                        $email,
                        $id,
                        $fields_str,
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
