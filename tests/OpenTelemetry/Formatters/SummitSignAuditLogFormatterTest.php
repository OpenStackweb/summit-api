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

use App\Audit\ConcreteFormatters\SummitSignAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SummitSignAuditLogFormatterTest extends TestCase
{
    private const SUMMIT_NAME = 'OpenStack Summit 2024';
    private const SIGN_ID = 321;
    private const SIGN_TEMPLATE = 'Welcome Template';
    private const LOCATION_NAME = 'Registration Area';

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
        
        $mockLocation = Mockery::mock('models\summit\SummitAbstractLocation');
        $mockLocation->shouldReceive('getName')->andReturn(self::LOCATION_NAME);
        
        $mock = Mockery::mock('App\Models\Foundation\Summit\Signs\SummitSign');
        
        // Configure return values
        $mock->shouldReceive('getId')->andReturn(self::SIGN_ID);
        $mock->shouldReceive('getTemplate')->andReturn(self::SIGN_TEMPLATE);
        $mock->shouldReceive('getLocation')->andReturn($mockLocation);
        $mock->shouldReceive('getSummit')->andReturn($mockSummit);
        
        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new SummitSignAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString((string)self::SIGN_ID, $result);
        $this->assertStringContainsString(self::SIGN_TEMPLATE, $result);
        $this->assertStringContainsString(self::LOCATION_NAME, $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new SummitSignAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = ['template' => [self::SIGN_TEMPLATE, 'Goodbye Template']];
        
        $result = $formatter->format($this->mockSubject, $changeSet);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new SummitSignAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SummitSignAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);
        $this->assertNull($result);
    }
}
