<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\SummitOrderExtraQuestionTypeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SummitOrderExtraQuestionTypeAuditLogFormatterTest extends TestCase
{
    private const QUESTION_ID = 444;
    private const QUESTION_LABEL = 'Dietary Restrictions';
    private const QUESTION_TYPE = 'CheckboxList';
    private const SUMMIT_NAME = 'OpenStack Summit 2024';
    private const NEW_LABEL = 'Food Preferences';

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

        $mock = Mockery::mock('models\summit\SummitOrderExtraQuestionType');
        $mock->shouldReceive('getId')->andReturn(self::QUESTION_ID);
        $mock->shouldReceive('getLabel')->andReturn(self::QUESTION_LABEL);
        $mock->shouldReceive('getType')->andReturn(self::QUESTION_TYPE);
        $mock->shouldReceive('getSummit')->andReturn($mockSummit);

        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new SummitOrderExtraQuestionTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::QUESTION_LABEL, $result);
        $this->assertStringContainsString((string)self::QUESTION_ID, $result);
        $this->assertStringContainsString(self::QUESTION_TYPE, $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new SummitOrderExtraQuestionTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = ['label' => [self::QUESTION_LABEL, self::NEW_LABEL]];

        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new SummitOrderExtraQuestionTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SummitOrderExtraQuestionTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    public function testFormatterHandlesEmptyChangeSet(): void
    {
        $formatter = new SummitOrderExtraQuestionTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }
}
