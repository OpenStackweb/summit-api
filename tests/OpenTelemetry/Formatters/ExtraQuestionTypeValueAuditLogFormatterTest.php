<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\ExtraQuestionTypeValueAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class ExtraQuestionTypeValueAuditLogFormatterTest extends TestCase
{
    private const VALUE_ID = 333;
    private const VALUE_LABEL = 'Option A';
    private const VALUE_VALUE = 'OPTION_A';
    private const QUESTION_LABEL = 'Choose an Option';
    private const CHANGED_LABEL = 'Option B';

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
        $mockQuestion = Mockery::mock('App\Models\Foundation\ExtraQuestions\ExtraQuestionType');
        $mockQuestion->shouldReceive('getLabel')->andReturn(self::QUESTION_LABEL);

        $mock = Mockery::mock('App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeValue');
        $mock->shouldReceive('getId')->andReturn(self::VALUE_ID);
        $mock->shouldReceive('getLabel')->andReturn(self::VALUE_LABEL);
        $mock->shouldReceive('getValue')->andReturn(self::VALUE_VALUE);
        $mock->shouldReceive('getQuestion')->andReturn($mockQuestion);

        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new ExtraQuestionTypeValueAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::VALUE_LABEL, $result);
        $this->assertStringContainsString((string)self::VALUE_ID, $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new ExtraQuestionTypeValueAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = ['label' => [self::VALUE_LABEL, self::CHANGED_LABEL]];

        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new ExtraQuestionTypeValueAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new ExtraQuestionTypeValueAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    public function testFormatterHandlesEmptyChangeSet(): void
    {
        $formatter = new ExtraQuestionTypeValueAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }
}
