<?php

namespace Tests\OpenTelemetry\Formatters;

use App\Audit\ConcreteFormatters\SponsorUserInfoGrantAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Mockery;
use Tests\TestCase;

class SponsorUserInfoGrantFormatterTest extends TestCase
{
    private const GRANT_ID = 4001;
    private const SPONSOR_ID = 105;
    private const SPONSOR_COMPANY = 'Enterprise Solutions';
    private const ALLOWED_USER_EMAIL = 'user@example.com';

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
        $mockUser->shouldReceive('getEmail')->andReturn(self::ALLOWED_USER_EMAIL);

        $mock = Mockery::mock('models\summit\SponsorUserInfoGrant');
        $mock->shouldReceive('getId')->andReturn(self::GRANT_ID);
        $mock->shouldReceive('getSponsor')->andReturn($mockSponsor);
        $mock->shouldReceive('getAllowedUser')->andReturn($mockUser);

        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $formatter = new SponsorUserInfoGrantAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::ALLOWED_USER_EMAIL, $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $formatter = new SponsorUserInfoGrantAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $changeSet = [
            'allowed_user_id' => [101, 102]
        ];
        $result = $formatter->format($this->mockSubject, $changeSet);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
        $this->assertStringContainsString('allowed_user_id', $result);
        $this->assertStringContainsString('101', $result);
        $this->assertStringContainsString('102', $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $formatter = new SponsorUserInfoGrantAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SponsorUserInfoGrantAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    public function testFormatterHandlesEmptyChangeSet(): void
    {
        $formatter = new SponsorUserInfoGrantAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $formatter->setContext(AuditContextBuilder::default()->build());
        $result = $formatter->format($this->mockSubject, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
    }
}
