<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\SponsorAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SponsorFormatterTest extends TestCase
{
    private const SPONSOR_ID = 42;
    private const COMPANY_NAME = 'OpenStack Foundation';
    private const SUMMIT_NAME = 'OpenStack Summit 2024';
    private const SPONSOR_ORDER = 1;

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
        $mockCompany->shouldReceive('getName')->andReturn(self::COMPANY_NAME);

        $mockSummit = Mockery::mock('models\summit\Summit');
        $mockSummit->shouldReceive('getName')->andReturn(self::SUMMIT_NAME);

        $mock = Mockery::mock('models\summit\Sponsor');
        $mock->shouldReceive('getId')->andReturn(self::SPONSOR_ID);
        $mock->shouldReceive('getCompany')->andReturn($mockCompany);
        $mock->shouldReceive('getSummit')->andReturn($mockSummit);
        $mock->shouldReceive('getOrder')->andReturn(self::SPONSOR_ORDER);

        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new SponsorAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::COMPANY_NAME, $result);
        $this->assertStringContainsString(self::SUMMIT_NAME, $result);
        $this->assertStringContainsString((string)self::SPONSOR_ID, $result);
        $this->assertStringContainsString('order', $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new SponsorAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = ['order' => [1, 2]];

        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
        $this->assertStringContainsString(self::COMPANY_NAME, $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new SponsorAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
        $this->assertStringContainsString(self::COMPANY_NAME, $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SponsorAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    public function testFormatterHandlesEmptyChangeSet(): void
    {
        $formatter = new SponsorAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }
}
