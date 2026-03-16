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
 * Formatter for Many-to-Many collection deletions
 */
class DefaultEntityManyToManyCollectionDeleteAuditLogFormatter extends AbstractAuditLogFormatter
{
    /**
     * @var IChildEntityAuditLogFormatter|null
     */
    private $child_entity_formatter;

    public function __construct(mixed $child_entity_formatter = null)
    {
        parent::__construct(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);
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
                Log::debug("DefaultEntityManyToManyCollectionDeleteAuditLogFormatter::format - no collection in change_set");
                return null;
            }

            $changes = [];
            
            // Check if deleted_ids are provided in payload (for uninitialized collections)
            $deletedIds = isset($change_set['deleted_ids']) ? $change_set['deleted_ids'] : null;
            Log::debug("DefaultEntityManyToManyCollectionDeleteAuditLogFormatter::format - deletedIds", [
                'deletedIds' => $deletedIds,
                'isNull' => $deletedIds === null,
                'count' => $deletedIds ? count($deletedIds) : 0
            ]);
            
            if ($deletedIds !== null) {
                // Use the deleted_ids from payload (for uninitialized collections)
                $deletedCount = count($deletedIds);

                if (!($collection instanceof PersistentCollection)) {
                    return null;
                }

                try {
                    $mapping = $collection->getMapping();
                    $fieldName = $mapping->fieldName ?? 'unknown';
                    $targetEntity = $mapping->targetEntity ?? 'unknown';
                    if ($targetEntity) {
                        $targetEntity = class_basename($targetEntity);
                    }
                    
                    if ($deletedCount === 0) {
                        return sprintf("Many-to-Many collection '%s' deleted: Removed IDs: []", $fieldName);
                    }

                    $details = [
                        'field' => $fieldName,
                        'target_entity' => $targetEntity,
                        'added_ids' => [],
                        'removed_ids' => $deletedIds,
                    ];
                    return self::formatManyToManyDetailedMessage($details, 0, $deletedCount, 'deleted');
                } catch (\Exception $e) {
                    Log::debug("DefaultEntityManyToManyCollectionDeleteAuditLogFormatter::format - error extracting metadata: " . $e->getMessage());
                }
            } else {
                if (!($collection instanceof PersistentCollection)) {
                    return null;
                }
                
                $deleteDiff = $collection->getDeleteDiff();
                
                if ($this->child_entity_formatter != null) {
                    foreach ($deleteDiff as $child_changed_entity) {
                        $formatted = $this->child_entity_formatter
                            ->format($child_changed_entity, IChildEntityAuditLogFormatter::CHILD_ENTITY_DELETION);
                        if ($formatted !== null) {
                            $changes[] = $formatted;
                        }
                    }

                    if (!empty($changes)) {
                        return implode("|", $changes);
                    }
                } else {
                    $deleted_count = count($deleteDiff);

                    if ($deleted_count > 0) {
                        $details = $this->buildManyToManyDetailedMessage($collection, [], $deleteDiff);
                        return self::formatManyToManyDetailedMessage($details, 0, $deleted_count, 'deleted');
                    }

                    try {
                        $mapping = $collection->getMapping();
                        $fieldName = $mapping->fieldName ?? 'unknown';
                        return sprintf("Many-to-Many collection '%s' deleted: Removed IDs: []", $fieldName);
                    } catch (\Exception $e) {
                        Log::debug("DefaultEntityManyToManyCollectionDeleteAuditLogFormatter::format - error extracting metadata: " . $e->getMessage());
                    }
                }
            }

            Log::debug("DefaultEntityManyToManyCollectionDeleteAuditLogFormatter::format - returning null");
            return null;
        } catch (\Throwable $e) {
            Log::error(get_class($this) . " error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return null;
        }
    }

   
}
