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
use App\Audit\ConcreteFormatters\SummitAccessLevelTypeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SummitAccessLevelType;
use Mockery;
use Tests\TestCase;

class SummitAccessLevelTypeAuditLogFormatterTest extends TestCase
{
    private const USER_ID = 789;
    private const USER_EMAIL = 'staff@example.com';
    private const USER_FIRST_NAME = 'Michael';
    private const USER_LAST_NAME = 'Johnson';
    private const MOCK_ID = 1;
    private const SUMMIT_NAME = 'Test Summit';

    private SummitAccessLevelTypeAuditLogFormatter $formatter_creation;
    private SummitAccessLevelTypeAuditLogFormatter $formatter_update;
    private SummitAccessLevelTypeAuditLogFormatter $formatter_deletion;
    private AuditContext $audit_context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter_creation = new SummitAccessLevelTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $this->formatter_update = new SummitAccessLevelTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $this->formatter_deletion = new SummitAccessLevelTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);

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
        $access_level = $this->createMockAccessLevelType('In-Person Access', 1);

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($access_level, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("In-Person Access", $result);
        $this->assertStringContainsString("(1)", $result);
        $this->assertStringContainsString("created", $result);
        $this->assertStringContainsString("Test Summit", $result);
    }

    public function testFormatUpdateEvent(): void
    {
        $access_level = $this->createMockAccessLevelType('Virtual Access', 2);

        $this->formatter_update->setContext($this->audit_context);
        $result = $this->formatter_update->format($access_level, [
            'name' => ['In-Person Access', 'Virtual Access'],
            'description' => ['For in-person attendees', 'For virtual attendees']
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Virtual Access", $result);
        $this->assertStringContainsString("(2)", $result);
        $this->assertStringContainsString("updated", $result);
    }

    public function testFormatDeletionEvent(): void
    {
        $access_level = $this->createMockAccessLevelType('Chat Access', 3);

        $this->formatter_deletion->setContext($this->audit_context);
        $result = $this->formatter_deletion->format($access_level, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Chat Access", $result);
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
        $access_level = $this->createMockAccessLevelType('Test Access', 5);

        $this->formatter_update->setContext($this->audit_context);
        $result = $this->formatter_update->format($access_level, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Test Access", $result);
        $this->assertStringContainsString("updated", $result);
    }

    public function testPropertiesExist(): void
    {
        $access_level = $this->createMockAccessLevelType('Verify Access', 6);

        $this->formatter_creation->setContext($this->audit_context);

        $this->assertTrue(method_exists($access_level, 'getId'));
        $this->assertTrue(method_exists($access_level, 'getName'));
        $this->assertTrue(method_exists($access_level, 'getSummit'));
    }

    private function createMockAccessLevelType(string $name, int $id): object
    {
        $mock = Mockery::mock(SummitAccessLevelType::class);
        $mock->shouldReceive('getName')->andReturn($name);
        $mock->shouldReceive('getId')->andReturn($id);

        $summit = Mockery::mock(\models\summit\Summit::class);
        $summit->shouldReceive('getName')->andReturn(self::SUMMIT_NAME);
        $mock->shouldReceive('getSummit')->andReturn($summit);

        return $mock;
    }
}
