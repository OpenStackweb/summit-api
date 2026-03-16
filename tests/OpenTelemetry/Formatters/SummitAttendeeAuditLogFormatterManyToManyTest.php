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

use App\Audit\ConcreteFormatters\SummitAttendeeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Tests\OpenTelemetry\Formatters\Support\PersistentCollectionTestHelper;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use models\summit\SummitAttendee;
use models\summit\Summit;
use models\main\Tag;

class SummitAttendeeAuditLogFormatterManyToManyTest extends TestCase
{
    private const ATTENDEE_ID = 1;
    private const ATTENDEE_EMAIL = 'test@example.com';
    private const ATTENDEE_FIRST_NAME = 'Test';
    private const ATTENDEE_SURNAME = 'User';
    private const SUMMIT_ID = 1;
    private const SUMMIT_TITLE = 'Test Summit';
    private const TAG_FIELD_NAME = 'tags';
    private const TAG_TARGET_ENTITY = Tag::class;
    private const LOG_REMOVED_IDS_EMPTY = 'Removed IDs: []';
    private const LOG_REMOVED_IDS_PAYLOAD = 'Removed IDs: [10,11,12]';
    private const LOG_REMOVED_IDS_FALLBACK = 'Removed IDs: [9,10]';
    private const LOG_ADDED_IDS_DIFF = 'Added IDs: [3]';
    private const LOG_REMOVED_IDS_DIFF = 'Removed IDs: [1]';
    private const LOG_ADDED_IDS_ONLY = 'Added IDs: [4,5]';
    private const LOG_REMOVED_IDS_LABEL = 'Removed IDs:';
    private const LOG_NO_CHANGES = 'No changes';
    private const DP_UPDATE_WITHOUT_CONTEXT = 'update without context';
    private const DP_DELETE_WITHOUT_CONTEXT = 'delete without context';
    private const DP_UPDATE_WITHOUT_COLLECTION = 'update without collection';
    private const DP_DELETE_WITHOUT_COLLECTION = 'delete without collection';
    private const DELETED_IDS_PAYLOAD = [10, 11, 12];
    private const DELETED_IDS_FALLBACK = [9, 10];
    private const SNAPSHOT_IDS_EMPTY = [];
    private const CURRENT_IDS_EMPTY = [];
    private const SNAPSHOT_IDS_REMOVE_ONE = [1, 2, 3];
    private const CURRENT_IDS_REMOVE_ONE = [2, 3];
    private const SNAPSHOT_IDS_UPDATE = [1, 2];
    private const CURRENT_IDS_UPDATE = [2, 3];
    private const SNAPSHOT_IDS_ONLY_ADDS = [1];
    private const CURRENT_IDS_ONLY_ADDS = [1, 4, 5];
    private const SNAPSHOT_IDS_NO_CHANGES = [1, 2];
    private const CURRENT_IDS_NO_CHANGES = [1, 2];

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
        $attendee = $this->makeAttendee();

        $result = $formatter->format($attendee, []);
        $this->assertNull($result);
    }

    public function testFormatterReturnsNullForInvalidSubject(): void
    {
        $formatter = new SummitAttendeeAuditLogFormatter(
            IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE
        );
        $formatter->setContext(AuditContextBuilder::default()->build());

        $result = $formatter->format(new \stdClass(), []);

        $this->assertNull($result);
    }

    public function testManyToManyDeleteReturnsNullWithoutRemovedIds(): void
    {
        $attendee = $this->makeAttendee();
        
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);
        $collection = $this->makeCollection(self::SNAPSHOT_IDS_EMPTY, self::CURRENT_IDS_EMPTY);

        $result = $formatter->format($attendee, [
            'collection' => $collection,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_REMOVED_IDS_EMPTY, $result);
    }

    public function testManyToManyDeleteUsesDeletedIdsFromPayload(): void
    {
        $attendee = $this->makeAttendee();

        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $deletedIds = self::DELETED_IDS_PAYLOAD;
        $collection = $this->makeCollection(self::SNAPSHOT_IDS_EMPTY, self::CURRENT_IDS_EMPTY);

        $result = $formatter->format($attendee, [
            'collection' => $collection,
            'deleted_ids' => $deletedIds,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_REMOVED_IDS_PAYLOAD, $result);
    }

    public function testManyToManyDeleteUsesDeletedIdsWhenSnapshotEmpty(): void
    {
        $attendee = $this->makeAttendee();

        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);
        $collection = $this->makeCollection(self::SNAPSHOT_IDS_EMPTY, self::CURRENT_IDS_UPDATE);

        $result = $formatter->format($attendee, [
            'collection' => $collection,
            'deleted_ids' => self::DELETED_IDS_FALLBACK,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_REMOVED_IDS_FALLBACK, $result);
    }

    public function testManyToManyUpdateUsesAddedAndRemovedIdsFromCollectionDiff(): void
    {
        $attendee = $this->makeAttendee();

        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);
        $collection = $this->makeCollection(self::SNAPSHOT_IDS_UPDATE, self::CURRENT_IDS_UPDATE);

        $result = $formatter->format($attendee, [
            'collection' => $collection,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_ADDED_IDS_DIFF, $result);
        $this->assertStringContainsString(self::LOG_REMOVED_IDS_DIFF, $result);
    }

    public function testManyToManyUpdateUsesAddedIdsWhenOnlyAdds(): void
    {
        $attendee = $this->makeAttendee();

        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);
        $collection = $this->makeCollection(self::SNAPSHOT_IDS_ONLY_ADDS, self::CURRENT_IDS_ONLY_ADDS);

        $result = $formatter->format($attendee, [
            'collection' => $collection,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_ADDED_IDS_ONLY, $result);
        $this->assertStringNotContainsString(self::LOG_REMOVED_IDS_LABEL, $result);
    }

    public function testManyToManyUpdateReturnsNoChangesWhenDiffEmpty(): void
    {
        $attendee = $this->makeAttendee();
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);
        $collection = $this->makeCollection(self::SNAPSHOT_IDS_NO_CHANGES, self::CURRENT_IDS_NO_CHANGES);

        $result = $formatter->format($attendee, [
            'collection' => $collection,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_NO_CHANGES, $result);
    }

    public function testManyToManyDeleteUsesRemovedIdsFromCollectionDiff(): void
    {
        $attendee = $this->makeAttendee();
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);
        $collection = $this->makeCollection(self::SNAPSHOT_IDS_REMOVE_ONE, self::CURRENT_IDS_REMOVE_ONE);

        $result = $formatter->format($attendee, [
            'collection' => $collection,
        ]);

        $this->assertNotNull($result);
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

    private function makeFormatter(string $eventType, bool $withContext = true): SummitAttendeeAuditLogFormatter
    {
        $formatter = new SummitAttendeeAuditLogFormatter($eventType);
        if ($withContext) {
            $formatter->setContext(AuditContextBuilder::default()->build());
        }
        return $formatter;
    }

    private function makeCollection(array $snapshotIds, array $currentIds)
    {
        return PersistentCollectionTestHelper::buildManyToManyCollection(
            SummitAttendee::class,
            self::TAG_FIELD_NAME,
            self::TAG_TARGET_ENTITY,
            $snapshotIds,
            $currentIds
        );
    }

    private function makeAttendee(): SummitAttendee
    {
        $attendee = Mockery::mock(SummitAttendee::class, [
            'getId' => self::ATTENDEE_ID,
            'getEmail' => self::ATTENDEE_EMAIL,
            'getFirstName' => self::ATTENDEE_FIRST_NAME,
            'getSurname' => self::ATTENDEE_SURNAME,
        ])->makePartial();

        $summit = Mockery::mock(Summit::class, [
            'getId' => self::SUMMIT_ID,
            'getTitle' => self::SUMMIT_TITLE,
        ])->makePartial();

        $attendee->shouldReceive('getSummit')->andReturn($summit);

        return $attendee;
    }

}
