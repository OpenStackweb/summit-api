<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\SummitAttendeeNoteAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SummitAttendeeNoteAuditLogFormatterTest extends TestCase
{
    private const NOTE_ID = 222;
    private const ATTENDEE_ID = 456;
    private const ATTENDEE_EMAIL = 'attendee@example.com';
    private const NOTE_CONTENT = 'This is a test note content';
    private const SUMMIT_NAME = 'OpenStack Summit 2024';
    private const ATTENDEE_FIRST_NAME = 'Jane';
    private const ATTENDEE_LAST_NAME = 'Smith';

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

        $mockAttendee = Mockery::mock('models\summit\SummitAttendee');
        $mockAttendee->shouldReceive('getId')->andReturn(self::ATTENDEE_ID);
        $mockAttendee->shouldReceive('getEmail')->andReturn(self::ATTENDEE_EMAIL);
        $mockAttendee->shouldReceive('getSummit')->andReturn($mockSummit);
        $mockAttendee->shouldReceive('getFirstName')->andReturn(self::ATTENDEE_FIRST_NAME);
        $mockAttendee->shouldReceive('getSurname')->andReturn(self::ATTENDEE_LAST_NAME);

        $mock = Mockery::mock('models\summit\SummitAttendeeNote');
        $mock->shouldReceive('getId')->andReturn(self::NOTE_ID);
        $mock->shouldReceive('getContent')->andReturn(self::NOTE_CONTENT);
        $mock->shouldReceive('getOwner')->andReturn($mockAttendee);

        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new SummitAttendeeNoteAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::ATTENDEE_FIRST_NAME . ' ' . self::ATTENDEE_LAST_NAME, $result);
        $this->assertStringContainsString((string)self::NOTE_ID, $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new SummitAttendeeNoteAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = ['content' => ['Old content', 'New content']];

        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new SummitAttendeeNoteAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SummitAttendeeNoteAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    public function testFormatterHandlesEmptyChangeSet(): void
    {
        $formatter = new SummitAttendeeNoteAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }
}
