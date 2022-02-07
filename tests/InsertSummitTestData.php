<?php namespace Tests;
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

use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\TrackTagGroup;
use LaravelDoctrine\ORM\Facades\EntityManager;
use LaravelDoctrine\ORM\Facades\Registry;
use models\main\Member;
use models\main\Tag;
use models\summit\PresentationCategoryGroup;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitBadgeType;
use models\summit\SummitTicketType;
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
use models\summit\Presentation;
use models\main\SummitAdministratorPermissionGroup;
use DateTimeZone;
use DateTime;
use DateInterval;
use Exception;
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
     * @var SelectionPlan
     */
    static $default_selection_plan;

    /**
     * @var SummitAdministratorPermissionGroup
     */
    static $summit_permission_group;

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
     * @var PresentationCategory
     */
    static $secondaryTrack;

    /**
     * @var SummitEventType
     */
    static $defaultEventType;

    /**
     * @var SummitTicketType
     */
    static $default_ticket_type;

    /**
     * @var SummitBadgeType
     */
    static $default_badge_type;

    /**
     * @var EntityManager
     */
    static $em;

    /**
     * @var ObjectRepository
     */
    static $summit_repository;

    /**
     * @var ObjectRepository
     */
    static $summit_permission_group_repository;

    /**
     * @var array|Presentation[]
     */
    static $presentations;

    /**
     * @var PresentationCategoryGroup
     */
    static $defaultTrackGroup;

    /**
     * @var TrackTagGroup;
     */
    static $defaultTrackTagGroup;

    /**
     * @var Tag[]
     */
    static $defaultTags = [];

    /**
     * @var Member
     */
    static $defaultMember;

    /**
     * @throws Exception
     */
    protected static function insertTestData(){
        DB::setDefaultConnection("model");
        //DB::table("Summit")->delete();
        self::$summit_repository = EntityManager::getRepository(Summit::class);
        self::$summit_permission_group_repository = EntityManager::getRepository(SummitAdministratorPermissionGroup::class);

        self::$default_badge_type = new SummitBadgeType();
        self::$default_badge_type->setName("BADGE TYPE1");
        self::$default_badge_type->setIsDefault(true);
        self::$default_badge_type->setDescription("BADGE TYPE1 DESCRIPTION");

        self::$default_ticket_type = new SummitTicketType();
        self::$default_ticket_type->setCost(100);
        self::$default_ticket_type->setCurrency("USD");
        self::$default_ticket_type->setName("TICKET TYPE 1");
        self::$default_ticket_type->setQuantity2Sell(100);
        self::$default_ticket_type->setBadgeType(self::$default_badge_type);

        self::$summit = new Summit();
        self::$summit->setActive(true);
        // set feed type (sched)
        self::$summit->setApiFeedUrl("");
        self::$summit->setApiFeedKey("");
        self::$summit->setTimeZoneId("America/Chicago");
        $time_zone = new DateTimeZone("America/Chicago");
        $begin_date = new DateTime("now", $time_zone);
        $begin_date = $begin_date->add(new DateInterval("P1D"));

        self::$summit->setBeginDate($begin_date);
        self::$summit->setEndDate((clone $begin_date)->add(new DateInterval("P30D")));
        self::$summit->setRegistrationBeginDate($begin_date);
        self::$summit->setRegistrationEndDate((clone $begin_date)->add(new DateInterval("P30D")));
        self::$summit->setName("TEST SUMMIT");
        self::$summit->addBadgeType(self::$default_badge_type);
        self::$summit->addTicketType(self::$default_ticket_type);

        $defaultBadge = new SummitBadgeType();
        $defaultBadge->setName("DEFAULT");
        $defaultBadge->setIsDefault(true);

        $presentation_type = new PresentationType();
        $presentation_type->setType('TEST PRESENTATION TYPE');
        $presentation_type->setMinSpeakers(1);
        $presentation_type->setMaxSpeakers(3);
        $presentation_type->setMinModerators(0);
        $presentation_type->setMaxModerators(0);
        $presentation_type->setUseSpeakers(true);
        $presentation_type->setShouldBeAvailableOnCfp(true);
        $presentation_type->setAreSpeakersMandatory(false);
        $presentation_type->setUseModerator(false);
        $presentation_type->setIsModeratorMandatory(false);

        self::$summit->addEventType($presentation_type);

        if (self::$defaultMember != null) {
            $attendee = new SummitAttendee();
            $attendee->setMember(self::$defaultMember);
            $attendee->setEmail(self::$defaultMember->getEmail());
            $attendee->setFirstName(self::$defaultMember->getFirstName());
            $attendee->setSurname(self::$defaultMember->getLastName());

            $ticket = new SummitAttendeeTicket();
            $ticket->setTicketType(self::$default_ticket_type);
            $ticket->activate();
            $ticket->setPaid(true);
            $attendee->addTicket($ticket);

            self::$summit->addAttendee($attendee);
        }

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
        self::$mainVenue->setName("TEST VENUE");
        self::$mainVenue->setIsMain(true);
        self::$summit->addLocation(self::$mainVenue);

        self::$defaultTrack = new PresentationCategory();
        self::$defaultTrack->setTitle('DEFAULT TRACK');
        self::$defaultTrack->setCode('DFT');
        self::$defaultTrack->setSessionCount(3);
        self::$defaultTrack->setAlternateCount(3);
        self::$defaultTrack->setLightningCount(3);
        self::$defaultTrack->setChairVisible(true);
        self::$defaultTrack->setVotingVisible(true);

        self::$secondaryTrack = new PresentationCategory();
        self::$secondaryTrack->setTitle('SECONDARY TRACK');
        self::$secondaryTrack->setCode('SDFT');
        self::$secondaryTrack->setSessionCount(3);
        self::$secondaryTrack->setAlternateCount(3);
        self::$secondaryTrack->setLightningCount(3);
        self::$secondaryTrack->setChairVisible(true);
        self::$secondaryTrack->setVotingVisible(true);

        self::$defaultTrackGroup = new PresentationCategoryGroup();
        self::$defaultTrackGroup->setName("DEFAULT TRACK GROUP");
        self::$defaultTrackGroup->addCategory(self::$defaultTrack);

        self::$defaultTrackTagGroup = New TrackTagGroup();
        self::$defaultTrackTagGroup->setName("DEFAULT TRACK TAG GROUP");
        self::$defaultTrackTagGroup->setOrder(1);
        self::$defaultTrackTagGroup->setLabel("DEFAULT TRACK TAG GROUP");

        $tags = ['101','Case Study', 'Demo'];

        foreach ($tags as $t){
            $tag = new Tag($t);
            self::$defaultTags[] = $tag;
            self::$defaultTrackTagGroup->addTag($tag, false);
        }

        self::$summit->addTrackTagGroup(self::$defaultTrackTagGroup);
        self::$summit->addPresentationCategory(self::$defaultTrack);
        self::$summit->addPresentationCategory(self::$secondaryTrack);
        self::$summit->addCategoryGroup(self::$defaultTrackGroup);

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

        self::$default_selection_plan = new SelectionPlan();
        self::$default_selection_plan->setName("TEST_SELECTION_PLAN");
        $submission_begin_date = new DateTime('now', self::$summit->getTimeZone());
        $submission_end_date = (clone $submission_begin_date)->add(new DateInterval("P14D"));
        self::$default_selection_plan->setSummit(self::$summit);

        self::$default_selection_plan->setSubmissionBeginDate($submission_begin_date);
        self::$default_selection_plan->setSubmissionEndDate($submission_end_date);
        self::$default_selection_plan->setSelectionBeginDate($submission_begin_date);
        self::$default_selection_plan->setSelectionEndDate($submission_end_date);
        self::$default_selection_plan->setIsEnabled(true);
        self::$default_selection_plan->addTrackGroup(self::$defaultTrackGroup);

        self::$summit->addSelectionPlan(self::$default_selection_plan);

        self::$em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!self::$em ->isOpen()) {
            self::$em  = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }

        self::$presentations = [];

        for($i = 0 ; $i < 20; $i++){
            $presentation = new Presentation();
            $presentation->setTitle(sprintf("Presentation Title %s %s", $i, str_random(16)));
            $presentation->setAbstract(sprintf("Presentation Abstract %s %s", $i, str_random(16)));
            $presentation->setCategory(self::$defaultTrack);
            $presentation->setProgress(Presentation::PHASE_COMPLETE);
            $presentation->setStatus(Presentation::STATUS_RECEIVED);
            $presentation->setType( self::$defaultEventType );
            self::$default_selection_plan->addPresentation($presentation);
            self::$summit->addEvent($presentation);
            self::$presentations[] = $presentation;
        }

        self::$summit_permission_group = new SummitAdministratorPermissionGroup();
        self::$summit_permission_group->setTitle(sprintf("DEFAULT PERMISSION GROUP %s", str_random(16)));
        self::$summit_permission_group->addSummit(self::$summit);

        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit2);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
    }

    protected static function clearTestData(){
        if (!self::$em ->isOpen()) {
            self::$em  = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }
        self::$summit = self::$summit_repository->find(self::$summit->getId());
        self::$summit2 = self::$summit_repository->find(self::$summit2->getId());
        self::$summit_permission_group = self::$summit_permission_group_repository->find(self::$summit_permission_group->getId());
        self::$em->remove(self::$summit);
        self::$em->remove(self::$summit2);
        self::$em->remove(self::$summit_permission_group);
        self::$em->flush();
    }
}