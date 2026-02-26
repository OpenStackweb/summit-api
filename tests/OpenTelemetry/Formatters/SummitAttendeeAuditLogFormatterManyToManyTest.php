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
    private const ATTENDEE_ID = 456;
    private const ATTENDEE_FIRST_NAME = 'John';
    private const ATTENDEE_LAST_NAME = 'Doe';
    private const SCHEDULE_MODEL = \models\main\SummitMemberSchedule::class;
    private const PRESENTATION_MODEL = \models\summit\Presentation::class;
    private const FIELD_LABEL = 'Field:';
    private const ADDED_IDS_LABEL = 'Added IDs';
    private const CLEARED_IDS_LABEL = 'Cleared IDs';
    private const SCHEDULES_FIELD = 'schedules';
    private const PRESENTATIONS_FIELD = 'presentations';
    private const ATTENDEE_DELETE_LABEL = 'association deleted';
    private const ATTENDEE_LABEL = 'Attendee';
    private const SCHEDULE_MODEL_NAME = 'SummitMemberSchedule';
    private const CREATED_LABEL = 'created';
    private const UPDATED_LABEL = 'updated';
    private const DELETED_LABEL = 'deleted';
    private const FIRST_NAME_FIELD = 'first_name';
    private const NEW_FIRST_NAME = 'Jane';
    private const ATTENDEE_PRESENTATIONS_TABLE = 'attendee_presentations';
    private const ATTENDEE_SCHEDULES_TABLE = 'attendee_schedules';
    
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
        $this->assertStringContainsString(self::ATTENDEE_LABEL, $result);
        $this->assertStringContainsString(self::FIELD_LABEL, $result);
        $this->assertStringContainsString(self::SCHEDULES_FIELD, $result);
        $this->assertStringContainsString(self::ADDED_IDS_LABEL, $result);
    }

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
        $this->assertStringContainsString(self::ATTENDEE_DELETE_LABEL, $result);
        $this->assertStringContainsString(self::FIELD_LABEL, $result);
        $this->assertStringContainsString(self::PRESENTATIONS_FIELD, $result);
        $this->assertStringContainsString(self::CLEARED_IDS_LABEL, $result);
    }

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
        $this->assertStringContainsString(self::ATTENDEE_LABEL, $result);
        $this->assertStringContainsString(self::FIELD_LABEL, $result);
        $this->assertStringContainsString(self::SCHEDULE_MODEL_NAME, $result);
    }

    public function testStandardEntityCreationStillWorks(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_ENTITY_CREATION
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::CREATED_LABEL, $result);
    }

    public function testStandardEntityUpdateStillWorks(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_ENTITY_UPDATE
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $changeSet = [self::FIRST_NAME_FIELD => [self::ATTENDEE_FIRST_NAME, self::NEW_FIRST_NAME]];

        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::UPDATED_LABEL, $result);
    }

    public function testStandardEntityDeletionStillWorks(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_ENTITY_DELETION
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::DELETED_LABEL, $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    private function buildCollectionChangeSet(array $added = [], array $removed = [], bool $isDeletion = false): array
    {
        $fieldName = $isDeletion ? self::PRESENTATIONS_FIELD : self::SCHEDULES_FIELD;
        $targetEntity = $isDeletion ? self::PRESENTATION_MODEL : self::SCHEDULE_MODEL;
        
        $mapping = [
            'fieldName' => $fieldName,
            'targetEntity' => $targetEntity,
            'type' => ClassMetadata::MANY_TO_MANY,
            'joinTable' => [
                'name' => $isDeletion ? self::ATTENDEE_PRESENTATIONS_TABLE : self::ATTENDEE_SCHEDULES_TABLE,
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
