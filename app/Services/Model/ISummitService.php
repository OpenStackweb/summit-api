<?php namespace services\model;
/**
 * Copyright 2015 OpenStack Foundation
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

use App\Models\Foundation\Summit\IPublishableEvent;
use App\Models\Foundation\Summit\Registration\SummitRegistrationFeedMetadata;
use App\Models\Foundation\Summit\Speakers\FeaturedSpeaker;
use App\Models\Utils\IStorageTypesConstants;
use Illuminate\Http\UploadedFile;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\File;
use models\main\Member;
use models\main\PersonalCalendarShareInfo;
use models\summit\ConfirmationExternalOrderRequest;
use models\summit\Presentation;
use models\summit\RSVP;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeBadge;
use models\summit\SummitBookableVenueRoomAttributeType;
use models\summit\SummitBookableVenueRoomAttributeValue;
use models\summit\SummitEvent;
use models\summit\SummitEventFeedback;
use models\summit\SummitLeadReportSetting;
use models\summit\SummitScheduleEmptySpot;
use utils\Filter;
/**
 * Interface ISummitService
 * @package services\model
 */
interface ISummitService
{
    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitEvent
     */
    public function addEvent(Summit $summit, array $data);

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param array $data
     * @return SummitEvent
     */
    public function updateEvent(Summit $summit, $event_id, array $data);

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param array $data
     * @return SummitEvent
     */
    public function publishEvent(Summit $summit, $event_id, array $data):SummitEvent;

    /**
     * @param Summit $summit
     * @param $event_id
     * @return mixed
     */
    public function unPublishEvent(Summit $summit, $event_id);

    /**
     * @param Summit $summit
     * @param $event_id
     * @param Member|null $current_user
     * @return mixed
     */
    public function deleteEvent(Summit $summit, $event_id, ?Member $current_user = null);

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @param bool $check_rsvp
     * @return bool
     */
    public function addEventToMemberSchedule(Summit $summit, Member $member, $event_id, $check_rsvp = true);

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @param bool $check_rsvp
     * @return void
     */
    public function removeEventFromMemberSchedule(Summit $summit, Member $member, $event_id, $check_rsvp = true);

    /**
     * @param Member $member
     * @param Summit $summit
     * @param int $event_id
     * @param array $payload
     * @return SummitEventFeedback
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addMyEventFeedback(Member $member, Summit $summit, int $event_id, array $payload):SummitEventFeedback;

    /**
     * @param Member $member
     * @param Summit $summit
     * @param int $event_id
     * @param array $payload
     * @return SummitEventFeedback
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateMyEventFeedback(Member $member, Summit $summit, int $event_id, array $payload):SummitEventFeedback;

    /**
     * @param Member $member
     * @param Summit $summit
     * @param int $event_id
     * @return SummitEventFeedback
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function getMyEventFeedback(Member $member, Summit $summit, int $event_id):SummitEventFeedback;

    /**
     * @param Member $member
     * @param Summit $summit
     * @param int $event_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteMyEventFeedback(Member $member, Summit $summit, int $event_id):void;

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param int $feedback_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteEventFeedback(Summit $summit, int $event_id, int $feedback_id):void;
    
    /**
     * @param Summit $summit
     * @param $external_order_id
     * @return array
     */
    public function getExternalOrder(Summit $summit, $external_order_id);

    /**
     * @param ConfirmationExternalOrderRequest $request
     * @return SummitAttendee
     */
    public function confirmExternalOrderAttendee(ConfirmationExternalOrderRequest $request);

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @throws EntityNotFoundException
     */
    public function removeEventFromMemberFavorites(Summit $summit, Member $member, $event_id);

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addEventToMemberFavorites(Summit $summit, Member $member, $event_id);

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return File
     */
    public function addEventAttachment(Summit $summit, $event_id, UploadedFile $file,  $max_file_size = 10485760);

    /**
     * @param Summit $summit
     * @param Filter $filter
     * @return SummitScheduleEmptySpot[]
     */
    public function getSummitScheduleEmptySpots
    (
        Summit $summit,
        Filter $filter
    );

    /**
     * @param Summit $summit
     * @param array $data
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return bool
     */
    public function unPublishEvents(Summit $summit, array $data);

    /**
     * @param Summit $summit
     * @param array $data
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return bool
     */
    public function updateAndPublishEvents(Summit $summit, array $data);

    /**
     * @param Summit $summit
     * @param array $data
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return bool
     */
    public function updateEvents(Summit $summit, array $data);

    /**
     * @param array $data
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return Summit
     */
    public function addSummit(array $data);

    /**
     * @param int $summit_id
     * @param array $data
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return Summit
     */
    public function updateSummit($summit_id, array $data);

    /**
     * @param int $summit_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return void
     */
    public function deleteSummit($summit_id);

    /**
     * @param int $current_member_id
     * @param int $speaker_id
     * @param int $presentation_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return Presentation
     */
    public function addSpeaker2Presentation(int $current_member_id, int $speaker_id, int $presentation_id):Presentation;

    /**
     * @param int $current_member_id
     * @param int $speaker_id
     * @param int $presentation_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return Presentation
     */
    public function addModerator2Presentation(int $current_member_id, int $speaker_id, int $presentation_id):Presentation;

    /**
     * @param int $current_member_id
     * @param int $speaker_id
     * @param int $presentation_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return Presentation
     */
    public function removeSpeakerFromPresentation(int $current_member_id, int $speaker_id, int $presentation_id):Presentation;

    /**
     * @param int $current_member_id
     * @param int $speaker_id
     * @param int $presentation_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return Presentation
     */
    public function removeModeratorFromPresentation(int $current_member_id, int $speaker_id, int $presentation_id):Presentation;

    /**
     * @param Summit $summit
     * @param int $event_id
     * @return SummitEvent
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function cloneEvent(Summit $summit, int $event_id):SummitEvent;

    // bookable room attribute types

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitBookableVenueRoomAttributeType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addBookableRoomAttribute(Summit $summit, array $payload):SummitBookableVenueRoomAttributeType;

    /**
     * @param Summit $summit
     * @param int $type_id
     * @param array $payload
     * @return SummitBookableVenueRoomAttributeType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateBookableRoomAttribute(Summit $summit, int $type_id, array $payload):SummitBookableVenueRoomAttributeType;

    /**
     * @param Summit $summit
     * @param int $type_id
     * @return void
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteBookableRoomAttribute(Summit $summit, int $type_id):void;

    /**
     * @param Summit $summit
     * @param int $type_id
     * @param array $payload
     * @return SummitBookableVenueRoomAttributeValue
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addBookableRoomAttributeValue(Summit $summit, int $type_id, array $payload):SummitBookableVenueRoomAttributeValue;

    /**
     * @param Summit $summit
     * @param int $type_id
     * @param int $value_id
     * @param array $payload
     * @return SummitBookableVenueRoomAttributeValue
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateBookableRoomAttributeValue(Summit $summit, int $type_id, int $value_id, array $payload):SummitBookableVenueRoomAttributeValue;

    /**
     * @param Summit $summit
     * @param int $type_id
     * @param int $value_id
     * @return void
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteBookableRoomAttributeValue(Summit $summit, int $type_id, int $value_id):void;

    /**
     * @param int $summit_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return File
     */
    public function addSummitLogo(int $summit_id, UploadedFile $file,  $max_file_size = 10485760);

    /**
     * @param int $summit
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteSummitLogo(int $summit_id):void;

    /**
     * @param int $summit_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return File
     */
    public function addSummitSecondaryLogo(int $summit_id, UploadedFile $file,  $max_file_size = 10485760);

    /**
     * @param int $summit
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteSummitSecondaryLogo(int $summit_id):void;

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @param array $data
     * @return RSVP
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addRSVP(Summit $summit, Member $member, int $event_id, array $data):RSVP;

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @param array $data
     * @return RSVP
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateRSVP(Summit $summit, Member $member, int $event_id, array $data):RSVP;

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @return bool
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function unRSVPEvent(Summit $summit, Member $member, int $event_id);

    /**
     * @param Summit $summit
     * @param Member $member
     * @return PersonalCalendarShareInfo|null
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function createScheduleShareableLink(Summit $summit, Member $member):?PersonalCalendarShareInfo;

    /**
     * @param Summit $summit
     * @param Member $member
     * @return PersonalCalendarShareInfo|null
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function revokeScheduleShareableLink(Summit $summit, Member $member):?PersonalCalendarShareInfo;

    /**
     * @param Summit $summit
     * @param string $cid
     * @return string
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function buildICSFeed(Summit $summit, string $cid): string;

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param array $data
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return void`
     */
    public function shareEventByEmail(Summit $summit, int $event_id, array $data):void;


    public function calculateFeedbackAverageForOngoingSummits():void;

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return SummitEvent
     */
    public function addEventImage(Summit $summit, $event_id, UploadedFile $file,  $max_file_size = 10485760);

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return void
     */
    public function removeEventImage(Summit $summit, $event_id):void;

    /**
     * @param int $summit_id
     * @param int $days
     * @param bool $negative
     * @param bool $check_summit_ends
     * @return void
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function advanceSummit(int $summit_id, int $days, bool $negative = false, $check_summit_ends = true):void;

    /**
     * @param Summit $summit
     * @param UploadedFile $csv_file
     * @param array $payload
     */
    public function importEventData(Summit $summit, UploadedFile $csv_file, array $payload):void;

    /**
     * @param int $summit_id
     * @param string $filename
     * @param bool $send_speaker_email
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function processEventData(int $summit_id, string $filename, bool $send_speaker_email):void;

    /**
     * @param int $summit_id
     * @param int $speaker_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return FeaturedSpeaker | null
     */
    public function addFeaturedSpeaker(int $summit_id, int $speaker_id):?FeaturedSpeaker;

    /**
     * @param int $summit_id
     * @param int $speaker_id
     * @param array $payload
     * @return FeaturedSpeaker|null
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateFeaturedSpeaker(int $summit_id, int $speaker_id, array $payload):?FeaturedSpeaker;

    /**
     * @param int $summit_id
     * @param int $speaker_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function removeFeaturedSpeaker(int $summit_id, int $speaker_id):void;

    /**
     * @param int $summit_id
     * @param int $company_id
     * @return Company|null
     * @throws EntityNotFoundException
     */
    public function addCompany(int $summit_id, int $company_id): ?Company;

    /**
     * @param int $summit_id
     * @param int $company_id
     * @return void
     * @throws EntityNotFoundException
     */
    public function removeCompany(int $summit_id, int $company_id): void;

    /**
     * @param int $summit_id
     * @param int $media_upload_type_id
     * @param string $default_public_storage
     * @return int
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function migratePrivateStorage2PublicStorage(int $summit_id, int $media_upload_type_id, string $default_public_storage = IStorageTypesConstants::S3):int;

    /**
     * @param int $summit_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function regenerateTemporalUrlsForMediaUploads(int $summit_id):void;

    /**
     * @param Summit $summit
     * @param UploadedFile $csv_file
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function importRegistrationCompanies(Summit $summit,  UploadedFile $csv_file):void;

    /**
     * @param int $summit_id
     * @param string $filename
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function processRegistrationCompaniesData(int $summit_id, string $filename):void;

    /**
     * @param array $data
     * @param Summit $summit
     * @param IPublishableEvent $event
     * @return IPublishableEvent
     * @throws ValidationException
     */
    public function updateDuration(array $data, Summit $summit, IPublishableEvent $event):IPublishableEvent;

    /**
     * @param Summit $summit
     * @return string
     * @throws ValidationException
     */
    public function generateQREncKey(Summit $summit):string;

    /**
     * @param int $summit_id
     */
    public function regenerateBadgeQRCodes(int $summit_id):void;

    /**
     * @param int $summit_id
     * @return void
     */
    public function generateMUXPrivateKey(int $summit_id):void;

    /**
     * @param int $summit_id
     * @return void
     */
    public function generateMuxPlaybackRestriction(int $summit_id):void;

    /*
     * @param Summit $summit
     * @param array $payload
     * @return SummitRegistrationFeedMetadata
     * @throws ValidationException
     */
    public function addRegistrationFeedMetadata(Summit $summit, array $payload):SummitRegistrationFeedMetadata;

    /**
     * @param Summit $summit
     * @param int $metadata_id
     * @param array $payload
     * @return SummitRegistrationFeedMetadata
     */
    public function updateRegistrationFeedMetadata(Summit $summit, int $metadata_id, array $payload):SummitRegistrationFeedMetadata;

    /**
     * @param Summit $summit
     * @param int $metadata_id
     * @return void
     * @throws EntityNotFoundException
     */
    public function removeRegistrationFeedMetadata(Summit $summit, int $metadata_id):void;

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitLeadReportSetting
     * @throws \Exception
     */
    public function addLeadReportSettings(Summit $summit, array $payload): SummitLeadReportSetting;

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitLeadReportSetting
     * @throws \Exception
     */
    public function updateLeadReportSettings(Summit $summit, array $payload): SummitLeadReportSetting;

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param int $type_id
     * @return SummitEvent
     * @throws \Exception
     */
    public function upgradeSummitEvent(Summit $summit, int $event_id, int $type_id): SummitEvent;

    /**
     * @param int $minutes
     * @return void
     */
    public function publishStreamUpdatesStartInXMinutes(int $minutes): void;

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param array $payload
     * @return SummitEvent
     */
    public function updateOverflowInfo(Summit $summit, int $event_id, array $payload): SummitEvent;

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param array $payload
     * @return void
     */
    public function removeOverflowState(Summit $summit, int $event_id, array $payload): SummitEvent;

    /**
     * @param Summit $summit
     * @param Member $current_user
     * @param int $event_id
     * @return SummitEvent|null
     */
    public function getEventForStreamingInfo(Summit $summit, Member $current_user, int $event_id): ?SummitEvent;

    /**
     * @param Summit $summit
     * @param string $badge_qr_code
     * @return SummitAttendeeBadge
     * @throws ValidationException
     */
    public function validateBadge(Summit $summit, string $badge_qr_code): SummitAttendeeBadge;
}