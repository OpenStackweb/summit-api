<?php

namespace App\Audit\ConcreteFormatters\ChildEntityFormatters;

/**
 * Copyright 2023 OpenStack Foundation
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


/**
 * Class PresentationSpeakerAssignmentAuditLogFormatter
 * @package App\Audit\ConcreteFormatters
 */
class PresentationSpeakerAssignmentAuditLogFormatter implements IChildEntityAuditLogFormatter
{
    /**
     * @inheritDoc
     */
    public function format($subject, string $child_entity_action_type, ?string $additional_info = ""): ?string {
        switch ($child_entity_action_type) {
            case IChildEntityAuditLogFormatter::CHILD_ENTITY_CREATION:
                return "Speaker {$subject->getSpeaker()->getEmail()} was added to the collection";
            case IChildEntityAuditLogFormatter::CHILD_ENTITY_DELETION:
                return "Speaker {$subject->getSpeaker()->getEmail()} was removed from the collection";
        }
        return "";
    }
}