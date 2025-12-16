<?php namespace App\Audit;
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
use App\Audit\ConcreteFormatters\ChildEntityFormatters\ChildEntityFormatterFactory;
use App\Audit\ConcreteFormatters\EntityCollectionUpdateAuditLogFormatter;
use App\Audit\ConcreteFormatters\EntityCreationAuditLogFormatter;
use App\Audit\ConcreteFormatters\EntityDeletionAuditLogFormatter;
use App\Audit\ConcreteFormatters\EntityUpdateAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;

class AuditLogFormatterFactory implements IAuditLogFormatterFactory
{

    private array $config;

    public function __construct()
    {
        // cache the config so we don't hit config() repeatedly
        $this->config = config('audit_log', []);
    }

    public function getStrategyClass(object $subject, string $event_type): ?IAuditLogFormatter
    {
        $class = get_class($subject);
        $cls = $this->config['entities'][$class]['strategy'] ?? null;
        return !is_null($cls) ? new $cls($event_type):null;
    }

    public function make(AuditContext $ctx, $subject, $eventType): ?IAuditLogFormatter
    {
        $formatter = null;
        switch ($eventType) {
            case IAuditStrategy::EVENT_COLLECTION_UPDATE:
                $child_entity = null;
                if (count($subject) > 0) {
                    $child_entity = $subject[0];
                }
                if (is_null($child_entity) && count($subject->getSnapshot()) > 0) {
                    $child_entity = $subject->getSnapshot()[0];
                }
                $child_entity_formatter = $child_entity != null ? ChildEntityFormatterFactory::build($child_entity) : null;
                $formatter = new EntityCollectionUpdateAuditLogFormatter($child_entity_formatter);
                break;
            case IAuditStrategy::EVENT_ENTITY_CREATION:
                $formatter = $this->getStrategyClass($subject, $eventType);
                if(is_null($formatter)) {
                    $formatter = new EntityCreationAuditLogFormatter();
                }
                break;
            case IAuditStrategy::EVENT_ENTITY_DELETION:
                $formatter = $this->getStrategyClass($subject, $eventType);
                if(is_null($formatter)) {
                    $child_entity_formatter = ChildEntityFormatterFactory::build($subject);
                    $formatter = new EntityDeletionAuditLogFormatter($child_entity_formatter);
                }
                break;
            case IAuditStrategy::EVENT_ENTITY_UPDATE:
                $formatter = $this->getStrategyClass($subject, $eventType);
                if(is_null($formatter)) {
                    $child_entity_formatter = ChildEntityFormatterFactory::build($subject);
                    $formatter = new EntityUpdateAuditLogFormatter($child_entity_formatter);
                }
                break;
        }
        $formatter->setContext($ctx);
        return $formatter;
    }
}
