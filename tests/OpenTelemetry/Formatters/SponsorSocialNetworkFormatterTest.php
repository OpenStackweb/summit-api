<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\SponsorSocialNetworkAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SponsorSocialNetworkFormatterTest extends TestCase
{
    private const NETWORK_ID = 1001;
    private const NETWORK_LINK = 'https://twitter.com/opensourceinc';
    private const ICON_CSS = 'fa-twitter';
    private const SPONSOR_ID = 102;
    private const SPONSOR_COMPANY = 'OpenSource Inc';

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

        $mock = Mockery::mock('models\summit\SponsorSocialNetwork');
        $mock->shouldReceive('getId')->andReturn(self::NETWORK_ID);
        $mock->shouldReceive('getLink')->andReturn(self::NETWORK_LINK);
        $mock->shouldReceive('getIconCSSClass')->andReturn(self::ICON_CSS);
        $mock->shouldReceive('getSponsor')->andReturn($mockSponsor);

        return $mock;
    }

    public function testCreationAuditMessage(): void
    {
        $formatter = new SponsorSocialNetworkAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::NETWORK_LINK, $result);
        $this->assertStringContainsString(self::ICON_CSS, $result);
    }

    public function testDeletionAuditMessage(): void
    {
        $formatter = new SponsorSocialNetworkAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SponsorSocialNetworkAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }
}
