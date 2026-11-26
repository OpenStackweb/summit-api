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

use App\Audit\ConcreteFormatters\SelectionPlanAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use App\Models\Foundation\Summit\SelectionPlan;
use Mockery;
use models\summit\PresentationCategoryGroup;
use models\summit\Summit;
use models\summit\SummitEventType;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Tests\OpenTelemetry\Formatters\Support\PersistentCollectionTestHelper;
use Tests\TestCase;

/**
 * SelectionPlan owns two many-to-many collections: category_groups and event_types.
 */
class SelectionPlanManyToManyAuditLogFormatterTest extends TestCase
{
    private const PLAN_ID = 77;
    private const PLAN_NAME = 'Main CFP';
    private const SUMMIT_NAME = 'Test Summit';
    private const MESSAGE_PREFIX = "Selection Plan 'Main CFP' (77) for Summit 'Test Summit'";

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCategoryGroupsUpdateEventReportsAddedAndRemovedIds(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makePlan(), [
            'collection' => $this->makeCollection('category_groups', PresentationCategoryGroup::class, [10, 11], [11, 12]),
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " category_groups (PresentationCategoryGroup) updated: Added IDs: [12], Removed IDs: [10] by user ",
            $result
        );
    }

    public function testEventTypesUpdateEventReportsAddedIds(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makePlan(), [
            'collection' => $this->makeCollection('event_types', SummitEventType::class, [], [5, 6]),
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " event_types (SummitEventType) updated: Added IDs: [5,6] by user ",
            $result
        );
    }

    public function testDeleteEventReportsRemovedIdsFromPayload(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $result = $formatter->format($this->makePlan(), [
            'collection'  => $this->makeCollection('category_groups', PresentationCategoryGroup::class, [], []),
            'deleted_ids' => [3, 4],
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " category_groups (PresentationCategoryGroup) deleted: Removed IDs: [3,4] by user ",
            $result
        );
    }

    public function testUpdateEventWithoutDiffIsSuppressed(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makePlan(), [
            'collection' => $this->makeCollection('event_types', SummitEventType::class, [3], [3]),
        ]);

        $this->assertNull($result);
    }

    public function testDeleteEventWithNothingToRemoveIsSuppressed(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $result = $formatter->format($this->makePlan(), [
            'collection'  => $this->makeCollection('event_types', SummitEventType::class, [], []),
            'deleted_ids' => [],
        ]);

        $this->assertNull($result);
    }

    public function testManyToManyEventWithoutCollectionReturnsNull(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $this->assertNull($formatter->format($this->makePlan(), []));
    }

    private function makeFormatter(string $eventType): SelectionPlanAuditLogFormatter
    {
        $formatter = new SelectionPlanAuditLogFormatter($eventType);
        $formatter->setContext(AuditContextBuilder::default()->build());
        return $formatter;
    }

    private function makeCollection(string $field, string $targetEntity, array $snapshotIds, array $currentIds)
    {
        return PersistentCollectionTestHelper::buildManyToManyCollection(
            SelectionPlan::class,
            $field,
            $targetEntity,
            $snapshotIds,
            $currentIds
        );
    }

    private function makePlan(): SelectionPlan
    {
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getName')->andReturn(self::SUMMIT_NAME);

        $plan = Mockery::mock(SelectionPlan::class);
        $plan->shouldReceive('getId')->andReturn(self::PLAN_ID);
        $plan->shouldReceive('getName')->andReturn(self::PLAN_NAME);
        $plan->shouldReceive('getSummit')->andReturn($summit);

        return $plan;
    }
}
