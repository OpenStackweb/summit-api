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
use App\Models\Foundation\Summit\SelectionPlan;
use Illuminate\Support\Facades\Log;

class SelectionPlanAuditLogFormatter extends AbstractAuditLogFormatter
{
    private string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SelectionPlan) {
            return null;
        }

        try {
            $name = $subject->getName() ?? 'Unknown Plan';
            $id = $subject->getId() ?? 'unknown';
            $summit = $subject->getSummit();
            $summit_name = $summit ? $summit->getName() : 'Unknown Summit';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    $submission_dates = $subject->hasSubmissionPeriodDefined()
                        ? sprintf(
                            "[%s - %s]",
                            $subject->getSubmissionBeginDate()?->format('Y-m-d H:i:s') ?? 'N/A',
                            $subject->getSubmissionEndDate()?->format('Y-m-d H:i:s') ?? 'N/A'
                        )
                        : 'No dates set';
                    
                    $selection_dates = $subject->hasSelectionPeriodDefined()
                        ? sprintf(
                            "[%s - %s]",
                            $subject->getSelectionBeginDate()?->format('Y-m-d H:i:s') ?? 'N/A',
                            $subject->getSelectionEndDate()?->format('Y-m-d H:i:s') ?? 'N/A'
                        )
                        : 'No dates set';
                    
                    return sprintf(
                        "Selection Plan '%s' (%d) created for Summit '%s' with CFP period: %s, selection period: %s by user %s",
                        $name,
                        $id,
                        $summit_name,
                        $submission_dates,
                        $selection_dates,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Selection Plan '%s' (%d) for Summit '%s' updated: %s by user %s",
                        $name,
                        $id,
                        $summit_name,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Selection Plan '%s' (%d) for Summit '%s' was deleted by user %s",
                        $name,
                        $id,
                        $summit_name,
                        $this->getUserInfo()
                    );
            }
        } catch (\Exception $ex) {
            Log::warning("SelectionPlanAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }
}
