<?php namespace Tests\OpenTelemetry\Formatters;
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

use App\Audit\ConcreteFormatters\SummitEventAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Mockery;
use models\main\Company;
use models\main\Tag;
use models\summit\Summit;
use models\summit\SummitEvent;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Tests\OpenTelemetry\Formatters\Support\PersistentCollectionTestHelper;
use Tests\TestCase;

/**
 * SummitEvent (non-presentation events) owns the same many-to-many collections as
 * Presentation: sponsors, tags and allowed_ticket_types.
 */
class SummitEventManyToManyAuditLogFormatterTest extends TestCase
{
    private const EVENT_ID = 4321;
    private const EVENT_TITLE = 'Opening Keynote';
    private const SUMMIT_NAME = 'Test Summit';
    private const MESSAGE_PREFIX = "Summit Event 'Opening Keynote' (4321) for Summit 'Test Summit'";

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testSponsorSwapDeleteEventReportsRemovedIdsFromPayload(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $result = $formatter->format($this->makeEvent(), [
            'collection'  => $this->makeCollection('sponsors', Company::class, [], []),
            'deleted_ids' => [3],
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " sponsors (Company) deleted: Removed IDs: [3] by user ",
            $result
        );
    }

    public function testSponsorSwapUpdateEventReportsAddedIds(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makeEvent(), [
            'collection' => $this->makeCollection('sponsors', Company::class, [], [7]),
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " sponsors (Company) updated: Added IDs: [7] by user ",
            $result
        );
    }

    public function testTagsUpdateEventReportsAddedAndRemovedIdsFromDiff(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makeEvent(), [
            'collection' => $this->makeCollection('tags', Tag::class, [10, 11], [11, 12]),
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " tags (Tag) updated: Added IDs: [12], Removed IDs: [10] by user ",
            $result
        );
    }

    public function testUpdateEventWithoutDiffIsSuppressed(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makeEvent(), [
            'collection' => $this->makeCollection('sponsors', Company::class, [3], [3]),
        ]);

        $this->assertNull($result);
    }

    public function testDeleteEventWithNothingToRemoveIsSuppressed(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $result = $formatter->format($this->makeEvent(), [
            'collection'  => $this->makeCollection('sponsors', Company::class, [], []),
            'deleted_ids' => [],
        ]);

        $this->assertNull($result);
    }

    public function testManyToManyEventWithoutCollectionReturnsNull(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $this->assertNull($formatter->format($this->makeEvent(), []));
    }

    public function testManyToManyEventWithInvalidSubjectReturnsNull(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $this->assertNull($formatter->format(new \stdClass(), [
            'collection' => $this->makeCollection('sponsors', Company::class, [], [7]),
        ]));
    }

    private function makeFormatter(string $eventType): SummitEventAuditLogFormatter
    {
        $formatter = new SummitEventAuditLogFormatter($eventType);
        $formatter->setContext(AuditContextBuilder::default()->build());
        return $formatter;
    }

    private function makeCollection(string $field, string $targetEntity, array $snapshotIds, array $currentIds)
    {
        return PersistentCollectionTestHelper::buildManyToManyCollection(
            SummitEvent::class,
            $field,
            $targetEntity,
            $snapshotIds,
            $currentIds
        );
    }

    private function makeEvent(): SummitEvent
    {
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getName')->andReturn(self::SUMMIT_NAME);

        $event = Mockery::mock(SummitEvent::class);
        $event->shouldReceive('getId')->andReturn(self::EVENT_ID);
        $event->shouldReceive('getTitle')->andReturn(self::EVENT_TITLE);
        $event->shouldReceive('getSummit')->andReturn($summit);

        return $event;
    }
}
