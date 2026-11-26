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

use App\Audit\ConcreteFormatters\SponsorAuditLogFormatter;
use App\Audit\Interfaces\IAuditStrategy;
use Mockery;
use models\main\Company;
use models\main\Member;
use models\summit\Sponsor;
use models\summit\Summit;
use Tests\OpenTelemetry\Formatters\Support\AuditContextBuilder;
use Tests\OpenTelemetry\Formatters\Support\PersistentCollectionTestHelper;
use Tests\TestCase;

/**
 * Sponsor owns one many-to-many collection: members.
 */
class SponsorManyToManyAuditLogFormatterTest extends TestCase
{
    private const MESSAGE_PREFIX = "Sponsor (17) for Company 'Acme' in Summit 'Test Summit'";

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testUpdateEventReportsAddedAndRemovedIdsFromDiff(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makeSubject(), [
            'collection' => $this->makeCollection('members', Member::class, [10, 11], [11, 12]),
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " members (Member) updated: Added IDs: [12], Removed IDs: [10] by user ",
            $result
        );
    }

    public function testUpdateEventReportsAddedIds(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makeSubject(), [
            'collection' => $this->makeCollection('members', Member::class, [], [5, 6]),
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " members (Member) updated: Added IDs: [5,6] by user ",
            $result
        );
    }

    public function testDeleteEventReportsRemovedIdsFromPayload(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $result = $formatter->format($this->makeSubject(), [
            'collection'  => $this->makeCollection('members', Member::class, [], []),
            'deleted_ids' => [3, 4],
        ]);

        $this->assertStringStartsWith(
            self::MESSAGE_PREFIX . " members (Member) deleted: Removed IDs: [3,4] by user ",
            $result
        );
    }

    public function testUpdateEventWithoutDiffIsSuppressed(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_UPDATE);

        $result = $formatter->format($this->makeSubject(), [
            'collection' => $this->makeCollection('members', Member::class, [3], [3]),
        ]);

        $this->assertNull($result);
    }

    public function testDeleteEventWithNothingToRemoveIsSuppressed(): void
    {
        $formatter = $this->makeFormatter(IAuditStrategy::EVENT_COLLECTION_MANYTOMANY_DELETE);

        $result = $formatter->format($this->makeSubject(), [
            'collection'  => $this->makeCollection('members', Member::class, [], []),
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
            'collection' => $this->makeCollection('members', Member::class, [], [7]),
        ]));
    }

    private function makeFormatter(string $eventType): SponsorAuditLogFormatter
    {
        $formatter = new SponsorAuditLogFormatter($eventType);
        $formatter->setContext(AuditContextBuilder::default()->build());
        return $formatter;
    }

    private function makeCollection(string $field, string $targetEntity, array $snapshotIds, array $currentIds)
    {
        return PersistentCollectionTestHelper::buildManyToManyCollection(
            Sponsor::class,
            $field,
            $targetEntity,
            $snapshotIds,
            $currentIds
        );
    }

    private function makeSubject(): Sponsor
    {
        $company = Mockery::mock(Company::class);
        $company->shouldReceive('getName')->andReturn('Acme');

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getName')->andReturn('Test Summit');

        $sponsor = Mockery::mock(Sponsor::class);
        $sponsor->shouldReceive('getId')->andReturn(17);
        $sponsor->shouldReceive('getCompany')->andReturn($company);
        $sponsor->shouldReceive('getSummit')->andReturn($summit);
        $sponsor->shouldReceive('getOrder')->andReturn(1);

        return $sponsor;
    }
}
