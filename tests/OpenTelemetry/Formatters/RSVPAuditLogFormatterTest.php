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

use App\Audit\ConcreteFormatters\RSVPAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class RSVPAuditLogFormatterTest extends TestCase
{
    private mixed $mockSubject;
    private mixed $mockEvent;
    private mixed $mockUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockEvent = $this->createMockEvent();
        $this->mockUser = $this->createMockUser();
        $this->mockSubject = $this->createMockSubject();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createMockEvent(): mixed
    {
        $mock = Mockery::mock('models\summit\SummitEvent');
        $mock->shouldReceive('getTitle')->andReturn('Welcome Reception');
        $mock->shouldReceive('getId')->andReturn(100);
        return $mock;
    }

    private function createMockUser(): mixed
    {
        $mock = Mockery::mock('models\main\Member');
        $mock->shouldReceive('getEmail')->andReturn('john@example.com');
        $mock->shouldReceive('getId')->andReturn(50);
        return $mock;
    }

    private function createMockSubject(): mixed
    {
        $mock = Mockery::mock('models\summit\RSVP');
        
        // Configure return values
        $mock->shouldReceive('getId')->andReturn(15);
        $mock->shouldReceive('getEvent')->andReturn($this->mockEvent);
        $mock->shouldReceive('getOwner')->andReturn($this->mockUser);
        $mock->shouldReceive('getStatus')->andReturn('confirmed');
        $mock->shouldReceive('getSeatType')->andReturn('standard');
        
        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new RSVPAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString('Welcome Reception', $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new RSVPAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = [
            'is_attending' => [true, false]
        ];
        
        $result = $formatter->format($this->mockSubject, $changeSet);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new RSVPAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new RSVPAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $result = $formatter->format(new \stdClass(), []);
        $this->assertNull($result);
    }
}
