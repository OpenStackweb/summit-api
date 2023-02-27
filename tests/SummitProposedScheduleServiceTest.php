<?php namespace Tests;
/**
 * Copyright 2023 OpenStack Foundation
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

use App\Models\Foundation\Main\IGroup;
use App\Services\Model\IScheduleService;
use Illuminate\Support\Facades\App;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\SummitProposedSchedule;

/**
 * Class SummitProposedScheduleServiceTest
 * @package Tests
 */
class SummitProposedScheduleServiceTest extends TestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::insertSummitTestData();

        $ctx = App::make(IResourceServerContext::class);
        if(!$ctx instanceof IResourceServerContext)
            throw new \Exception();

        $context = [];
        $context['user_id'] = self::$member->getId();
        $context['external_user_id'] = self::$member->getId();
        $context['user_identifier']  = "test";
        $context['user_email']       = self::$member->getEmail();
        $context['user_email_verified'] = true;
        $context['user_first_name']  = self::$member->getFirstName();
        $context['user_last_name']   = self::$member->getLastName();
        $context['user_groups']      = [IGroup::Administrators];
        $ctx->setAuthorizationContext($context);
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testPublishProposedActivity()
    {
        $service = App::make(IScheduleService::class);

        $start_date = new \DateTime("now", new \DateTimeZone("UTC"));
        $end_date = (clone $start_date)->add(new \DateInterval("P10D"));
        $presentation = self::$presentations[23];

        $published_schedule_event = $service->publishProposedActivityToSource(
            "track-chairs",
            $presentation->getId(),
            [
                "location_id" => $presentation->getLocation()->getId(),
                "start_date" => $start_date->getTimestamp(),
                "end_date" => $end_date->getTimestamp(),
            ]
        );

        $this->assertNotNull($published_schedule_event->getSchedule());
        $this->assertEquals($published_schedule_event->getLocationId(), $presentation->getLocation()->getId());

        return $published_schedule_event;
    }

    public function testPublishOverlappingProposedActivities()
    {
        $first_published_schedule_event = $this->testPublishProposedActivity();

        $service = App::make(IScheduleService::class);

        $start_date = $first_published_schedule_event->getStartDate()->getTimestamp();
        $end_date = $first_published_schedule_event->getEndDate()->getTimestamp();
        $presentation = self::$presentations[1];

        $this->expectException(ValidationException::class);

        $service->publishProposedActivityToSource(
            "track-chairs",
            $presentation->getId(),
            [
                "location_id" => $presentation->getLocation()->getId(),
                "start_date" => $start_date,
                "end_date" => $end_date,
            ]
        );
    }

    public function testUnPublishProposedActivity()
    {
        $published_schedule_event = $this->testPublishProposedActivity();

        $service = App::make(IScheduleService::class);

        $schedule = $published_schedule_event->getSchedule();

        $schedule_event_to_unpublish = $service->unPublishProposedActivity(
            "track-chairs",
            $published_schedule_event->getSummitEventId()
        );

        $unpublished_schedule_event = $schedule->getScheduledSummitEventById($schedule_event_to_unpublish->getId());

        $this->assertNull($unpublished_schedule_event);
    }

    public function testPublishAllProposedActivitiesByLocationAndDateRange()
    {
        $service = App::make(IScheduleService::class);

        $start_date = new \DateTime("now", new \DateTimeZone("UTC"));
        $end_date = (clone $start_date)->add(new \DateInterval("P10D"));
        $presentation = self::$summit->getPresentations()[0];

        $published_schedule_events = $service->publishAll(
            "track-chairs",
            self::$summit->getId(),
            [
                "location_id" => $presentation->getLocation()->getId(),
                "start_date" => $start_date->getTimestamp(),
                "end_date" => $end_date->getTimestamp(),
            ]
        );
        $this->assertNotEmpty($published_schedule_events);
    }

    public function testPublishAllProposedActivitiesByPresentationIds()
    {
        $service = App::make(IScheduleService::class);

        $candidate_event_ids = [];
        foreach (self::$presentations as $presentation) {
            $candidate_event_ids[] = $presentation->getId();
        }

        $published_schedule_events = $service->publishAll(
            "track-chairs",
            self::$summit->getId(),
            [
                "presentation_ids" => $candidate_event_ids
            ]
        );
        $this->assertNotEmpty($published_schedule_events);
        $this->assertTrue(count($published_schedule_events) <= count($candidate_event_ids));
    }

    public function testPublishAllProposedActivities()
    {
        $service = App::make(IScheduleService::class);

        $published_schedule_events =
            $service->publishAll("track-chairs", self::$summit->getId(), []);

        $this->assertNotEmpty($published_schedule_events);
        $this->assertTrue(count($published_schedule_events) == count(self::$summit->getPresentations()));
    }
}