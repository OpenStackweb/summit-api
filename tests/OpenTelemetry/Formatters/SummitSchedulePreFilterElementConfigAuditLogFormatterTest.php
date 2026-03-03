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

use App\Audit\ConcreteFormatters\SummitSchedulePreFilterElementConfigAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SummitSchedulePreFilterElementConfigAuditLogFormatterTest extends TestCase
{
    private const FILTER_ELEMENT_ID = 789;
    private const FILTER_ELEMENT_TYPE = 'EVENT_TYPE_ID';
    private const FILTER_ELEMENT_TYPE_UPDATE = 'TRACK_ID';
    private const CONFIG_KEY = 'main_schedule';

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
        return $this->createMockSubjectWithConfig(true);
    }

    private function createMockSubjectWithConfig(bool $hasConfig): mixed
    {
        $mock = Mockery::mock('models\summit\SummitSchedulePreFilterElementConfig');
        
        // Configure return values
        $mock->shouldReceive('getId')->andReturn(self::FILTER_ELEMENT_ID);
        $mock->shouldReceive('getType')->andReturn(self::FILTER_ELEMENT_TYPE);
        $mock->shouldReceive('hasConfig')->andReturn($hasConfig);
        
        if ($hasConfig) {
            $mockConfig = Mockery::mock('models\summit\SummitScheduleConfig');
            $mockConfig->shouldReceive('getKey')->andReturn(self::CONFIG_KEY);
            $mock->shouldReceive('getConfig')->andReturn($mockConfig);
        }
        
        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new SummitSchedulePreFilterElementConfigAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString((string)self::FILTER_ELEMENT_ID, $result);
        $this->assertStringContainsString(self::FILTER_ELEMENT_TYPE, $result);
        $this->assertStringContainsString(self::CONFIG_KEY, $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new SummitSchedulePreFilterElementConfigAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = ['type' => [self::FILTER_ELEMENT_TYPE, self::FILTER_ELEMENT_TYPE_UPDATE]];
        
        $result = $formatter->format($this->mockSubject, $changeSet);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new SummitSchedulePreFilterElementConfigAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SummitSchedulePreFilterElementConfigAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);
        $this->assertNull($result);
    }

    public function testFormatterHandlesNullConfigGracefully(): void
    {
        $mockSubjectNoConfig = $this->createMockSubjectWithConfig(false);
        
        $formatter = new SummitSchedulePreFilterElementConfigAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($mockSubjectNoConfig, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
        $this->assertStringContainsString('Unknown Config', $result);
        $this->assertStringContainsString((string)self::FILTER_ELEMENT_ID, $result);
    }
}
