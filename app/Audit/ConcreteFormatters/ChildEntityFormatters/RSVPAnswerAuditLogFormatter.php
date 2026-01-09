<?php

namespace App\Audit\ConcreteFormatters\ChildEntityFormatters;

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

use models\summit\RSVPAnswer;

class RSVPAnswerAuditLogFormatter implements IChildEntityAuditLogFormatter
{
    public function format($subject, string $child_entity_action_type, ?string $additional_info = ""): ?string
    {
        if (!$subject instanceof RSVPAnswer) {
            return null;
        }

        try {
            $questionId = $subject->getQuestionId();
            $value = $subject->getValue();
            $question = $subject->getQuestion();
            $questionLabel = $question?->getLabel() ?? sprintf("Question #%d", $questionId);

            switch ($child_entity_action_type) {
                case IChildEntityAuditLogFormatter::CHILD_ENTITY_CREATION:
                    return sprintf(
                        "RSVP Answer added for question '%s' with value '%s'",
                        $questionLabel,
                        $value
                    );

                case IChildEntityAuditLogFormatter::CHILD_ENTITY_UPDATE:
                    if (!empty($additional_info)) {
                        return sprintf(
                            "RSVP Answer for question '%s' updated: %s",
                            $questionLabel,
                            $additional_info
                        );
                    }
                    return sprintf(
                        "RSVP Answer for question '%s' updated to '%s'",
                        $questionLabel,
                        $value
                    );

                case IChildEntityAuditLogFormatter::CHILD_ENTITY_DELETION:
                    return sprintf(
                        "RSVP Answer removed for question '%s' (had value '%s')",
                        $questionLabel,
                        $value
                    );
            }
        } catch (\Exception $ex) {
            return null;
        }

        return null;
    }
}
