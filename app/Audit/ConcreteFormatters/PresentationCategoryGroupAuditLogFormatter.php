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
use models\summit\PresentationCategoryGroup;
use Illuminate\Support\Facades\Log;

class PresentationCategoryGroupAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof PresentationCategoryGroup) {
            return null;
        }

        try {
            $name = $subject->getName() ?? 'Unknown Track Group';
            $id = $subject->getId() ?? 'unknown';
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            $color = $subject->getColor() ?? 'N/A';
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:

                    return sprintf(
                        "Track Group (PresentationCategoryGroup) '%s' (%s) created for Summit '%s' with color '%s', max votes: %d by user %s",
                        $name,
                        $id,
                        $summit_name,
                        $color,
                        $subject->getMaxAttendeeVotes(),
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $change_details = $this->buildChangeDetails($change_set);
                    return sprintf(
                        "Track Group (PresentationCategoryGroup) '%s' (%s) for Summit '%s' updated: %s by user %s",
                        $name,
                        $id,
                        $summit_name,
                        $change_details,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Track Group (PresentationCategoryGroup) '%s' (%s) for Summit '%s' with color '%s' was deleted by user %s",
                        $name,
                        $id,
                        $summit_name,
                        $color,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE:
                case IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE:
                    return $this->handleCategoryGroupManyToManyCollection($change_set, $id, $name, $summit_name);
            }
        } catch (\Exception $ex) {
            Log::warning("PresentationCategoryGroupAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }

    private function handleCategoryGroupManyToManyCollection(array $change_set, $id, $name, $summit_name): ?string
    {
        $metadata = $this->handleManyToManyCollection($change_set);
        if ($metadata === null) {
            return null;
        }

        $collectionData = $this->processCollection($metadata);
        if ($collectionData === null) {
            return null;
        }

        return $this->event_type === IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE
            ? $this->formatManyToManyDelete($collectionData, $id, $name, $summit_name)
            : $this->formatManyToManyUpdate($collectionData, $id, $name, $summit_name);
    }

    private function formatManyToManyUpdate(array $collectionData, $id, $name, $summit_name): string
    {
        $field = $collectionData['field'] ?? 'unknown';
        $targetEntity = $collectionData['target_entity'] ?? 'unknown';
        $addedIds = $collectionData['added_ids'] ?? [];
        $removedIds = $collectionData['removed_ids'] ?? [];

        return sprintf(
            "Track Group (PresentationCategoryGroup) '%s' (%s) for Summit '%s' updated M2M (%s to %s): Added IDs: %s, Removed IDs: %s by user %s",
            $name,
            $id,
            $summit_name,
            $field,
            $targetEntity,
            json_encode($addedIds),
            json_encode($removedIds),
            $this->getUserInfo()
        );
    }

    private function formatManyToManyDelete(array $collectionData, $id, $name, $summit_name): string
    {
        $field = $collectionData['field'] ?? 'unknown';
        $targetEntity = $collectionData['target_entity'] ?? 'unknown';
        $removedIds = $collectionData['removed_ids'] ?? [];

        return sprintf(
            "Track Group (PresentationCategoryGroup) '%s' (%s) for Summit '%s' deleted M2M (%s to %s): Removed IDs: %s by user %s",
            $name,
            $id,
            $summit_name,
            $field,
            $targetEntity,
            json_encode($removedIds),
            $this->getUserInfo()
        );
    }
}
