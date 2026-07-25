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

use App\Events\SponsorServices\SponsorDomainEvents;
use App\Jobs\SponsorServices\PublishSponsorServiceDomainEventsJob;
use App\Services\Model\ISummitSponsorshipService;
use Illuminate\Support\Facades\Queue;
use Tests\InsertSummitTestData;
use Tests\TestCase;

/**
 * Class SummitSponsorshipServiceEventDispatchTest
 *
 * Regression coverage for a bug where SponsorshipRemoved/SponsorshipAddOnRemoved
 * jobs were dispatched with 'id' => 0: the removed entity had already been
 * flushed (Doctrine nulls the identifier on a deleted entity), so building the
 * DeletedEventDTO from the entity (DeletedEventDTO::fromEntity($entity)) after
 * the transaction closed read a stale getId(). The fix builds the DTO from the
 * id captured before removal (DeletedEventDTO::fromId($id)).
 *
 * @package Tests\Unit\Services
 */
class SummitSponsorshipServiceEventDispatchTest extends TestCase
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

    private function getService(): ISummitSponsorshipService
    {
        return app(ISummitSponsorshipService::class);
    }

    /**
     * @return PublishSponsorServiceDomainEventsJob[]
     */
    private function jobsFor(string $event_type): array
    {
        return Queue::pushed(PublishSponsorServiceDomainEventsJob::class, function ($job) use ($event_type) {
            return $job->getEventType() === $event_type;
        })->all();
    }

    public function testRemoveSponsorshipDispatchesSponsorshipRemovedWithOriginalId(): void
    {
        $sponsor = self::$sponsors[0];
        $sponsorship = $sponsor->getSponsorships()->first();
        $this->assertNotFalse($sponsorship, 'Pre-condition: sponsor must have a sponsorship');
        $sponsorship_id = $sponsorship->getId();

        Queue::fake();

        $this->getService()->removeSponsorship(self::$summit, $sponsor->getId(), $sponsorship_id);

        $jobs = $this->jobsFor(SponsorDomainEvents::SponsorshipRemoved);
        $this->assertCount(1, $jobs, 'Expected 1 SponsorshipRemoved');
        $this->assertSame($sponsorship_id, $jobs[0]->getPayload()['id'], 'Dispatched id must be the removed sponsorship id, not 0');
    }

    public function testRemoveAddOnDispatchesSponsorshipAddOnRemovedWithOriginalId(): void
    {
        $sponsor = self::$sponsors[0];
        $sponsorship = $sponsor->getSponsorships()->first();
        $this->assertNotFalse($sponsorship, 'Pre-condition: sponsor must have a sponsorship');
        $add_on = $sponsorship->getAddOns()->first();
        $this->assertNotFalse($add_on, 'Pre-condition: sponsorship must have an add-on');
        $add_on_id = $add_on->getId();

        Queue::fake();

        $this->getService()->removeAddOn(self::$summit, $sponsor->getId(), $sponsorship->getId(), $add_on_id);

        $jobs = $this->jobsFor(SponsorDomainEvents::SponsorshipAddOnRemoved);
        $this->assertCount(1, $jobs, 'Expected 1 SponsorshipAddOnRemoved');
        $this->assertSame($add_on_id, $jobs[0]->getPayload()['id'], 'Dispatched id must be the removed add-on id, not 0');
    }
}
