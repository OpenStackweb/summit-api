<?php

namespace App\Audit\ConcreteFormatters;

/**
 * Copyright 2026 OpenStack Foundation
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
use models\summit\SummitOrderExtraQuestionType;
use Illuminate\Support\Facades\Log;

class SummitOrderExtraQuestionTypeAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitOrderExtraQuestionType) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $label = $subject->getLabel() ?? 'Unknown Question';
            $question_type = $subject->getType() ?? 'Unknown Type';
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf("Order Extra Question '%s' (%s) of type '%s' created in Summit '%s' by user %s", $label, $id, $question_type, $summit_name, $this->getUserInfo());
                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf("Order Extra Question '%s' (%s) in Summit '%s' updated: %s by user %s", $label, $id, $summit_name, $details, $this->getUserInfo());
                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf("Order Extra Question '%s' (%s) in Summit '%s' deleted by user %s", $label, $id, $summit_name, $this->getUserInfo());
            }
        } catch (\Exception $ex) {
            Log::warning("SummitOrderExtraQuestionTypeAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
