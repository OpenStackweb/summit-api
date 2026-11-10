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

use App\Audit\ConcreteFormatters\SummitBadgeTypeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Mockery;
use models\summit\Summit;
use models\summit\SummitAccessLevelType;
use models\summit\SummitBadgeFeatureType;
use models\summit\SummitBadgeType;
use models\summit\SummitBadgeViewType;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Tests\OpenTelemetry\Formatters\Support\PersistentCollectionTestHelper;
use Tests\TestCase;

/**
 * SummitBadgeType owns three many-to-many collections: access_levels, badge_features and allowed_view_types.
 */
class SummitBadgeTypeManyToManyAuditLogFormatterTest extends TestCase
{
    private const MESSAGE_PREFIX = "Badge Type 'General' (2) in Summit 'Test Summit'";

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testUpdateEventReportsAddedAndRemovedIdsFromDiff(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makeSubject(), [
            'collection' => $this->makeCollection('access_levels', SummitAccessLevelType::class, [10, 11], [11, 12]),
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " access_levels (SummitAccessLevelType) updated: Added IDs: [12], Removed IDs: [10] by user ",
            $result
        );
    }

    public function testUpdateEventReportsAddedIds(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makeSubject(), [
            'collection' => $this->makeCollection('badge_features', SummitBadgeFeatureType::class, [], [5, 6]),
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " badge_features (SummitBadgeFeatureType) updated: Added IDs: [5,6] by user ",
            $result
        );
    }

    public function testDeleteEventReportsRemovedIdsFromPayload(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $result = $formatter->format($this->makeSubject(), [
            'collection'  => $this->makeCollection('access_levels', SummitAccessLevelType::class, [], []),
            'deleted_ids' => [3, 4],
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " access_levels (SummitAccessLevelType) deleted: Removed IDs: [3,4] by user ",
            $result
        );
    }

    public function testUpdateEventWithoutDiffIsSuppressed(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makeSubject(), [
            'collection' => $this->makeCollection('badge_features', SummitBadgeFeatureType::class, [3], [3]),
        ]);

        $this->assertNull($result);
    }

    public function testDeleteEventWithNothingToRemoveIsSuppressed(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $result = $formatter->format($this->makeSubject(), [
            'collection'  => $this->makeCollection('badge_features', SummitBadgeFeatureType::class, [], []),
            'deleted_ids' => [],
        ]);

        $this->assertNull($result);
    }

    public function testManyToManyEventWithoutCollectionReturnsNull(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $this->assertNull($formatter->format($this->makeSubject(), []));
    }

    public function testManyToManyEventWithInvalidSubjectReturnsNull(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $this->assertNull($formatter->format(new \stdClass(), [
            'collection' => $this->makeCollection('access_levels', SummitAccessLevelType::class, [], [7]),
        ]));
    }

    private function makeFormatter(string $eventType): SummitBadgeTypeAuditLogFormatter
    {
        $formatter = new SummitBadgeTypeAuditLogFormatter($eventType);
        $formatter->setContext(AuditContextBuilder::default()->build());
        return $formatter;
    }

    private function makeCollection(string $field, string $targetEntity, array $snapshotIds, array $currentIds)
    {
        return PersistentCollectionTestHelper::buildManyToManyCollection(
            SummitBadgeType::class,
            $field,
            $targetEntity,
            $snapshotIds,
            $currentIds
        );
    }

    private function makeSubject(): SummitBadgeType
    {
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getName')->andReturn('Test Summit');

        $type = Mockery::mock(SummitBadgeType::class);
        $type->shouldReceive('getId')->andReturn(2);
        $type->shouldReceive('getName')->andReturn('General');
        $type->shouldReceive('getSummit')->andReturn($summit);

        return $type;
    }
}
