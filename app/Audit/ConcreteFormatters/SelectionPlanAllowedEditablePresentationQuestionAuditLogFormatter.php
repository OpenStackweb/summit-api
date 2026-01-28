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
use App\Models\Foundation\Summit\SelectionPlanAllowedEditablePresentationQuestion;
use Illuminate\Support\Facades\Log;

class SelectionPlanAllowedEditablePresentationQuestionAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SelectionPlanAllowedEditablePresentationQuestion) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $type = $subject->getType() ?? 'Unknown Question Type';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    $selection_plan = $subject->getSelectionPlan();
                    $selection_plan_id = $selection_plan ? ($selection_plan->getId() ?? 'unknown') : 'unknown';
                    $selection_plan_name = $selection_plan ? ($selection_plan->getName() ?? 'Unknown') : 'Unknown';
                    return sprintf(
                        "Selection Plan Allowed Editable Presentation Question (%s) type '%s' created for selection plan %s '%s' by user %s",
                        $id,
                        $type,
                        $selection_plan_id,
                        $selection_plan_name,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Selection Plan Allowed Editable Presentation Question (%s) type '%s' updated: %s by user %s",
                        $id,
                        $type,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Selection Plan Allowed Editable Presentation Question (%s) type '%s' was deleted by user %s",
                        $id,
                        $type,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SelectionPlanAllowedEditablePresentationQuestionAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
