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
use App\Audit\ConcreteFormatters\SelectionPlanAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use App\Models\Foundation\Summit\SelectionPlan;
use Mockery;
use models\summit\Summit;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Tests\TestCase;

class SelectionPlanAuditLogFormatterTest extends TestCase
{
    private const MOCK_ID = 1;
    private const SUMMIT_NAME = 'Test Summit';
    private const PLAN_NAME = 'Regular CFP';

    private SelectionPlanAuditLogFormatter $formatter_creation;
    private SelectionPlanAuditLogFormatter $formatter_update;
    private SelectionPlanAuditLogFormatter $formatter_deletion;
    private AuditContext $audit_context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter_creation = new SelectionPlanAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_CREATION);
        $this->formatter_creation->setContext(AuditContextBuilder::default()->build());

        $this->formatter_update = new SelectionPlanAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_UPDATE);
        $this->formatter_update->setContext(AuditContextBuilder::default()->build());

        $this->formatter_deletion = new SelectionPlanAuditLogFormatter(IAuditStrategy::EVENT_ENTITY_DELETION);
        $this->formatter_deletion->setContext(AuditContextBuilder::default()->build());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createMockPlan(string $name = self::PLAN_NAME): object
    {
        $mock = Mockery::mock(SelectionPlan::class);
        $mock->shouldReceive('getName')->andReturn($name);
        $mock->shouldReceive('getId')->andReturn(self::MOCK_ID);
        $mock->shouldReceive('hasSubmissionPeriodDefined')->andReturn(false);
        $mock->shouldReceive('hasSelectionPeriodDefined')->andReturn(false);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getName')->andReturn(self::SUMMIT_NAME);
        $mock->shouldReceive('getSummit')->andReturn($summit);

        return $mock;
    }

    public function testSubjectCreationAuditMessage(): void
    {
        $plan = $this->createMockPlan();

        $result = $this->formatter_creation->format($plan, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('created', $result);
        $this->assertStringContainsString(self::PLAN_NAME, $result);
        $this->assertStringContainsString(self::SUMMIT_NAME, $result);
        $this->assertStringContainsString((string)self::MOCK_ID, $result);
    }

    public function testSubjectUpdateAuditMessage(): void
    {
        $plan = $this->createMockPlan();

        $result = $this->formatter_update->format($plan, [
            'name' => [self::PLAN_NAME, 'Late CFP'],
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString('updated', $result);
        $this->assertStringContainsString(self::PLAN_NAME, $result);
    }

    public function testSubjectDeletionAuditMessage(): void
    {
        $plan = $this->createMockPlan();

        $result = $this->formatter_deletion->format($plan, []);

        $this->assertNotNull($result);
        $this->assertStringContainsString('deleted', $result);
        $this->assertStringContainsString(self::PLAN_NAME, $result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $result = $this->formatter_creation->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    public function testFormatterHandlesEmptyChangeSet(): void
    {
        $plan = $this->createMockPlan();

        $result = $this->formatter_update->format($plan, []);

        $this->assertNull($result);
    }

    public function testUpdateWithSameValueDifferentDateTimeInstanceIsSuppressed(): void
    {
        $plan = $this->createMockPlan();

        $begin = new \DateTime('2024-06-01 10:00:00');
        $begin_reloaded = new \DateTime('2024-06-01 10:00:00'); // same value, different instance
        $end = new \DateTime('2024-06-15 18:00:00');
        $end_reloaded = new \DateTime('2024-06-15 18:00:00'); // same value, different instance

        $result = $this->formatter_update->format($plan, [
            'begin_date' => [$begin, $begin_reloaded],
            'end_date' => [$end, $end_reloaded],
        ]);

        $this->assertNull($result);
    }
}
