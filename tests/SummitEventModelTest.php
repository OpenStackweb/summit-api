<?php namespace Tests;
/**
 * Copyright 2022 OpenStack Foundation
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
use models\summit\SummitEvent;
use models\summit\Presentation;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Illuminate\Support\Facades\DB;
use DateTimeZone;
use DateTime;
use DateInterval;
use Mockery;
/**
 * Class SummitEventModelTest
 */
class SummitEventModelTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    /**
     * @var SummitEvent
     */
    static $event1;

    /**
     * @var ObjectRepository
     */
    static $event_repository;


    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    public function tearDown():void
    {
        self::clearSummitTestData();
        Mockery::close();
    }

    public function testChangeEventDuration(){
        $presentation = self::$presentations[0];
        $old_end_date = $presentation->getEndDate();
        $presentation->setDuration(864000);
        $new_end_date = $presentation->getEndDate();
        $this->assertTrue($old_end_date < $new_end_date);
    }

    public function testChangingStartDateShouldRecalculateDuration(){
        $presentation = self::$presentations[0];
        $start_date = (clone $presentation->getStartDate())->sub(new DateInterval("P1D"));

        $old_duration = $presentation->getDuration();
        $presentation->setStartDate($start_date);
        $new_duration = $presentation->getDuration();
        $this->assertTrue($old_duration < $new_duration);
    }

    public function testChangingEndDateShouldRecalculateDuration(){
        $presentation = self::$presentations[0];
        $end_date = (clone $presentation->getEndDate())->add(new DateInterval("PT1H"));

        $old_duration = $presentation->getDuration();
        $presentation->setEndDate($end_date);
        $new_duration = $presentation->getDuration();
        $this->assertTrue($old_duration < $new_duration);
    }
}