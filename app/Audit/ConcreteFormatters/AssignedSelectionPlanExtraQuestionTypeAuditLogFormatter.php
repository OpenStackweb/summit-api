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
use App\Models\Foundation\Summit\ExtraQuestions\AssignedSelectionPlanExtraQuestionType;
use Illuminate\Support\Facades\Log;

class AssignedSelectionPlanExtraQuestionTypeAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof AssignedSelectionPlanExtraQuestionType) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    $question_type = $subject->getQuestionType();
                    $question_label = $question_type ? ($question_type->getLabel() ?? 'Unknown Question') : 'Unknown Question';
                    $selection_plan = $subject->getSelectionPlan();
                    $selection_plan_id = $selection_plan ? ($selection_plan->getId() ?? 'unknown') : 'unknown';
                    $selection_plan_name = $selection_plan ? ($selection_plan->getName() ?? 'Unknown') : 'Unknown';
                    $is_editable = $subject->isEditable() ? 'editable' : 'not editable';
                    return sprintf(
                        "Assigned Selection Plan Extra Question (%s) '%s' created for selection plan %s '%s' as %s by user %s",
                        $id,
                        $question_label,
                        $selection_plan_id,
                        $selection_plan_name,
                        $is_editable,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $question_type = $subject->getQuestionType();
                    $question_label = $question_type ? ($question_type->getLabel() ?? 'Unknown Question') : 'Unknown Question';
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Assigned Selection Plan Extra Question (%s) '%s' updated: %s by user %s",
                        $id,
                        $question_label,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    $question_type = $subject->getQuestionType();
                    $question_label = $question_type ? ($question_type->getLabel() ?? 'Unknown Question') : 'Unknown Question';
                    return sprintf(
                        "Assigned Selection Plan Extra Question (%s) '%s' was deleted by user %s",
                        $id,
                        $question_label,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("AssignedSelectionPlanExtraQuestionTypeAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
