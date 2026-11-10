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

use App\Audit\ConcreteFormatters\SummitAttendeeBadgeAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Mockery;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeBadge;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitBadgeFeatureType;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Tests\OpenTelemetry\Formatters\Support\PersistentCollectionTestHelper;
use Tests\TestCase;

/**
 * SummitAttendeeBadge owns one many-to-many collection: features.
 */
class SummitAttendeeBadgeManyToManyAuditLogFormatterTest extends TestCase
{
    private const MESSAGE_PREFIX = "Attendee Badge (6) for 'Jane Doe'";

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testUpdateEventReportsAddedAndRemovedIdsFromDiff(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makeSubject(), [
            'collection' => $this->makeCollection('features', SummitBadgeFeatureType::class, [10, 11], [11, 12]),
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " features (SummitBadgeFeatureType) updated: Added IDs: [12], Removed IDs: [10] by user ",
            $result
        );
    }

    public function testUpdateEventReportsAddedIds(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makeSubject(), [
            'collection' => $this->makeCollection('features', SummitBadgeFeatureType::class, [], [5, 6]),
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " features (SummitBadgeFeatureType) updated: Added IDs: [5,6] by user ",
            $result
        );
    }

    public function testDeleteEventReportsRemovedIdsFromPayload(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $result = $formatter->format($this->makeSubject(), [
            'collection'  => $this->makeCollection('features', SummitBadgeFeatureType::class, [], []),
            'deleted_ids' => [3, 4],
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " features (SummitBadgeFeatureType) deleted: Removed IDs: [3,4] by user ",
            $result
        );
    }

    public function testUpdateEventWithoutDiffIsSuppressed(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makeSubject(), [
            'collection' => $this->makeCollection('features', SummitBadgeFeatureType::class, [3], [3]),
        ]);

        $this->assertNull($result);
    }

    public function testDeleteEventWithNothingToRemoveIsSuppressed(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $result = $formatter->format($this->makeSubject(), [
            'collection'  => $this->makeCollection('features', SummitBadgeFeatureType::class, [], []),
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
            'collection' => $this->makeCollection('features', SummitBadgeFeatureType::class, [], [7]),
        ]));
    }

    private function makeFormatter(string $eventType): SummitAttendeeBadgeAuditLogFormatter
    {
        $formatter = new SummitAttendeeBadgeAuditLogFormatter($eventType);
        $formatter->setContext(AuditContextBuilder::default()->build());
        return $formatter;
    }

    private function makeCollection(string $field, string $targetEntity, array $snapshotIds, array $currentIds)
    {
        return PersistentCollectionTestHelper::buildManyToManyCollection(
            SummitAttendeeBadge::class,
            $field,
            $targetEntity,
            $snapshotIds,
            $currentIds
        );
    }

    private function makeSubject(): SummitAttendeeBadge
    {
        $owner = Mockery::mock(SummitAttendee::class);
        $owner->shouldReceive('getFirstName')->andReturn('Jane');
        $owner->shouldReceive('getSurname')->andReturn('Doe');

        $ticket = Mockery::mock(SummitAttendeeTicket::class);
        $ticket->shouldReceive('getOwner')->andReturn($owner);

        $badge = Mockery::mock(SummitAttendeeBadge::class);
        $badge->shouldReceive('getId')->andReturn(6);
        $badge->shouldReceive('getTicket')->andReturn($ticket);

        return $badge;
    }
}
