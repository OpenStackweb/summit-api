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
use App\Audit\ConcreteFormatters\PrePaidSummitRegistrationDiscountCodeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\PrePaidSummitRegistrationDiscountCode;
use Mockery;
use Tests\TestCase;

class PrePaidSummitRegistrationDiscountCodeAuditLogFormatterTest extends TestCase
{
    private const USER_ID = 999;
    private const USER_EMAIL = 'andres.tejerina@fntech.com';
    private const USER_FIRST_NAME = 'Andres';
    private const USER_LAST_NAME = 'Tejerina';
    private const MOCK_ID = 1;
    private const SUMMIT_NAME = 'Test Summit';

    private PrePaidSummitRegistrationDiscountCodeAuditLogFormatter $formatter_creation;
    private PrePaidSummitRegistrationDiscountCodeAuditLogFormatter $formatter_update;
    private PrePaidSummitRegistrationDiscountCodeAuditLogFormatter $formatter_deletion;
    private AuditContext $audit_context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter_creation = new PrePaidSummitRegistrationDiscountCodeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $this->formatter_update = new PrePaidSummitRegistrationDiscountCodeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $this->formatter_deletion = new PrePaidSummitRegistrationDiscountCodeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);

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

    public function testFormatCreationEventWithRate(): void
    {
        $code = $this->createMockCode('EARLY2024', 0.15, 0, 50, true, 'Alice', 'Brown');

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($code, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("EARLY2024", $result);
        $this->assertStringContainsString("created", $result);
        $this->assertStringContainsString("15.00%", $result);
        $this->assertStringContainsString("50 uses", $result);
    }

    public function testFormatCreationEventWithAmount(): void
    {
        $code = $this->createMockCode('DISCOUNT25', 0, 25.00, 100, true, 'Bob', 'Davis');

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($code, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("DISCOUNT25", $result);
        $this->assertStringContainsString("$25.00", $result);
        $this->assertStringContainsString("100 uses", $result);
    }

    public function testFormatUpdateEvent(): void
    {
        $code = $this->createMockCode('SUMMER24', 0.20, 0, 75, true, 'Carol', 'Evans');

        $this->formatter_update->setContext($this->audit_context);
        $result = $this->formatter_update->format($code, [
            'rate' => [0.15, 0.20],
            'quantity_available' => [100, 75]
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString("SUMMER24", $result);
        $this->assertStringContainsString("updated", $result);
    }

    public function testFormatDeletionEvent(): void
    {
        $code = $this->createMockCode('VIP2024', 0.50, 0, 10, false, 'David', 'Frank');

        $this->formatter_deletion->setContext($this->audit_context);
        $result = $this->formatter_deletion->format($code, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("VIP2024", $result);
        $this->assertStringContainsString("deleted", $result);
        $this->assertStringContainsString("50.00%", $result);
        $this->assertStringContainsString("inactive", $result);
    }

    public function testFormatInactiveCode(): void
    {
        $code = $this->createMockCode('EXPIRED', 0.10, 0, 0, false, 'Eve', 'Green');

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($code, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("inactive", $result);
    }

    public function testFormatInvalidSubject(): void
    {
        $invalid_subject = new \stdClass();

        $result = $this->formatter_creation->format($invalid_subject, []);

        $this->assertNull($result);
    }

    private function createMockCode(
        string $code,
        float $rate,
        float $amount,
        int $quantity_available,
        bool $is_active,
        string $creator_first,
        string $creator_last
    ): object {
        $mock = Mockery::mock(PrePaidSummitRegistrationDiscountCode::class);
        $mock->shouldReceive('getCode')->andReturn($code);
        $mock->shouldReceive('getId')->andReturn(self::MOCK_ID);
        $mock->shouldReceive('getRate')->andReturn($rate);
        $mock->shouldReceive('getAmount')->andReturn($amount);
        $mock->shouldReceive('getQuantityAvailable')->andReturn($quantity_available);
        $mock->shouldReceive('isLive')->andReturn($is_active);

        $creator = Mockery::mock(\models\main\Member::class);
        $creator->shouldReceive('getFirstName')->andReturn($creator_first);
        $creator->shouldReceive('getLastName')->andReturn($creator_last);
        $mock->shouldReceive('getCreatedBy')->andReturn($creator);

        $summit = Mockery::mock(\models\summit\Summit::class);
        $summit->shouldReceive('getName')->andReturn(self::SUMMIT_NAME);
        $mock->shouldReceive('getSummit')->andReturn($summit);

        return $mock;
    }
}
