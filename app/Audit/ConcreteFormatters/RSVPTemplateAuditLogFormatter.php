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
use App\Models\Foundation\Summit\Events\RSVP\RSVPTemplate;
use Illuminate\Support\Facades\Log;

class RSVPTemplateAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof RSVPTemplate) {
            return null;
        }

        try {
            $title = $subject->getTitle() ?? 'Unknown Template';
            $id = $subject->getId() ?? 'unknown';
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            $is_enabled = $subject->isEnabled() ? 'enabled' : 'disabled';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    $created_by = $subject->getCreatedBy();
                    $created_by_name = $created_by 
                        ? sprintf("%s %s", $created_by->getFirstName() ?? '', $created_by->getLastName() ?? '')
                        : 'Unknown';
                    $created_by_name = trim($created_by_name) ?: 'Unknown';
                    
                    return sprintf(
                        "RSVP Template '%s' (%d) created for Summit '%s', status: %s, template creator: %s by user %s",
                        $title,
                        $id,
                        $summit_name,
                        $is_enabled,
                        $created_by_name,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "RSVP Template '%s' (%d) for Summit '%s' updated: %s by user %s",
                        $title,
                        $id,
                        $summit_name,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    $question_count = $subject->getQuestions()->count();
                    return sprintf(
                        "RSVP Template '%s' (%d) for Summit '%s' with status %s and %d questions was deleted by user %s",
                        $title,
                        $id,
                        $summit_name,
                        $is_enabled,
                        $question_count,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("RSVPTemplateAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
