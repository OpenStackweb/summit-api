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

use App\Audit\ConcreteFormatters\PresentationCategoryAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class PresentationCategoryAuditLogFormatterTest extends TestCase
{
    private mixed $mockSubject;
    private mixed $mockSummit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockSummit = $this->createMockSummit();
        $this->mockSubject = $this->createMockSubject();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createMockSummit(): mixed
    {
        $mock = Mockery::mock('models\summit\Summit');
        $mock->shouldReceive('getName')->andReturn('OpenStack Summit 2024');
        return $mock;
    }

    private function createMockSubject(): mixed
    {
        $mock = Mockery::mock('models\summit\PresentationCategory');
        
        // Configure return values
        $mock->shouldReceive('getId')->andReturn(10);
        $mock->shouldReceive('getTitle')->andReturn('Cloud Architecture');
        $mock->shouldReceive('getCode')->andReturn('CLOUD-ARCH');
        $mock->shouldReceive('getSummit')->andReturn($this->mockSummit);
        
        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new PresentationCategoryAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString('Cloud Architecture', $result);
        $this->assertStringContainsString('CLOUD-ARCH', $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new PresentationCategoryAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = [
            'title' => ['Cloud Architecture', 'Infrastructure & Architecture']
        ];
        
        $result = $formatter->format($this->mockSubject, $changeSet);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new PresentationCategoryAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new PresentationCategoryAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $result = $formatter->format(new \stdClass(), []);
        $this->assertNull($result);
    }
}
