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

use App\Audit\ConcreteFormatters\SummitBookableVenueRoomAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SummitBookableVenueRoomAuditLogFormatterTest extends TestCase
{
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
        $mockSummit = Mockery::mock('models\summit\Summit');
        $mockSummit->shouldReceive('getName')->andReturn('OpenStack Summit 2024');
        
        $mockVenue = Mockery::mock('models\summit\SummitVenue');
        $mockVenue->shouldReceive('getName')->andReturn('Convention Center');
        $mockVenue->shouldReceive('getSummit')->andReturn($mockSummit);
        
        $mock = Mockery::mock('models\summit\SummitBookableVenueRoom');
        
        // Configure return values
        $mock->shouldReceive('getId')->andReturn(789);
        $mock->shouldReceive('getName')->andReturn('Ballroom A');
        $mock->shouldReceive('getVenue')->andReturn($mockVenue);
        $mock->shouldReceive('getSummit')->andReturn($mockSummit);
        
        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new SummitBookableVenueRoomAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString('Ballroom A', $result);
        $this->assertStringContainsString('789', $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new SummitBookableVenueRoomAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = ['name' => ['Ballroom A', 'Ballroom A & B'], 'time_slot_cost' => [1000, 1500]];
        
        $result = $formatter->format($this->mockSubject, $changeSet);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new SummitBookableVenueRoomAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SummitBookableVenueRoomAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);
        $this->assertNull($result);
    }

    public function testFormatterHandlesEmptyChangeSet(): void
    {
        $formatter = new SummitBookableVenueRoomAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testPropertiesExist(): void
    {
        $formatter = new SummitBookableVenueRoomAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        
        $this->assertTrue(method_exists($this->mockSubject, 'getId'));
        $this->assertTrue(method_exists($this->mockSubject, 'getName'));
        $this->assertTrue(method_exists($this->mockSubject, 'getVenue'));
        
        $result = $formatter->format($this->mockSubject, []);
        $this->assertNotNull($result);
    }
}
