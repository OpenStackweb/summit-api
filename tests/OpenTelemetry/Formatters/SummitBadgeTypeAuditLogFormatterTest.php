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
use App\Audit\ConcreteFormatters\SummitBadgeTypeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SummitBadgeType;
use Mockery;
use Tests\TestCase;

class SummitBadgeTypeAuditLogFormatterTest extends TestCase
{
    private const USER_ID = 202;
    private const USER_EMAIL = 'coordinator@example.com';
    private const USER_FIRST_NAME = 'Robert';
    private const USER_LAST_NAME = 'Brown';
    private const SUMMIT_NAME = 'Test Summit';
    private const NULL_SUMMIT_BADGE_TYPE_NAME = 'Standard Badge Type';
    private const NULL_SUMMIT_BADGE_TYPE_ID = 1;
    private const NULL_SUMMIT_LABEL = 'Unknown Summit';

    private SummitBadgeTypeAuditLogFormatter $formatter_creation;
    private SummitBadgeTypeAuditLogFormatter $formatter_update;
    private SummitBadgeTypeAuditLogFormatter $formatter_deletion;
    private AuditContext $audit_context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter_creation = new SummitBadgeTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $this->formatter_update = new SummitBadgeTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $this->formatter_deletion = new SummitBadgeTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);

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
        $badge_type = $this->createMockBadgeType('Standard Badge Type', 1);

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($badge_type, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Standard Badge Type", $result);
        $this->assertStringContainsString("(1)", $result);
        $this->assertStringContainsString("created", $result);
        $this->assertStringContainsString("Test Summit", $result);
    }

    public function testFormatUpdateEvent(): void
    {
        $badge_type = $this->createMockBadgeType('Premium Badge Type', 2);

        $this->formatter_update->setContext($this->audit_context);
        $result = $this->formatter_update->format($badge_type, [
            'name' => ['Standard Badge Type', 'Premium Badge Type'],
            'description' => ['Standard badges', 'Premium badges'],
            'is_default' => [false, true]
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Premium Badge Type", $result);
        $this->assertStringContainsString("(2)", $result);
        $this->assertStringContainsString("updated", $result);
    }

    public function testFormatDeletionEvent(): void
    {
        $badge_type = $this->createMockBadgeType('Legacy Badge Type', 3);

        $this->formatter_deletion->setContext($this->audit_context);
        $result = $this->formatter_deletion->format($badge_type, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Legacy Badge Type", $result);
        $this->assertStringContainsString("(3)", $result);
        $this->assertStringContainsString("deleted", $result);
    }

    public function testFormatInvalidSubject(): void
    {
        $invalid_subject = new \stdClass();

        $result = $this->formatter_creation->format($invalid_subject, []);

        $this->assertNull($result);
    }

    public function testFormatterHandlesEmptyChangeSet(): void
    {
        $badge_type = $this->createMockBadgeType('Test Badge Type', 5);

        $this->formatter_update->setContext($this->audit_context);
        $result = $this->formatter_update->format($badge_type, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Test Badge Type", $result);
        $this->assertStringContainsString("updated", $result);
    }

    public function testPropertiesExist(): void
    {
        $badge_type = $this->createMockBadgeType('Verify Badge Type', 6);

        $this->formatter_creation->setContext($this->audit_context);

        $this->assertTrue(method_exists($badge_type, 'getId'));
        $this->assertTrue(method_exists($badge_type, 'getName'));
        $this->assertTrue(method_exists($badge_type, 'getSummit'));
    }

    public function testFormatCreationWithNullSummit(): void
    {
        $badge_type = Mockery::mock(SummitBadgeType::class);
        $badge_type->shouldReceive('getName')->andReturn(self::NULL_SUMMIT_BADGE_TYPE_NAME);
        $badge_type->shouldReceive('getId')->andReturn(self::NULL_SUMMIT_BADGE_TYPE_ID);
        $badge_type->shouldReceive('getSummit')->andReturn(null);

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($badge_type, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::NULL_SUMMIT_LABEL, $result);
        $this->assertStringContainsString(self::NULL_SUMMIT_BADGE_TYPE_NAME, $result);
    }

    private function createMockBadgeType(string $name, int $id): object
    {
        $mock = Mockery::mock(SummitBadgeType::class);
        $mock->shouldReceive('getName')->andReturn($name);
        $mock->shouldReceive('getId')->andReturn($id);

        $summit = Mockery::mock(\models\summit\Summit::class);
        $summit->shouldReceive('getName')->andReturn(self::SUMMIT_NAME);
        $mock->shouldReceive('getSummit')->andReturn($summit);

        return $mock;
    }
}
