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

use App\Audit\AbstractAuditLogFormatter;
use App\Audit\ConcreteFormatters\PresentationFormatters\PresentationEventApiAuditLogFormatter;
use App\Audit\ConcreteFormatters\PresentationFormatters\PresentationSubmissionAuditLogFormatter;
use App\Audit\ConcreteFormatters\PresentationFormatters\PresentationUserSubmissionAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Mockery;
use models\main\Company;
use models\summit\Presentation;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Tests\OpenTelemetry\Formatters\Support\PersistentCollectionTestHelper;
use Tests\TestCase;

/**
 * Presentation is audited through three formatters (config/audit_log.php): the default
 * PresentationUserSubmissionAuditLogFormatter plus the route-scoped
 * PresentationEventApiAuditLogFormatter and PresentationSubmissionAuditLogFormatter.
 * All three must report its many-to-many collections (sponsors, tags, allowed_ticket_types)
 * per ADR "M2M Audit Logging via Lightweight Join Table Query".
 */
class PresentationManyToManyAuditLogFormatterTest extends TestCase
{
    private const PRESENTATION_ID = 9643;
    private const PRESENTATION_TITLE = 'Rack-Scale AI Systems';
    private const SPONSORS_FIELD = 'sponsors';
    private const MESSAGE_PREFIX = "Presentation 'Rack-Scale AI Systems' (9643) sponsors (Company)";

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public static function formatterClassProvider(): array
    {
        return [
            'PUT /events route formatter'        => [PresentationEventApiAuditLogFormatter::class],
            'PUT /presentations route formatter' => [PresentationSubmissionAuditLogFormatter::class],
            'default Presentation formatter'     => [PresentationUserSubmissionAuditLogFormatter::class],
        ];
    }

    /**
     * SummitService::saveOrUpdateEvent() does clearSponsors() + addSponsor(): Doctrine emits a
     * collection deletion (delete diff empty, listener preloads deleted_ids from the join table)
     * followed by a collection update whose insert diff carries the new sponsor.
     */
    #[DataProvider('formatterClassProvider')]
    public function testSponsorSwapDeleteEventReportsRemovedIdsFromPayload(string $formatterClass): void
    {
        $formatter = $this->makeFormatter($formatterClass, IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $result = $formatter->format($this->makePresentation(), [
            'collection'  => $this->makeSponsorsCollection([], []),
            'deleted_ids' => [3],
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " deleted: Removed IDs: [3] by user ",
            $result
        );
    }

    #[DataProvider('formatterClassProvider')]
    public function testSponsorSwapUpdateEventReportsAddedIds(string $formatterClass): void
    {
        $formatter = $this->makeFormatter($formatterClass, IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makePresentation(), [
            'collection' => $this->makeSponsorsCollection([], [7]),
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " updated: Added IDs: [7] by user ",
            $result
        );
    }

    #[DataProvider('formatterClassProvider')]
    public function testUpdateEventReportsAddedAndRemovedIdsFromDiff(string $formatterClass): void
    {
        $formatter = $this->makeFormatter($formatterClass, IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makePresentation(), [
            'collection' => $this->makeSponsorsCollection([3, 5], [5, 7]),
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " updated: Added IDs: [7], Removed IDs: [3] by user ",
            $result
        );
    }

    #[DataProvider('formatterClassProvider')]
    public function testDeleteEventReportsRemovedIdsFromDiff(string $formatterClass): void
    {
        $formatter = $this->makeFormatter($formatterClass, IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $result = $formatter->format($this->makePresentation(), [
            'collection' => $this->makeSponsorsCollection([3, 5], [5]),
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " deleted: Removed IDs: [3] by user ",
            $result
        );
    }

    #[DataProvider('formatterClassProvider')]
    public function testUpdateEventWithoutDiffIsSuppressed(string $formatterClass): void
    {
        $formatter = $this->makeFormatter($formatterClass, IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makePresentation(), [
            'collection' => $this->makeSponsorsCollection([3, 5], [3, 5]),
        ]);

        $this->assertNull($result);
    }

    #[DataProvider('formatterClassProvider')]
    public function testDeleteEventWithNothingToRemoveIsSuppressed(string $formatterClass): void
    {
        $formatter = $this->makeFormatter($formatterClass, IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $result = $formatter->format($this->makePresentation(), [
            'collection'  => $this->makeSponsorsCollection([], []),
            'deleted_ids' => [],
        ]);

        $this->assertNull($result);
    }

    #[DataProvider('formatterClassProvider')]
    public function testManyToManyEventWithoutCollectionReturnsNull(string $formatterClass): void
    {
        $formatter = $this->makeFormatter($formatterClass, IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $this->assertNull($formatter->format($this->makePresentation(), []));
    }

    #[DataProvider('formatterClassProvider')]
    public function testManyToManyEventWithInvalidSubjectReturnsNull(string $formatterClass): void
    {
        $formatter = $this->makeFormatter($formatterClass, IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $this->assertNull($formatter->format(new \stdClass(), [
            'collection' => $this->makeSponsorsCollection([], [7]),
        ]));
    }

    private function makeFormatter(string $formatterClass, string $eventType): AbstractAuditLogFormatter
    {
        $formatter = new $formatterClass($eventType);
        $formatter->setContext(AuditContextBuilder::default()->build());
        return $formatter;
    }

    private function makeSponsorsCollection(array $snapshotIds, array $currentIds)
    {
        return PersistentCollectionTestHelper::buildManyToManyCollection(
            Presentation::class,
            self::SPONSORS_FIELD,
            Company::class,
            $snapshotIds,
            $currentIds
        );
    }

    private function makePresentation(): Presentation
    {
        $presentation = Mockery::mock(Presentation::class);
        $presentation->shouldReceive('getId')->andReturn(self::PRESENTATION_ID);
        $presentation->shouldReceive('getTitle')->andReturn(self::PRESENTATION_TITLE);
        $presentation->shouldReceive('getCreator')->andReturn(null);
        $presentation->shouldReceive('getCategory')->andReturn(null);
        $presentation->shouldReceive('getSelectionPlan')->andReturn(null);

        return $presentation;
    }
}
