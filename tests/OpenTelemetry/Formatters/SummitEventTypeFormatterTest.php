<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\SummitEventTypeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SummitEventTypeFormatterTest extends TestCase
{
    private const EVENT_TYPE_ID = 123;
    private const EVENT_TYPE_NAME = 'Keynote';
    private const SUMMIT_NAME = 'OpenStack Summit 2024';
    private const EVENT_TYPE_COLOR = '#FF0000';
    private const IS_DEFAULT = true;
    private const IS_PRIVATE = false;

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

        $mock = Mockery::mock('models\summit\SummitEventType');
        $mock->shouldReceive('getId')->andReturn(self::EVENT_TYPE_ID);
        $mock->shouldReceive('getType')->andReturn(self::EVENT_TYPE_NAME);
        $mock->shouldReceive('getSummit')->andReturn($mockSummit);
        $mock->shouldReceive('getColor')->andReturn(self::EVENT_TYPE_COLOR);
        $mock->shouldReceive('isDefault')->andReturn(self::IS_DEFAULT);
        $mock->shouldReceive('isPrivate')->andReturn(self::IS_PRIVATE);

        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new SummitEventTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::EVENT_TYPE_NAME, $result);
        $this->assertStringContainsString(self::SUMMIT_NAME, $result);
        $this->assertStringContainsString(self::EVENT_TYPE_COLOR, $result);
        $this->assertStringContainsString('default: yes', $result);
        $this->assertStringContainsString('private: no', $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new SummitEventTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = ['color' => ['#FF0000', '#00FF00']];

        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
        $this->assertStringContainsString(self::EVENT_TYPE_NAME, $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new SummitEventTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
        $this->assertStringContainsString(self::EVENT_TYPE_NAME, $result);
        $this->assertStringContainsString(self::EVENT_TYPE_COLOR, $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SummitEventTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    public function testFormatterHandlesEmptyChangeSet(): void
    {
        $formatter = new SummitEventTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testFormatterHandlesNullCompanyAndSummit(): void
    {
        $mock = Mockery::mock('models\summit\SummitEventType');
        $mock->shouldReceive('getId')->andReturn(self::EVENT_TYPE_ID);
        $mock->shouldReceive('getType')->andReturn(self::EVENT_TYPE_NAME);
        $mock->shouldReceive('getSummit')->andReturn(null);
        $mock->shouldReceive('getColor')->andReturn(self::EVENT_TYPE_COLOR);
        $mock->shouldReceive('isDefault')->andReturn(self::IS_DEFAULT);
        $mock->shouldReceive('isPrivate')->andReturn(self::IS_PRIVATE);

        $formatter = new SummitEventTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($mock, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('Unknown Summit', $result);
    }
}
