<?php namespace Tests;
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

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use models\exceptions\EntityNotFoundException;
use models\summit\ISummitEventType;
use models\summit\SummitEvent;
use models\summit\SummitEventType;
use App\Models\Foundation\Summit\Events\SummitEventTypeConstants;
use services\model\ISummitService;

/**
 * Class SummitServiceTest
 */
final class SummitServiceTest extends BrowserKitTestCase
{
    use InsertSummitTestData;
    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(\App\Models\Foundation\Main\IGroup::SuperAdmins);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();

        // SummitService::saveOrUpdateEvent()/publishEvent() read the current user via the
        // \App\Facades\ResourceServerContext static facade to stamp created_by/updated_by.
        // The concrete \models\oauth2\ResourceServerContext is final, so Mockery can't mock
        // the facade directly - mock the interface instead and rebind it under the facade's
        // accessor key (established this session in tests/PresentationServiceTest.php).
        $resource_server_context_mock = \Mockery::mock(\models\oauth2\IResourceServerContext::class);
        $resource_server_context_mock->shouldReceive('getCurrentUser')->with(false)->andReturn(self::$defaultMember);
        App::instance('resource_server_context', $resource_server_context_mock);
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
        \Mockery::close();
    }

    /**
     * self::$defaultEventType has blackout_times='All' (a deliberate InsertSummitTestData
     * fixture choice meant to conflict with everything else), and a pre-seeded event of that
     * type already occupies most of the summit's first day - unusable for tests that need to
     * create their OWN non-conflicting events. Build a dedicated, non-blackout event type.
     */
    private function createNonBlackoutEventType(): SummitEventType
    {
        $type = new SummitEventType();
        $type->setType(ISummitEventType::Lunch);
        $type->setBlackoutTimes(SummitEventTypeConstants::BLACKOUT_TIME_NONE);
        self::$summit->addEventType($type);
        self::$em->persist($type);
        self::$em->flush();
        return $type;
    }

    /**
     * @return \DateTime[] [$start_date, $end_date], both within the summit's date range.
     */
    private function nonConflictingEventWindow(): array
    {
        $start_date = (clone self::$summit->getBeginDate())->add(new \DateInterval('PT1H'));
        $end_date = (clone $start_date)->add(new \DateInterval('PT1H'));
        return [$start_date, $end_date];
    }

    /**
     * SummitService::processRegistrationCompaniesData() (SummitService.php:3586) wraps each
     * CSV row's tx_service->transaction() call in a LOCAL try/catch (:3608-3646), outside the
     * transaction's own closure - the same log-and-skip shape SummitOrderService::processTicketData()
     * now also uses. Prove one bad row (a company already attached to the summit, triggering
     * addCompany()'s ValidationException at SummitService.php:3334) does NOT stop a later, valid
     * row from being processed.
     */
    public function testProcessRegistrationCompaniesDataSkipsFailingRowButProcessesLaterRows()
    {
        Storage::fake('swift');

        $service = App::make(ISummitService::class);
        $summit_id = self::$summit->getId();

        // pre-attach an existing company to the summit so the CSV row for it
        // triggers addCompany()'s "already has a company" ValidationException
        $existing_company = self::$companies[0];
        $service->addCompany($summit_id, $existing_company->getId());

        $new_company_name = 'New Row Company ' . uniqid();

        $csv_content = <<<CSV
name,
{$existing_company->getName()},
{$new_company_name},
CSV;

        $filename = 'registration_companies_isolation_test.csv';
        Storage::disk('swift')->put("tmp/registration_companies_import/{$filename}", $csv_content);

        // processRegistrationCompaniesData() catches each row's transaction failure
        // locally, so it returns normally even though the first row fails.
        $service->processRegistrationCompaniesData($summit_id, $filename);

        self::$em->clear();
        self::$summit = self::$summit_repository->find($summit_id);

        $new_company_on_summit = self::$summit->getRegistrationCompanyByName($new_company_name);
        $this->assertNotNull($new_company_on_summit);
    }

    /**
     * SummitService::unPublishEvents() (SummitService.php:1467) loops calling
     * unPublishEvent() (:1034, its own nested transaction) per event id - no try/catch.
     * The FIRST (valid) id's unpublish commits inside its own nested transaction; a SECOND,
     * nonexistent id then throws EntityNotFoundException (:1041), rolling back the ENTIRE
     * batch, including the first id's already-committed unpublish.
     */
    public function testUnPublishEventsRollsBackAlreadyCommittedUnpublishOnLaterBadEventId()
    {
        $service = App::make(ISummitService::class);
        $event_type = $this->createNonBlackoutEventType();
        [$start_date, $end_date] = $this->nonConflictingEventWindow();

        // No location_id set - AbstractPublishService::validateBlackOutTimesAndTimes()
        // only enforces blackout collisions when the event has a non-null location
        // (Summit fixture data seeds many published events spanning the whole date
        // range with blackout_times set, so any located event anywhere would collide).
        $event = $service->addEvent(self::$summit, [
            'title' => 'Batch Unpublish Test ' . uniqid(),
            'type_id' => $event_type->getId(),
            'start_date' => $start_date->getTimestamp(),
            'end_date' => $end_date->getTimestamp(),
        ]);
        $event_id = $event->getId();
        $service->publishEvent(self::$summit, $event_id, []);

        try {
            $service->unPublishEvents(self::$summit, ['events' => [$event_id, 999999999]]);
            $this->fail('Expected EntityNotFoundException was not thrown');
        } catch (EntityNotFoundException $ex) {
            $this->assertStringContainsString('999999999', $ex->getMessage());
        }

        self::$em->clear();
        $reFetched = self::$em->find(SummitEvent::class, $event_id);
        $this->assertNotNull($reFetched);
        $this->assertTrue($reFetched->isPublished());
    }

    /**
     * SummitService::updateAndPublishEvents() (SummitService.php:1488) loops calling
     * updateEvent() (:620, own nested transaction) then publishEvent() (:966, own nested
     * transaction) per event - no try/catch. Item 1's update+publish commits fully; item 2's
     * bad location_id then rolls back the whole call - including item 1's already-committed
     * update+publish.
     *
     * The exception is thrown by updateEvent()'s underlying saveOrUpdateEvent()
     * (SummitService.php:702-708, "location id %s does not exists!") - NOT by publishEvent()'s
     * own, textually-identical location check (:999-1008). Since updateAndPublishEvents()
     * passes the SAME $event_data to both calls and calls updateEvent() first (:1495-1496),
     * saveOrUpdateEvent()'s unconditional (no isAllowsLocation gate) location-existence check
     * always preempts publishEvent()'s gated one for any bad location_id in that payload -
     * confirmed via code review during spec-verify; publishEvent()'s own check is not reachable
     * through this call site. This still proves the pair's core claim (a batch item's nested-tx
     * failure rolls back an EARLIER item's already-committed nested-tx work), just via
     * updateEvent()'s nested transaction rather than publishEvent()'s specifically.
     */
    public function testUpdateAndPublishEventsRollsBackAlreadyCommittedFirstItemWhenSecondItemsUpdateEventFailsOnBadLocationId()
    {
        $service = App::make(ISummitService::class);
        $event_type = $this->createNonBlackoutEventType();
        [$start_date, $end_date] = $this->nonConflictingEventWindow();

        // event1 has no location_id - it gets published within the batch call, and
        // AbstractPublishService::validateBlackOutTimesAndTimes() only enforces blackout
        // collisions when the event has a non-null location (see the sibling test above).
        $original_title_1 = 'Original Title 1 ' . uniqid();
        $event1 = $service->addEvent(self::$summit, [
            'title' => $original_title_1,
            'type_id' => $event_type->getId(),
            'start_date' => $start_date->getTimestamp(),
            'end_date' => $end_date->getTimestamp(),
        ]);
        $event1_id = $event1->getId();

        $event2 = $service->addEvent(self::$summit, [
            'title' => 'Event 2 ' . uniqid(),
            'type_id' => $event_type->getId(),
            'location_id' => self::$mainVenue->getId(),
            'start_date' => $start_date->getTimestamp(),
            'end_date' => $end_date->getTimestamp(),
        ]);
        $event2_id = $event2->getId();

        try {
            $service->updateAndPublishEvents(self::$summit, [
                'events' => [
                    [
                        'id' => $event1_id,
                        'title' => 'Updated Title Should Not Persist',
                        'start_date' => $start_date->getTimestamp(),
                        'end_date' => $end_date->getTimestamp(),
                    ],
                    [
                        'id' => $event2_id,
                        'start_date' => $start_date->getTimestamp(),
                        'end_date' => $end_date->getTimestamp(),
                        'location_id' => 999999999,
                    ],
                ],
            ]);
            $this->fail('Expected EntityNotFoundException was not thrown');
        } catch (EntityNotFoundException $ex) {
            $this->assertStringContainsString('location id', $ex->getMessage());
        }

        self::$em->clear();
        $reFetchedEvent1 = self::$em->find(SummitEvent::class, $event1_id);
        $this->assertNotNull($reFetchedEvent1);
        $this->assertEquals($original_title_1, $reFetchedEvent1->getTitle());
        $this->assertFalse($reFetchedEvent1->isPublished());
    }

    /**
     * Volume happy path for processEventData(): every row of a 20-row CSV is valid
     * (existing event type + track, title, description), so all 20 events must exist
     * afterwards and the source file must be deleted.
     */
    public function testProcessEventDataImportsAllRowsWhenEveryRowIsValid()
    {
        Storage::fake('swift');

        $service = App::make(ISummitService::class);
        $summit_id = self::$summit->getId();
        $type_name = self::$defaultEventType->getType();
        $track_title = self::$defaultTrack->getTitle();

        $uid = uniqid();
        $titles = [];
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            $title = sprintf('CSV Bulk Event %02d %s', $i, $uid);
            $titles[] = $title;
            $rows[] = sprintf('%s,%s,%s,Bulk import row %02d', $type_name, $track_title, $title, $i);
        }
        $csv_content = "type,track,title,description\n" . implode("\n", $rows);

        $filename = 'events_all_valid.csv';
        $path = "tmp/events_imports/{$filename}";
        Storage::disk('swift')->put($path, $csv_content);

        $service->processEventData($summit_id, $filename, false);

        self::$em->clear();

        foreach ($titles as $title) {
            $event = self::$em->getRepository(SummitEvent::class)->findOneBy(['title' => $title]);
            $this->assertNotNull($event, sprintf('Event "%s" should have been imported', $title));
            $this->assertEquals($summit_id, $event->getSummitId());
        }
        $this->assertFalse(
            Storage::disk('swift')->exists($path),
            'A fully-processed import must delete its source file'
        );
    }

    /**
     * Volume mixed outcome for processEventData(): 20 rows where 15 carry a
     * nonexistent event type (the row transaction throws EntityNotFoundException,
     * the per-row catch logs and skips) interleaved with 5 valid rows. The 5 valid
     * events must import, the 15 failing rows must leave nothing behind, the import
     * must not throw, and the file is still deleted.
     */
    public function testProcessEventDataImportsOnlyValidRowsWhenMostRowsFail()
    {
        Storage::fake('swift');

        $service = App::make(ISummitService::class);
        $summit_id = self::$summit->getId();
        $type_name = self::$defaultEventType->getType();
        $track_title = self::$defaultTrack->getTitle();

        $uid = uniqid();
        $good_titles = [];
        $bad_titles = [];
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            // every 4th row is valid: failures happen before and after each success
            $is_good = ($i % 4 === 0);
            $title = sprintf('CSV Mixed Event %02d %s', $i, $uid);
            if ($is_good) $good_titles[] = $title; else $bad_titles[] = $title;
            $rows[] = sprintf(
                '%s,%s,%s,Mixed import row %02d',
                $is_good ? $type_name : 'NON EXISTENT EVENT TYPE',
                $track_title,
                $title,
                $i
            );
        }
        $csv_content = "type,track,title,description\n" . implode("\n", $rows);

        $filename = 'events_mixed.csv';
        $path = "tmp/events_imports/{$filename}";
        Storage::disk('swift')->put($path, $csv_content);

        // must not throw: failing rows are logged and skipped
        $service->processEventData($summit_id, $filename, false);

        self::$em->clear();

        $this->assertCount(5, $good_titles);
        $this->assertCount(15, $bad_titles);
        foreach ($good_titles as $title) {
            $this->assertNotNull(
                self::$em->getRepository(SummitEvent::class)->findOneBy(['title' => $title]),
                sprintf('Valid row event "%s" should have been imported', $title)
            );
        }
        foreach ($bad_titles as $title) {
            $this->assertNull(
                self::$em->getRepository(SummitEvent::class)->findOneBy(['title' => $title]),
                sprintf('Failing row event "%s" should not exist', $title)
            );
        }
        $this->assertFalse(
            Storage::disk('swift')->exists($path),
            'Known per-row failures must not block the source file deletion'
        );
    }
}
