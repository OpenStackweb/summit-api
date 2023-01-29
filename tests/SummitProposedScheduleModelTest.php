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
use App\ModelSerializers\SerializerUtils;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\SummitProposedSchedule;
use models\summit\SummitProposedScheduleSummitEvent;
use ModelSerializers\SerializerRegistry;

/**
 * Class SummitProposedScheduledModelTest
 * @package Tests
 */
class SummitProposedScheduleModelTest extends BrowserKitTestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
        self::insertMemberTestData(IGroup::TrackChairs);
    }

    protected function tearDown():void
    {
        self::clearMemberTestData();
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testCreateProposedSchedule()
    {
        $proposed_schedule = new SummitProposedSchedule();
        $proposed_schedule->setName("TEST_PROPOSED_SCHEDULE");
        $proposed_schedule->setSource(SummitProposedSchedule::General);
        $proposed_schedule->setSummit(self::$summit);
        $proposed_schedule->setCreatedBy(self::$member);

        $proposed_scheduled_event = new SummitProposedScheduleSummitEvent();

        $start_date = new \DateTime("now", new \DateTimeZone("UTC"));
        $end_date = (clone $start_date)->add(new \DateInterval("P10D"));

        $proposed_scheduled_event->setStartDate($start_date);
        $proposed_scheduled_event->setEndDate($end_date);
        $proposed_scheduled_event->setCreatedBy(self::$member);
        $proposed_scheduled_event->setUpdatedBy(self::$member);
        $proposed_scheduled_event->setLocation(self::$summit->getLocations()[0]);
        $proposed_scheduled_event->setSummitEvent(self::$summit->getEvents()[0]);

        $proposed_schedule->addScheduledSummitEvent($proposed_scheduled_event);

        self::$em->persist($proposed_schedule);
        self::$em->flush();

        $this->assertTrue($proposed_schedule->getScheduledSummitEvents()->count() > 0);
    }

    public function testRepository()
    {
        $proposed_schedule = new SummitProposedSchedule();
        $proposed_schedule->setName("TEST_PROPOSED_SCHEDULE");
        $proposed_schedule->setSource(SummitProposedSchedule::General);
        $proposed_schedule->setSummit(self::$summit);
        $proposed_schedule->setCreatedBy(self::$member);

        $proposed_scheduled_event = new SummitProposedScheduleSummitEvent();

        $start_date = new \DateTime("now", new \DateTimeZone("UTC"));
        $end_date = (clone $start_date)->add(new \DateInterval("P10D"));

        $proposed_scheduled_event->setStartDate($start_date);
        $proposed_scheduled_event->setEndDate($end_date);
        $proposed_scheduled_event->setCreatedBy(self::$member);
        $proposed_scheduled_event->setUpdatedBy(self::$member);
        $proposed_scheduled_event->setLocation(self::$summit->getLocations()[0]);
        $proposed_scheduled_event->setSummitEvent(self::$summit->getEvents()[0]);

        $proposed_schedule->addScheduledSummitEvent($proposed_scheduled_event);

        self::$em->persist($proposed_schedule);
        self::$em->flush();

        $repository = EntityManager::getRepository(SummitProposedSchedule::class);

        $current_proposed_schedule = $repository->getById($proposed_schedule->getId());

        $this->assertTrue(!is_null($current_proposed_schedule));
    }

    public function testSerializer(int $proposed_schedule = 7){

        $repository = EntityManager::getRepository(SummitProposedSchedule::class);

        $current_proposed_schedule = $repository->getById($proposed_schedule);

        $res = SerializerRegistry::getInstance()->getSerializer($current_proposed_schedule)
            ->serialize(
                'created_by,scheduled_summit_events,scheduled_summit_events.summit_event,scheduled_summit_events.location',
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            );

        $this->assertTrue(count($res) == 8);
    }
}