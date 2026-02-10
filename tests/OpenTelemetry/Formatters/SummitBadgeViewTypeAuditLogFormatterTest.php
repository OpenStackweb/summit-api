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
use App\Audit\ConcreteFormatters\SummitBadgeViewTypeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SummitBadgeViewType;
use Mockery;
use Tests\TestCase;

class SummitBadgeViewTypeAuditLogFormatterTest extends TestCase
{
    private const USER_ID = 456;
    private const USER_EMAIL = 'admin@example.com';
    private const USER_FIRST_NAME = 'Jane';
    private const USER_LAST_NAME = 'Smith';
    private const MOCK_ID = 1;
    private const SUMMIT_NAME = 'Test Summit';

    private SummitBadgeViewTypeAuditLogFormatter $formatter_creation;
    private SummitBadgeViewTypeAuditLogFormatter $formatter_update;
    private SummitBadgeViewTypeAuditLogFormatter $formatter_deletion;
    private AuditContext $audit_context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter_creation = new SummitBadgeViewTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $this->formatter_update = new SummitBadgeViewTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $this->formatter_deletion = new SummitBadgeViewTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);

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
        $badge_view = $this->createMockBadgeViewType('Standard Badge', 1);

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($badge_view, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Standard Badge", $result);
        $this->assertStringContainsString("(1)", $result);
        $this->assertStringContainsString("created", $result);
        $this->assertStringContainsString("Test Summit", $result);
    }

    public function testFormatUpdateEvent(): void
    {
        $badge_view = $this->createMockBadgeViewType('Premium Badge', 2);

        $this->formatter_update->setContext($this->audit_context);
        $result = $this->formatter_update->format($badge_view, [
            'name' => ['Standard Badge', 'Premium Badge'],
            'description' => ['Standard', 'Premium']
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Premium Badge", $result);
        $this->assertStringContainsString("(2)", $result);
        $this->assertStringContainsString("updated", $result);
    }

    public function testFormatDeletionEvent(): void
    {
        $badge_view = $this->createMockBadgeViewType('Archived Badge', 3);

        $this->formatter_deletion->setContext($this->audit_context);
        $result = $this->formatter_deletion->format($badge_view, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Archived Badge", $result);
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
        $badge_view = $this->createMockBadgeViewType('Test Badge', 5);

        $this->formatter_update->setContext($this->audit_context);
        $result = $this->formatter_update->format($badge_view, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Test Badge", $result);
        $this->assertStringContainsString("updated", $result);
    }

    public function testPropertiesExist(): void
    {
        $badge_view = $this->createMockBadgeViewType('Verify Badge', 6);

        $this->formatter_creation->setContext($this->audit_context);

        $this->assertTrue(method_exists($badge_view, 'getId'));
        $this->assertTrue(method_exists($badge_view, 'getName'));
        $this->assertTrue(method_exists($badge_view, 'getSummit'));
    }

    private function createMockBadgeViewType(string $name, int $id): object
    {
        $mock = Mockery::mock(SummitBadgeViewType::class);
        $mock->shouldReceive('getName')->andReturn($name);
        $mock->shouldReceive('getId')->andReturn($id);

        $summit = Mockery::mock(\models\summit\Summit::class);
        $summit->shouldReceive('getName')->andReturn(self::SUMMIT_NAME);
        $mock->shouldReceive('getSummit')->andReturn($summit);

        return $mock;
    }
}
