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
use models\summit\SummitEvent;
use Illuminate\Support\Facades\Log;

class SummitEventAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitEvent) {
            return null;
        }

        try {
            $title = $subject->getTitle() ?? 'Unknown Event';
            $id = $subject->getId() ?? 'unknown';
            $summit = $subject->getSummit();
            $summit_name = $summit ? ($summit->getName() ?? 'Unknown Summit') : 'Unknown Summit';
            
            
            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf(
                        "Summit Event '%s' (%d) created for Summit '%s' by user %s",
                        $title,
                        $id,
                        $summit_name,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    return $this->formatUpdateMessage($change_set, fn($change_details) => sprintf(
                        "Summit Event '%s' (%d) for Summit '%s' updated: %s by user %s",
                        $title,
                        $id,
                        $summit_name,
                        $change_details,
                        $this->getUserInfo()
                    ));

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf(
                        "Summit Event '%s' (%d) for Summit '%s' was deleted by user %s",
                        $title,
                        $id,
                        $summit_name,
                        $this->getUserInfo()
                    );

                case IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE:
                case IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE:
                    return $this->handleEventManyToManyCollection($change_set, $id, $title, $summit_name);
            }
        } catch (\Exception $ex) {
            Log::warning("SummitEventAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }

    /**
     * sponsors, tags and allowed_ticket_types; two-step pipeline per the M2M audit ADR.
     */
    private function handleEventManyToManyCollection(array $change_set, $id, string $title, string $summit_name): ?string
    {
        $metadata = $this->handleManyToManyCollection($change_set);
        if ($metadata === null) {
            return null;
        }

        $collectionData = $this->processCollection($metadata);
        if (!$collectionData) {
            return null;
        }

        return $this->event_type === IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE
            ? $this->formatManyToManyDelete($collectionData, $id, $title, $summit_name)
            : $this->formatManyToManyUpdate($collectionData, $id, $title, $summit_name);
    }

    private function formatManyToManyUpdate(array $collectionData, $id, string $title, string $summit_name): ?string
    {
        $added_ids = $collectionData['added_ids'] ?? [];
        $removed_ids = $collectionData['removed_ids'] ?? [];

        $parts = [];
        if (!empty($added_ids)) {
            $parts[] = 'Added IDs: ' . json_encode($added_ids);
        }
        if (!empty($removed_ids)) {
            $parts[] = 'Removed IDs: ' . json_encode($removed_ids);
        }
        if (empty($parts)) {
            return null;
        }

        return sprintf(
            "Summit Event '%s' (%s) for Summit '%s' %s (%s) updated: %s by user %s",
            $title,
            $id,
            $summit_name,
            $collectionData['field'] ?? 'unknown',
            $collectionData['target_entity'] ?? 'unknown',
            implode(', ', $parts),
            $this->getUserInfo()
        );
    }

    private function formatManyToManyDelete(array $collectionData, $id, string $title, string $summit_name): ?string
    {
        $removed_ids = $collectionData['removed_ids'] ?? [];
        if (empty($removed_ids)) {
            return null;
        }

        return sprintf(
            "Summit Event '%s' (%s) for Summit '%s' %s (%s) deleted: Removed IDs: %s by user %s",
            $title,
            $id,
            $summit_name,
            $collectionData['field'] ?? 'unknown',
            $collectionData['target_entity'] ?? 'unknown',
            json_encode($removed_ids),
            $this->getUserInfo()
        );
    }
}
