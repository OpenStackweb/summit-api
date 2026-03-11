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

use App\Audit\ConcreteFormatters\DefaultEntityManyToManyCollectionUpdateAuditLogFormatter;
use App\Audit\ConcreteFormatters\ChildEntityFormatters\IChildEntityAuditLogFormatter;
use models\summit\SummitAttendee;
use models\main\Tag;
use Mockery;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Tests\OpenTelemetry\Formatters\Support\PersistentCollectionTestHelper;
use Tests\TestCase;

class DefaultEntityManyToManyCollectionUpdateAuditLogFormatterTest extends TestCase
{
    private const TEST_ERROR_MESSAGE = 'Test error';
    private const FIELD_NAME = 'tags';
    private const TARGET_ENTITY = Tag::class;
    private const ENTITY_CLASS = SummitAttendee::class;

    private const SNAPSHOT_IDS_EMPTY = [];
    private const CURRENT_IDS_EMPTY = [];
    private const SNAPSHOT_IDS_UPDATE = [1, 2];
    private const CURRENT_IDS_UPDATE = [2, 3];
    private const SNAPSHOT_IDS_ONLY_ADDS = [1];
    private const CURRENT_IDS_ONLY_ADDS = [1, 2];
    private const SNAPSHOT_IDS_ONLY_DELETES = [1, 2];
    private const CURRENT_IDS_ONLY_DELETES = [];
    private const SNAPSHOT_IDS_UNSORTED = [5, 1];
    private const CURRENT_IDS_UNSORTED = [1, 5, 3, 2];
    private const PAYLOAD_ADDED_IDS = [2];
    private const PAYLOAD_REMOVED_IDS = [1];
    private const PAYLOAD_EMPTY_IDS = [];

    private const LOG_UPDATED_PREFIX = "Many-to-Many collection 'tags' updated";
    private const LOG_ADDED_ONE = 'Added 1 Tag(s): 3';
    private const LOG_REMOVED_ONE = 'Removed 1 Tag(s): 1';
    private const LOG_ADDED_ONLY = 'Added 1 Tag(s): 2';
    private const LOG_REMOVED_ONLY = 'Removed 2 Tag(s): 1, 2';
    private const LOG_ADDED_SORTED = 'Added 2 Tag(s): 2, 3';
    private const LOG_ADDED_PAYLOAD = 'Added 1 Tag(s): 2';
    private const LOG_REMOVED_PAYLOAD = 'Removed 1 Tag(s): 1';

    private DefaultEntityManyToManyCollectionUpdateAuditLogFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new DefaultEntityManyToManyCollectionUpdateAuditLogFormatter();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testFormatWithoutCollectionReturnsNull(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());
        $result = $this->formatter->format(new \stdClass(), []);
        $this->assertNull($result);
    }

    public function testFormatWithNullCollectionReturnsNull(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());
        $result = $this->formatter->format(new \stdClass(), ['collection' => null]);
        $this->assertNull($result);
    }

    public function testFormatWithNonPersistentCollectionReturnsNull(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());

        $badCollection = new class {
            public function getInsertDiff(): array
            {
                return [];
            }
            public function getDeleteDiff(): array
            {
                return [];
            }
        };

        $result = $this->formatter->format(new \stdClass(), ['collection' => $badCollection]);
        $this->assertNull($result);
    }

    public function testFormatWithNonPersistentCollectionAndChangesReturnsNull(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());

        $badCollection = new class {
            public function getInsertDiff(): array
            {
                return [new \stdClass()];
            }
            public function getDeleteDiff(): array
            {
                return [];
            }
        };

        $result = $this->formatter->format(new \stdClass(), ['collection' => $badCollection]);
        $this->assertNull($result);
    }

    public function testFormatWithNoChangesReturnsNull(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_EMPTY, self::CURRENT_IDS_EMPTY);
        $result = $this->formatter->format(new \stdClass(), ['collection' => $collection]);

        $this->assertNull($result);
    }

    public function testFormatWithInsertAndDeleteDiffReturnsDetailedMessage(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_UPDATE, self::CURRENT_IDS_UPDATE);
        $result = $this->formatter->format(new \stdClass(), ['collection' => $collection]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_UPDATED_PREFIX, $result);
        $this->assertStringContainsString(self::LOG_ADDED_ONE, $result);
        $this->assertStringContainsString(self::LOG_REMOVED_ONE, $result);
    }

    public function testFormatWithOnlyAddsReturnsDetailedMessage(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_ONLY_ADDS, self::CURRENT_IDS_ONLY_ADDS);
        $result = $this->formatter->format(new \stdClass(), ['collection' => $collection]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_UPDATED_PREFIX, $result);
        $this->assertStringContainsString(self::LOG_ADDED_ONLY, $result);
        $this->assertStringNotContainsString(self::LOG_REMOVED_ONE, $result);
    }

    public function testFormatWithPayloadAddedAndRemovedReturnsDetailedMessage(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_EMPTY, self::CURRENT_IDS_UPDATE);
        $result = $this->formatter->format(new \stdClass(), [
            'collection' => $collection,
            'added_ids' => self::PAYLOAD_ADDED_IDS,
            'removed_ids' => self::PAYLOAD_REMOVED_IDS,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_UPDATED_PREFIX, $result);
        $this->assertStringContainsString(self::LOG_ADDED_PAYLOAD, $result);
        $this->assertStringContainsString(self::LOG_REMOVED_PAYLOAD, $result);
    }

    public function testFormatWithPayloadEmptyReturnsNull(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_EMPTY, self::CURRENT_IDS_UPDATE);
        $result = $this->formatter->format(new \stdClass(), [
            'collection' => $collection,
            'added_ids' => self::PAYLOAD_EMPTY_IDS,
            'removed_ids' => self::PAYLOAD_EMPTY_IDS,
        ]);

        $this->assertNull($result);
    }

    public function testFormatWithPayloadSkipsChildFormatter(): void
    {
        $childFormatter = Mockery::mock(IChildEntityAuditLogFormatter::class);
        $childFormatter->shouldNotReceive('format');

        $formatterWithChild = new DefaultEntityManyToManyCollectionUpdateAuditLogFormatter($childFormatter);
        $formatterWithChild->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_EMPTY, self::CURRENT_IDS_UPDATE);
        $result = $formatterWithChild->format(new \stdClass(), [
            'collection' => $collection,
            'added_ids' => self::PAYLOAD_ADDED_IDS,
            'removed_ids' => self::PAYLOAD_REMOVED_IDS,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_UPDATED_PREFIX, $result);
    }

    public function testFormatWithOnlyDeletesReturnsDetailedMessage(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_ONLY_DELETES, self::CURRENT_IDS_ONLY_DELETES);
        $result = $this->formatter->format(new \stdClass(), ['collection' => $collection]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_UPDATED_PREFIX, $result);
        $this->assertStringContainsString(self::LOG_REMOVED_ONLY, $result);
        $this->assertStringNotContainsString(self::LOG_ADDED_ONLY, $result);
    }

    public function testFormatWithUnsortedIdsReturnsSortedMessage(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_UNSORTED, self::CURRENT_IDS_UNSORTED);
        $result = $this->formatter->format(new \stdClass(), ['collection' => $collection]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_ADDED_SORTED, $result);
    }

    public function testFormatWithChildFormatterUsesInsertAndDeleteDiff(): void
    {
        $childFormatter = Mockery::mock(IChildEntityAuditLogFormatter::class);
        $childFormatter
            ->shouldReceive('format')
            ->twice()
            ->andReturn('child-create', 'child-delete');

        $formatterWithChild = new DefaultEntityManyToManyCollectionUpdateAuditLogFormatter($childFormatter);
        $formatterWithChild->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_UPDATE, self::CURRENT_IDS_UPDATE);
        $result = $formatterWithChild->format(new \stdClass(), ['collection' => $collection]);

        $this->assertSame('child-create|child-delete', $result);
    }

    public function testFormatWithChildFormatterSkipsNullMessages(): void
    {
        $childFormatter = Mockery::mock(IChildEntityAuditLogFormatter::class);
        $childFormatter
            ->shouldReceive('format')
            ->twice()
            ->andReturn(null, 'child-delete');

        $formatterWithChild = new DefaultEntityManyToManyCollectionUpdateAuditLogFormatter($childFormatter);
        $formatterWithChild->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_UPDATE, self::CURRENT_IDS_UPDATE);
        $result = $formatterWithChild->format(new \stdClass(), ['collection' => $collection]);

        $this->assertSame('|child-delete', $result);
    }

    public function testFormatWithChildFormatterAndNoChangesReturnsNull(): void
    {
        $childFormatter = Mockery::mock(IChildEntityAuditLogFormatter::class);
        $childFormatter->shouldNotReceive('format');

        $formatterWithChild = new DefaultEntityManyToManyCollectionUpdateAuditLogFormatter($childFormatter);
        $formatterWithChild->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_EMPTY, self::CURRENT_IDS_EMPTY);
        $result = $formatterWithChild->format(new \stdClass(), ['collection' => $collection]);

        $this->assertNull($result);
    }

    public function testFormatterExceptionHandlingReturnsNull(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());
        $testErrorMessage = self::TEST_ERROR_MESSAGE;

        $badCollection = new class($testErrorMessage) {
            public function __construct(private string $errorMessage) {}

            public function getInsertDiff(): array
            {
                throw new \Exception($this->errorMessage);
            }
            public function getDeleteDiff(): array
            {
                return [];
            }
        };

        $result = $this->formatter->format(new \stdClass(), ['collection' => $badCollection]);
        $this->assertNull($result);
    }

    private function makeCollection(array $snapshotIds, array $currentIds)
    {
        return PersistentCollectionTestHelper::buildManyToManyCollection(
            self::ENTITY_CLASS,
            self::FIELD_NAME,
            self::TARGET_ENTITY,
            $snapshotIds,
            $currentIds
        );
    }
}
