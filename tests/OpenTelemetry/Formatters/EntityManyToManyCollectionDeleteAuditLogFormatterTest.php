<?php

namespace Tests\OpenTelemetry\Formatters;

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

use App\Audit\ConcreteFormatters\EntityManyToManyCollectionDeleteAuditLogFormatter;
use App\Audit\ConcreteFormatters\ChildEntityFormatters\IChildEntityAuditLogFormatter;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class EntityManyToManyCollectionDeleteAuditLogFormatterTest extends TestCase
{
    private const TEST_ERROR_MESSAGE = 'Test error';
    private const FIELD_NAME_MOCK = 'items';
    private const TARGET_ENTITY_MOCK = 'TestEntity';
    private const DELETED_ITEMS_COUNT = 3;
    private const CHILD_FORMATTED_MESSAGE = 'Formatted child message';
    private EntityManyToManyCollectionDeleteAuditLogFormatter $formatterWithChildFormatter;
    private Mockery\MockInterface $childFormatterMock;
    private EntityManyToManyCollectionDeleteAuditLogFormatter $formatter;
    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new EntityManyToManyCollectionDeleteAuditLogFormatter();
        
        $this->childFormatterMock = Mockery::mock(IChildEntityAuditLogFormatter::class);
        $this->formatterWithChildFormatter = new EntityManyToManyCollectionDeleteAuditLogFormatter($this->childFormatterMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testFormatWithoutContextReturnsNull(): void
    {
        $changeSet = ['collection' => $this->createMockCollection([], [1, 2, 3])];
        $result = $this->formatter->format(new \stdClass(), $changeSet);
        $this->assertNull($result);
    }

    public function testFormatWithoutCollectionReturnsNull(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());
        $result = $this->formatter->format(new \stdClass(), []);
        $this->assertNull($result);
    }

    public function testFormatWithNullCollectionReturnsNull(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());
        $result = $this->formatter->format(new \stdClass(), ['collection' => null]);
        $this->assertNull($result);
    }

    public function testFormatWithNonPersistentCollectionReturnsNull(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());
        
        $badCollection = new class {
            public function getInsertDiff(): array
            {
                return [];
            }
            public function getDeleteDiff(): array
            {
                return [];
            }
        };
        
        $result = $this->formatter->format(new \stdClass(), ['collection' => $badCollection]);
        $this->assertNull($result);
    }

    public function testFormatterInitialization(): void
    {
        $this->assertNotNull($this->formatter);
        $this->assertNotNull($this->formatterWithChildFormatter);
    }

    public function testFormatWithOnlyAddedItemsReturnsNull(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = ['collection' => $this->createMockCollection([1, 2], [])];
        $result = $this->formatter->format(new \stdClass(), $changeSet);
        $this->assertNull($result);
    }

    public function testFormatWithDeletedItemsAndChildFormatterMocksBehavior(): void
    {
        $this->childFormatterMock->shouldReceive('format')
            ->andReturn(self::CHILD_FORMATTED_MESSAGE);

        $deletedItems = $this->createMultipleMockEntities(self::DELETED_ITEMS_COUNT);
        $changeSet = ['collection' => $this->createMockCollection([], $deletedItems)];
        $result = $this->formatterWithChildFormatter->format(new \stdClass(), $changeSet);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString(self::CHILD_FORMATTED_MESSAGE, $result);
    }

    public function testFormatWithChildFormatterButNoDeletedItems(): void
    {
        $this->childFormatterMock->shouldNotReceive('format');
        
        $changeSet = ['collection' => $this->createMockCollection([], [])];
        $result = $this->formatterWithChildFormatter->format(new \stdClass(), $changeSet);
        $this->assertNull($result);
    }

    public function testFormatterExceptionHandlingReturnsNull(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());
        $testErrorMessage = self::TEST_ERROR_MESSAGE;
        
        $badCollection = new class($testErrorMessage) {
            public function __construct(private string $errorMessage) {}
            
            public function getDeleteDiff(): array
            {
                throw new \Exception($this->errorMessage);
            }
        };
        
        $result = $this->formatter->format(new \stdClass(), ['collection' => $badCollection]);
        $this->assertNull($result);
    }

    private function createMockCollection(array $inserted = [], array $deleted = [])
    {
        $fieldNameMock = self::FIELD_NAME_MOCK;
        $targetEntityMock = self::TARGET_ENTITY_MOCK;
        
        return new class($inserted, $deleted, $fieldNameMock, $targetEntityMock) {
            public function __construct(
                private array $inserted,
                private array $deleted,
                private string $fieldName,
                private string $targetEntity
            ) {}

            public function getInsertDiff(): array
            {
                return $this->inserted;
            }

            public function getDeleteDiff(): array
            {
                return $this->deleted;
            }

            public function getMapping(): array
            {
                return [
                    'fieldName' => $this->fieldName,
                    'targetEntity' => $this->targetEntity,
                ];
            }
        };
    }

    private function createMultipleMockEntities(int $count): array
    {
        $entities = [];
        for ($i = 0; $i < $count; $i++) {
            $entities[] = $this->createMockEntity($i + 1);
        }
        return $entities;
    }

    private function createMockEntity($id)
    {
        return new class($id) {
            public function __construct(private int $id) {}
            
            public function getId()
            {
                return $this->id;
            }
        };
    }
}
