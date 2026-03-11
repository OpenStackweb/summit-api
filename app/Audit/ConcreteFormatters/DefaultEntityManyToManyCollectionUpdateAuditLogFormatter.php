<?php

namespace App\Audit\ConcreteFormatters;

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\ConcreteFormatters\ChildEntityFormatters\IChildEntityAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Doctrine\ORM\PersistentCollection;
use Illuminate\Support\Facades\Log;

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

/**
 * Formatter for Many-to-Many collection updates
 */
class DefaultEntityManyToManyCollectionUpdateAuditLogFormatter extends AbstractAuditLogFormatter
{
    /**
     * @var IChildEntityAuditLogFormatter|null
     */
    private $child_entity_formatter;

    public function __construct(mixed $child_entity_formatter = null)
    {
        parent::__construct(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);
        $this->child_entity_formatter = $child_entity_formatter;
    }

    /**
     * @inheritDoc
     */
    public function format($subject, array $change_set): ?string
    {
        try {
          
            $collection = is_array($change_set) && isset($change_set['collection']) 
                ? $change_set['collection'] 
                : null;

            if ($collection === null) {
                return null;
            }

            $addedIds = $change_set['added_ids'] ?? null;
            $removedIds = $change_set['removed_ids'] ?? ($change_set['deleted_ids'] ?? null);
            if ($addedIds !== null || $removedIds !== null) {
                if (!($collection instanceof PersistentCollection)) {
                    return null;
                }

                $addedIds = $addedIds ?? [];
                $removedIds = $removedIds ?? [];
                $inserted_count = count($addedIds);
                $deleted_count = count($removedIds);

                if ($inserted_count === 0 && $deleted_count === 0) {
                    return null;
                }

                try {
                    $mapping = $collection->getMapping();
                    $fieldName = $mapping->fieldName ?? 'unknown';
                    $targetEntity = $mapping->targetEntity ?? 'unknown';
                    if ($targetEntity) {
                        $targetEntity = class_basename($targetEntity);
                    }

                    $details = [
                        'field' => $fieldName,
                        'target_entity' => $targetEntity,
                        'added_ids' => $addedIds,
                        'removed_ids' => $removedIds,
                    ];

                    return self::formatManyToManyDetailedMessage($details, $inserted_count, $deleted_count, 'updated');
                } catch (\Exception $e) {
                    Log::debug("DefaultEntityManyToManyCollectionUpdateAuditLogFormatter::format - error extracting metadata: " . $e->getMessage());
                }
            }

            $changes = [];
            $insertDiff = $collection->getInsertDiff();
            $deleteDiff = $collection->getDeleteDiff();

            if ($this->child_entity_formatter != null) {
                foreach ($insertDiff as $child_changed_entity) {
                    $changes[] = $this->child_entity_formatter
                        ->format($child_changed_entity, IChildEntityAuditLogFormatter::CHILD_ENTITY_CREATION);
                }

                foreach ($deleteDiff as $child_changed_entity) {
                    $changes[] = $this->child_entity_formatter
                        ->format($child_changed_entity, IChildEntityAuditLogFormatter::CHILD_ENTITY_DELETION);
                }

                if (!empty($changes)) {
                    return implode("|", $changes);
                }
            } else {
                $inserted_count = count($insertDiff);
                $deleted_count = count($deleteDiff);

                if ($inserted_count > 0 || $deleted_count > 0) {
                    $details = $this->buildManyToManyDetailedMessage($collection, $insertDiff, $deleteDiff);
                    return self::formatManyToManyDetailedMessage($details, $inserted_count, $deleted_count, 'updated');
                }
            }

            return null;
        } catch (\Throwable $e) {
            Log::error(get_class($this) . " error: " . $e->getMessage());
            return null;
        }
    }

   
}
