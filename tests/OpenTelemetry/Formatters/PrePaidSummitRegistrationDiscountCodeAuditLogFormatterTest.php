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

    private const DISCOUNT_CODE_RATE_EARLY = 'EARLY2024';
    private const DISCOUNT_CODE_AMOUNT = 'DISCOUNT25';
    private const DISCOUNT_CODE_UPDATE = 'SUMMER24';
    private const DISCOUNT_CODE_DELETE = 'VIP2024';
    private const DISCOUNT_CODE_EXPIRED = 'EXPIRED';

    private const RATE_EARLY = 15;
    private const RATE_UPDATE_OLD = 15;
    private const RATE_UPDATE_NEW = 20;
    private const RATE_HIGH = 50;
    private const RATE_EXPIRED = 10;

    private const AMOUNT_DISCOUNT = 25.00;
    private const AMOUNT_ZERO = 0;

    private const QUANTITY_EARLY = 50;
    private const QUANTITY_AMOUNT = 100;
    private const QUANTITY_UPDATE_OLD = 100;
    private const QUANTITY_UPDATE_NEW = 75;
    private const QUANTITY_HIGH = 10;
    private const QUANTITY_NONE = 0;

    private const EVENT_CREATED = 'created';
    private const EVENT_UPDATED = 'updated';
    private const EVENT_DELETED = 'deleted';
    private const EVENT_CURRENT = 'current:';

    private const EXPECTED_RATE_FORMAT = 'rate: %.2f%%';
    private const EXPECTED_AMOUNT_FORMAT = 'amount: $%.2f';
    private const EXPECTED_QUANTITY_FORMAT = 'quantity: %d';
    private const EXPECTED_ID_FORMAT = '(%d)';

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
        $code = $this->createMockCode(
            self::DISCOUNT_CODE_RATE_EARLY,
            self::RATE_EARLY,
            self::AMOUNT_ZERO,
            self::QUANTITY_EARLY
        );

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($code, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::DISCOUNT_CODE_RATE_EARLY, $result);
        $this->assertStringContainsString(sprintf(self::EXPECTED_ID_FORMAT, self::MOCK_ID), $result);
        $this->assertStringContainsString(self::EVENT_CREATED, $result);
        $this->assertStringContainsString(self::SUMMIT_NAME, $result);
        $this->assertStringContainsString(sprintf(self::EXPECTED_RATE_FORMAT, self::RATE_EARLY), $result);
        $this->assertStringContainsString(sprintf(self::EXPECTED_QUANTITY_FORMAT, self::QUANTITY_EARLY), $result);
    }

    public function testFormatCreationEventWithAmount(): void
    {
        $code = $this->createMockCode(
            self::DISCOUNT_CODE_AMOUNT,
            self::AMOUNT_ZERO,
            self::AMOUNT_DISCOUNT,
            self::QUANTITY_AMOUNT
        );

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($code, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::DISCOUNT_CODE_AMOUNT, $result);
        $this->assertStringContainsString(sprintf(self::EXPECTED_ID_FORMAT, self::MOCK_ID), $result);
        $this->assertStringContainsString(self::EVENT_CREATED, $result);
        $this->assertStringContainsString(sprintf(self::EXPECTED_AMOUNT_FORMAT, self::AMOUNT_DISCOUNT), $result);
        $this->assertStringContainsString(sprintf(self::EXPECTED_QUANTITY_FORMAT, self::QUANTITY_AMOUNT), $result);
    }

    public function testFormatUpdateEvent(): void
    {
        $code = $this->createMockCode(
            self::DISCOUNT_CODE_UPDATE,
            self::RATE_UPDATE_NEW,
            self::AMOUNT_ZERO,
            self::QUANTITY_UPDATE_NEW
        );

        $this->formatter_update->setContext($this->audit_context);
        $result = $this->formatter_update->format($code, [
            'rate' => [self::RATE_UPDATE_OLD, self::RATE_UPDATE_NEW],
            'quantity_available' => [self::QUANTITY_UPDATE_OLD, self::QUANTITY_UPDATE_NEW]
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::DISCOUNT_CODE_UPDATE, $result);
        $this->assertStringContainsString(sprintf(self::EXPECTED_ID_FORMAT, self::MOCK_ID), $result);
        $this->assertStringContainsString(self::EVENT_UPDATED, $result);
        $this->assertStringContainsString(sprintf(self::EXPECTED_RATE_FORMAT, self::RATE_UPDATE_NEW), $result);
        $this->assertStringContainsString(sprintf(self::EXPECTED_QUANTITY_FORMAT, self::QUANTITY_UPDATE_NEW), $result);
        $this->assertStringContainsString(self::EVENT_CURRENT, $result);
    }

    public function testFormatDeletionEvent(): void
    {
        $code = $this->createMockCode(
            self::DISCOUNT_CODE_DELETE,
            self::RATE_HIGH,
            self::AMOUNT_ZERO,
            self::QUANTITY_HIGH
        );

        $this->formatter_deletion->setContext($this->audit_context);
        $result = $this->formatter_deletion->format($code, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::DISCOUNT_CODE_DELETE, $result);
        $this->assertStringContainsString(sprintf(self::EXPECTED_ID_FORMAT, self::MOCK_ID), $result);
        $this->assertStringContainsString(self::EVENT_DELETED, $result);
        $this->assertStringContainsString(sprintf(self::EXPECTED_RATE_FORMAT, self::RATE_HIGH), $result);
        $this->assertStringContainsString(sprintf(self::EXPECTED_QUANTITY_FORMAT, self::QUANTITY_HIGH), $result);
    }

    public function testFormatInactiveCode(): void
    {
        $code = $this->createMockCode(
            self::DISCOUNT_CODE_EXPIRED,
            self::RATE_EXPIRED,
            self::AMOUNT_ZERO,
            self::QUANTITY_NONE
        );

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($code, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::DISCOUNT_CODE_EXPIRED, $result);
        $this->assertStringContainsString(sprintf(self::EXPECTED_ID_FORMAT, self::MOCK_ID), $result);
        $this->assertStringContainsString(self::EVENT_CREATED, $result);
        $this->assertStringContainsString(sprintf(self::EXPECTED_RATE_FORMAT, self::RATE_EXPIRED), $result);
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
        int $quantity_available
    ): object {
        $mock = Mockery::mock(PrePaidSummitRegistrationDiscountCode::class);
        $mock->shouldReceive('getCode')->andReturn($code);
        $mock->shouldReceive('getId')->andReturn(self::MOCK_ID);
        $mock->shouldReceive('getRate')->andReturn($rate);
        $mock->shouldReceive('getAmount')->andReturn($amount);
        $mock->shouldReceive('getQuantityAvailable')->andReturn($quantity_available);

        $summit = Mockery::mock(\models\summit\Summit::class);
        $summit->shouldReceive('getName')->andReturn(self::SUMMIT_NAME);
        $mock->shouldReceive('getSummit')->andReturn($summit);

        return $mock;
    }
}
