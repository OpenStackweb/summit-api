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
use App\Audit\ConcreteFormatters\SummitTaxTypeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SummitTaxType;
use Mockery;
use Tests\TestCase;

class SummitTaxTypeAuditLogFormatterTest extends TestCase
{
    private const USER_ID = 789;
    private const USER_EMAIL = 'finance@example.com';
    private const USER_FIRST_NAME = 'Mike';
    private const USER_LAST_NAME = 'Johnson';
    private const MOCK_ID = 1;
    private const SUMMIT_NAME = 'Test Summit';

    private SummitTaxTypeAuditLogFormatter $formatter_creation;
    private SummitTaxTypeAuditLogFormatter $formatter_update;
    private SummitTaxTypeAuditLogFormatter $formatter_deletion;
    private AuditContext $audit_context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter_creation = new SummitTaxTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $this->formatter_update = new SummitTaxTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $this->formatter_deletion = new SummitTaxTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);

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
        $tax = $this->createMockTax('VAT', 'VAT-001', 21.0);

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($tax, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("VAT", $result);
        $this->assertStringContainsString("created", $result);
        $this->assertStringContainsString("21", $result);
    }

    public function testFormatUpdateEvent(): void
    {
        $tax = $this->createMockTax('Sales Tax', 'ST-002', 8.0);

        $this->formatter_update->setContext($this->audit_context);
        $result = $this->formatter_update->format($tax, [
            'rate' => [7.0, 8.0],
            'ticket_types' => [[], []]
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Sales Tax", $result);
        $this->assertStringContainsString("updated", $result);
    }

    public function testFormatDeletionEvent(): void
    {
        $tax = $this->createMockTax('GST', 'GST-CA', 5.0);

        $this->formatter_deletion->setContext($this->audit_context);
        $result = $this->formatter_deletion->format($tax, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("GST", $result);
        $this->assertStringContainsString("deleted", $result);
        $this->assertStringContainsString("5", $result);
    }

    public function testFormatWithoutTicketTypes(): void
    {
        $tax = $this->createMockTax('Empty Tax', 'EMPTY', 10.0);

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($tax, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Empty Tax", $result);
        $this->assertStringContainsString("created", $result);
    }

    public function testFormatInvalidSubject(): void
    {
        $invalid_subject = new \stdClass();

        $result = $this->formatter_creation->format($invalid_subject, []);

        $this->assertNull($result);
    }

    private function createMockTax(string $name, string $tax_id,  $rate): object
    {
        $mock = Mockery::mock(SummitTaxType::class);
        $mock->shouldReceive('getName')->andReturn($name);
        $mock->shouldReceive('getId')->andReturn(self::MOCK_ID);
        $mock->shouldReceive('getTaxId')->andReturn($tax_id);
        $mock->shouldReceive('getRate')->andReturn($rate);

        $summit = Mockery::mock(\models\summit\Summit::class);
        $summit->shouldReceive('getName')->andReturn(self::SUMMIT_NAME);
        $mock->shouldReceive('getSummit')->andReturn($summit);

        return $mock;
    }
}
