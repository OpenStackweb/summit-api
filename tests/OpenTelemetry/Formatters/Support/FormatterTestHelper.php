<?php

namespace Tests\OpenTelemetry\Formatters\Support;

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
use App\Audit\Interfaces\IAuditStrategy;
use App\Audit\IAuditLogFormatter;
use ReflectionClass;

class FormatterTestHelper
{
    public static function assertFormatterCanBeInstantiated(string $formatterClass, string $eventType): IAuditLogFormatter
    {
        try {
            if (!class_exists($formatterClass)) {
                throw new \Exception("Formatter class does not exist: {$formatterClass}");
            }

            $reflection = new ReflectionClass($formatterClass);
            
            try {
                $formatter = $reflection->newInstance($eventType);
            } catch (\Throwable $e) {
                $formatter = $reflection->newInstance();
            }

            if (!$formatter instanceof IAuditLogFormatter) {
                throw new \Exception("Formatter must implement IAuditLogFormatter");
            }

            return $formatter;
        } catch (\ReflectionException $e) {
            throw new \Exception("Failed to instantiate {$formatterClass}: " . $e->getMessage());
        }
    }

    public static function assertFormatterHasSetContextMethod(IAuditLogFormatter $formatter): void
    {
        $reflection = new ReflectionClass($formatter);
        
        if (!$reflection->hasMethod('setContext')) {
            throw new \Exception(
                get_class($formatter) . " must have a setContext method"
            );
        }
    }

    public static function assertFormatterHasValidConstructor(string $formatterClass): void
    {
        try {
            $reflection = new ReflectionClass($formatterClass);
            
            if ($reflection->isAbstract()) {
                throw new \Exception("Cannot test abstract formatter: {$formatterClass}");
            }

            $constructor = $reflection->getConstructor();
            if ($constructor === null) {
                return;
            }

            try {
                $reflection->newInstance(IAuditStrategy::EVENT_ENTITY_CREATION);
                return;
            } catch (\Throwable $e) {
                try {
                    $reflection->newInstance();
                    return;
                } catch (\Throwable $e) {
                    $requiredParams = [];
                    foreach ($constructor->getParameters() as $param) {
                        if (!$param->isOptional() && !$param->allowsNull()) {
                            $requiredParams[] = $param->getName();
                        }
                    }
                    
                    if (!empty($requiredParams)) {
                        throw new \Exception(
                            "{$formatterClass} has required constructor parameters: " . 
                            implode(', ', $requiredParams) . 
                            ". These parameters must either have default values or be optionally injectable."
                        );
                    }
                    throw $e;
                }
            }
        } catch (\ReflectionException $e) {
            throw new \Exception("Failed to validate constructor for {$formatterClass}: " . $e->getMessage());
        }
    }

    public static function assertFormatterHandlesInvalidSubjectGracefully(
        IAuditLogFormatter $formatter,
        mixed $invalidSubject
    ): void {
        try {
            $formatter->format($invalidSubject, []);
        } catch (\Throwable $e) {
            throw new \Exception(
                get_class($formatter) . " must handle invalid subjects gracefully: " . $e->getMessage()
            );
        }
    }

    public static function assertFormatterHandlesEmptyChangesetGracefully(
        IAuditLogFormatter $formatter
    ): void {
        try {
            $formatter->format(new \stdClass(), []);
        } catch (\Throwable $e) {
            throw new \Exception(
                get_class($formatter) . " must handle empty changesets gracefully: " . $e->getMessage()
            );
        }
    }

    public static function assertFormatterExtendsAbstractFormatter(string $formatterClass): void
    {
        try {
            $reflection = new ReflectionClass($formatterClass);
            
            if (!$reflection->isSubclassOf('App\Audit\AbstractAuditLogFormatter')) {
                throw new \Exception(
                    "{$formatterClass} must extend AbstractAuditLogFormatter"
                );
            }
        } catch (\ReflectionException $e) {
            throw new \Exception("Failed to validate {$formatterClass}: " . $e->getMessage());
        }
    }

    public static function assertFormatterHasValidFormatMethod(string $formatterClass): void
    {
        try {
            $reflection = new ReflectionClass($formatterClass);
            
            if (!$reflection->hasMethod('format')) {
                throw new \Exception(
                    "{$formatterClass} must have a format() method"
                );
            }

            $method = $reflection->getMethod('format');
            
            if ($method->isAbstract()) {
                throw new \Exception(
                    "{$formatterClass}::format() must not be abstract"
                );
            }

            $params = $method->getParameters();
            if (count($params) < 1) {
                throw new \Exception(
                    "{$formatterClass}::format() must accept at least 1 parameter (subject)"
                );
            }
        } catch (\ReflectionException $e) {
            throw new \Exception("Failed to validate format method for {$formatterClass}: " . $e->getMessage());
        }
    }
}
