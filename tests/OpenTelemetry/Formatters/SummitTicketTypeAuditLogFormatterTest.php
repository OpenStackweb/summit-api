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
use App\Audit\ConcreteFormatters\SummitTicketTypeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SummitTicketType;
use Mockery;
use Tests\TestCase;

class SummitTicketTypeAuditLogFormatterTest extends TestCase
{
    private const USER_ID = 123;
    private const USER_EMAIL = 'test@example.com';
    private const USER_FIRST_NAME = 'John';
    private const USER_LAST_NAME = 'Doe';
    private const MOCK_ID = 1;
    private const SUMMIT_NAME = 'Test Summit';
    private const AUDIENCE = 'All';
    private const DATE_OFFSET_START = '-30 days';
    private const DATE_OFFSET_END = '+30 days';

    private SummitTicketTypeAuditLogFormatter $formatter_creation;
    private SummitTicketTypeAuditLogFormatter $formatter_update;
    private SummitTicketTypeAuditLogFormatter $formatter_deletion;
    private AuditContext $audit_context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter_creation = new SummitTicketTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $this->formatter_update = new SummitTicketTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $this->formatter_deletion = new SummitTicketTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);

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
        $ticket_type = $this->createMockTicketType('VIP Pass', 150.00, 'USD', 100, 0);

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($ticket_type, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('VIP Pass', $result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString('150', $result);
        $this->assertStringContainsString('USD', $result);
    }

    public function testFormatUpdateEvent(): void
    {
        $ticket_type = $this->createMockTicketType('Standard Ticket', 99.99, 'USD', 500, 150);

        $this->formatter_update->setContext($this->audit_context);
        $result = $this->formatter_update->format($ticket_type, [
            'cost' => [49.99, 99.99],
            'quantity_2_sell' => [1000, 500]
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Standard Ticket", $result);
        $this->assertStringContainsString("updated", $result);
    }

    public function testFormatDeletionEvent(): void
    {
        $ticket_type = $this->createMockTicketType('Early Bird', 79.99, 'EUR', 200, 200);

        $this->formatter_deletion->setContext($this->audit_context);
        $result = $this->formatter_deletion->format($ticket_type, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Early Bird", $result);
        $this->assertStringContainsString("deleted", $result);
        $this->assertStringContainsString("200", $result);
    }

    public function testFormatWithoutContext(): void
    {
        $ticket_type = $this->createMockTicketType('Test Ticket', 50.00, 'USD', 100, 0);

        $result = $this->formatter_creation->format($ticket_type, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Test Ticket", $result);
        $this->assertStringContainsString("created", $result);
    }

    public function testFormatInvalidSubject(): void
    {
        $invalid_subject = new \stdClass();

        $result = $this->formatter_creation->format($invalid_subject, []);

        $this->assertNull($result);
    }

    private function createMockTicketType(
        string $name,
        float $cost,
        string $currency,
        int $quantity,
        int $sold
    ): object {
        $mock = Mockery::mock(SummitTicketType::class);
        $mock->shouldReceive('getName')->andReturn($name);
        $mock->shouldReceive('getId')->andReturn(self::MOCK_ID);
        $mock->shouldReceive('getCost')->andReturn($cost);
        $mock->shouldReceive('getCurrency')->andReturn($currency);
        $mock->shouldReceive('getQuantity2Sell')->andReturn($quantity);
        $mock->shouldReceive('getQuantitySold')->andReturn($sold);
        $mock->shouldReceive('getAudience')->andReturn(self::AUDIENCE);

        $summit = Mockery::mock(\models\summit\Summit::class);
        $summit->shouldReceive('getName')->andReturn(self::SUMMIT_NAME);
        $mock->shouldReceive('getSummit')->andReturn($summit);

        return $mock;
    }
}
