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

use App\Audit\ConcreteFormatters\RSVPQuestionTemplateAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class RSVPQuestionTemplateAuditLogFormatterTest extends TestCase
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

    private function createMockTemplate(): mixed
    {
        $mock = Mockery::mock('App\Models\Foundation\Summit\Events\RSVP\RSVPTemplate');
        $mock->shouldReceive('getId')->andReturn(999);
        return $mock;
    }

    private function createMockSubject(): mixed
    {
        $mock = Mockery::mock('App\Models\Foundation\Summit\Events\RSVP\RSVPQuestionTemplate');
        
        // Configure return values
        $mock->shouldReceive('getId')->andReturn(25);
        $mock->shouldReceive('getName')->andReturn('attendance_question');
        $mock->shouldReceive('getLabel')->andReturn('Will you attend?');
        $mock->shouldReceive('getTemplate')->andReturn($this->createMockTemplate());
        $mock->shouldReceive('isMandatory')->andReturn(true);
        $mock->shouldReceive('getOrder')->andReturn(1);
        
        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new RSVPQuestionTemplateAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString('Will you attend?', $result);
        $this->assertStringContainsString('required', $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new RSVPQuestionTemplateAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = [
            'label' => ['Will you attend?', 'Can you attend?']
        ];
        
        $result = $formatter->format($this->mockSubject, $changeSet);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
        $this->assertStringContainsString('Will you attend?', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new RSVPQuestionTemplateAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new RSVPQuestionTemplateAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $result = $formatter->format(new \stdClass(), []);
        $this->assertNull($result);
    }
}
