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

use App\Audit\ConcreteFormatters\DefaultEntityManyToManyCollectionDeleteAuditLogFormatter;
use App\Audit\ConcreteFormatters\ChildEntityFormatters\IChildEntityAuditLogFormatter;
use models\summit\SummitAttendee;
use models\main\Tag;
use Mockery;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Tests\OpenTelemetry\Formatters\Support\PersistentCollectionTestHelper;
use Tests\TestCase;

class DefaultEntityManyToManyCollectionDeleteAuditLogFormatterTest extends TestCase
{
    private const FIELD_NAME = 'tags';
    private const TARGET_ENTITY = Tag::class;
    private const ENTITY_CLASS = SummitAttendee::class;

    private const SNAPSHOT_IDS_EMPTY = [];
    private const CURRENT_IDS_EMPTY = [];
    private const SNAPSHOT_IDS_REMOVE_ONE = [1, 2, 3];
    private const CURRENT_IDS_REMOVE_ONE = [2, 3];
    private const SNAPSHOT_IDS_REMOVE_TWO = [1, 2];
    private const CURRENT_IDS_REMOVE_TWO = [];

    private const DELETED_IDS_PAYLOAD = [1, 2, 3];
    private const DELETED_IDS_EMPTY_PAYLOAD = [];
    private const DELETED_IDS_ORDERED_PAYLOAD = [3, 1, 2];

    private const LOG_DELETED_PREFIX = "Many-to-Many collection 'tags' deleted";
    private const LOG_REMOVED_IDS_PAYLOAD = 'Removed 3 Tag(s): 1, 2, 3';
    private const LOG_REMOVED_IDS_ORDERED = 'Removed 3 Tag(s): 1, 2, 3';
    private const LOG_REMOVED_IDS_EMPTY = 'Removed IDs: []';

    private DefaultEntityManyToManyCollectionDeleteAuditLogFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new DefaultEntityManyToManyCollectionDeleteAuditLogFormatter();
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

    public function testFormatWithDeletedIdsEmptyPayloadReturnsFallbackMessage(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_EMPTY, self::CURRENT_IDS_REMOVE_ONE);
        $result = $this->formatter->format(new \stdClass(), [
            'collection' => $collection,
            'deleted_ids' => self::DELETED_IDS_EMPTY_PAYLOAD,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_DELETED_PREFIX, $result);
        $this->assertStringContainsString(self::LOG_REMOVED_IDS_EMPTY, $result);
    }

    public function testFormatWithDeletedIdsPayloadAndNonPersistentCollectionReturnsNull(): void
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

        $result = $this->formatter->format(new \stdClass(), [
            'collection' => $badCollection,
            'deleted_ids' => self::DELETED_IDS_PAYLOAD,
        ]);

        $this->assertNull($result);
    }

    public function testFormatWithEmptyDeleteDiffReturnsFallbackMessage(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_EMPTY, self::CURRENT_IDS_EMPTY);
        $result = $this->formatter->format(new \stdClass(), ['collection' => $collection]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_DELETED_PREFIX, $result);
        $this->assertStringContainsString(self::LOG_REMOVED_IDS_EMPTY, $result);
    }

    public function testFormatWithDeletedIdsPayloadReturnsDetailedMessage(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_EMPTY, self::CURRENT_IDS_REMOVE_ONE);
        $result = $this->formatter->format(new \stdClass(), [
            'collection' => $collection,
            'deleted_ids' => self::DELETED_IDS_PAYLOAD,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_DELETED_PREFIX, $result);
        $this->assertStringContainsString(self::LOG_REMOVED_IDS_PAYLOAD, $result);
    }

    public function testFormatWithDeletedIdsPayloadNormalizesOrder(): void
    {
        $this->formatter->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_EMPTY, self::CURRENT_IDS_REMOVE_ONE);
        $result = $this->formatter->format(new \stdClass(), [
            'collection' => $collection,
            'deleted_ids' => self::DELETED_IDS_ORDERED_PAYLOAD,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_REMOVED_IDS_ORDERED, $result);
    }

    public function testFormatWithChildFormatterUsesDeleteDiff(): void
    {
        $childFormatter = Mockery::mock(IChildEntityAuditLogFormatter::class);
        $childFormatter
            ->shouldReceive('format')
            ->twice()
            ->andReturn('child-1', 'child-2');

        $formatterWithChild = new DefaultEntityManyToManyCollectionDeleteAuditLogFormatter($childFormatter);
        $formatterWithChild->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_REMOVE_TWO, self::CURRENT_IDS_REMOVE_TWO);
        $result = $formatterWithChild->format(new \stdClass(), ['collection' => $collection]);

        $this->assertSame('child-1|child-2', $result);
    }

    public function testFormatWithChildFormatterSkipsNullMessages(): void
    {
        $childFormatter = Mockery::mock(IChildEntityAuditLogFormatter::class);
        $childFormatter
            ->shouldReceive('format')
            ->twice()
            ->andReturn(null, 'child-2');

        $formatterWithChild = new DefaultEntityManyToManyCollectionDeleteAuditLogFormatter($childFormatter);
        $formatterWithChild->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_REMOVE_TWO, self::CURRENT_IDS_REMOVE_TWO);
        $result = $formatterWithChild->format(new \stdClass(), ['collection' => $collection]);

        $this->assertSame('child-2', $result);
    }

    public function testFormatWithDeletedIdsPayloadSkipsChildFormatter(): void
    {
        $childFormatter = Mockery::mock(IChildEntityAuditLogFormatter::class);
        $childFormatter->shouldNotReceive('format');

        $formatterWithChild = new DefaultEntityManyToManyCollectionDeleteAuditLogFormatter($childFormatter);
        $formatterWithChild->setContext(AuditContextBuilder::default()->build());

        $collection = $this->makeCollection(self::SNAPSHOT_IDS_EMPTY, self::CURRENT_IDS_REMOVE_ONE);
        $result = $formatterWithChild->format(new \stdClass(), [
            'collection' => $collection,
            'deleted_ids' => self::DELETED_IDS_PAYLOAD,
        ]);

        $this->assertNotNull($result);
        $this->assertStringContainsString(self::LOG_DELETED_PREFIX, $result);
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
