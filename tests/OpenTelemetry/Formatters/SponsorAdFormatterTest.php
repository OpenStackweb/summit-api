<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\SponsorAdAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SponsorAdFormatterTest extends TestCase
{
    private const AD_ID = 2001;
    private const AD_TEXT = 'Premium Sponsorship Package';
    private const AD_LINK = 'https://company.com/sponsorship';
    private const AD_ORDER = 1;
    private const SPONSOR_ID = 103;
    private const SPONSOR_COMPANY = 'Premium Corp';

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
        $mockCompany->shouldReceive('getName')->andReturn(self::SPONSOR_COMPANY);

        $mockSponsor = Mockery::mock('models\summit\Sponsor');
        $mockSponsor->shouldReceive('getId')->andReturn(self::SPONSOR_ID);
        $mockSponsor->shouldReceive('getCompany')->andReturn($mockCompany);

        $mock = Mockery::mock('models\summit\SponsorAd');
        $mock->shouldReceive('getId')->andReturn(self::AD_ID);
        $mock->shouldReceive('getText')->andReturn(self::AD_TEXT);
        $mock->shouldReceive('getLink')->andReturn(self::AD_LINK);
        $mock->shouldReceive('getOrder')->andReturn(self::AD_ORDER);
        $mock->shouldReceive('getSponsor')->andReturn($mockSponsor);

        return $mock;
    }

    public function testCreationAuditMessage(): void
    {
        $formatter = new SponsorAdAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::AD_TEXT, $result);
    }

    public function testDeletionAuditMessage(): void
    {
        $formatter = new SponsorAdAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SponsorAdAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }
}
