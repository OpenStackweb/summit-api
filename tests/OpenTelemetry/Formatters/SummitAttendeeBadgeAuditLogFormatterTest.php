<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\SummitAttendeeBadgeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SummitAttendeeBadgeAuditLogFormatterTest extends TestCase
{
    private const BADGE_ID = 111;
    private const TICKET_ID = 789;
    private const TICKET_NUMBER = 'TICKET-2024-001';
    private const SUMMIT_NAME = 'OpenStack Summit 2024';

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

        $mockOrder = Mockery::mock('models\summit\SummitOrder');
        $mockOrder->shouldReceive('getSummit')->andReturn($mockSummit);

        $mockTicket = Mockery::mock('models\summit\SummitAttendeeTicket');
        $mockTicket->shouldReceive('getId')->andReturn(self::TICKET_ID);
        $mockTicket->shouldReceive('getNumber')->andReturn(self::TICKET_NUMBER);
        $mockTicket->shouldReceive('getOrder')->andReturn($mockOrder);

        $mock = Mockery::mock('models\summit\SummitAttendeeBadge');
        $mock->shouldReceive('getId')->andReturn(self::BADGE_ID);
        $mock->shouldReceive('isVoid')->andReturn(false);
        $mock->shouldReceive('getTicket')->andReturn($mockTicket);

        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new SummitAttendeeBadgeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::TICKET_NUMBER, $result);
        $this->assertStringContainsString((string)self::TICKET_ID, $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new SummitAttendeeBadgeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = ['is_void' => [false, true]];

        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new SummitAttendeeBadgeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SummitAttendeeBadgeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    public function testFormatterHandlesEmptyChangeSet(): void
    {
        $formatter = new SummitAttendeeBadgeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }
}
