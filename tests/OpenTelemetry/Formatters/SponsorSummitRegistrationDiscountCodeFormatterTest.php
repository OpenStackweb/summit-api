<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\SponsorSummitRegistrationDiscountCodeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SponsorSummitRegistrationDiscountCodeFormatterTest extends TestCase
{
    private const CODE_ID = 666;
    private const CODE_VALUE = 'SPONSORDISCOUNT2024';
    private const SPONSOR_ID = 101;
    private const SUMMIT_NAME = 'OpenStack Summit 2024';
    private const DISCOUNT_RATE = 10.5;
    private const NEW_CODE_VALUE = 'SPONSORDISCOUNT2025';

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

        $mock = Mockery::mock('models\summit\SponsorSummitRegistrationDiscountCode');
        $mock->shouldReceive('getId')->andReturn(self::CODE_ID);
        $mock->shouldReceive('getCode')->andReturn(self::CODE_VALUE);
        $mock->shouldReceive('getSummit')->andReturn($mockSummit);
        $mock->shouldReceive('getSponsorId')->andReturn(self::SPONSOR_ID);
        $mock->shouldReceive('getRate')->andReturn(self::DISCOUNT_RATE);
        $mock->shouldReceive('getAmount')->andReturn(0);

        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new SponsorSummitRegistrationDiscountCodeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::CODE_VALUE, $result);
        $this->assertStringContainsString((string)self::CODE_ID, $result);
        $this->assertStringContainsString((string)self::SPONSOR_ID, $result);
        $this->assertStringContainsString('rate', $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new SponsorSummitRegistrationDiscountCodeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = ['code' => [self::CODE_VALUE, self::NEW_CODE_VALUE]];

        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
        $this->assertStringContainsString('current:', $result);
        $this->assertStringContainsString('rate', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new SponsorSummitRegistrationDiscountCodeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
        $this->assertStringContainsString('rate', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SponsorSummitRegistrationDiscountCodeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    public function testFormatterHandlesEmptyChangeSet(): void
    {
        $formatter = new SponsorSummitRegistrationDiscountCodeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }
}
