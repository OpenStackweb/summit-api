<?php namespace Tests\Unit\Services;
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

use App\Events\SponsorServices\SummitDomainEvents;
use App\Jobs\SponsorServices\PublishSponsorServiceDomainEventsJob;
use Illuminate\Support\Facades\Queue;
use services\model\ISummitService;
use Tests\InsertSummitTestData;
use Tests\TestCase;

/**
 * Class SummitServiceEventDispatchTest
 *
 * Companion to SummitSponsorshipServiceEventDispatchTest: verifies the
 * dispatched 'id' for SummitDeleted matches the original summit id. Unlike
 * the sponsorship/add-on/media-file-type cases, deleteSummit() only flips a
 * soft-delete flag (no Doctrine remove()), so this is a characterization test
 * confirming the id was never actually zeroed here - see test body.
 *
 * @package Tests\Unit\Services
 */
class SummitServiceEventDispatchTest extends TestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    public function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function getService(): ISummitService
    {
        return app(ISummitService::class);
    }

    public function testDeleteSummitDispatchesSummitDeletedWithOriginalId(): void
    {
        $summit_id = self::$summit->getId();

        Queue::fake();

        $this->getService()->deleteSummit($summit_id);

        $jobs = Queue::pushed(PublishSponsorServiceDomainEventsJob::class, function ($job) {
            return $job->getEventType() === SummitDomainEvents::SummitDeleted;
        })->all();

        $this->assertCount(1, $jobs, 'Expected 1 SummitDeleted');
        $this->assertSame($summit_id, $jobs[0]->getPayload()['id'], 'Dispatched id must be the deleted summit id, not 0');
    }
}
