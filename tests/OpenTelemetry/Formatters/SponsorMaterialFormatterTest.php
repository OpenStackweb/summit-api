<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\SponsorMaterialAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SponsorMaterialFormatterTest extends TestCase
{
    private const MATERIAL_ID = 3001;
    private const MATERIAL_NAME = 'Annual Report 2024';
    private const MATERIAL_TYPE = 'Slide';
    private const MATERIAL_ORDER = 2;
    private const SPONSOR_ID = 104;
    private const SPONSOR_COMPANY = 'Tech Solutions Inc';

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

        $mock = Mockery::mock('models\summit\SponsorMaterial');
        $mock->shouldReceive('getId')->andReturn(self::MATERIAL_ID);
        $mock->shouldReceive('getName')->andReturn(self::MATERIAL_NAME);
        $mock->shouldReceive('getType')->andReturn(self::MATERIAL_TYPE);
        $mock->shouldReceive('getOrder')->andReturn(self::MATERIAL_ORDER);
        $mock->shouldReceive('getSponsor')->andReturn($mockSponsor);

        return $mock;
    }

    public function testCreationAuditMessage(): void
    {
        $formatter = new SponsorMaterialAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::MATERIAL_NAME, $result);
        $this->assertStringContainsString(self::MATERIAL_TYPE, $result);
    }

    public function testDeletionAuditMessage(): void
    {
        $formatter = new SponsorMaterialAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SponsorMaterialAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }
}
