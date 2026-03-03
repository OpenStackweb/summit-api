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

use App\Audit\ConcreteFormatters\SummitScheduleConfigAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SummitScheduleConfigAuditLogFormatterTest extends TestCase
{
    private const SUMMIT_NAME = 'OpenStack Summit 2024';
    private const CONFIG_ID = 456;
    private const CONFIG_KEY = 'main_schedule';
    private const CONFIG_IS_DEFAULT = true;
    private const CONFIG_COLOR_SOURCE = 'EVENT_TYPES';
    private const ALTERNATE_CONFIG_KEY = 'alternate_schedule';
    private const ALTERNATE_COLOR_SOURCE = 'TRACK';

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
        
        $mock = Mockery::mock('models\summit\SummitScheduleConfig');
        
        // Configure return values
        $mock->shouldReceive('getId')->andReturn(self::CONFIG_ID);
        $mock->shouldReceive('getKey')->andReturn(self::CONFIG_KEY);
        $mock->shouldReceive('isDefault')->andReturn(self::CONFIG_IS_DEFAULT);
        $mock->shouldReceive('getColorSource')->andReturn(self::CONFIG_COLOR_SOURCE);
        $mock->shouldReceive('getSummit')->andReturn($mockSummit);
        
        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new SummitScheduleConfigAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::CONFIG_KEY, $result);
        $this->assertStringContainsString((string)self::CONFIG_ID, $result);
        $this->assertStringContainsString('default', $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new SummitScheduleConfigAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = [
            'key' => [self::CONFIG_KEY, self::ALTERNATE_CONFIG_KEY],
            'color_source' => [self::CONFIG_COLOR_SOURCE, self::ALTERNATE_COLOR_SOURCE]
        ];
        
        $result = $formatter->format($this->mockSubject, $changeSet);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new SummitScheduleConfigAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SummitScheduleConfigAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);
        $this->assertNull($result);
    }
}
