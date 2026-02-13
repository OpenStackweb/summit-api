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

use App\Audit\ConcreteFormatters\SummitAttendeeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\PersistentCollection;
use Mockery;
use Tests\TestCase;

class SummitAttendeeAuditLogFormatterManyToManyTest extends TestCase
{
    // Test Constants
    private const ATTENDEE_ID = 456;
    private const ATTENDEE_FIRST_NAME = 'John';
    private const ATTENDEE_LAST_NAME = 'Doe';
    
    
    
    private const SCHEDULE_MODEL = \models\main\SummitMemberSchedule::class;
    private const PRESENTATION_MODEL = \models\summit\Presentation::class;
    
    private mixed $mockSubject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockSubject = $this->createMockSubject();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createMockSubject(): mixed
    {
        $mock = Mockery::mock('models\summit\SummitAttendee');
        $mock->shouldReceive('getId')->andReturn(self::ATTENDEE_ID);
        $mock->shouldReceive('getFirstName')->andReturn(self::ATTENDEE_FIRST_NAME);
        $mock->shouldReceive('getSurname')->andReturn(self::ATTENDEE_LAST_NAME);
        return $mock;
    }

    /**
     * Test many-to-many association update with added IDs
     */
    public function testManyToManyAssociationUpdateWithAddedIds(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $changeSet = $this->buildCollectionChangeSet(
            added: [100, 101, 102],
            removed: [],
            isDeletion: false
        );

        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString('Attendee', $result);
        $this->assertStringContainsString('Field:', $result);
        $this->assertStringContainsString('schedules', $result);
        $this->assertStringContainsString('Added IDs', $result);
    }

    /**
     * Test many-to-many association deletion with removed IDs
     */
    public function testManyToManyAssociationDeleteWithRemovedIds(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $changeSet = $this->buildCollectionChangeSet(
            added: [],
            removed: [200, 201],
            isDeletion: true
        );

        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString('Attendee Delete', $result);
        $this->assertStringContainsString('Field:', $result);
        $this->assertStringContainsString('presentations', $result);
        $this->assertStringContainsString('Cleared IDs', $result);
    }

    /**
     * Test many-to-many update preserves correct formatting
     */
    public function testCollectionUpdateWithTags(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $changeSet = $this->buildCollectionChangeSet(
            added: [100, 101],
            removed: [],
            isDeletion: false
        );

        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString('Attendee', $result);
        $this->assertStringContainsString('Field:', $result);
        $this->assertStringContainsString('SummitMemberSchedule', $result);
    }

    /**
     * Test backward compatibility - entity creation
     */
    public function testStandardEntityCreationStillWorks(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_ENTITY_CREATION
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
    }

    /**
     * Test backward compatibility - entity update
     */
    public function testStandardEntityUpdateStillWorks(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_ENTITY_UPDATE
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $changeSet = ['first_name' => [self::ATTENDEE_FIRST_NAME, 'Jane']];

        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    /**
     * Test backward compatibility - entity deletion
     */
    public function testStandardEntityDeletionStillWorks(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_ENTITY_DELETION
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    /**
     * Test formatter returns null for invalid subject type
     */
    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    /**
     * Helper method to build collection change set with mocked collection and UnitOfWork
     */
    private function buildCollectionChangeSet(array $added = [], array $removed = [], bool $isDeletion = false): array
    {
        $fieldName = $isDeletion ? 'presentations' : 'schedules';
        $targetEntity = $isDeletion ? self::PRESENTATION_MODEL : self::SCHEDULE_MODEL;
        
        // Mock the mapping data
        $mapping = [
            'fieldName' => $fieldName,
            'targetEntity' => $targetEntity,
            'type' => ClassMetadata::MANY_TO_MANY,
            'joinTable' => [
                'name' => $isDeletion ? 'attendee_presentations' : 'attendee_schedules',
            ],
        ];

        // Create concrete entity objects with getId() method (avoids method_exists issues with Mockery)
        $addedEntities = array_map(function($id) {
            return new class($id) {
                public function __construct(private int $id) {}
                public function getId(): int
                {
                    return $this->id;
                }
            };
        }, $added);

        $removedEntities = array_map(function($id) {
            return new class($id) {
                public function __construct(private int $id) {}
                public function getId(): int
                {
                    return $this->id;
                }
            };
        }, $removed);

        // Create a concrete collection object to ensure is_object() and method_exists() work
        $mockCollection = new class($mapping, $addedEntities, $removedEntities, $this->mockSubject) {
            public function __construct(
                private array $mapping,
                private array $addedEntities,
                private array $removedEntities,
                private mixed $owner
            ) {}

            public function getMapping(): array
            {
                return $this->mapping;
            }

            public function getInsertDiff(): array
            {
                return $this->addedEntities;
            }

            public function getDeleteDiff(): array
            {
                return $this->removedEntities;
            }

            public function getOwner(): mixed
            {
                return $this->owner;
            }

            public function toArray(): array
            {
                return array_merge($this->addedEntities, $this->removedEntities);
            }
        };

        // Mock UnitOfWork - return original removed entities for recovery
        $mockUow = \Mockery::mock(UnitOfWork::class);
        $mockUow->shouldReceive('getOriginalEntityData')->andReturn([
            $fieldName => $removedEntities
        ]);

        return [
            'collection' => $mockCollection,
            'uow' => $mockUow,
            'is_deletion' => $isDeletion,
        ];
    }
}
