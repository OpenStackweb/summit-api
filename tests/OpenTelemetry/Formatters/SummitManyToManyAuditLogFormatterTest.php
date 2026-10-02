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

use App\Audit\ConcreteFormatters\SummitAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Mockery;
use models\main\Company;
use models\summit\PresentationCategory;
use models\summit\Summit;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Tests\OpenTelemetry\Formatters\Support\PersistentCollectionTestHelper;
use Tests\TestCase;

/**
 * Summit owns five many-to-many collections: the four excluded_categories_for_* collections (PresentationCategory) and registration_companies (Company).
 */
class SummitManyToManyAuditLogFormatterTest extends TestCase
{
    private const MESSAGE_PREFIX = "Summit 'Test Summit' (3)";

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testUpdateEventReportsAddedAndRemovedIdsFromDiff(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makeSubject(), [
            'collection' => $this->makeCollection('excluded_categories_for_accepted_presentations', PresentationCategory::class, [10, 11], [11, 12]),
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " excluded_categories_for_accepted_presentations (PresentationCategory) updated: Added IDs: [12], Removed IDs: [10] by user ",
            $result
        );
    }

    public function testUpdateEventReportsAddedIds(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makeSubject(), [
            'collection' => $this->makeCollection('registration_companies', Company::class, [], [5, 6]),
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " registration_companies (Company) updated: Added IDs: [5,6] by user ",
            $result
        );
    }

    public function testDeleteEventReportsRemovedIdsFromPayload(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $result = $formatter->format($this->makeSubject(), [
            'collection'  => $this->makeCollection('excluded_categories_for_accepted_presentations', PresentationCategory::class, [], []),
            'deleted_ids' => [3, 4],
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " excluded_categories_for_accepted_presentations (PresentationCategory) deleted: Removed IDs: [3,4] by user ",
            $result
        );
    }

    public function testUpdateEventWithoutDiffIsSuppressed(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makeSubject(), [
            'collection' => $this->makeCollection('registration_companies', Company::class, [3], [3]),
        ]);

        $this->assertNull($result);
    }

    public function testDeleteEventWithNothingToRemoveIsSuppressed(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $result = $formatter->format($this->makeSubject(), [
            'collection'  => $this->makeCollection('registration_companies', Company::class, [], []),
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
            'collection' => $this->makeCollection('excluded_categories_for_accepted_presentations', PresentationCategory::class, [], [7]),
        ]));
    }

    private function makeFormatter(string $eventType): SummitAuditLogFormatter
    {
        $formatter = new SummitAuditLogFormatter($eventType);
        $formatter->setContext(AuditContextBuilder::default()->build());
        return $formatter;
    }

    private function makeCollection(string $field, string $targetEntity, array $snapshotIds, array $currentIds)
    {
        return PersistentCollectionTestHelper::buildManyToManyCollection(
            Summit::class,
            $field,
            $targetEntity,
            $snapshotIds,
            $currentIds
        );
    }

    private function makeSubject(): Summit
    {
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(3);
        $summit->shouldReceive('getName')->andReturn('Test Summit');

        return $summit;
    }
}
