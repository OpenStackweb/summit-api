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

use App\Audit\ConcreteFormatters\TrackTagGroupAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class TrackTagGroupAuditLogFormatterTest extends TestCase
{
    private const SUMMIT_NAME = 'OpenStack Summit 2024';
    private const TAG_GROUP_ID = 654;
    private const TAG_GROUP_NAME = 'Technology';
    private const TAG_GROUP_LABEL = 'Technology Tags';

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
        
        $mock = Mockery::mock('App\Models\Foundation\Summit\TrackTagGroup');
        
        // Configure return values
        $mock->shouldReceive('getId')->andReturn(self::TAG_GROUP_ID);
        $mock->shouldReceive('getName')->andReturn(self::TAG_GROUP_NAME);
        $mock->shouldReceive('getLabel')->andReturn(self::TAG_GROUP_LABEL);
        $mock->shouldReceive('getSummit')->andReturn($mockSummit);
        
        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new TrackTagGroupAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::TAG_GROUP_NAME, $result);
        $this->assertStringContainsString(self::TAG_GROUP_LABEL, $result);
        $this->assertStringContainsString((string)self::TAG_GROUP_ID, $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new TrackTagGroupAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = [
            'name' => [self::TAG_GROUP_NAME, 'Infrastructure'],
            'label' => [self::TAG_GROUP_LABEL, 'Infrastructure Tags']
        ];
        
        $result = $formatter->format($this->mockSubject, $changeSet);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new TrackTagGroupAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);
        
        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new TrackTagGroupAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);
        $this->assertNull($result);
    }
}
