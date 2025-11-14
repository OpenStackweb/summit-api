<?php namespace App\Audit\Interfaces;
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
use App\Audit\AuditContext;

/**
 * Audit Strategy Interface
 * Defines contract for different audit implementations (DB, OTLP, etc.)
 */
interface IAuditStrategy
{
    /**
     * @param $subject
     * @param array $change_set
     * @param string $event_type
     * @param AuditContext $ctx
     * @return void
     */
    public function audit($subject, array $change_set, string $event_type, AuditContext $ctx): void;


    public const EVENT_COLLECTION_UPDATE = 'event_collection_update';
    public const EVENT_ENTITY_CREATION = 'event_entity_creation';
    public const EVENT_ENTITY_DELETION = 'event_entity_deletion';
    public const EVENT_ENTITY_UPDATE = 'event_entity_update';

    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_COLLECTION_UPDATE = 'collection_update';
    public const ACTION_UNKNOWN = 'unknown';

    public const LOG_MESSAGE_CREATED = 'audit.entity.created';
    public const LOG_MESSAGE_UPDATED = 'audit.entity.updated';
    public const LOG_MESSAGE_DELETED = 'audit.entity.deleted';
    public const LOG_MESSAGE_COLLECTION_UPDATED = 'audit.collection.updated';
    public const LOG_MESSAGE_CHANGED = 'audit.entity.changed';
}
