<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\SummitSponsorExtraQuestionTypeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SummitSponsorExtraQuestionTypeAuditLogFormatterTest extends TestCase
{
    private const QUESTION_ID = 888;
    private const QUESTION_LABEL = 'Company Size';
    private const QUESTION_TYPE = 'Dropdown';
    private const SPONSOR_ID = 102;
    private const SPONSOR_COMPANY_NAME = 'Acme Corp';
    private const NEW_QUESTION_LABEL = 'Company Industry';

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
        $mockCompany = Mockery::mock('models\main\Company');
        $mockCompany->shouldReceive('getName')->andReturn(self::SPONSOR_COMPANY_NAME);

        $mockSponsor = Mockery::mock('models\summit\Sponsor');
        $mockSponsor->shouldReceive('getId')->andReturn(self::SPONSOR_ID);
        $mockSponsor->shouldReceive('getCompany')->andReturn($mockCompany);

        $mock = Mockery::mock('App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType');
        $mock->shouldReceive('getId')->andReturn(self::QUESTION_ID);
        $mock->shouldReceive('getLabel')->andReturn(self::QUESTION_LABEL);
        $mock->shouldReceive('getType')->andReturn(self::QUESTION_TYPE);
        $mock->shouldReceive('getSponsor')->andReturn($mockSponsor);

        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new SummitSponsorExtraQuestionTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::QUESTION_LABEL, $result);
        $this->assertStringContainsString((string)self::QUESTION_ID, $result);
        $this->assertStringContainsString(self::QUESTION_TYPE, $result);
        $this->assertStringContainsString((string)self::SPONSOR_ID, $result);
        $this->assertStringContainsString(self::SPONSOR_COMPANY_NAME, $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new SummitSponsorExtraQuestionTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = ['label' => [self::QUESTION_LABEL, self::NEW_QUESTION_LABEL]];

        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new SummitSponsorExtraQuestionTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SummitSponsorExtraQuestionTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    public function testFormatterHandlesEmptyChangeSet(): void
    {
        $formatter = new SummitSponsorExtraQuestionTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }
}
