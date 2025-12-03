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
use App\Models\Foundation\Summit\Speakers\FeaturedSpeaker;
use Illuminate\Support\Facades\Log;

class FeaturedSpeakerAuditLogFormatter extends AbstractAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    private function getUserInfo(): string
    {
        if (!$this->ctx) {
            return 'Unknown (unknown)';
        }

        $user_name = 'Unknown';
        if ($this->ctx->userFirstName || $this->ctx->userLastName) {
            $user_name = trim(sprintf("%s %s", $this->ctx->userFirstName ?? '', $this->ctx->userLastName ?? '')) ?: 'Unknown';
        } elseif ($this->ctx->userEmail) {
            $user_name = $this->ctx->userEmail;
        }
        
        $user_id = $this->ctx->userId ?? 'unknown';
        return sprintf("%s (%s)", $user_name, $user_id);
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof FeaturedSpeaker) {
            return null;
        }

        try {
            $speaker = $subject->getSpeaker();
            $speaker_email = $speaker ? ($speaker->getEmail() ?? 'unknown') : 'unknown';
            $speaker_name = $speaker ? sprintf("%s %s", $speaker->getFirstName() ?? '', $speaker->getLastName() ?? '') : 'Unknown';
            $speaker_name = trim($speaker_name) ?: $speaker_name;
            $speaker_id = $speaker ? ($speaker->getId() ?? 'unknown') : 'unknown';
            
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            
            $order = $subject->getOrder();

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Speaker '%s' (%s) added as featured speaker for Summit '%s' with display order %d by user %s",
                        $speaker_name,
                        $speaker_id,
                        $summit_name,
                        $order,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $changed_fields = [];
                    
                    if (isset($change_set['Order'])) {
                        $old_order = $change_set['Order'][0] ?? 'unknown';
                        $new_order = $change_set['Order'][1] ?? 'unknown';
                        $changed_fields[] = sprintf("display_order %s → %s", $old_order, $new_order);
                    }
                    if (isset($change_set['PresentationSpeakerID'])) {
                        $changed_fields[] = "speaker";
                    }
                    
                    $fields_str = !empty($changed_fields) ? implode(', ', $changed_fields) : 'properties';
                    return sprintf(
                        "Featured speaker '%s' (%s) updated (%s changed) by user %s",
                        $speaker_name,
                        $speaker_id,
                        $fields_str,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Speaker '%s' (%s) removed from featured speakers list of Summit '%s' by user %s",
                        $speaker_name,
                        $speaker_id,
                        $summit_name,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("FeaturedSpeakerAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
