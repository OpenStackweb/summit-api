<?php namespace App\Audit\ConcreteFormatters\PresentationFormatters;
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

use App\Audit\Interfaces\IAuditStrategy;

/**
 * Many-to-many collection audit for Presentation (sponsors, tags, allowed_ticket_types).
 *
 * Presentation is formatted by BasePresentationAuditLogFormatter subclasses and by
 * PresentationUserSubmissionAuditLogFormatter, so the entity-specific handler lives here
 * and both hosts wire it from their EVENT_COLLECTION_MANYTOMANY_UPDATE / DELETE cases,
 * following the two-step base pipeline (handleManyToManyCollection -> processCollection).
 */
trait FormatsPresentationManyToManyCollections
{
    private function handlePresentationManyToManyCollection(array $change_set, $id, string $title): ?string
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
            ? $this->formatPresentationManyToManyDelete($collectionData, $id, $title)
            : $this->formatPresentationManyToManyUpdate($collectionData, $id, $title);
    }

    private function formatPresentationManyToManyUpdate(array $collectionData, $id, string $title): ?string
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
            "Presentation '%s' (%s) %s (%s) updated: %s by user %s",
            $title,
            $id,
            $collectionData['field'] ?? 'unknown',
            $collectionData['target_entity'] ?? 'unknown',
            implode(', ', $parts),
            $this->getUserInfo()
        );
    }

    private function formatPresentationManyToManyDelete(array $collectionData, $id, string $title): ?string
    {
        $removed_ids = $collectionData['removed_ids'] ?? [];
        if (empty($removed_ids)) {
            return null;
        }

        return sprintf(
            "Presentation '%s' (%s) %s (%s) deleted: Removed IDs: %s by user %s",
            $title,
            $id,
            $collectionData['field'] ?? 'unknown',
            $collectionData['target_entity'] ?? 'unknown',
            json_encode($removed_ids),
            $this->getUserInfo()
        );
    }
}
