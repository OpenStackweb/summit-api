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
use App\Audit\ConcreteFormatters\SummitBadgeFeatureTypeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use models\summit\SummitBadgeFeatureType;
use Mockery;
use Tests\TestCase;

class SummitBadgeFeatureTypeAuditLogFormatterTest extends TestCase
{
    private const USER_ID = 101;
    private const USER_EMAIL = 'designer@example.com';
    private const USER_FIRST_NAME = 'Sarah';
    private const USER_LAST_NAME = 'Williams';
    private const SUMMIT_NAME = 'Test Summit';
    private const NULL_SUMMIT_BADGE_FEATURE_NAME = 'VIP Badge Feature';
    private const NULL_SUMMIT_BADGE_FEATURE_ID = 1;
    private const NULL_SUMMIT_LABEL = 'Unknown Summit';

    private SummitBadgeFeatureTypeAuditLogFormatter $formatter_creation;
    private SummitBadgeFeatureTypeAuditLogFormatter $formatter_update;
    private SummitBadgeFeatureTypeAuditLogFormatter $formatter_deletion;
    private AuditContext $audit_context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter_creation = new SummitBadgeFeatureTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $this->formatter_update = new SummitBadgeFeatureTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $this->formatter_deletion = new SummitBadgeFeatureTypeAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);

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
        $badge_feature = $this->createMockBadgeFeatureType('VIP Badge Feature', 1);

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($badge_feature, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("VIP Badge Feature", $result);
        $this->assertStringContainsString("(1)", $result);
        $this->assertStringContainsString("created", $result);
        $this->assertStringContainsString("Test Summit", $result);
    }

    public function testFormatUpdateEvent(): void
    {
        $badge_feature = $this->createMockBadgeFeatureType('Networking Feature', 2);

        $this->formatter_update->setContext($this->audit_context);
        $result = $this->formatter_update->format($badge_feature, [
            'name' => ['VIP Badge Feature', 'Networking Feature'],
            'description' => ['For VIP attendees', 'For networking participants']
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Networking Feature", $result);
        $this->assertStringContainsString("(2)", $result);
        $this->assertStringContainsString("updated", $result);
    }

    public function testFormatDeletionEvent(): void
    {
        $badge_feature = $this->createMockBadgeFeatureType('Speaker Feature', 3);

        $this->formatter_deletion->setContext($this->audit_context);
        $result = $this->formatter_deletion->format($badge_feature, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Speaker Feature", $result);
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
        $badge_feature = $this->createMockBadgeFeatureType('Test Feature', 5);

        $this->formatter_update->setContext($this->audit_context);
        $result = $this->formatter_update->format($badge_feature, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString("Test Feature", $result);
        $this->assertStringContainsString("updated", $result);
    }

    public function testPropertiesExist(): void
    {
        $badge_feature = $this->createMockBadgeFeatureType('Verify Feature', 6);

        $this->formatter_creation->setContext($this->audit_context);

        $this->assertTrue(method_exists($badge_feature, 'getId'));
        $this->assertTrue(method_exists($badge_feature, 'getName'));
        $this->assertTrue(method_exists($badge_feature, 'getSummit'));
    }

    public function testFormatCreationWithNullSummit(): void
    {
        $badge_feature = Mockery::mock(SummitBadgeFeatureType::class);
        $badge_feature->shouldReceive('getName')->andReturn(self::NULL_SUMMIT_BADGE_FEATURE_NAME);
        $badge_feature->shouldReceive('getId')->andReturn(self::NULL_SUMMIT_BADGE_FEATURE_ID);
        $badge_feature->shouldReceive('getSummit')->andReturn(null);

        $this->formatter_creation->setContext($this->audit_context);
        $result = $this->formatter_creation->format($badge_feature, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::NULL_SUMMIT_LABEL, $result);
        $this->assertStringContainsString(self::NULL_SUMMIT_BADGE_FEATURE_NAME, $result);
    }

    private function createMockBadgeFeatureType(string $name, int $id): object
    {
        $mock = Mockery::mock(SummitBadgeFeatureType::class);
        $mock->shouldReceive('getName')->andReturn($name);
        $mock->shouldReceive('getId')->andReturn($id);

        $summit = Mockery::mock(\models\summit\Summit::class);
        $summit->shouldReceive('getName')->andReturn(self::SUMMIT_NAME);
        $mock->shouldReceive('getSummit')->andReturn($summit);

        return $mock;
    }
}
