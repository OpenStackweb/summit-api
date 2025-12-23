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
use Doctrine\ORM\PersistentCollection;
use Illuminate\Support\Facades\Log;
use Doctrine\ORM\Mapping\ClassMetadata;
class AuditLogFormatterFactory implements IAuditLogFormatterFactory
{

    private array $config;

    public function __construct()
    {
        // cache the config so we don't hit config() repeatedly
        $this->config = config('audit_log', []);
    }

    public function make(AuditContext $ctx, $subject, string $event_type): ?IAuditLogFormatter
    {
        $formatter = null;
        switch ($event_type) {
            case IAuditStrategy::EVENT_COLLECTION_UPDATE:
                $child_entity_formatter = null;

                if ($subject instanceof PersistentCollection) {
                    $targetEntity = null;
                    Log::debug
                    (
                        sprintf
                        (
                            "AuditLogFormatterFactory::make subject is a PersistentCollection isInitialized %b ?",
                            $subject->isInitialized()
                        )
                    );
                    if (method_exists($subject, 'getTypeClass')) {
                        $type = $subject->getTypeClass();
                        // Your log shows this is ClassMetadata
                        if ($type instanceof ClassMetadata) {
                            // Doctrine supports either getName() or public $name
                            $targetEntity = method_exists($type, 'getName') ? $type->getName() : ($type->name ?? null);
                        } elseif (is_string($type)) {
                            $targetEntity = $type;
                        }
                        Log::debug("AuditLogFormatterFactory::make getTypeClass targetEntity {$targetEntity}");
                    }
                    elseif (method_exists($subject, 'getMapping')) {
                        $mapping = $subject->getMapping();
                        $targetEntity = $mapping['targetEntity'] ?? null;
                        Log::debug("AuditLogFormatterFactory::make getMapping targetEntity {$targetEntity}");
                    } else {
                        // last-resort: read private association metadata (still no hydration)
                        $ref = new \ReflectionObject($subject);
                        foreach (['association', 'mapping', 'associationMapping'] as $propName) {
                            if ($ref->hasProperty($propName)) {
                                $prop = $ref->getProperty($propName);
                                $prop->setAccessible(true);
                                $mapping = $prop->getValue($subject);
                                $targetEntity = $mapping['targetEntity'] ?? null;
                                if ($targetEntity) break;
                            }
                        }
                    }

                    if ($targetEntity) {
                        // IMPORTANT: build formatter WITHOUT touching collection items
                        $child_entity_formatter = ChildEntityFormatterFactory::build($targetEntity);
                    }
                    Log::debug
                    (
                        sprintf
                        (
                            "AuditLogFormatterFactory::make subject is a PersistentCollection isInitialized %b ? ( final )",
                            $subject->isInitialized()
                        )
                    );
                } elseif (is_array($subject)) {
                    $child_entity = $subject[0] ?? null;
                    $child_entity_formatter = $child_entity ? ChildEntityFormatterFactory::build($child_entity) : null;
                } elseif (is_object($subject) && method_exists($subject, 'getSnapshot')) {
                    $snap = $subject->getSnapshot(); // only once
                    $child_entity = $snap[0] ?? null;
                    $child_entity_formatter = $child_entity ? ChildEntityFormatterFactory::build($child_entity) : null;
                }

                $formatter = new EntityCollectionUpdateAuditLogFormatter($child_entity_formatter);
                break;
            case IAuditStrategy::EVENT_ENTITY_CREATION:
                $formatter = $this->getFormatterByContext($subject, $event_type, $ctx);
                if(is_null($formatter)) {
                    $formatter = new EntityCreationAuditLogFormatter($event_type);
                }
                break;
            case IAuditStrategy::EVENT_ENTITY_DELETION:
                $formatter = $this->getFormatterByContext($subject, $event_type, $ctx);
                if(is_null($formatter)) {
                    $child_entity_formatter = ChildEntityFormatterFactory::build($subject);
                    $formatter = new EntityDeletionAuditLogFormatter($child_entity_formatter);
                }
                break;
            case IAuditStrategy::EVENT_ENTITY_UPDATE:
                $formatter = $this->getFormatterByContext($subject, $event_type, $ctx);
                if(is_null($formatter)) {
                    $child_entity_formatter = ChildEntityFormatterFactory::build($subject);
                    $formatter = new EntityUpdateAuditLogFormatter($child_entity_formatter);
                }
                break;
        }
        if ($formatter === null) return null;
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
