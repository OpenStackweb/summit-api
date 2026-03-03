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

use App\Audit\ConcreteFormatters\RSVPInvitationAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class RSVPInvitationAuditLogFormatterTest extends TestCase
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

    private function createMockEvent(): mixed
    {
        $mock = Mockery::mock('models\summit\SummitEvent');
        $mock->shouldReceive('getTitle')->andReturn('Wedding Event');
        $mock->shouldReceive('getId')->andReturn(200);
        return $mock;
    }

    private function createMockAttendee(): mixed
    {
        $mock = Mockery::mock('models\summit\SummitAttendee');
        $mock->shouldReceive('getEmail')->andReturn('attendee@example.com');
        $mock->shouldReceive('getId')->andReturn(75);
        return $mock;
    }

    private function createMockSubject(): mixed
    {
        $mock = Mockery::mock('App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation');
        
        // Configure return values
        $mock->shouldReceive('getId')->andReturn(20);
        $mock->shouldReceive('getInvitee')->andReturn($this->createMockAttendee());
        $mock->shouldReceive('getEvent')->andReturn($this->createMockEvent());
        
        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new RSVPInvitationAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString('attendee@example.com', $result);
        $this->assertStringContainsString('Wedding Event', $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new RSVPInvitationAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = [
            'confirmed' => [false, true]
        ];
        
        $result = $formatter->format($this->mockSubject, $changeSet);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
        $this->assertStringContainsString('attendee@example.com', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new RSVPInvitationAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new RSVPInvitationAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $result = $formatter->format(new \stdClass(), []);
        $this->assertNull($result);
    }
}
