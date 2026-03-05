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
use Mockery;
use Tests\TestCase;

class SummitAttendeeAuditLogFormatterManyToManyTest extends TestCase
{
    private const ATTENDEE_ID = 456;
    private const ATTENDEE_FIRST_NAME = 'Juan';
    private const ATTENDEE_LAST_NAME = 'García';

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

    public function testManyToManyUpdateReturnsNullWithoutContext(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE
        );

        $result = $formatter->format($this->mockSubject, []);
        $this->assertNull($result);
    }

    public function testManyToManyDeleteReturnsNullWithoutContext(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE
        );

        $result = $formatter->format($this->mockSubject, []);
        $this->assertNull($result);
    }

    public function testManyToManyUpdateReturnsNullWithoutCollection(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $result = $formatter->format($this->mockSubject, []);
        $this->assertNull($result);
    }

    public function testManyToManyDeleteReturnsNullWithoutCollection(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $result = $formatter->format($this->mockSubject, []);
        $this->assertNull($result);
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

    public function testManyToManyDeleteReturnsNullWithoutRemovedIds(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $collection = $this->createMockCollection([], []);
        $uow = Mockery::mock(UnitOfWork::class);

        $result = $formatter->format($this->mockSubject, [
            'collection' => $collection,
            'uow' => $uow
        ]);

        $this->assertNull($result);
    }

   

    


    private function createMockCollection(array $inserted = [], array $deleted = [])
    {
        return new class($inserted, $deleted) {
            public function __construct(
                private array $inserted,
                private array $deleted
            ) {}

            public function getInsertDiff(): array
            {
                return $this->inserted;
            }

            public function getDeleteDiff(): array
            {
                return $this->deleted;
            }

            public function getMapping()
            {
                $meta = Mockery::mock(ClassMetadata::class);
                $meta->fieldName = 'testField';
                $meta->targetEntity = 'TestEntity';
                return $meta;
            }
        };
    }
}
