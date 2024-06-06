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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeValue;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType;
use App\Models\Foundation\Summit\SelectionPlan;
use App\Models\Foundation\Summit\TrackTagGroup;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\EntityManager;
use LaravelDoctrine\ORM\Facades\Registry;
use models\main\Company;
use models\main\Member;
use models\main\SummitAdministratorPermissionGroup;
use models\main\Tag;
use models\summit\ISponsorshipTypeConstants;
use models\summit\ISummitEventType;
use models\summit\PrePaidSummitRegistrationDiscountCode;
use models\summit\PrePaidSummitRegistrationPromoCode;
use models\summit\Presentation;
use models\summit\PresentationActionType;
use models\summit\PresentationCategory;
use models\summit\PresentationCategoryGroup;
use models\summit\PresentationMediaUpload;
use models\summit\PresentationSpeaker;
use models\summit\PresentationType;
use models\summit\Sponsor;
use models\summit\SponsorAd;
use models\summit\SponsorMaterial;
use models\summit\SponsorshipType;
use models\summit\SponsorSocialNetwork;
use models\summit\SponsorSummitRegistrationDiscountCode;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeBadge;
use models\summit\SummitAttendeeNote;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitBadgeType;
use models\summit\SummitBadgeViewType;
use models\summit\SummitEvent;
use models\summit\SummitEventType;
use models\summit\SummitMediaFileType;
use models\summit\SummitMediaUploadType;
use models\summit\SummitOrder;
use models\summit\SummitRegistrationDiscountCode;
use models\summit\SummitRegistrationPromoCode;
use models\summit\SummitSponsorshipType;
use models\summit\SummitTicketType;
use models\summit\SummitVenue;
use models\summit\SummitVenueFloor;
use models\summit\SummitVenueRoom;
use models\utils\SilverstripeBaseModel;
use models\summit\SummitAccessLevelType;
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
     * @var SelectionPlan
     */
    static $default_selection_plan2;

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
     * @var PresentationType
     */
    static $defaultPresentationType;

    /**
     * @var PresentationType
     */
    static $allow2VotePresentationType;

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
     * @var SummitTicketType
     */
    static $default_ticket_type_2;

    /**
     * @var SummitTicketType
     */
    static $default_ticket_type_3;

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
     * @var ObjectRepository
     */
    static $company_repository;

    /**
     * @var array|Presentation[]
     */
    static $presentations;

    /**
     * @var array|SummitVenueRoom[]
     */
    static $venue_rooms;

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
     * @var Member
     */
    static $defaultMember2;

    /**
     * @var array | SummitAccessLevelType[]
     */
    static $access_levels;

    /**
     * @var PresentationActionType
     */
    static $default_presentation_action_type;

    /**
     * @var array | Company[]
     */
    static $companies;

    /**
     * @var array | Company[]
     */
    static $companies_without_sponsor;

    /**
     * @var array | Sponsor[]
     */
    static $sponsors;

    /**
     * @var SponsorshipType
     */
    static $default_sponsor_ship_type;

    /**
     * @var SponsorshipType
     */
    static $default_sponsor_ship_type2;

    /**
     * @var SummitSponsorshipType
     */
    static $default_summit_sponsor_type;

    /**
     * @var array | SummitTicketType
     */
    static $ticket_types = [];

    /**
     * @var array | SummitMediaUploadType[]
     */
    static $media_uploads_types = [];

    static $default_media_file_type;

    static $media_file_type_repository;

    static $default_badge_view_type;

    static $default_prepaid_promo_code;

    static $default_prepaid_discount_code;

    static $default_discount_code;

    /**
     * @var array | SummitRegistrationPromoCode[]
     */
    static $default_sponsors_promo_codes = [];

    /**
     * @throws Exception
     */
    protected static function insertSummitTestData(){

        DB::setDefaultConnection("model");

        DB::delete("DELETE FROM SummitBadgeViewType");
        DB::delete("DELETE FROM SummitMediaFileType");
        DB::delete('DELETE FROM SponsorSummitRegistrationDiscountCode');
        DB::delete("DELETE FROM SummitRegistrationPromoCode");
        DB::delete("DELETE FROM Sponsor");
        DB::delete("DELETE FROM Company");
        DB::delete("DELETE FROM Summit");

        self::$em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!self::$em ->isOpen()) {
            self::$em  = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }

        self::$summit_repository = EntityManager::getRepository(Summit::class);
        self::$company_repository = EntityManager::getRepository(Company::class);
        self::$summit_permission_group_repository = EntityManager::getRepository(SummitAdministratorPermissionGroup::class);

        self::$summit = new Summit();
        self::$summit->setActive(true);
        self::$summit->setAvailableOnApi(true);
        self::$summit->setRawSlug("TEST1");
        self::$summit->setExternalSummitId("123456");
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
        self::$summit->setRawSlug("testsummit");

        self::$default_badge_type = new SummitBadgeType();
        self::$default_badge_type->setName("BADGE TYPE1");
        self::$default_badge_type->setIsDefault(true);
        self::$default_badge_type->setDescription("BADGE TYPE1 DESCRIPTION");
        self::$summit->addBadgeType(self::$default_badge_type);

        for($i = 0 ; $i < 5; $i++){
            $access_level = new SummitAccessLevelType();
            $access_level->setName(sprintf("Access Level %s", $i));
            self::$default_badge_type->addAccessLevel($access_level);
            self::$summit->addBadgeAccessLevelType($access_level);
        }

        self::$em->persist(self::$summit);
        self::$em->flush();

        self::$ticket_types = [];
        self::$default_ticket_type = new SummitTicketType();
        self::$default_ticket_type->setCost(100);
        self::$default_ticket_type->setCurrency("USD");
        self::$default_ticket_type->setExternalId("123456");
        self::$default_ticket_type->setName("TICKET TYPE 1");
        self::$default_ticket_type->setQuantity2Sell(100);
        self::$default_ticket_type->setBadgeType(self::$default_badge_type);
        self::$default_ticket_type->setAudience(SummitTicketType::Audience_All);
        self::$default_ticket_type->setAllowsToDelegate(true);
        self::$ticket_types[] = self::$default_ticket_type;

        self::$default_ticket_type_2 = new SummitTicketType();
        self::$default_ticket_type_2->setCost(100);
        self::$default_ticket_type_2->setCurrency("USD");
        self::$default_ticket_type_2->setName("TICKET TYPE 1");
        self::$default_ticket_type_2->setQuantity2Sell(100);
        self::$default_ticket_type_2->setBadgeType(self::$default_badge_type);
        self::$default_ticket_type_2->setAudience(SummitTicketType::Audience_Without_Invitation);
        self::$ticket_types[] = self::$default_ticket_type_2;

        self::$default_ticket_type_3 = new SummitTicketType();
        self::$default_ticket_type_3->setCost(100);
        self::$default_ticket_type_3->setCurrency("USD");
        self::$default_ticket_type_3->setName("TICKET TYPE 1");
        self::$default_ticket_type_3->setQuantity2Sell(100);
        self::$default_ticket_type_3->setBadgeType(self::$default_badge_type);
        self::$default_ticket_type_3->setAudience(SummitTicketType::Audience_With_Invitation);
        self::$ticket_types[] = self::$default_ticket_type_3;

        $ticket_type_names = [
            'Invited Attendee', 'Chaperone', 'Press', 'Speaker', 'Roblox Core Staff', 'Roblox Volunteer Staff',
            'General Attendee', 'Roblox Staff'
        ];

        foreach ($ticket_type_names as $ticket_type_name) {
            $type = new SummitTicketType();
            $type->setName($ticket_type_name);
            $type->setCost(100);
            $type->setCurrency("USD");
            $type->setQuantity2Sell(100);
            $type->setBadgeType(self::$default_badge_type);
            $type->setAudience(SummitTicketType::Audience_All);
            self::$ticket_types[] = $type;
        }

        foreach(self::$ticket_types as $ticket_type) {
            self::$summit->addTicketType($ticket_type);
        }

        self::$em->persist(self::$summit);
        self::$em->flush();

        self::$defaultPresentationType = new PresentationType();
        self::$defaultPresentationType->setType('TEST PRESENTATION TYPE');
        self::$defaultPresentationType->setAsDefault();
        self::$defaultPresentationType->setMinSpeakers(1);
        self::$defaultPresentationType->setMaxSpeakers(3);
        self::$defaultPresentationType->setMinModerators(0);
        self::$defaultPresentationType->setMaxModerators(0);
        self::$defaultPresentationType->setUseSpeakers(true);
        self::$defaultPresentationType->setShouldBeAvailableOnCfp(true);
        self::$defaultPresentationType->setAreSpeakersMandatory(false);
        self::$defaultPresentationType->setUseModerator(false);
        self::$defaultPresentationType->setIsModeratorMandatory(false);
        self::$defaultPresentationType->setAllowsLocationTimeframeCollision(true);
        self::$defaultPresentationType->setAllowsSpeakerEventCollision(true);
        self::$defaultPresentationType->setBlackoutTimes('Final');

        self::$summit->addEventType(self::$defaultPresentationType);

        self::$media_file_type_repository = EntityManager::getRepository(SummitMediaFileType::class);
        self::$default_media_file_type = new SummitMediaFileType();
        self::$default_media_file_type->setName("PDF_".rand(1,100));
        self::$default_media_file_type->setDescription("PDF");
        self::$default_media_file_type->setAllowedExtensions(".PDF");

        self::$em->persist(self::$default_media_file_type);
        self::$em->flush();
        self::$media_uploads_types = [];
        // media upload types
        for($i = 0; $i <5 ; $i++) {
            $media_upload_type = new SummitMediaUploadType();
            $media_upload_type->setType(self::$default_media_file_type);
            $media_upload_type->setName(sprintf("Media Upload Type %s", $i));
            $media_upload_type->setDescription(sprintf("Media Upload Type %s Description", $i));
            $media_upload_type->addPresentationType(self::$defaultPresentationType);
            self::$summit->addMediaUploadType($media_upload_type);
            self::$em->persist($media_upload_type);
            self::$media_uploads_types[] = $media_upload_type;
        }

        self::$allow2VotePresentationType = new PresentationType();
        self::$allow2VotePresentationType->setType('TEST PRESENTATION TYPE VOTABLE');
        self::$allow2VotePresentationType->setMinSpeakers(1);
        self::$allow2VotePresentationType->setMaxSpeakers(3);
        self::$allow2VotePresentationType->setMinModerators(0);
        self::$allow2VotePresentationType->setMaxModerators(0);
        self::$allow2VotePresentationType->setUseSpeakers(true);
        self::$allow2VotePresentationType->setShouldBeAvailableOnCfp(true);
        self::$allow2VotePresentationType->setAreSpeakersMandatory(false);
        self::$allow2VotePresentationType->setUseModerator(false);
        self::$allow2VotePresentationType->setIsModeratorMandatory(false);
        self::$allow2VotePresentationType->setAllowAttendeeVote(true);
        self::$allow2VotePresentationType->setAllowCustomOrdering(true);
        self::$allow2VotePresentationType->setAllowsLocation(false);
        self::$allow2VotePresentationType->setAllowsPublishingDates(false);
        self::$allow2VotePresentationType->setBlackoutTimes('Final');

        self::$defaultEventType = new SummitEventType();
        self::$defaultEventType->setType(ISummitEventType::Breaks);
        self::$defaultEventType->setBlackoutTimes('All');
        self::$summit->addEventType(self::$defaultEventType);

        self::$summit->addEventType(self::$allow2VotePresentationType);

        // badge view types

        self::$default_badge_view_type = new SummitBadgeViewType();
        self::$default_badge_view_type ->setName("VIEW 1");
        self::$default_badge_view_type ->setDescription("VIEW 1");
        self::$default_badge_view_type ->setDefault(true);

        self::$summit->addBadgeViewType(self::$default_badge_view_type );

        if (self::$defaultMember != null) {
            $attendee = new SummitAttendee();
            $attendee->setMember(self::$defaultMember);
            $attendee->setEmail(self::$defaultMember->getEmail());
            $attendee->setFirstName(self::$defaultMember->getFirstName());
            $attendee->setSurname(self::$defaultMember->getLastName());

            $summitAttendeeBadge = new SummitAttendeeBadge();
            $summitAttendeeBadge->setType(self::$default_badge_type);

            for($i = 0; $i < 5; $i++) {
                $order = new SummitOrder();
                $order->setOwner(self::$defaultMember);
                $ticket = new SummitAttendeeTicket();
                $ticket->setTicketType(self::$default_ticket_type);
                $ticket->setBadge($summitAttendeeBadge);

                $ticket->activate();
                $attendee->addTicket($ticket);
                $order->addTicket($ticket);

                $attendeeNote = new SummitAttendeeNote('Test attendee note', $attendee);
                $attendee->addNote($attendeeNote);

                self::$summit->addAttendee($attendee);
                self::$summit->addOrder($order);
                $order->setPaid();
                $order->generateNumber();

                $ticket->generateNumber();
                $ticket->generateQRCode();
                $summitAttendeeBadge->generateQRCode();
            }
        }

        if (self::$defaultMember2 != null) {
            $attendee = new SummitAttendee();
            $attendee->setMember(self::$defaultMember2);
            $attendee->setEmail(self::$defaultMember2->getEmail());
            $attendee->setFirstName(self::$defaultMember2->getFirstName());
            $attendee->setSurname(self::$defaultMember2->getLastName());

            $ticket = new SummitAttendeeTicket();
            $ticket->setTicketType(self::$default_ticket_type);
            $ticket->activate();
            $ticket->setPaid(true);
            $attendee->addTicket($ticket);

            self::$summit->addAttendee($attendee);
        }

        self::$summit2 = new Summit();
        self::$summit2->setActive(true);
        self::$summit2->setAvailableOnApi(true);
        self::$summit2->setRawSlug("TEST2");
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

        $floor = new SummitVenueFloor();
        $floor->setNumber(1);
        $floor->setName("F1");
        $floor->setDescription("Floor number 1");

        self::$mainVenue->addFloor($floor);

        self::$em = Registry::getManager(SilverstripeBaseModel::EntityManager);

        self::$venue_rooms = [];
        for($i = 0 ; $i < 20; $i++){
            $room = new SummitVenueRoom();
            $room->setName(sprintf("Room %s", $i));
            $room->setCapacity(10);
            self::$venue_rooms[] = $room;

            self::$summit->addLocation($room);
            self::$mainVenue->addRoom($room);
            $floor->addRoom($room);

            self::$em->persist($room);
        }

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
        self::$defaultTags = [];
        foreach ($tags as $t){
            $tag = new Tag($t);
            self::$defaultTags[] = $tag;
            self::$defaultTrackTagGroup->addTag($tag, false);
        }

        self::$summit->addTrackTagGroup(self::$defaultTrackTagGroup);
        self::$summit->addPresentationCategory(self::$defaultTrack);
        self::$summit->addPresentationCategory(self::$secondaryTrack);
        self::$summit->addCategoryGroup(self::$defaultTrackGroup);

        self::$default_selection_plan = new SelectionPlan();
        self::$default_selection_plan->setName("TEST_SELECTION_PLAN");
        self::$default_selection_plan->setPresentationCreatorNotificationEmailTemplate("PRESENTATION_CREATOR_EMAIL_TEMPLATE");
        self::$default_selection_plan->setPresentationModeratorNotificationEmailTemplate("PRESENTATION_MODERATOR_EMAIL_TEMPLATE");
        self::$default_selection_plan->setPresentationSpeakerNotificationEmailTemplate("PRESENTATION_SPEAKER_EMAIL_TEMPLATE");
        $submission_begin_date = new DateTime('now', self::$summit->getTimeZone());
        $submission_end_date = (clone $submission_begin_date)->add(new DateInterval("P14D"));
        self::$default_selection_plan->setSummit(self::$summit);

        self::$default_selection_plan->setSubmissionBeginDate($submission_begin_date);
        self::$default_selection_plan->setSubmissionEndDate($submission_end_date);
        self::$default_selection_plan->setSelectionBeginDate($submission_begin_date);
        self::$default_selection_plan->setSelectionEndDate($submission_end_date);
        self::$default_selection_plan->setIsEnabled(true);
        self::$default_selection_plan->addTrackGroup(self::$defaultTrackGroup);

        // create extra questions

        $q = new SummitSelectionPlanExtraQuestionType();
        $q->setType(ExtraQuestionTypeConstants::CheckBoxListQuestionType);
        $q->setLabel("TEST");
        $q->setName("TEST");
        self::$summit->addSelectionPlanExtraQuestion($q);
        $v = new ExtraQuestionTypeValue();
        $v->setLabel("TEST");
        $v->setValue("TEST");
        $q->addValue($v);

        self::$default_selection_plan->addExtraQuestion($q);

        // sel plan 2

        self::$default_selection_plan2 = new SelectionPlan();
        self::$default_selection_plan2->setName("TEST_SELECTION_PLAN2");
        self::$default_selection_plan2->setPresentationCreatorNotificationEmailTemplate("PRESENTATION_CREATOR_EMAIL_TEMPLATE");
        self::$default_selection_plan2->setPresentationModeratorNotificationEmailTemplate("PRESENTATION_MODERATOR_EMAIL_TEMPLATE");
        self::$default_selection_plan2->setPresentationSpeakerNotificationEmailTemplate("PRESENTATION_SPEAKER_EMAIL_TEMPLATE");
        $submission_begin_date = new DateTime('now', self::$summit->getTimeZone());
        $submission_end_date = (clone $submission_begin_date)->add(new DateInterval("P14D"));
        self::$default_selection_plan2->setSummit(self::$summit);

        self::$default_selection_plan2->setSubmissionBeginDate($submission_begin_date);
        self::$default_selection_plan2->setSubmissionEndDate($submission_end_date);
        self::$default_selection_plan2->setSelectionBeginDate($submission_begin_date);
        self::$default_selection_plan2->setSelectionEndDate($submission_end_date);
        self::$default_selection_plan2->setIsEnabled(true);
        self::$default_selection_plan2->addTrackGroup(self::$defaultTrackGroup);

        $track_chair_score_type = new PresentationTrackChairScoreType();
        $track_chair_score_type->setScore(1);
        $track_chair_score_type->setName("TEST_SCORE_TYPE");
        $track_chair_score_type->setDescription("SCORE TYPE TEST");

        $track_chair_score_type2 = new PresentationTrackChairScoreType();
        $track_chair_score_type2->setScore(2);
        $track_chair_score_type2->setName("TEST_SCORE_TYPE2");
        $track_chair_score_type2->setDescription("SCORE TYPE TEST2");

        $track_chair_score_type3 = new PresentationTrackChairScoreType();
        $track_chair_score_type3->setScore(3);
        $track_chair_score_type3->setName("TEST_SCORE_TYPE3");
        $track_chair_score_type3->setDescription("SCORE TYPE TEST3");

        $track_chair_rating_type = new PresentationTrackChairRatingType();
        $track_chair_rating_type->setWeight(1.5);
        $track_chair_rating_type->setName("TEST_RATING_TYPE");
        $track_chair_rating_type->setOrder(1);

        $track_chair_rating_type->addScoreType($track_chair_score_type);
        $track_chair_rating_type->addScoreType($track_chair_score_type3);
        $track_chair_rating_type->addScoreType($track_chair_score_type2);

        $track_chair_rating_type->setSelectionPlan(self::$default_selection_plan);

        self::$default_selection_plan->addTrackChairRatingType($track_chair_rating_type);

        self::$default_presentation_action_type = new PresentationActionType();
        self::$default_presentation_action_type->setLabel("TEST_PRESENTATION_ACTION_TYPE_1");
        self::$default_presentation_action_type->setSummit(self::$summit);
        self::$summit->addPresentationActionType(self::$default_presentation_action_type);
        self::$default_selection_plan->addPresentationActionType(self::$default_presentation_action_type);

        $presentation_action_type = new PresentationActionType();
        $presentation_action_type->setLabel("TEST_PRESENTATION_ACTION_TYPE_2");
        $presentation_action_type->setSummit(self::$summit);
        self::$summit->addPresentationActionType($presentation_action_type);
        self::$default_selection_plan->addPresentationActionType($presentation_action_type);

        self::$summit->addSelectionPlan(self::$default_selection_plan);
        self::$summit->addSelectionPlan(self::$default_selection_plan2);

        if (!self::$em ->isOpen()) {
            self::$em  = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }

        self::$presentations = [];

        $start_date = clone($begin_date);
        $end_date  = clone($start_date);
        $end_date = $end_date->add(new DateInterval("P1D"));
        $speaker1 = new PresentationSpeaker();
        if (self::$defaultMember != null) {
            $speaker1->setMember(self::$defaultMember);
        }

        $speaker1->setFirstName("Sebastian");
        $speaker1->setLastName("Marcet");
        $speaker1->setBio("This is the Bio");
        self::$em->persist($speaker1);

        for($i = 0 ; $i < 20; $i++){
            $presentation = new Presentation();
            self::$summit->addEvent($presentation);
            $presentation->setTitle(sprintf("Presentation Title %s %s", $i, str_random(16)));
            $presentation->setAbstract(sprintf("Presentation Abstract %s %s", $i, str_random(16)));
            $presentation->setCategory(self::$defaultTrack);
            $presentation->setProgress(Presentation::PHASE_COMPLETE);
            $presentation->setStatus(Presentation::STATUS_RECEIVED);
            $presentation->setType( self::$defaultPresentationType );
            $presentation->setStartDate($start_date);
            $presentation->setEndDate($end_date);
            $presentation->addSpeaker($speaker1);
            $media_upload = new PresentationMediaUpload();
            $media_upload_type_idx = array_rand(self::$media_uploads_types);
            $media_upload->setName(sprintf("Media Upload %s", $i));
            $media_upload->setDescription(sprintf("Media Upload Description %s", $i));
            $media_upload->setFilename(sprintf("Media Upload Filename %s", $i));
            $media_upload->setMediaUploadType(self::$media_uploads_types[$media_upload_type_idx]);
            $presentation->addMediaUpload($media_upload);
            self::$default_selection_plan->addPresentation($presentation);
            self::$presentations[] = $presentation;
            $presentation->publish();
            $start_date = clone($start_date);
            $start_date = $start_date->add(new DateInterval("P1D"));
            $end_date = clone($start_date);
            $end_date = $end_date->add(new DateInterval("P1D"));

        }

        for($i = 20 ; $i < 40; $i++){
            $presentation = new Presentation();
            self::$summit->addEvent($presentation);
            $presentation->setTitle(sprintf("Presentation Title %s %s", $i, str_random(16)));
            $presentation->setAbstract(sprintf("Presentation Abstract %s %s", $i, str_random(16)));
            $presentation->setCategory(self::$defaultTrack);
            $presentation->setProgress(Presentation::PHASE_COMPLETE);
            $presentation->setStatus(Presentation::STATUS_RECEIVED);
            $presentation->setType( self::$defaultPresentationType );
            $presentation->setStartDate($start_date);
            $presentation->setEndDate($end_date);
            $presentation->addSpeaker($speaker1);
            $presentation->setLocation(self::$mainVenue);
            self::$default_selection_plan2->addPresentation($presentation);
            self::$presentations[] = $presentation;
            $presentation->publish();
            $start_date = clone($start_date);
            $start_date = $start_date->add(new DateInterval("P1D"));
            $end_date = clone($start_date);
            $end_date = $end_date->add(new DateInterval("P1D"));
        }

        for($i = 0 ; $i < 20; $i++){
            $presentation = new Presentation();
            $presentation->setTitle(sprintf("Presentation Title %s %s Votable", $i, str_random(16)));
            $presentation->setAbstract(sprintf("Presentation Abstract %s %s Votable", $i, str_random(16)));
            $presentation->setCategory(self::$defaultTrack);
            $presentation->setProgress(Presentation::PHASE_COMPLETE);
            $presentation->setStatus(Presentation::STATUS_RECEIVED);
            $presentation->setType( self::$allow2VotePresentationType );
            self::$summit->addEvent($presentation);
            self::$presentations[] = $presentation;
            $presentation->publish();
        }

        for($i = 0 ; $i < 20; $i++){
            $event = new SummitEvent();
            $event->setTitle(sprintf("Raw Event Title %s %s", $i, str_random(16)));
            $event->setAbstract(sprintf("Raw Event Abstract %s %s", $i, str_random(16)));
            $event->setCategory(self::$defaultTrack);
            $event->setType( self::$defaultEventType );
            $event->setSubmissionSource( $i < 10 ? SummitEvent::SOURCE_ADMIN : SummitEvent::SOURCE_SUBMISSION);
            $event->setOverflowStreamingUrl(sprintf("https://testoverflowurl_%s.org", $i));
            $event->setOverflowStreamIsSecure(true);
            self::$summit->addEvent($event);
            self::$presentations[] = $event;
        }

        self::$summit_permission_group = new SummitAdministratorPermissionGroup();
        self::$summit_permission_group->setTitle(sprintf("DEFAULT PERMISSION GROUP %s", str_random(16)));
        self::$summit_permission_group->addSummit(self::$summit);

        // insert companies
        self::$default_sponsor_ship_type = new SponsorshipType();
        self::$default_sponsor_ship_type->setName("Default");
        self::$default_sponsor_ship_type->setSize(ISponsorshipTypeConstants::BigSize);
        self::$em->persist(self::$default_sponsor_ship_type);

        self::$default_sponsor_ship_type2 = new SponsorshipType();
        self::$default_sponsor_ship_type2->setName("Default2");
        self::$default_sponsor_ship_type2->setSize(ISponsorshipTypeConstants::SmallSize);
        self::$em->persist(self::$default_sponsor_ship_type2);

        self::$default_summit_sponsor_type = new SummitSponsorshipType();
        self::$default_summit_sponsor_type->setType(self::$default_sponsor_ship_type);
        self::$summit->addSponsorshipType(self::$default_summit_sponsor_type);
        self::$companies = [];
        self::$sponsors = [];
        for($i = 0 ; $i < 20; $i++){
            $c = new Company();
            $c->setName(sprintf("Company %s %s", $i, str_random(16)));
            $c->setIndustry(sprintf("Industry %s %s", $i, str_random(16)));

            self::$em->persist($c);
            self::$companies[] = $c;

            $s = new Sponsor();
            $s->setCompany($c);
            $s->setIntro(sprintf("this is an intro %s %s", $i, str_random(16)));
            $s->setMarquee(sprintf("this is a marquee %s %s", $i, str_random(16)));
            $s->setVideoLink(sprintf("https://%s.%s.video.com", $i, str_random(16)));
            $s->setChatLink(sprintf("https://%s.%s.chat.com", $i, str_random(16)));
            $s->setExternalLink(sprintf("https://%s.%s.exterma;.com", $i, str_random(16)));
            $s->setSponsorship(self::$default_summit_sponsor_type);

            for($j = 0; $j < 10; $j ++){

                $m = new SponsorMaterial();
                $m->setName(sprintf("Material %s %s %s", $i, $j, str_random(16)));
                $m->setOrder($j);
                $m->setLink(sprintf("https://%s.%s.%s.com", $i, $j, str_random(10)));
                $m->setType(SponsorMaterial::ValidTypes[ array_rand(SponsorMaterial::ValidTypes)]);
                $s->addMaterial($m);

                $sn = new SponsorSocialNetwork();
                $sn->setLink(sprintf("https://%s.%s.%s.com", $i, $j, str_random(10)));
                $sn->setIsEnabled(true);
                $sn->setIconCssClass("icon");
                $s->addSocialNetwork($sn);

                $ad = new SponsorAd();
                $ad->setText(sprintf("Text %s %s %s", $i, $j, str_random(16)));
                $ad->setAlt(sprintf("Alt Text %s %s %s", $i, $j, str_random(16)));
                $ad->setLink(sprintf("https://%s.%s.%s.com", $i, $j, str_random(10)));
                $ad->setOrder($j);

                $s->addAd($ad);
            }

            self::$em->persist($s);
            self::$summit->addSummitSponsor($s);
            self::$sponsors[] = $s;
        }

        //Promo codes

        $prepaid_promo_code = new PrePaidSummitRegistrationPromoCode();
        $prepaid_promo_code->setCode('TEST_PPPC_' . rand());
        $prepaid_promo_code->setDescription('TEST PREPAID PROMO CODE');
        $prepaid_promo_code->setValidSinceDate(null);
        $prepaid_promo_code->setValidUntilDate(null);

        self::$summit->addPromoCode($prepaid_promo_code);
        self::$default_prepaid_promo_code = $prepaid_promo_code;

        $prepaid_discount_code = new PrePaidSummitRegistrationDiscountCode();
        $prepaid_discount_code->setCode('TEST_PPDC_' . rand());
        $prepaid_discount_code->setDescription('TEST PREPAID DISCOUNT CODE');
        $prepaid_discount_code->setValidSinceDate(null);
        $prepaid_discount_code->setValidUntilDate(null);
        $prepaid_discount_code->setAmount(12);
        $prepaid_discount_code->setQuantityAvailable(10);

        self::$summit->addPromoCode($prepaid_discount_code);
        self::$default_prepaid_discount_code = $prepaid_discount_code;

        $discount_code = new SummitRegistrationDiscountCode();
        $discount_code->setCode('TEST_DC_' . rand());
        $discount_code->setDescription('TEST DISCOUNT CODE');
        $discount_code->setValidSinceDate(null);
        $discount_code->setValidUntilDate(null);
        $discount_code->setAmount(15);
        $discount_code->setQuantityAvailable(10);
        $discount_code->addTag(new Tag('TEST TAG'));

        self::$summit->addPromoCode($discount_code);
        self::$default_discount_code = $discount_code;
        self::$companies_without_sponsor = [];
        for($i = 0 ; $i < 20; $i++){
            $c = new Company();
            $c->setName(sprintf("Company %s %s", $i, str_random(16)));
            $c->setIndustry(sprintf("Industry %s %s", $i, str_random(16)));

            self::$em->persist($c);
            self::$companies_without_sponsor[] = $c;
        }

        // sponsor promo codes
        self::$default_sponsors_promo_codes = [];
        for($i = 0 ; $i < 20; $i++){
            $promo_code = new SponsorSummitRegistrationDiscountCode();
            $promo_code->setCode(sprintf("TEST_SPONSOR_PROMO_CODE_%s", $i));
            $promo_code->setDescription(sprintf("TEST SPONSOR PROMO CODE %s", $i));
            $promo_code->setValidSinceDate(null);
            $promo_code->setValidUntilDate(null);
            $promo_code->setRate(50.00);
            $promo_code->addTag(new Tag('TEST TAG'));
            $promo_code->setContactEmail("test@test.com");
            $promo_code->setSponsor(self::$sponsors[array_rand(self::$sponsors)]);
            self::$summit->addPromoCode($promo_code);
            self::$default_sponsors_promo_codes[] = $promo_code;
        }

        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit2);
        self::$em->persist(self::$summit_permission_group);

        self::$em->flush();
    }

    protected static function clearSummitTestData(){
        if (!self::$em ->isOpen()) {
            self::$em  = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }
        self::$summit = self::$summit_repository->find(self::$summit->getId());
        self::$summit2 = self::$summit_repository->find(self::$summit2->getId());
        self::$default_media_file_type = self::$media_file_type_repository->find(self::$default_media_file_type->getId());
        self::$summit_permission_group = self::$summit_permission_group_repository->find(self::$summit_permission_group->getId());
        self::$summit->clearOrders();
        self::$summit2->clearOrders();
        self::$summit->clearMetrics();
        self::$summit2->clearMetrics();
        self::$em->remove(self::$summit);
        self::$em->remove(self::$summit2);
        self::$em->remove(self::$summit_permission_group);
        self::$em->remove(self::$default_media_file_type);
        self::$em->flush();

        // reset static vars

        self::$summit = null;
        self::$default_badge_type = null;
        self::$summit2 = null;
        self::$mainVenue = null;
        self::$defaultTags = [];
        self::$ticket_types = [];
        self::$sponsors = [];
        self::$companies_without_sponsor = [];
        self::$media_uploads_types = [];
        self::$default_sponsors_promo_codes = [];
        self::$access_levels = [];
        self::$venue_rooms = [];
        self::$companies = [];
        self::$presentations = [];
    }
}