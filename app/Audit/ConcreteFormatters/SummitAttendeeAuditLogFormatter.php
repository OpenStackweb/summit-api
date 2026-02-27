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
use models\summit\SummitAttendee;
use Illuminate\Support\Facades\Log;

class SummitAttendeeAuditLogFormatter extends AbstractAuditLogFormatter
{
    public function format($subject, array $change_set): ?string
    {
        if (!$subject instanceof SummitAttendee) {
            return null;
        }

        try {
            $id = $subject->getId() ?? 'unknown';
            $name = trim(($subject->getFirstName() ?? '') . ' ' . ($subject->getSurname() ?? '')) ?: 'Unknown Attendee';

            switch ($this->event_type) {
                case IAuditStrategy::EVENT_ENTITY_CREATION:
                    return sprintf("Attendee (%s) '%s' created by user %s", $id, $name, $this->getUserInfo());

                case IAuditStrategy::EVENT_ENTITY_UPDATE:
                    $details = $this->buildChangeDetails($change_set);
                    return sprintf("Attendee (%s) '%s' updated: %s by user %s", $id, $name, $details, $this->getUserInfo());

                case IAuditStrategy::EVENT_ENTITY_DELETION:
                    return sprintf("Attendee (%s) '%s' deleted by user %s", $id, $name, $this->getUserInfo());

                case IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE:
                    return $this->handleManyToManyCollection($subject, $change_set, $id, $name, false);

                case IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE:
                    return $this->handleManyToManyCollection($subject, $change_set, $id, $name, true);
            }
        } catch (\Exception $ex) {
            Log::warning("SummitAttendeeAuditLogFormatter error: " . $ex->getMessage());
        }

        return null;
    }

    private function handleManyToManyCollection(SummitAttendee $subject, array $change_set, $id, $name, bool $isDeletion): ?string
    {
        if (!isset($change_set['collection']) || !isset($change_set['uow'])) {
            return null;
        }
        
        $col = $change_set['collection'];
        $uow = $change_set['uow'];
        
        $collectionData = $this->processCollection($subject, $col, $uow, $isDeletion);
        if (!$collectionData) {
            return null;
        }
        
        return $isDeletion
            ? $this->formatManyToManyDelete($subject, $collectionData, $id, $name)
            : $this->formatManyToManyUpdate($subject, $collectionData, $id, $name);
    }

    private function formatManyToManyUpdate(SummitAttendee $subject, array $collectionData, $id, $name): ?string
    {
        try {
            $field = $collectionData['field'] ?? 'unknown';
            $targetEntity = $collectionData['target_entity'] ?? 'unknown';
            $added_ids = $collectionData['added_ids'] ?? [];
            $removed_ids = $collectionData['removed_ids'] ?? [];

            $idsPart = '';
            if (!empty($added_ids)) {
                $idsPart .= 'Added IDs: ' . json_encode($added_ids);
            }
            if (!empty($removed_ids)) {
                $idsPart .= (!empty($added_ids) ? ', ' : '') . 'Removed IDs: ' . json_encode($removed_ids);
            }
            if (empty($idsPart)) {
                $idsPart = 'No changes';
            }

            $description = sprintf(
                "Attendee (%s) '%s', Field: %s, Target: %s, %s, by user %s",
                $id,
                $name,
                $field,
                class_basename($targetEntity),
                $idsPart,
                $this->getUserInfo()
            );

            return $description;

        } catch (\Exception $ex) {
            Log::warning("SummitAttendeeAuditLogFormatter::formatManyToManyUpdate error: " . $ex->getMessage());
            return sprintf("Attendee (%s) '%s' association updated by user %s", $id, $name, $this->getUserInfo());
        }
    }

    private function formatManyToManyDelete(SummitAttendee $subject, array $collectionData, $id, $name): ?string
    {
        try {
            $field = $collectionData['field'] ?? 'unknown';
            $targetEntity = $collectionData['target_entity'] ?? 'unknown';
            $removed_ids = $collectionData['removed_ids'] ?? [];

            $description = sprintf(
                "Attendee (%s) '%s' association deleted: Field: %s, Target: %s, Cleared IDs: %s, by user %s",
                $id,
                $name,
                $field,
                class_basename($targetEntity),
                json_encode($removed_ids),
                $this->getUserInfo()
            );

            return $description;

        } catch (\Exception $ex) {
            Log::warning("SummitAttendeeAuditLogFormatter::formatManyToManyDelete error: " . $ex->getMessage());
            return sprintf("Attendee (%s) '%s' association deleted by user %s", $id, $name, $this->getUserInfo());
        }
    }
}
