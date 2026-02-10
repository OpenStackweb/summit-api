<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\SummitOrderAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SummitOrderAuditLogFormatterTest extends TestCase
{
    private const ORDER_ID = 555;
    private const ORDER_NUMBER = 'ORDER-2024-0001';
    private const ORDER_STATUS = 'Approved';
    private const SUMMIT_NAME = 'OpenStack Summit 2024';
    private const OWNER_EMAIL = 'buyer@example.com';
    private const NEW_STATUS = 'Refunded';

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

        $mockOwner = Mockery::mock('models\main\Member');
        $mockOwner->shouldReceive('getEmail')->andReturn(self::OWNER_EMAIL);

        $mock = Mockery::mock('models\summit\SummitOrder');
        $mock->shouldReceive('getId')->andReturn(self::ORDER_ID);
        $mock->shouldReceive('getNumber')->andReturn(self::ORDER_NUMBER);
        $mock->shouldReceive('getStatus')->andReturn(self::ORDER_STATUS);
        $mock->shouldReceive('getSummit')->andReturn($mockSummit);
        $mock->shouldReceive('getOwner')->andReturn($mockOwner);

        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new SummitOrderAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::ORDER_NUMBER, $result);
        $this->assertStringContainsString((string)self::ORDER_ID, $result);
        $this->assertStringContainsString(self::ORDER_STATUS, $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new SummitOrderAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = ['status' => [self::ORDER_STATUS, self::NEW_STATUS]];

        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new SummitOrderAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SummitOrderAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    public function testFormatterHandlesEmptyChangeSet(): void
    {
        $formatter = new SummitOrderAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }
}
