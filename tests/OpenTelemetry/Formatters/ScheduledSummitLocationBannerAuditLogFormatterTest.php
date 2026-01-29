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

use App\Audit\ConcreteFormatters\ScheduledSummitLocationBannerAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class ScheduledSummitLocationBannerAuditLogFormatterTest extends TestCase
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
        
        $mockLocation = Mockery::mock('models\summit\SummitGeoLocatedLocation');
        $mockLocation->shouldReceive('getName')->andReturn('Keynote Hall');
        $mockLocation->shouldReceive('getSummit')->andReturn($mockSummit);
        
        $mock = Mockery::mock('App\Models\Foundation\Summit\Locations\Banners\ScheduledSummitLocationBanner');
        
        // Configure return values
        $mock->shouldReceive('getId')->andReturn(222);
        $mock->shouldReceive('getTitle')->andReturn('Live Keynote Banner');
        $mock->shouldReceive('getLocation')->andReturn($mockLocation);
        $mock->shouldReceive('getStartDate')->andReturn(new \DateTime('2024-01-01 09:00:00'));
        $mock->shouldReceive('getEndDate')->andReturn(new \DateTime('2024-01-01 17:00:00'));
        
        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new ScheduledSummitLocationBannerAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString('Live Keynote Banner', $result);
        $this->assertStringContainsString('222', $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new ScheduledSummitLocationBannerAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = ['title' => ['Live Keynote Banner', 'Keynote Session Banner'], 'start_date' => ['2024-01-01 09:00:00', '2024-01-01 10:00:00']];
        
        $result = $formatter->format($this->mockSubject, $changeSet);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new ScheduledSummitLocationBannerAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new ScheduledSummitLocationBannerAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);
        $this->assertNull($result);
    }

    public function testFormatterHandlesEmptyChangeSet(): void
    {
        $formatter = new ScheduledSummitLocationBannerAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testPropertiesExist(): void
    {
        $formatter = new ScheduledSummitLocationBannerAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        
        $this->assertTrue(method_exists($this->mockSubject, 'getId'));
        $this->assertTrue(method_exists($this->mockSubject, 'getTitle'));
        $this->assertTrue(method_exists($this->mockSubject, 'getLocation'));
        $this->assertTrue(method_exists($this->mockSubject, 'getStartDate'));
        $this->assertTrue(method_exists($this->mockSubject, 'getEndDate'));
        
        $result = $formatter->format($this->mockSubject, []);
        $this->assertNotNull($result);
    }
}
