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

use App\Audit\ConcreteFormatters\PresentationCategoryGroupAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Tests\OpenTelemetry\Formatters\Support\PersistentCollectionTestHelper;
use Tests\TestCase;
use models\summit\PresentationCategory;
use models\summit\PresentationCategoryGroup;
use models\summit\Summit;

class PresentationCategoryGroupAuditLogFormatterManyToManyTest extends TestCase
{
    private const GROUP_ID = 7;
    private const GROUP_NAME = 'Core Tracks';
    private const SUMMIT_ID = 1;
    private const SUMMIT_NAME = 'Test Summit';
    private const GROUP_COLOR = '#00AAFF';
    private const GROUP_MAX_ATTENDEE_VOTES = 5;
    private const FIELD_NAME = 'categories';
    private const TARGET_ENTITY = PresentationCategory::class;
    private const DP_UPDATE_WITHOUT_CONTEXT = 'update without context';
    private const DP_DELETE_WITHOUT_CONTEXT = 'delete without context';
    private const DP_UPDATE_WITHOUT_COLLECTION = 'update without collection';
    private const DP_DELETE_WITHOUT_COLLECTION = 'delete without collection';
    private const LOG_DELETED_M2M = 'deleted M2M';
    private const LOG_UPDATED_M2M = 'updated M2M';
    private const LOG_REMOVED_IDS_EMPTY = 'Removed IDs: []';
    private const LOG_REMOVED_IDS_PAYLOAD = 'Removed IDs: [10,11,12]';
    private const LOG_REMOVED_IDS_DIFF = 'Removed IDs: [1]';
    private const LOG_ADDED_IDS_DIFF = 'Added IDs: [3]';
    private const DELETED_IDS_PAYLOAD = [10, 11, 12];
    private const SNAPSHOT_IDS_EMPTY = [];
    private const CURRENT_IDS_EMPTY = [];
    private const SNAPSHOT_IDS_REMOVE_ONE = [1, 2, 3];
    private const CURRENT_IDS_REMOVE_ONE = [2, 3];
    private const SNAPSHOT_IDS_UPDATE = [1, 2];
    private const CURRENT_IDS_UPDATE = [2, 3];

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[DataProvider('providesNullCasesForManyToMany')]
    public function testManyToManyReturnsNullWithoutRequiredContextOrCollection(
        string $eventType,
        bool $withContext
    ): void {
        $formatter = $this->makeFormatter($eventType, $withContext);
        $group = $this->makeGroup();

        $result = $formatter->format($group, []);
        $this->assertNull($result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new PresentationCategoryGroupAuditLogFormatter(
            IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    public function testManyToManyDeleteReturnsEmptyRemovedIdsWhenPayloadMissing(): void
    {
        $group = $this->makeGroup();
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);
        $collection = $this->makeCollection(self::SNAPSHOT_IDS_EMPTY, self::CURRENT_IDS_EMPTY);

        $result = $formatter->format($group, [
            'collection' => $collection,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_DELETED_M2M, $result);
        $this->assertStringContainsString(self::LOG_REMOVED_IDS_EMPTY, $result);
    }

    public function testManyToManyDeleteUsesDeletedIdsFromPayload(): void
    {
        $group = $this->makeGroup();
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);
        $collection = $this->makeCollection(self::SNAPSHOT_IDS_EMPTY, self::CURRENT_IDS_EMPTY);

        $result = $formatter->format($group, [
            'collection' => $collection,
            'deleted_ids' => self::DELETED_IDS_PAYLOAD,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_REMOVED_IDS_PAYLOAD, $result);
    }

    public function testManyToManyDeleteUsesRemovedIdsFromCollectionDiff(): void
    {
        $group = $this->makeGroup();
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);
        $collection = $this->makeCollection(self::SNAPSHOT_IDS_REMOVE_ONE, self::CURRENT_IDS_REMOVE_ONE);

        $result = $formatter->format($group, [
            'collection' => $collection,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_REMOVED_IDS_DIFF, $result);
    }

    public function testManyToManyUpdateUsesAddedAndRemovedIdsFromCollectionDiff(): void
    {
        $group = $this->makeGroup();
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);
        $collection = $this->makeCollection(self::SNAPSHOT_IDS_UPDATE, self::CURRENT_IDS_UPDATE);

        $result = $formatter->format($group, [
            'collection' => $collection,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_UPDATED_M2M, $result);
        $this->assertStringContainsString(self::LOG_ADDED_IDS_DIFF, $result);
        $this->assertStringContainsString(self::LOG_REMOVED_IDS_DIFF, $result);
    }

    public static function providesNullCasesForManyToMany(): array
    {
        return [
            self::DP_UPDATE_WITHOUT_CONTEXT => [IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE, false],
            self::DP_DELETE_WITHOUT_CONTEXT => [IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE, false],
            self::DP_UPDATE_WITHOUT_COLLECTION => [IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE, true],
            self::DP_DELETE_WITHOUT_COLLECTION => [IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE, true],
        ];
    }

    private function makeFormatter(string $eventType, bool $withContext = true): PresentationCategoryGroupAuditLogFormatter
    {
        $formatter = new PresentationCategoryGroupAuditLogFormatter($eventType);
        if ($withContext) {
            $formatter->setContext(AuditContextBuilder::default()->build());
        }
        return $formatter;
    }

    private function makeCollection(array $snapshotIds, array $currentIds)
    {
        return PersistentCollectionTestHelper::buildManyToManyCollection(
            PresentationCategoryGroup::class,
            self::FIELD_NAME,
            self::TARGET_ENTITY,
            $snapshotIds,
            $currentIds
        );
    }

    private function makeGroup(): PresentationCategoryGroup
    {
        $group = Mockery::mock(PresentationCategoryGroup::class, [
            'getId' => self::GROUP_ID,
            'getName' => self::GROUP_NAME,
            'getColor' => self::GROUP_COLOR,
            'getMaxAttendeeVotes' => self::GROUP_MAX_ATTENDEE_VOTES,
        ])->makePartial();

        $summit = Mockery::mock(Summit::class, [
            'getId' => self::SUMMIT_ID,
            'getName' => self::SUMMIT_NAME,
        ])->makePartial();

        $group->shouldReceive('getSummit')->andReturn($summit);

        return $group;
    }
}
