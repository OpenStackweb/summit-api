<?php

namespace Tests\OpenTelemetry\Formatters;

/**
 * Copyright 2026 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

use App\Audit\AuditContext;
use App\Audit\ConcreteFormatters\SummitRefundPolicyTypeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SummitRefundPolicyType;
use Mockery;
use Tests\TestCase;

class SummitRefundPolicyTypeAuditLogFormatterTest extends TestCase
{
    private const USER_ID = 456;
    private const USER_EMAIL = 'admin@example.com';
    private const USER_FIRST_NAME = 'Jane';
    private const USER_LAST_NAME = 'Smith';
    private const MOCK_ID = 1;
    private const SUMMIT_NAME = 'Test Summit';

    private SummitRefundPolicyTypeAuditLogFormatter $formatter_creation;
    private SummitRefundPolicyTypeAuditLogFormatter $formatter_update;
    private SummitRefundPolicyTypeAuditLogFormatter $formatter_deletion;
    private AuditContext $audit_context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter_creation = new SummitRefundPolicyTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $this->formatter_update = new SummitRefundPolicyTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $this->formatter_deletion = new SummitRefundPolicyTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);

        $this->audit_context = new AuditContext(
            userId: self::USER_ID,
            userEmail: self::USER_EMAIL,
            userFirstName: self::USER_FIRST_NAME,
            userLastName: self::USER_LAST_NAME
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testFormatCreationEvent(): void
    {
        $policy = $this->createMockPolicy('Full Refund', 30, 1.0);

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($policy, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Full Refund", $result);
        $this->assertStringContainsString("created", $result);
        $this->assertStringContainsString("100%", $result);
        $this->assertStringContainsString("30 days", $result);
    }

    public function testFormatUpdateEvent(): void
    {
        $policy = $this->createMockPolicy('Partial Refund', 14, 0.5);

        $this->formatter_update->setContext($this->audit_context);
        $result = $this->formatter_update->format($policy, [
            'until_x_days_before_event_starts' => [30, 14],
            'refund_rate' => [1.0, 0.5]
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Partial Refund", $result);
        $this->assertStringContainsString("updated", $result);
    }

    public function testFormatDeletionEvent(): void
    {
        $policy = $this->createMockPolicy('No Refund', 0, 0.0);

        $this->formatter_deletion->setContext($this->audit_context);
        $result = $this->formatter_deletion->format($policy, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("No Refund", $result);
        $this->assertStringContainsString("deleted", $result);
        $this->assertStringContainsString("0%", $result);
    }

    public function testFormatInvalidSubject(): void
    {
        $invalid_subject = new \stdClass();

        $result = $this->formatter_creation->format($invalid_subject, []);

        $this->assertNull($result);
    }

    private function createMockPolicy(string $name, int $days, float $rate): object
    {
        $mock = Mockery::mock(SummitRefundPolicyType::class);
        $mock->shouldReceive('getName')->andReturn($name);
        $mock->shouldReceive('getId')->andReturn(self::MOCK_ID);
        $mock->shouldReceive('getUntilXDaysBeforeEventStarts')->andReturn($days);
        $mock->shouldReceive('getRefundRate')->andReturn($rate);

        $summit = Mockery::mock(\models\summit\Summit::class);
        $summit->shouldReceive('getName')->andReturn(self::SUMMIT_NAME);
        $mock->shouldReceive('getSummit')->andReturn($summit);

        return $mock;
    }
}
