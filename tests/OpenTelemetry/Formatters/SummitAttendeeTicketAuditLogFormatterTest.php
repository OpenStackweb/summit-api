<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\SummitAttendeeTicketAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SummitAttendeeTicketAuditLogFormatterTest extends TestCase
{
    private const TICKET_ID = 789;
    private const TICKET_NUMBER = 'TICKET-2024-001';
    private const TICKET_STATUS = 'Active';
    private const TICKET_TYPE = 'General Admission';
    private const ORDER_ID = 555;
    private const ORDER_NUMBER = 'ORDER-2024-0001';
    private const OWNER_EMAIL = 'owner@example.com';
    private const SUMMIT_NAME = 'OpenStack Summit 2024';
    private const NEW_STATUS = 'Cancelled';
    private const OWNER_FIRST_NAME = 'John';
    private const OWNER_LAST_NAME = 'Doe';

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

        $mockOwner = Mockery::mock('models\summit\SummitAttendee');
        $mockOwner->shouldReceive('getFirstName')->andReturn(self::OWNER_FIRST_NAME);
        $mockOwner->shouldReceive('getSurname')->andReturn(self::OWNER_LAST_NAME);

        $mockOrder = Mockery::mock('models\summit\SummitOrder');
        $mockOrder->shouldReceive('getId')->andReturn(self::ORDER_ID);
        $mockOrder->shouldReceive('getNumber')->andReturn(self::ORDER_NUMBER);
        $mockOrder->shouldReceive('getSummit')->andReturn($mockSummit);

        $mockTicketType = Mockery::mock('models\summit\SummitTicketType');
        $mockTicketType->shouldReceive('getName')->andReturn(self::TICKET_TYPE);

        $mock = Mockery::mock('models\summit\SummitAttendeeTicket');
        $mock->shouldReceive('getId')->andReturn(self::TICKET_ID);
        $mock->shouldReceive('getNumber')->andReturn(self::TICKET_NUMBER);
        $mock->shouldReceive('getOrder')->andReturn($mockOrder);
        $mock->shouldReceive('getTicketType')->andReturn($mockTicketType);
        $mock->shouldReceive('getStatus')->andReturn(self::TICKET_STATUS);
        $mock->shouldReceive('getOwner')->andReturn($mockOwner);

        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new SummitAttendeeTicketAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::OWNER_FIRST_NAME . ' ' . self::OWNER_LAST_NAME, $result);
        $this->assertStringContainsString((string)self::TICKET_ID, $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new SummitAttendeeTicketAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = ['status' => [self::TICKET_STATUS, self::NEW_STATUS]];

        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new SummitAttendeeTicketAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SummitAttendeeTicketAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    public function testFormatterHandlesEmptyChangeSet(): void
    {
        $formatter = new SummitAttendeeTicketAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }
}
