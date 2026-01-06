<?php

namespace Tests\OpenTelemetry\Formatters;

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

use App\Audit\AuditLogFormatterFactory;
use App\Audit\AuditContext;
use App\Audit\Interfaces\IAuditStrategy;
use App\Audit\IAuditLogFormatter;
use Tests\OpenTelemetry\Formatters\Support\FormatterTestHelper;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use PHPUnit\Framework\TestCase;

class AllFormattersIntegrationTest extends TestCase
{
    private AuditContext $defaultContext;
    private const BASE_FORMATTERS_NAMESPACE = 'App\\Audit\\ConcreteFormatters\\';
    private const BASE_FORMATTERS_DIR = __DIR__ . '/../../../app/Audit/ConcreteFormatters';
    private const CHILD_ENTITY_DIR_NAME = 'ChildEntityFormatters';

    protected function setUp(): void
    {
        parent::setUp();
        $this->defaultContext = AuditContextBuilder::default()->build();
    }

    private function discoverFormatters(string $directory = null): array
    {
        $directory = $directory ?? self::BASE_FORMATTERS_DIR;
        $formatters = [];
        
        if (!is_dir($directory)) {
            return $formatters;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php' || strpos($file->getPathname(), self::CHILD_ENTITY_DIR_NAME) !== false) {
                continue;
            }

            $className = $this->buildClassName($file->getPathname(), $directory);

            if (class_exists($className) && $this->isMainFormatter($className)) {
                $formatters[] = $className;
            }
        }

        return array_values($formatters);
    }


    private function buildClassName(string $filePath, string $basePath): string
    {
        $relativePath = str_replace([$basePath . DIRECTORY_SEPARATOR, '.php'], '', $filePath);
        $classPath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
        return self::BASE_FORMATTERS_NAMESPACE . $classPath;
    }

    private function isMainFormatter(string $className): bool
    {
        try {
            $reflection = new \ReflectionClass($className);
            
            if ($reflection->isAbstract() || $reflection->isInterface()) {
                return false;
            }

            $genericFormatters = [
                'EntityCreationAuditLogFormatter',
                'EntityDeletionAuditLogFormatter',
                'EntityUpdateAuditLogFormatter',
                'EntityCollectionUpdateAuditLogFormatter',
            ];

            return !in_array($reflection->getShortName(), $genericFormatters) &&
                   $reflection->isSubclassOf('App\Audit\AbstractAuditLogFormatter');
        } catch (\ReflectionException $e) {
            return false;
        }
    }

    public function testAllFormattersCanBeInstantiated(): void
    {
        foreach ($this->discoverFormatters() as $formatterClass) {
            try {
                $formatter = FormatterTestHelper::assertFormatterCanBeInstantiated(
                    $formatterClass,
                    IAuditStrategy::EVENT_ENTITY_CREATION
                );

                FormatterTestHelper::assertFormatterHasSetContextMethod($formatter);
                $formatter->setContext($this->defaultContext);
                $this->assertNotNull($formatter);
            } catch (\Exception $e) {
                $this->fail("Failed to validate {$formatterClass}: " . $e->getMessage());
            }
        }
    }

    public function testAllFormatterConstructorParametersRequired(): void
    {
        $errors = [];
        $count = 0;

        foreach ($this->discoverFormatters() as $formatterClass) {
            try {
                FormatterTestHelper::assertFormatterHasValidConstructor($formatterClass);
                $count++;
            } catch (\Exception $e) {
                $errors[] = "{$formatterClass}: " . $e->getMessage();
            }
        }

        $this->assertEmpty($errors, implode("\n", $errors));
        $this->assertGreaterThan(0, $count, 'At least one formatter should be validated');
    }

    public function testAllFormattersHandleAllEventTypes(): void
    {
        $eventTypes = [
            IAuditStrategy::EVENT_ENTITY_CREATION,
            IAuditStrategy::EVENT_ENTITY_UPDATE,
            IAuditStrategy::EVENT_ENTITY_DELETION,
            IAuditStrategy::EVENT_COLLECTION_UPDATE,
        ];

        $errors = [];
        $unsupported = [];

        foreach ($this->discoverFormatters() as $formatterClass) {
            foreach ($eventTypes as $eventType) {
                try {
                    $formatter = FormatterTestHelper::assertFormatterCanBeInstantiated(
                        $formatterClass,
                        $eventType
                    );
                    $formatter->setContext($this->defaultContext);
                    $this->assertNotNull($formatter);
                } catch (\Exception $e) {
                    if (strpos($e->getMessage(), 'event type') !== false) {
                        $unsupported[] = "{$formatterClass} does not support {$eventType}";
                    } else {
                        $errors[] = "{$formatterClass} with {$eventType}: " . $e->getMessage();
                    }
                }
            }
        }

        $this->assertEmpty($errors, "Event type handling failed:\n" . implode("\n", $errors));
    }

    public function testAllFormattersHandleInvalidSubjectGracefully(): void
    {
        $errors = [];
        $count = 0;

        foreach ($this->discoverFormatters() as $formatterClass) {
            try {
                $formatter = FormatterTestHelper::assertFormatterCanBeInstantiated(
                    $formatterClass,
                    IAuditStrategy::EVENT_ENTITY_CREATION
                );
                $formatter->setContext($this->defaultContext);

                FormatterTestHelper::assertFormatterHandlesInvalidSubjectGracefully($formatter, new \stdClass());
                $count++;
            } catch (\Exception $e) {
                $errors[] = "{$formatterClass}: " . $e->getMessage();
            }
        }

        $this->assertEmpty($errors, implode("\n", $errors));
        $this->assertGreaterThan(0, $count, 'At least one formatter should be validated');
    }

    public function testAllFormattersHandleMissingContextGracefully(): void
    {
        $errors = [];
        $count = 0;

        foreach ($this->discoverFormatters() as $formatterClass) {
            try {
                $formatter = FormatterTestHelper::assertFormatterCanBeInstantiated(
                    $formatterClass,
                    IAuditStrategy::EVENT_ENTITY_CREATION
                );

                $result = $formatter->format(new \stdClass(), []);
                
                $this->assertNull(
                    $result,
                    "{$formatterClass}::format() must return null when context not set, got " . 
                    (is_string($result) ? "'{$result}'" : gettype($result))
                );
                $count++;
            } catch (\Exception $e) {
                $errors[] = "{$formatterClass} threw exception without context: " . $e->getMessage();
            }
        }

        $this->assertEmpty($errors, implode("\n", $errors));
        $this->assertGreaterThan(0, $count, 'At least one formatter should be validated');
    }

    public function testFormattersHandleEmptyChangeSetGracefully(): void
    {
        $errors = [];
        $count = 0;

        foreach ($this->discoverFormatters() as $formatterClass) {
            try {
                $formatter = FormatterTestHelper::assertFormatterCanBeInstantiated(
                    $formatterClass,
                    IAuditStrategy::EVENT_ENTITY_UPDATE
                );
                $formatter->setContext($this->defaultContext);

                FormatterTestHelper::assertFormatterHandlesEmptyChangesetGracefully($formatter);
                $count++;
            } catch (\Exception $e) {
                $errors[] = "{$formatterClass}: " . $e->getMessage();
            }
        }

        $this->assertEmpty($errors, implode("\n", $errors));
        $this->assertGreaterThan(0, $count, 'At least one formatter should be validated');
    }

    public function testAllFormattersImplementCorrectInterfaces(): void
    {
        $errors = [];
        $count = 0;

        foreach ($this->discoverFormatters() as $formatterClass) {
            try {
                FormatterTestHelper::assertFormatterExtendsAbstractFormatter($formatterClass);
                FormatterTestHelper::assertFormatterHasValidFormatMethod($formatterClass);
                $count++;
            } catch (\Exception $e) {
                $errors[] = "{$formatterClass}: " . $e->getMessage();
            }
        }

        $this->assertEmpty($errors, implode("\n", $errors));
        $this->assertGreaterThan(0, $count, 'At least one formatter should be validated');
    }

    public function testAllFormattersHaveCorrectFormatMethodSignature(): void
    {
        $errors = [];
        $count = 0;

        foreach ($this->discoverFormatters() as $formatterClass) {
            try {
                FormatterTestHelper::assertFormatterHasValidFormatMethod($formatterClass);
                $count++;
            } catch (\Exception $e) {
                $errors[] = "{$formatterClass}: " . $e->getMessage();
            }
        }

        $this->assertEmpty($errors, implode("\n", $errors));
        $this->assertGreaterThan(0, $count, 'At least one formatter should be validated');
    }


    public function testAuditContextHasRequiredFields(): void
    {
        $context = $this->defaultContext;

        $this->assertIsInt($context->userId);
        $this->assertGreaterThan(0, $context->userId);
        
        $this->assertIsString($context->userEmail);
        $this->assertNotEmpty($context->userEmail);
        $this->assertNotFalse(filter_var($context->userEmail, FILTER_VALIDATE_EMAIL), 
            "User email '{$context->userEmail}' is not valid");
        
        $this->assertIsString($context->userFirstName);
        $this->assertNotEmpty($context->userFirstName);
        
        $this->assertIsString($context->userLastName);
        $this->assertNotEmpty($context->userLastName);
        
        $this->assertIsString($context->uiApp);
        $this->assertNotEmpty($context->uiApp);
        
        $this->assertIsString($context->uiFlow);
        $this->assertNotEmpty($context->uiFlow);
        
        $this->assertIsString($context->route);
        $this->assertNotEmpty($context->route);
        
        $this->assertIsString($context->httpMethod);
        $this->assertNotEmpty($context->httpMethod);
        
        $this->assertIsString($context->clientIp);
        $this->assertNotEmpty($context->clientIp);
        $this->assertNotFalse(filter_var($context->clientIp, FILTER_VALIDATE_IP), 
            "Client IP '{$context->clientIp}' is not valid");
        
        $this->assertIsString($context->userAgent);
        $this->assertNotEmpty($context->userAgent);
    }

    public function testAuditStrategyDefinesAllEventTypes(): void
    {
        $this->assertTrue(defined('App\Audit\Interfaces\IAuditStrategy::EVENT_ENTITY_CREATION'));
        $this->assertTrue(defined('App\Audit\Interfaces\IAuditStrategy::EVENT_ENTITY_UPDATE'));
        $this->assertTrue(defined('App\Audit\Interfaces\IAuditStrategy::EVENT_ENTITY_DELETION'));
        $this->assertTrue(defined('App\Audit\Interfaces\IAuditStrategy::EVENT_COLLECTION_UPDATE'));
    }

    public function testFactoryInstantiatesCorrectFormatterForSubject(): void
    {
        $factory = new AuditLogFormatterFactory();

        $unknownSubject = new \stdClass();
        $formatter = $factory->make($this->defaultContext, $unknownSubject, IAuditStrategy::EVENT_ENTITY_CREATION);
        
        $this->assertTrue(
            $formatter === null || $formatter instanceof IAuditLogFormatter,
            'Factory must return null or IAuditLogFormatter for unknown subject type'
        );

        $validSubject = new class {
            public function __toString() { return 'MockEntity'; }
        };

        $formatter = $factory->make($this->defaultContext, $validSubject, IAuditStrategy::EVENT_ENTITY_CREATION);
        
        if ($formatter !== null) {
            $this->assertInstanceOf(
                IAuditLogFormatter::class,
                $formatter,
                'Factory must return IAuditLogFormatter instance for valid subject'
            );

            $this->assertNotNull($formatter, 'Returned formatter must not be null');
        }
    }
}
