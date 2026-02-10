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
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeValue;
use Illuminate\Support\Facades\Log;

class ExtraQuestionTypeValueAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof ExtraQuestionTypeValue) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $label = $subject->getLabel() ?? 'Unknown Value';
            $question = $subject->getQuestion();
            $question_label = $question ? ($question->getLabel() ?? 'Unknown Question') : 'Unknown Question';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf("Extra Question Value '%s' (%s) for Question '%s' created by user %s", $label, $id, $question_label, $this->getUserInfo());
                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf("Extra Question Value '%s' (%s) for Question '%s' updated: %s by user %s", $label, $id, $question_label, $details, $this->getUserInfo());
                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf("Extra Question Value '%s' (%s) for Question '%s' deleted by user %s", $label, $id, $question_label, $this->getUserInfo());
            }
        } catch (\Exception $ex) {
            Log::warning("ExtraQuestionTypeValueAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
