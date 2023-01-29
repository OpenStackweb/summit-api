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
    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::Administrators);
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
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testPublishProposedActivity(int $presentation_id = 107226, int $location_id = 7455)
    {
        $service = App::make(IScheduleService::class);

        $start_date = new \DateTime("now", new \DateTimeZone("UTC"));
        $end_date = (clone $start_date)->add(new \DateInterval("P10D"));

        $published_schedule_event = $service->publishProposedActivityToSource(
            SummitProposedSchedule::TrackChairs,
            $presentation_id,
            [
                "location_id" => $location_id,
                "start_date" => $start_date->getTimestamp(),
                "end_date" => $end_date->getTimestamp(),
            ]
        );

        $this->assertNotNull($published_schedule_event->getSchedule());
        $this->assertEquals($published_schedule_event->getLocationId(), $location_id);

        return $published_schedule_event;
    }

    public function testPublishOverlappingProposedActivities(
        int $first_presentation_id = 107226, int $second_presentation_id = 107227, int $location_id = 7455
    )
    {
        $first_published_schedule_event = $this->testPublishProposedActivity($first_presentation_id, $location_id);

        $service = App::make(IScheduleService::class);

        $start_date = $first_published_schedule_event->getStartDate()->getTimestamp();
        $end_date = $first_published_schedule_event->getEndDate()->getTimestamp();

        $this->expectException(ValidationException::class);

        $second_published_schedule_event = $service->publishProposedActivityToSource(
            SummitProposedSchedule::TrackChairs,
            $second_presentation_id,
            [
                "location_id" => $location_id,
                "start_date" => $start_date,
                "end_date" => $end_date,
            ]
        );
    }

    public function testUnPublishProposedActivity($presentation_id = 107227, int $location_id = 7455)
    {
        $published_schedule_event = $this->testPublishProposedActivity($presentation_id, $location_id);

        $service = App::make(IScheduleService::class);

        $schedule = $published_schedule_event->getSchedule();

        $schedule_event_to_unpublish = $service->unPublishProposedActivity(
            $published_schedule_event->getSchedule()->getId(),
            $published_schedule_event->getSummitEventId()
        );

        $unpublished_schedule_event = $schedule->getScheduledSummitEventById($schedule_event_to_unpublish->getId());

        $this->assertNull($unpublished_schedule_event);
    }

    public function testPublishAllProposedActivityByLocationAndDateRange(int $schedule_id = 7, int $location_id = 7455)
    {
        $service = App::make(IScheduleService::class);

        $start_date = new \DateTime("now", new \DateTimeZone("UTC"));
        $end_date = (clone $start_date)->add(new \DateInterval("P10D"));

        $published_schedule_events = $service->publishAll(
            $schedule_id,
            [
                "location_id" => $location_id,
                "start_date" => $start_date->getTimestamp(),
                "end_date" => $end_date->getTimestamp(),
            ]
        );
        $this->assertNotEmpty($published_schedule_events);
    }

    public function testPublishAllProposedActivityByEventIds(int $schedule_id = 7, array $candidate_event_ids = [107226, 107227])
    {
        $service = App::make(IScheduleService::class);

        $published_schedule_events = $service->publishAll(
            $schedule_id,
            [
                "event_ids" => $candidate_event_ids
            ]
        );
        $this->assertNotEmpty($published_schedule_events);
        $this->assertTrue(count($published_schedule_events) <= count($candidate_event_ids));
    }
}