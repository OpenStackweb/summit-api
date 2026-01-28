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
    public function __construct(string $event_type)
    {
        parent::__construct($event_type);
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

    public function format(mixed $subject, array $change_set): ?string
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
                    return $this->formatUpdate($data, $change_set);

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return $this->formatDeletion($data);
            }
        } catch (\Exception $ex) {
            Log::warning(static::class . " error: " . $ex->getMessage());
        }

        return null;
    }

    abstract protected function formatCreation(array $data): string;

    abstract protected function formatUpdate(array $data, array $change_set): string;

    abstract protected function formatDeletion(array $data): string;
}
