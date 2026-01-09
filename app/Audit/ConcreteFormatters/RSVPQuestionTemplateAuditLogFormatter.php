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
use Illuminate\Support\Facades\Log;
use App\Models\Foundation\Summit\Events\RSVP\RSVPQuestionTemplate;

class RSVPQuestionTemplateAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof RSVPQuestionTemplate) {
            return null;
        }

        try {
            $name = $subject->getName() ?? 'Unknown';
            $label = $subject->getLabel() ?? 'Unknown';
            $templateId = $subject->getTemplate()?->getId() ?? 'unknown';
            $id = $subject->getId() ?? 'unknown';
            $className = class_basename($subject);

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    $isMandatory = $subject->isMandatory() ? 'required' : 'optional';
                    $order = $subject->getOrder() ?? 0;
                    return sprintf(
                        "%s question '%s' (label: '%s') created in RSVP template (ID: %s) - Position: %d, Type: %s by user %s",
                        $className,
                        $name,
                        $label,
                        $templateId,
                        $order,
                        $isMandatory,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "%s question '%s' (ID: %s) in RSVP template (ID: %s) updated: %s by user %s",
                        $className,
                        $label,
                        $id,
                        $templateId,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    $order = $subject->getOrder() ?? 0;
                    return sprintf(
                        "%s question '%s' (ID: %s, label: '%s') deleted from RSVP template (ID: %s) - Was at position %d by user %s",
                        $className,
                        $name,
                        $id,
                        $label,
                        $templateId,
                        $order,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("RSVPQuestionTemplateAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
