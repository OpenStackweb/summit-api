<?php

namespace App\Audit\ConcreteFormatters;

use App\Audit\ConcreteFormatters\ChildEntityFormatters\IChildEntityAuditLogFormatter;
use App\Audit\IAuditLogFormatter;
use Illuminate\Support\Facades\Log;
use ReflectionException;

/**
 * Copyright 2022 OpenStack Foundation
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
 * Class EntityCollectionUpdateAuditLogFormatter
 * @package App\Audit\ConcreteFormatters
 */
class EntityCollectionUpdateAuditLogFormatter implements IAuditLogFormatter
{
    /**
     * @var IChildEntityAuditLogFormatter
     */
    private $child_entity_formatter;

    public function __construct(?IChildEntityAuditLogFormatter $child_entity_formatter)
    {
        $this->child_entity_formatter = $child_entity_formatter;
    }

    /**
     * @inheritDoc
     */
    public function format($subject, $change_set): ?string {
        try {
            if ($this->child_entity_formatter != null) {
                $changes = [];

                $insertDiff = $subject->getInsertDiff();

                foreach ($insertDiff as $child_changed_entity) {
                    $changes[] = $this->child_entity_formatter
                        ->format($child_changed_entity, IChildEntityAuditLogFormatter::CHILD_ENTITY_CREATION);
                }

                $deleteDiff = $subject->getDeleteDiff();

                foreach ($deleteDiff as $child_changed_entity) {
                    $changes[] = $this->child_entity_formatter
                        ->format($child_changed_entity, IChildEntityAuditLogFormatter::CHILD_ENTITY_DELETION);
                }
                return implode("|", $changes);
            }
            return null;
        } catch (ReflectionException $e) {
            Log::error($e);
            return null;
        }
    }
}