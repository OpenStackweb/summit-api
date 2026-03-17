<?php

namespace App\Audit\ConcreteFormatters;

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\ConcreteFormatters\ChildEntityFormatters\IChildEntityAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
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
            $metadata = $this->handleManyToManyCollection($change_set);
            if ($metadata === null) {
                return null;
            }

            $changes = [];

            if ($this->child_entity_formatter != null && empty($metadata->preloadedDeletedIds)) {
                $deleteDiff = $metadata->collection->getDeleteDiff();
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
                $collectionData = $this->processCollection($metadata);
                if ($collectionData === null) {
                    return null;
                }

                $deletedCount = count($collectionData['removed_ids']);
                if ($deletedCount === 0) {
                    return sprintf("Many-to-Many collection '%s' deleted: Removed IDs: []", $collectionData['field']);
                }

                return self::formatManyToManyDetailedMessage($collectionData, 0, $deletedCount, 'deleted');
            }

            return null;
        } catch (\Throwable $e) {
            Log::error(get_class($this) . " error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return null;
        }
    }

   
}
