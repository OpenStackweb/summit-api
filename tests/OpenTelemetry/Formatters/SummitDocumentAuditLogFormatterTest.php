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

use App\Audit\ConcreteFormatters\SummitDocumentAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SummitDocumentAuditLogFormatterTest extends TestCase
{
    private const SUMMIT_NAME = 'OpenStack Summit 2024';
    private const DOCUMENT_ID = 123;
    private const DOCUMENT_NAME = 'Summit Guidelines';
    private const DOCUMENT_LABEL = 'Important Document';

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
        $mockSummit->shouldReceive('getName')->andReturn(self::SUMMIT_NAME);
        
        $mock = Mockery::mock('models\summit\SummitDocument');
        
        // Configure return values
        $mock->shouldReceive('getId')->andReturn(self::DOCUMENT_ID);
        $mock->shouldReceive('getName')->andReturn(self::DOCUMENT_NAME);
        $mock->shouldReceive('getLabel')->andReturn(self::DOCUMENT_LABEL);
        $mock->shouldReceive('getSummit')->andReturn($mockSummit);
        
        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new SummitDocumentAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::DOCUMENT_NAME, $result);
        $this->assertStringContainsString(self::DOCUMENT_LABEL, $result);
        $this->assertStringContainsString((string)self::DOCUMENT_ID, $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new SummitDocumentAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = [
            'name' => [self::DOCUMENT_NAME, 'Summit Rules'],
            'label' => [self::DOCUMENT_LABEL, 'Critical Document']
        ];
        
        $result = $formatter->format($this->mockSubject, $changeSet);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new SummitDocumentAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SummitDocumentAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);
        $this->assertNull($result);
    }
}
