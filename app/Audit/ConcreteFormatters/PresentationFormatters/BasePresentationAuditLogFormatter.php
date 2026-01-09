<?php

namespace App\Audit\ConcreteFormatters\PresentationFormatters;

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
use models\summit\Presentation;
use Illuminate\Support\Facades\Log;

abstract class BasePresentationAuditLogFormatter extends AbstractAuditLogFormatter
{
    protected string $event_type;

    public function __construct(string $event_type)
    {
        $this->event_type = $event_type;
    }

    protected function extractChangedFields(array $change_set): array
    {
        $changed_fields = [];
        $old_status = null;
        $new_status = null;

        if (isset($change_set['Title'])) {
            $changed_fields[] = "title";
        }
        if (isset($change_set['Abstract'])) {
            $changed_fields[] = "abstract";
        }
        if (isset($change_set['ProblemAddressed'])) {
            $changed_fields[] = "problem_addressed";
        }
        if (isset($change_set['AttendeesExpectedLearnt'])) {
            $changed_fields[] = "attendees_expected_learnt";
        }
        
        if (isset($change_set['Status'])) {
            $changed_fields[] = "status";
            $old_status = $change_set['Status'][0] ?? null;
            $new_status = $change_set['Status'][1] ?? null;
        }
        if (isset($change_set['CategoryID']) || isset($change_set['category'])) {
            $changed_fields[] = "track";
        }
        if (isset($change_set['Published'])) {
            $changed_fields[] = "published";
        }
        if (isset($change_set['SelectionPlanID'])) {
            $changed_fields[] = "selection_plan";
        }

        return [
            'fields' => !empty($changed_fields) ? implode(', ', $changed_fields) : 'properties',
            'old_status' => $old_status,
            'new_status' => $new_status,
        ];
    }

    protected function getPresentationData(Presentation $subject): array
    {
        $creator = $subject->getCreator();
        $creator_name = $creator ? sprintf("%s %s", $creator->getFirstName() ?? '', $creator->getLastName() ?? '') : 'Unknown';
        $creator_name = trim($creator_name) ?: 'Unknown';
        
        $category = $subject->getCategory();
        $category_name = $category ? $category->getTitle() : 'Unassigned Track';
        
        $selection_plan = $subject->getSelectionPlan();
        $plan_name = $selection_plan ? $selection_plan->getName() : 'Unknown Plan';

        return [
            'title' => $subject->getTitle() ?? 'Unknown Presentation',
            'id' => $subject->getId() ?? 'unknown',
            'creator_name' => $creator_name,
            'category_name' => $category_name,
            'plan_name' => $plan_name,
        ];
    }

    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof Presentation) {
            return null;
        }

        try {
            $data = $this->getPresentationData($subject);

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return $this->formatCreation($data);

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $extracted = $this->extractChangedFields($change_set);
                    $extracted['change_set'] = $change_set;
                    return $this->formatUpdate($data, $extracted);

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return $this->formatDeletion($data);
            }
        } catch (\Exception $ex) {
            Log::warning(static::class . " error: " . $ex->getMessage());
        }

        return null;
    }

    abstract protected function formatCreation(array $data): string;

    abstract protected function formatUpdate(array $data, array $extracted): string;

    abstract protected function formatDeletion(array $data): string;
}
