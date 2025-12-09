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

    public function make(AuditContext $ctx, $subject, $eventType): ?IAuditLogFormatter
    {
        $formatter = null;
        switch ($eventType) {
            case IAuditStrategy::EVENT_COLLECTION_UPDATE:
                $child_entity = null;
                if (count($subject) > 0) {
                    $child_entity = $subject[0];
                }
                if (is_null($child_entity) && isset($subject->getSnapshot()[0]) && count($subject->getSnapshot()) > 0) {
                    $child_entity = $subject->getSnapshot()[0];
                }
                $child_entity_formatter = $child_entity != null ? ChildEntityFormatterFactory::build($child_entity) : null;
                $formatter = new EntityCollectionUpdateAuditLogFormatter($child_entity_formatter);
                break;
            case IAuditStrategy::EVENT_ENTITY_CREATION:
                $formatter = $this->getFormatterByContext($subject, $eventType, $ctx);
                if(is_null($formatter)) {
                    $formatter = new EntityCreationAuditLogFormatter();
                }
                break;
            case IAuditStrategy::EVENT_ENTITY_DELETION:
                $formatter = $this->getFormatterByContext($subject, $eventType, $ctx);
                if(is_null($formatter)) {
                    $child_entity_formatter = ChildEntityFormatterFactory::build($subject);
                    $formatter = new EntityDeletionAuditLogFormatter($child_entity_formatter);
                }
                break;
            case IAuditStrategy::EVENT_ENTITY_UPDATE:
                $formatter = $this->getFormatterByContext($subject, $eventType, $ctx);
                if(is_null($formatter)) {
                    $child_entity_formatter = ChildEntityFormatterFactory::build($subject);
                    $formatter = new EntityUpdateAuditLogFormatter($child_entity_formatter);
                }
                break;
        }
        $formatter->setContext($ctx);
        return $formatter;
    }

    private function getFormatterByContext(object $subject, string $event_type, AuditContext $ctx): ?IAuditLogFormatter
    {
        $class = get_class($subject);
        $entity_config = $this->config['entities'][$class] ?? null;
        
        if (!$entity_config) {
            return null;
        }

        if (isset($entity_config['strategies'])) {
            foreach ($entity_config['strategies'] as $strategy) {
                if (!$this->matchesStrategy($strategy, $ctx)) {
                    continue;
                }

                $formatter_class = $strategy['formatter'] ?? null;
                return $formatter_class ? new $formatter_class($event_type) : null;
            }
        }

        if (isset($entity_config['strategy'])) {
            $strategy_class = $entity_config['strategy'];
            return new $strategy_class($event_type);
        }

        return null;
    }

    private function matchesStrategy(array $strategy, AuditContext $ctx): bool
    {
        if (isset($strategy['route']) && !$this->routeMatches($strategy['route'], $ctx->rawRoute)) {
            return false;
        }

        return true;
    }

    private function routeMatches(string $route, string $actual_route): bool
    {
        return strcmp($actual_route, $route) === 0;
    }
}
