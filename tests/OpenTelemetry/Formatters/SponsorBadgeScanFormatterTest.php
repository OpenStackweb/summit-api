<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\SponsorBadgeScanAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SponsorBadgeScanFormatterTest extends TestCase
{
    private const SCAN_ID = 5001;
    private const QR_CODE = 'QR123456789';
    private const SPONSOR_ID = 106;
    private const SPONSOR_COMPANY = 'Global Sponsors Ltd';
    private const USER_EMAIL = 'attendee@conference.com';

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

        $mockUser = Mockery::mock('models\main\Member');
        $mockUser->shouldReceive('getEmail')->andReturn(self::USER_EMAIL);

        $mockDate = new \DateTime('2024-02-09 12:00:00');

        $mock = Mockery::mock('models\summit\SponsorBadgeScan');
        $mock->shouldReceive('getId')->andReturn(self::SCAN_ID);
        $mock->shouldReceive('getQRCode')->andReturn(self::QR_CODE);
        $mock->shouldReceive('getScanDate')->andReturn($mockDate);
        $mock->shouldReceive('getSponsor')->andReturn($mockSponsor);
        $mock->shouldReceive('getUser')->andReturn($mockUser);

        return $mock;
    }

    public function testCreationAuditMessage(): void
    {
        $formatter = new SponsorBadgeScanAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::USER_EMAIL, $result);
    }

    public function testDeletionAuditMessage(): void
    {
        $formatter = new SponsorBadgeScanAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SponsorBadgeScanAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }
}
