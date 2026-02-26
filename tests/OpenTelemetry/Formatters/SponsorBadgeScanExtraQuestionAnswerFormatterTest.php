<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\SponsorBadgeScanExtraQuestionAnswerAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SponsorBadgeScanExtraQuestionAnswerFormatterTest extends TestCase
{
    private const ANSWER_ID = 6001;
    private const ANSWER_VALUE = 'Company Size: 500+ employees';
    private const QUESTION_LABEL = 'Company Size';
    private const SPONSOR_ID = 107;

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
        $mockSponsor = Mockery::mock('models\summit\Sponsor');
        $mockSponsor->shouldReceive('getId')->andReturn(self::SPONSOR_ID);

        $mockBadgeScan = Mockery::mock('models\summit\SponsorBadgeScan');
        $mockBadgeScan->shouldReceive('getSponsor')->andReturn($mockSponsor);

        $mockQuestion = Mockery::mock('App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType');
        $mockQuestion->shouldReceive('getLabel')->andReturn(self::QUESTION_LABEL);

        $mock = Mockery::mock('models\summit\SponsorBadgeScanExtraQuestionAnswer');
        $mock->shouldReceive('getId')->andReturn(self::ANSWER_ID);
        $mock->shouldReceive('getValue')->andReturn(self::ANSWER_VALUE);
        $mock->shouldReceive('getQuestion')->andReturn($mockQuestion);
        $mock->shouldReceive('getBadgeScan')->andReturn($mockBadgeScan);

        return $mock;
    }

    public function testCreationAuditMessage(): void
    {
        $formatter = new SponsorBadgeScanExtraQuestionAnswerAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::QUESTION_LABEL, $result);
        $this->assertStringContainsString(self::ANSWER_VALUE, $result);
    }

    public function testDeletionAuditMessage(): void
    {
        $formatter = new SponsorBadgeScanExtraQuestionAnswerAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SponsorBadgeScanExtraQuestionAnswerAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }
}
