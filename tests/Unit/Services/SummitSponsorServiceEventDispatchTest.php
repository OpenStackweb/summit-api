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
use Illuminate\Support\Facades\Queue;
use services\model\ISummitSponsorService;
use Tests\InsertSummitTestData;
use Tests\TestCase;

/**
 * Class SummitSponsorServiceEventDispatchTest
 *
 * Verifies that PublishSponsorServiceDomainEventsJob is queued with the correct
 * event type and non-zero entity IDs for every dispatch path introduced by the
 * inline-sponsorship fix:
 *
 * - addSponsor with sponsorship_id          → SponsorCreated + SponsorshipCreated
 * - addSponsor with sponsorships[]          → SponsorCreated + SponsorshipCreated (×N)
 * - updateSponsor adds new sponsorship      → SponsorUpdated + SponsorshipCreated
 * - updateSponsor mutates existing type     → SponsorUpdated + SponsorshipUpdated
 * - updateSponsor removes sponsorship       → SponsorUpdated + SponsorshipRemoved
 * - updateSponsor replaces (remove + add)   → SponsorUpdated + SponsorshipRemoved + SponsorshipCreated (in order)
 *
 * @package Tests\Unit\Services
 */
class SummitSponsorServiceEventDispatchTest extends TestCase
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

    private function getService(): ISummitSponsorService
    {
        return app(ISummitSponsorService::class);
    }

    /**
     * Returns all queued PublishSponsorServiceDomainEventsJob instances for the
     * given event type, asserting each carries a non-zero 'id' in its payload.
     *
     * @return PublishSponsorServiceDomainEventsJob[]
     */
    private function jobsFor(string $event_type): array
    {
        $jobs = Queue::pushed(PublishSponsorServiceDomainEventsJob::class, function ($job) use ($event_type) {
            return $job->getEventType() === $event_type;
        })->all();

        foreach ($jobs as $job) {
            $payload = $job->getPayload();
            $this->assertArrayHasKey('id', $payload, "Payload for '$event_type' is missing 'id'");
            $this->assertGreaterThan(0, $payload['id'], "Payload 'id' for '$event_type' must be > 0");
        }

        return $jobs;
    }

    // -------------------------------------------------------------------------
    // addSponsor — sponsorship_id path
    // -------------------------------------------------------------------------

    public function testAddSponsorWithSponsorshipIdDispatchesSponsorCreatedAndSponsorshipCreated(): void
    {
        Queue::fake();

        $this->getService()->addSponsor(self::$summit, [
            'company_id'     => self::$companies_without_sponsor[0]->getId(),
            'sponsorship_id' => self::$default_summit_sponsor_type->getId(),
        ]);

        $this->assertCount(1, $this->jobsFor(SponsorDomainEvents::SponsorCreated),    'Expected 1 SponsorCreated');
        $this->assertCount(1, $this->jobsFor(SponsorDomainEvents::SponsorshipCreated), 'Expected 1 SponsorshipCreated');
    }

    // -------------------------------------------------------------------------
    // addSponsor — sponsorships[] path
    // -------------------------------------------------------------------------

    public function testAddSponsorWithSponsorshipsArrayDispatchesSponsorshipCreatedForEach(): void
    {
        Queue::fake();

        $this->getService()->addSponsor(self::$summit, [
            'company_id'   => self::$companies_without_sponsor[1]->getId(),
            'sponsorships' => [
                self::$default_summit_sponsor_type->getId(),
                self::$default_summit_sponsor_type2->getId(),
            ],
        ]);

        $this->assertCount(1, $this->jobsFor(SponsorDomainEvents::SponsorCreated),    'Expected 1 SponsorCreated');
        $this->assertCount(2, $this->jobsFor(SponsorDomainEvents::SponsorshipCreated), 'Expected 1 SponsorshipCreated per type');
    }

    // -------------------------------------------------------------------------
    // updateSponsor — sponsorship_id path, no existing sponsorship → creates new
    // -------------------------------------------------------------------------

    public function testUpdateSponsorWithSponsorshipIdCreatesNewDispatchesSponsorshipCreated(): void
    {
        $sponsor = self::$sponsors[0];
        foreach ($sponsor->getSponsorships()->toArray() as $sp) {
            $sponsor->removeSponsorship($sp);
        }
        self::$em->flush();

        Queue::fake();

        $this->getService()->updateSponsor(self::$summit, $sponsor->getId(), [
            'sponsorship_id' => self::$default_summit_sponsor_type->getId(),
        ]);

        $this->assertCount(1, $this->jobsFor(SponsorDomainEvents::SponsorUpdated),    'Expected 1 SponsorUpdated');
        $this->assertCount(1, $this->jobsFor(SponsorDomainEvents::SponsorshipCreated), 'Expected 1 SponsorshipCreated');
    }

    // -------------------------------------------------------------------------
    // updateSponsor — sponsorship_id path, hasSponsorships = true → mutates type
    // -------------------------------------------------------------------------

    public function testUpdateSponsorWithSponsorshipIdMutatesTypeDispatchesSponsorshipUpdated(): void
    {
        $sponsor = self::$sponsors[0];
        $this->assertTrue($sponsor->hasSponsorships(), 'Pre-condition: sponsor must have at least one sponsorship');

        Queue::fake();

        $this->getService()->updateSponsor(self::$summit, $sponsor->getId(), [
            'sponsorship_id' => self::$default_summit_sponsor_type2->getId(),
        ]);

        $this->assertCount(1, $this->jobsFor(SponsorDomainEvents::SponsorUpdated),    'Expected 1 SponsorUpdated');
        $this->assertCount(1, $this->jobsFor(SponsorDomainEvents::SponsorshipUpdated), 'Expected 1 SponsorshipUpdated');
        $this->assertCount(0, $this->jobsFor(SponsorDomainEvents::SponsorshipCreated), 'Expected no SponsorshipCreated for a type-change');
        $this->assertCount(0, $this->jobsFor(SponsorDomainEvents::SponsorshipRemoved), 'Expected no SponsorshipRemoved for a type-change');
    }

    // -------------------------------------------------------------------------
    // updateSponsor — sponsorships[] path, remove all
    // -------------------------------------------------------------------------

    public function testUpdateSponsorRemovesAllSponsorshipsDispatchesSponsorshipRemoved(): void
    {
        $sponsor = self::$sponsors[0];
        $this->assertTrue($sponsor->hasSponsorships(), 'Pre-condition: sponsor must have at least one sponsorship');

        Queue::fake();

        $this->getService()->updateSponsor(self::$summit, $sponsor->getId(), [
            'sponsorships' => [],
        ]);

        $this->assertCount(1, $this->jobsFor(SponsorDomainEvents::SponsorUpdated), 'Expected 1 SponsorUpdated');
        $this->assertNotEmpty($this->jobsFor(SponsorDomainEvents::SponsorshipRemoved), 'Expected at least 1 SponsorshipRemoved');
        $this->assertCount(0, $this->jobsFor(SponsorDomainEvents::SponsorshipCreated), 'Expected no SponsorshipCreated');
    }

    // -------------------------------------------------------------------------
    // updateSponsor — sponsorships[] path, replace → SponsorshipRemoved before SponsorshipCreated
    // -------------------------------------------------------------------------

    public function testUpdateSponsorReplacesSponsorshipDispatchesRemovedBeforeCreated(): void
    {
        // sponsors[0] starts with default_summit_sponsor_type (type1); switch to type2.
        $sponsor = self::$sponsors[0];

        Queue::fake();

        $this->getService()->updateSponsor(self::$summit, $sponsor->getId(), [
            'sponsorships' => [self::$default_summit_sponsor_type2->getId()],
        ]);

        $this->assertCount(1, $this->jobsFor(SponsorDomainEvents::SponsorUpdated),    'Expected 1 SponsorUpdated');
        $this->assertNotEmpty($this->jobsFor(SponsorDomainEvents::SponsorshipRemoved), 'Expected at least 1 SponsorshipRemoved');
        $this->assertCount(1, $this->jobsFor(SponsorDomainEvents::SponsorshipCreated), 'Expected 1 SponsorshipCreated');

        // SponsorshipRemoved must appear before SponsorshipCreated in the queue.
        $all = Queue::pushed(PublishSponsorServiceDomainEventsJob::class)->values();
        $types = $all->map(fn($job) => $job->getEventType())->all();

        $removed_idx = array_search(SponsorDomainEvents::SponsorshipRemoved, $types);
        $created_idx = array_search(SponsorDomainEvents::SponsorshipCreated, $types);

        $this->assertLessThan($created_idx, $removed_idx, 'SponsorshipRemoved must be dispatched before SponsorshipCreated');
    }
}
