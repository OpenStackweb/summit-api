<?php
/**
 * Copyright 2020 OpenStack Foundation
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
use LaravelDoctrine\ORM\Facades\EntityManager;
use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\PresentationCategoryGroup;
use models\utils\SilverstripeBaseModel;
use models\summit\SummitVenue;
use models\summit\Summit;
use Doctrine\Common\Persistence\ObjectRepository;
use models\summit\PresentationCategory;
use models\summit\SummitEventType;
use models\summit\PresentationType;
use models\summit\IPresentationType;
use App\Models\Foundation\Summit\SelectionPlan;
use Illuminate\Support\Facades\DB;
/**
 * Trait InsertSummitTestData
 * @package Tests
 */
trait InsertSummitTestData
{

    /**
     * @var Summit
     */
    static $summit;

    /**
     * @var Summit
     */
    static $summit2;

    /**
     * @var SummitVenue
     */
    static $mainVenue;

    /**
     * @var PresentationCategory
     */
    static $defaultTrack;

    /**
     * @var SummitEventType
     */
    static $defaultEventType;

    /**
     * @var EntityManager
     */
    static $em;

    /**
     * @var ObjectRepository
     */
    static $summit_repository;

    protected static function insertTestData(){
        DB::setDefaultConnection("model");
        DB::table("Summit")->delete();
        self::$summit_repository = EntityManager::getRepository(Summit::class);
        self::$summit = new Summit();
        self::$summit->setActive(true);
        // set feed type (sched)
        self::$summit->setApiFeedUrl("");
        self::$summit->setApiFeedKey("");
        self::$summit->setTimeZoneId("America/Chicago");
        $time_zone = new DateTimeZone("America/Chicago");
        $begin_date = new \DateTime("now", $time_zone);
        self::$summit->setBeginDate($begin_date);
        self::$summit->setEndDate((clone $begin_date)->add(new DateInterval("P30D")));
        self::$summit->setRegistrationBeginDate($begin_date);
        self::$summit->setRegistrationEndDate((clone $begin_date)->add(new DateInterval("P30D")));
        self::$summit->setName("TEST SUMMIT");

        $presentation_type = new PresentationType();
        $presentation_type->setType('TEST PRESENTATION TYPE');
        self::$summit->addEventType($presentation_type);

        self::$summit2 = new Summit();
        self::$summit2->setActive(true);
        // set feed type (sched)
        self::$summit2->setApiFeedUrl("");
        self::$summit2->setApiFeedKey("");
        self::$summit2->setTimeZoneId("America/Chicago");
        $time_zone = new DateTimeZone("America/Chicago");
        $begin_date = new \DateTime("now", $time_zone);
        self::$summit2->setBeginDate($begin_date);
        self::$summit2->setEndDate((clone $begin_date)->add(new DateInterval("P30D")));
        self::$summit2->setRegistrationBeginDate($begin_date);
        self::$summit2->setRegistrationEndDate((clone $begin_date)->add(new DateInterval("P30D")));
        self::$summit2->setName("TEST SUMMIT2");

        self::$mainVenue = new SummitVenue();
        self::$mainVenue->setIsMain(true);
        self::$summit->addLocation(self::$mainVenue);

        self::$defaultTrack = new PresentationCategory();
        self::$defaultTrack->setTitle('DEFAULT TRACK');
        self::$defaultTrack->setCode('DFT');
        self::$defaultTrack->setLightningCount(true);

        $track_group = new PresentationCategoryGroup();
        $track_group->setName("DEFAULT TRACK GROUP");
        $track_group->addCategory(self::$defaultTrack);
        self::$summit->addPresentationCategory(self::$defaultTrack);
        self::$summit->addCategoryGroup($track_group);

        self::$defaultEventType = new PresentationType();
        self::$defaultEventType->setType(IPresentationType::Presentation);
        self::$defaultEventType->setMinSpeakers(1);
        self::$defaultEventType->setMaxSpeakers(3);
        self::$defaultEventType->setMinModerators(0);
        self::$defaultEventType->setMaxModerators(0);
        self::$defaultEventType->setUseSpeakers(true);
        self::$defaultEventType->setShouldBeAvailableOnCfp(true);
        self::$defaultEventType->setAreSpeakersMandatory(false);
        self::$defaultEventType->setUseModerator(false);
        self::$defaultEventType->setIsModeratorMandatory(false);
        self::$summit->addEventType(self::$defaultEventType);

        $selection_plan = new SelectionPlan();
        $selection_plan->setName("TEST_SELECTION_PLAN");
        $submission_begin_date = new DateTime('now', self::$summit->getTimeZone());
        $submission_end_date = (clone $submission_begin_date)->add(new DateInterval("P14D"));
        $selection_plan->setSummit(self::$summit);
        $selection_plan->setSubmissionBeginDate($submission_begin_date);
        $selection_plan->setSubmissionEndDate($submission_end_date);
        $selection_plan->setIsEnabled(true);
        $selection_plan->addTrackGroup($track_group);

        self::$summit->addSelectionPlan($selection_plan);

        self::$em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!self::$em ->isOpen()) {
            self::$em  = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit2);
        self::$em->flush();
    }

    protected static function clearTestData(){
        self::$em  = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        self::$summit = self::$summit_repository->find(self::$summit->getId());
        self::$summit2 = self::$summit_repository->find(self::$summit2->getId());
        self::$em->remove(self::$summit);
        self::$em->remove(self::$summit2);
        self::$em->flush();
    }
}