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

use App\Events\MyFavoritesAdd;
use App\Events\MyFavoritesRemove;
use App\Events\MyScheduleAdd;
use App\Events\MyScheduleRemove;
use App\Events\RSVPCreated;
use App\Events\RSVPUpdated;
use App\Facades\ResourceServerContext;
use App\Http\Utils\IFileUploader;
use App\Jobs\Emails\PresentationSubmissions\ImportEventSpeakerEmail;
use App\Jobs\Emails\PresentationSubmissions\PresentationModeratorNotificationEmail;
use App\Jobs\Emails\PresentationSubmissions\PresentationSpeakerNotificationEmail;
use App\Jobs\Emails\Schedule\ShareEventEmail;
use App\Jobs\EncryptAllSummitBadgeQRCodes;
use App\Jobs\ProcessEventDataImport;
use App\Jobs\ProcessRegistrationCompaniesDataImport;
use App\Jobs\ProcessScheduleEntityLifeCycleEvent;
use App\Models\Foundation\Main\Factories\CompanyFactory;
use App\Models\Foundation\Summit\Factories\LeadReportSettingsFactory;
use App\Models\Foundation\Summit\Factories\PresentationFactory;
use App\Models\Foundation\Summit\Factories\SummitEventFeedbackFactory;
use App\Models\Foundation\Summit\Factories\SummitFactory;
use App\Models\Foundation\Summit\Factories\SummitRSVPFactory;
use App\Models\Foundation\Summit\Registration\SummitRegistrationFeedMetadata;
use App\Models\Foundation\Summit\Repositories\IDefaultSummitEventTypeRepository;
use App\Models\Foundation\Summit\Repositories\IPresentationMediaUploadRepository;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgeRepository;
use App\Models\Foundation\Summit\Speakers\FeaturedSpeaker;
use App\Models\Utils\IntervalParser;
use App\Models\Utils\IStorageTypesConstants;
use App\Permissions\IPermissionsManager;
use App\Services\Apis\IMUXApi;
use App\Services\Apis\MuxCredentials;
use App\Services\Filesystem\FileDownloadStrategyFactory;
use App\Services\Filesystem\FileUploadStrategyFactory;
use App\Services\FileSystem\IFileDownloadStrategy;
use App\Services\FileSystem\IFileUploadStrategy;
use App\Services\Model\IMemberService;
use App\Services\Model\AbstractPublishService;
use App\Services\Utils\Security\IEncryptionAES256KeysGenerator;
use App\Utils\AES;
use DateInterval;
use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use libs\utils\ICacheService;
use libs\utils\ICalTimeZoneBuilder;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\File;
use models\main\ICompanyRepository;
use models\main\IGroupRepository;
use models\main\IMemberRepository;
use models\main\ITagRepository;
use models\main\Member;
use models\main\PersonalCalendarShareInfo;
use models\main\Tag;
use models\summit\CalendarSync\WorkQueue\AbstractCalendarSyncWorkRequest;
use models\summit\CalendarSync\WorkQueue\MemberEventScheduleSummitActionSyncWorkRequest;
use models\summit\ConfirmationExternalOrderRequest;
use models\summit\IAbstractCalendarSyncWorkRequestRepository;
use models\summit\IRSVPRepository;
use models\summit\ISpeakerRepository;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\ISummitEntityEventRepository;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\Presentation;
use models\summit\PresentationMediaUpload;
use models\summit\PresentationSpeaker;
use models\summit\PresentationType;
use models\summit\RSVP;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeBadge;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitBadgeViewType;
use models\summit\SummitBookableVenueRoomAttributeType;
use models\summit\SummitBookableVenueRoomAttributeValue;
use models\summit\SummitEvent;
use models\summit\SummitEventFactory;
use models\summit\SummitEventFeedback;
use models\summit\SummitEventType;
use models\summit\SummitEventWithFile;
use models\summit\SummitGeoLocatedLocation;
use models\summit\SummitGroupEvent;
use models\summit\SummitLeadReportSetting;
use models\summit\SummitMediaUploadType;
use models\summit\SummitScheduleEmptySpot;
use Random\RandomException;
use services\apis\IEventbriteAPI;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;
use utils\Order;
use utils\OrderElement;
use utils\PagingInfo;

/**
 * Class SummitService
 * @package services\model
 */
final class SummitService
    extends AbstractPublishService implements ISummitService
{
    /**
     * @var ISummitEventRepository
     */
    private $event_repository;

    /**
     * @var IEventbriteAPI
     */
    private $eventbrite_api;

    /**
     * @var ISpeakerRepository
     */
    private $speaker_repository;

    /**
     * @var ISummitAttendeeTicketRepository
     */
    private $ticket_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitAttendeeRepository
     */
    private $attendee_repository;

    /**
     * @var ITagRepository
     */
    private $tag_repository;

    /**
     * @var IRSVPRepository
     */
    private $rsvp_repository;

    /**
     * @var IAbstractCalendarSyncWorkRequestRepository
     */
    private $calendar_sync_work_request_repository;

    /**
     * @var ICompanyRepository
     */
    private $company_repository;

    /**
     * @var IGroupRepository
     */
    private $group_repository;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IDefaultSummitEventTypeRepository
     */
    private $default_event_types_repository;

    /**
     * @var IPermissionsManager
     */
    private $permissions_manager;

    /**
     * @var IFileUploader
     */
    private $file_uploader;

    /**
     * @var ISpeakerService ISpeakerService
     */
    private $speaker_service;

    /**
     * @var IMemberService
     */
    private $member_service;

    /**
     * @var IPresentationMediaUploadRepository
     */
    private $presentation_media_upload_repository;

    /**
     * @var ISummitAttendeeBadgeRepository
     */
    private $summit_attendee_badge_repository;

    /**
     * @var IFileUploadStrategy
     */
    private $upload_strategy;

    /**
     * @var IFileDownloadStrategy
     */
    private $download_strategy;

    /**
     * @var IEncryptionAES256KeysGenerator
     */
    private $encryption_key_generator;

    /**
     * @var IMUXApi
     */
    private $mux_api;

    /**
     * @var ICacheService
     */
    private $cache_service;

    /**
     * @param ISummitRepository $summit_repository
     * @param ISummitEventRepository $event_repository
     * @param ISpeakerRepository $speaker_repository
     * @param ISummitEntityEventRepository $entity_events_repository
     * @param ISummitAttendeeTicketRepository $ticket_repository
     * @param ISummitAttendeeRepository $attendee_repository
     * @param IMemberRepository $member_repository
     * @param ITagRepository $tag_repository
     * @param IRSVPRepository $rsvp_repository
     * @param IAbstractCalendarSyncWorkRequestRepository $calendar_sync_work_request_repository
     * @param IEventbriteAPI $eventbrite_api
     * @param ICompanyRepository $company_repository
     * @param IGroupRepository $group_repository
     * @param IDefaultSummitEventTypeRepository $default_event_types_repository
     * @param IPresentationMediaUploadRepository $presentation_media_upload_repository
     * @param ISummitAttendeeBadgeRepository $summit_attendee_badge_repository
     * @param IPermissionsManager $permissions_manager
     * @param IFileUploader $file_uploader
     * @param ISpeakerService $speaker_service
     * @param IMemberService $member_service
     * @param IFileUploadStrategy $upload_strategy
     * @param IFileDownloadStrategy $download_strategy
     * @param IEncryptionAES256KeysGenerator $encryption_key_generator
     * @param IMUXApi $mux_api
     * @param ICacheService $cache_service
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitRepository                          $summit_repository,
        ISummitEventRepository                     $event_repository,
        ISpeakerRepository                         $speaker_repository,
        ISummitEntityEventRepository               $entity_events_repository,
        ISummitAttendeeTicketRepository            $ticket_repository,
        ISummitAttendeeRepository                  $attendee_repository,
        IMemberRepository                          $member_repository,
        ITagRepository                             $tag_repository,
        IRSVPRepository                            $rsvp_repository,
        IAbstractCalendarSyncWorkRequestRepository $calendar_sync_work_request_repository,
        IEventbriteAPI                             $eventbrite_api,
        ICompanyRepository                         $company_repository,
        IGroupRepository                           $group_repository,
        IDefaultSummitEventTypeRepository          $default_event_types_repository,
        IPresentationMediaUploadRepository         $presentation_media_upload_repository,
        ISummitAttendeeBadgeRepository             $summit_attendee_badge_repository,
        IPermissionsManager                        $permissions_manager,
        IFileUploader                              $file_uploader,
        ISpeakerService                            $speaker_service,
        IMemberService                             $member_service,
        IFileUploadStrategy                        $upload_strategy,
        IFileDownloadStrategy                      $download_strategy,
        IEncryptionAES256KeysGenerator             $encryption_key_generator,
        IMUXApi                                    $mux_api,
        ICacheService                              $cache_service,
        ITransactionService                        $tx_service
    )
    {
        parent::__construct($event_repository, $tx_service);
        $this->summit_repository = $summit_repository;
        $this->event_repository = $event_repository;
        $this->speaker_repository = $speaker_repository;
        $this->entity_events_repository = $entity_events_repository;
        $this->ticket_repository = $ticket_repository;
        $this->member_repository = $member_repository;
        $this->attendee_repository = $attendee_repository;
        $this->tag_repository = $tag_repository;
        $this->rsvp_repository = $rsvp_repository;
        $this->calendar_sync_work_request_repository = $calendar_sync_work_request_repository;
        $this->eventbrite_api = $eventbrite_api;
        $this->company_repository = $company_repository;
        $this->group_repository = $group_repository;
        $this->default_event_types_repository = $default_event_types_repository;
        $this->permissions_manager = $permissions_manager;
        $this->file_uploader = $file_uploader;
        $this->speaker_service = $speaker_service;
        $this->member_service = $member_service;
        $this->presentation_media_upload_repository = $presentation_media_upload_repository;
        $this->summit_attendee_badge_repository = $summit_attendee_badge_repository;
        $this->upload_strategy = $upload_strategy;
        $this->encryption_key_generator = $encryption_key_generator;
        $this->download_strategy = $download_strategy;
        $this->mux_api = $mux_api;
        $this->cache_service = $cache_service;
    }

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @param bool $check_rsvp
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addEventToMemberSchedule(Summit $summit, Member $member, $event_id, $check_rsvp = true)
    {
        try {
            $this->tx_service->transaction(function () use ($summit, $member, $event_id, $check_rsvp) {

                $event = $summit->getScheduleEvent($event_id);

                if (is_null($event)) {
                    throw new EntityNotFoundException('event not found on summit!');
                }

                if (!Summit::allowToSee($event, $member))
                    throw new EntityNotFoundException('event not found on summit!');

                if ($check_rsvp && $event->hasRSVP() && !$event->isExternalRSVP())
                    throw new ValidationException("event has rsvp set on it!");

                $member->add2Schedule($event);

                if ($member->hasSyncInfoFor($summit)) {
                    Log::info(sprintf("synching externally event id %s", $event_id));
                    $sync_info = $member->getSyncInfoBy($summit);
                    $request = new MemberEventScheduleSummitActionSyncWorkRequest();
                    $request->setType(AbstractCalendarSyncWorkRequest::TypeAdd);
                    $request->setSummitEvent($event);
                    $request->setOwner($member);
                    $request->setCalendarSyncInfo($sync_info);
                    $this->calendar_sync_work_request_repository->add($request);
                }

            });
            Event::dispatch(new MyScheduleAdd($member, $summit, $event_id));
        } catch (UniqueConstraintViolationException $ex) {
            throw new ValidationException
            (
                sprintf('Event %s already belongs to member %s schedule.', $event_id, $member->getId())
            );
        }
    }

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @param boolean $check_rsvp
     * @return void
     * @throws \Exception
     */
    public function removeEventFromMemberSchedule(Summit $summit, Member $member, $event_id, $check_rsvp = true)
    {
        $this->tx_service->transaction(function () use ($summit, $member, $event_id, $check_rsvp) {
            $event = $summit->getScheduleEvent($event_id);
            if (is_null($event))
                throw new EntityNotFoundException('event not found on summit!');

            if ($check_rsvp && $event->hasRSVP() && !$event->isExternalRSVP())
                throw new ValidationException("event has rsvp set on it!");

            $member->removeFromSchedule($event);

            if ($member->hasSyncInfoFor($summit)) {
                Log::info(sprintf("unsynching externally event id %s", $event_id));
                $sync_info = $member->getSyncInfoBy($summit);
                $request = new MemberEventScheduleSummitActionSyncWorkRequest();
                $request->setType(AbstractCalendarSyncWorkRequest::TypeRemove);
                $request->setSummitEvent($event);
                $request->setOwner($member);
                $request->setCalendarSyncInfo($sync_info);
                $this->calendar_sync_work_request_repository->add($request);
            }
        });

        Event::dispatch(new MyScheduleRemove($member, $summit, $event_id));
    }

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addEventToMemberFavorites(Summit $summit, Member $member, $event_id)
    {
        try {
            $this->tx_service->transaction(function () use ($summit, $member, $event_id) {
                $event = $summit->getScheduleEvent($event_id);
                if (is_null($event)) {
                    throw new EntityNotFoundException('event not found on summit!');
                }
                if (!Summit::allowToSee($event, $member))
                    throw new EntityNotFoundException('event not found on summit!');
                $member->addFavoriteSummitEvent($event);
            });

            Event::dispatch(new MyFavoritesAdd($member, $summit, $event_id));
        } catch (UniqueConstraintViolationException $ex) {
            throw new ValidationException
            (
                sprintf('Event %s already belongs to member %s favorites.', $event_id, $member->getId())
            );
        }
    }

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @throws EntityNotFoundException
     */
    public function removeEventFromMemberFavorites(Summit $summit, Member $member, $event_id)
    {
        $this->tx_service->transaction(function () use ($summit, $member, $event_id) {
            $event = $summit->getScheduleEvent($event_id);
            if (is_null($event))
                throw new EntityNotFoundException('event not found on summit!');
            $member->removeFavoriteSummitEvent($event);
        });

        Event::dispatch(new MyFavoritesRemove($member, $summit, $event_id));
    }

    /**
     * @param Member $member
     * @param Summit $summit
     * @param int $event_id
     * @param array $payload
     * @return SummitEventFeedback
     * @throws Exception
     */
    public function addMyEventFeedback(Member $member, Summit $summit, int $event_id, array $payload): SummitEventFeedback
    {
        return $this->tx_service->transaction(function () use ($member, $summit, $event_id, $payload) {

            $event = $summit->getScheduleEvent($event_id);
            if (is_null($event))
                throw new EntityNotFoundException("Event not found.");

            if (!Summit::allowToSee($event, $member))
                throw new EntityNotFoundException("Event not found.");

            if (!$event->isAllowFeedback())
                throw new ValidationException(sprintf("Event id %s does not allow feedback.", $event->getIdentifier()));

            // check older feedback
            $former_feedback = $member->getFeedbackByEvent($event);

            if (!is_null($former_feedback))
                throw new ValidationException(sprintf("You already sent feedback for event id %s!.", $event->getIdentifier()));

            $newFeedback = SummitEventFeedbackFactory::build($payload);
            $newFeedback->setOwner($member);
            $event->addFeedBack($newFeedback);
            return $newFeedback;
        });
    }

    /**
     * @param Member $member
     * @param Summit $summit
     * @param int $event_id
     * @param array $payload
     * @return SummitEventFeedback
     * @throws Exception
     */
    public function updateMyEventFeedback(Member $member, Summit $summit, int $event_id, array $payload): SummitEventFeedback
    {
        return $this->tx_service->transaction(function () use ($member, $summit, $event_id, $payload) {

            $event = $summit->getScheduleEvent($event_id);

            if (is_null($event))
                throw new EntityNotFoundException("Event not found.");

            if (!Summit::allowToSee($event, $member))
                throw new EntityNotFoundException("Event not found.");

            if (!$event->isAllowFeedback())
                throw new ValidationException(sprintf("Event id %s does not allow feedback.", $event->getIdentifier()));

            // check older feedback
            $feedback = $member->getFeedbackByEvent($event);

            if (is_null($feedback))
                throw new ValidationException(sprintf("you dont have feedback for event id %s!.", $event->getIdentifier()));

            return SummitEventFeedbackFactory::populate($feedback, $payload);
        });
    }

    /**
     * @param Member $member
     * @param Summit $summit
     * @param int $event_id
     * @return SummitEventFeedback
     * @throws Exception
     */
    public function getMyEventFeedback(Member $member, Summit $summit, int $event_id): SummitEventFeedback
    {
        return $this->tx_service->transaction(function () use ($member, $summit, $event_id) {

            $event = $summit->getScheduleEvent($event_id);

            if (is_null($event))
                throw new EntityNotFoundException("Event not found.");

            if (!Summit::allowToSee($event, $member))
                throw new EntityNotFoundException("Event not found.");

            if (!$event->isAllowFeedback())
                throw new ValidationException(sprintf("Event id %s does not allow feedback.", $event->getIdentifier()));

            // check older feedback
            $feedback = $member->getFeedbackByEvent($event);

            if (is_null($feedback))
                throw new ValidationException(sprintf("you dont have feedback for event id %s!.", $event->getIdentifier()));

            return $feedback;
        });
    }

    /**
     * @param Member $member
     * @param Summit $summit
     * @param int $event_id
     * @throws Exception
     */
    public function deleteMyEventFeedback(Member $member, Summit $summit, int $event_id): void
    {
        $this->tx_service->transaction(function () use ($member, $summit, $event_id) {

            $event = $summit->getScheduleEvent($event_id);
            if (is_null($event))
                throw new EntityNotFoundException("Event not found.");

            if (!Summit::allowToSee($event, $member))
                throw new EntityNotFoundException("Event not found.");

            if (!$event->isAllowFeedback())
                throw new ValidationException(sprintf("Event id %s does not allow feedback.", $event->getIdentifier()));

            // check older feedback
            $feedback = $member->getFeedbackByEvent($event);

            $member->removeFeedback($feedback);

        });
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitEvent
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addEvent(Summit $summit, array $data)
    {
        return $this->saveOrUpdateEvent($summit, $data, null);
    }

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param array $data
     * @return SummitEvent
     */
    public function updateEvent(Summit $summit, $event_id, array $data)
    {
        return $this->saveOrUpdateEvent($summit, $data, $event_id);
    }

    /**
     * @param SummitEventType $old_event_type
     * @param SummitEventType $event_type
     * @return bool
     */
    private function canPerformEventTypeTransition(SummitEventType $old_event_type, SummitEventType $event_type)
    {

        if ($old_event_type->getId() == $event_type->getId()) return true;
        // cant upgrade from raw event to presentation and vice versa
        if ($old_event_type->getClassName() != $event_type->getClassName()) {
            return false;
        }

        $old_is_private = $old_event_type->isPrivate();
        $new_is_private = $event_type->isPrivate();

        if ((!$old_is_private && $new_is_private) || ($old_is_private && !$new_is_private))
            return false;

        $old_allow_attach = $old_event_type->isAllowsAttachment();
        $new_allow_attach = $event_type->isAllowsAttachment();

        if ((!$old_allow_attach && $new_allow_attach) || ($old_allow_attach && !$new_allow_attach))
            return false;

        return true;
    }


    /**
     * @param Summit $summit
     * @param array $data
     * @param null|int $event_id
     * @return SummitEvent
     * @throws Exception
     */
    private function saveOrUpdateEvent(Summit $summit, array $data, $event_id = null)
    {
        return $this->tx_service->transaction(function () use ($summit, $data, $event_id) {

            Log::debug
            (
                sprintf
                (
                    "SummitService::saveOrUpdateEvent summit %s event_id %s data %s",
                    $summit->getId(),
                    $event_id ?? "NEW",
                    json_encode($data)
                )
            );

            $current_member = ResourceServerContext::getCurrentUser(false);

            if (!is_null($current_member) && !$this->permissions_manager->canEditFields($current_member, 'SummitEvent', $data)) {
                throw new ValidationException(sprintf("user %s cant set requested summit event fields", $current_member->getEmail()));
            }

            $event_type = null;
            if (isset($data['type_id'])) {
                $event_type = $summit->getEventType(intval($data['type_id']));
                if (is_null($event_type)) {
                    throw new EntityNotFoundException(sprintf("event type id %s does not exists!", $data['type_id']));
                }
            }

            $track = null;
            if (isset($data['track_id'])) {
                $track = $summit->getPresentationCategory(intval($data['track_id']));
                if (is_null($track)) {
                    throw new EntityNotFoundException(sprintf("track id %s does not exists!", $data['track_id']));
                }
            }

            $location = null;
            if (isset($data['location_id'])) {
                $location = $summit->getLocation(intval($data['location_id']));
                if (is_null($location) && intval($data['location_id']) > 0) {
                    throw new EntityNotFoundException(sprintf("location id %s does not exists!", $data['location_id']));
                }
            }

            $event = null;

            // existing event

            if (!is_null($event_id) && $event_id > 0) {

                $event = $this->event_repository->getByIdRefreshed($event_id);

                if (is_null($event))
                    throw new ValidationException(sprintf("event id %s does not exists!", $event_id));

                $old_event_type = $event->getType();

                // check event type transition ...
                if (!is_null($event_type) && !$this->canPerformEventTypeTransition($old_event_type, $event_type)) {
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "invalid event type transition for event id %s ( from %s to %s)",
                            $event_id,
                            $old_event_type->getType(),
                            $event_type->getType()
                        )
                    );
                }
                if (is_null($event_type)) $event_type = $old_event_type;
            }

            if (is_null($event_id) && is_null($event_type)) {
                // is event is new one and we dont provide an event type ...
                throw new ValidationException('type_id is mandatory!');
            }

            // new event
            if (is_null($event)) {
                $event = SummitEventFactory::build($event_type, $summit, $data);
                $event->setCreatedBy($current_member);
                if (!is_null($track)) {
                    $event->setCategory($track);
                }
            } else {
                $event->setSummit($summit);
                if (!is_null($event_type))
                    $event->setType($event_type);

                if (!is_null($track)) {
                    $event->setCategory($track);
                }

                SummitEventFactory::populate($summit, $event, $data);
            }

            $created_by = null;
            if (isset($data['created_by_id'])) {
                $created_by = $this->member_repository->getById(intval($data['created_by_id']));
                if (is_null($created_by) && intval($data['created_by_id']) > 0) {
                    throw new EntityNotFoundException(sprintf("member id %s does not exists!", $data['created_by_id']));
                }
            }

            if (!is_null($created_by)) // override
                $event->setCreatedBy($created_by);

            $event->setUpdatedBy($current_member);

            if (isset($data['rsvp_template_id'])) {

                $rsvp_template = $summit->getRSVPTemplateById(intval($data['rsvp_template_id']));

                if (is_null($rsvp_template))
                    throw new EntityNotFoundException(sprintf('rsvp template id %s does not belongs to summit id %s', $data['rsvp_template_id'], $summit->getId()));

                if (!$rsvp_template->isEnabled())
                    throw new ValidationException(sprintf('rsvp template id %s is not enabled', $data['rsvp_template_id']));

                $event->setRSVPTemplate($rsvp_template);

                $event->setRSVPMaxUserNumber(intval($data['rsvp_max_user_number']));
                $event->setRSVPMaxUserWaitListNumber(intval($data['rsvp_max_user_wait_list_number']));
            }

            if (!is_null($location)) {
                if (!$event->hasType()) {
                    throw new ValidationException("To be able to set a location, event type must be set First.");
                }
                if (!$event_type->isAllowsLocation())
                    throw new ValidationException("Event Type does not allow location.");
                $event->setLocation($location);
            }

            if (is_null($location) && isset($data['location_id'])) {
                // clear location
                $event->clearLocation();
            }

            $this->updateEventDates($data, $summit, $event);

            if (isset($data['tags'])) {
                $event->clearTags();
                foreach ($data['tags'] as $str_tag) {
                    $tag = $this->tag_repository->getByTag($str_tag);
                    if ($tag == null) $tag = new Tag($str_tag);
                    $event->addTag($tag);
                }
            }

            // sponsors

            $sponsors = ($event_type->isUseSponsors() && isset($data['sponsors'])) ?
                $data['sponsors'] : [];

            if ($event_type->isAreSponsorsMandatory() && count($sponsors) == 0) {
                throw new ValidationException('sponsors are mandatory!');
            }

            if (isset($data['sponsors'])) {
                $event->clearSponsors();
                foreach ($sponsors as $sponsor_id) {
                    $sponsor = $this->company_repository->getById(intval($sponsor_id));
                    if (is_null($sponsor)) throw new EntityNotFoundException(sprintf('sponsor id %s', $sponsor_id));
                    $event->addSponsor($sponsor);
                }
            }

            $this->saveOrUpdatePresentationData($event, $event_type, $data);
            $this->saveOrUpdateSummitGroupEventData($event, $event_type, $data);

            if (!$event_type->isAllowsLocation())
                $event->clearLocation();
            if (!$event_type->isAllowsPublishingDates())
                $event->clearPublishingDates();

            if ($event->isPublished()) {
                $location = $event->getLocation();
                $opening_hour = null;
                $closing_hour = null;

                if (!is_null($location)) {
                    $opening_hour = $location->getOpeningHour();
                    $closing_hour = $location->getClosingHour();
                }

                $this->validateBlackOutTimesAndTimes($event, $opening_hour, $closing_hour);
                $event->unPublish();
                $event->publish();
            }

            $this->event_repository->add($event);
            $event->updateLastEdited();
            return $event;
        });
    }

    private function saveOrUpdateSummitGroupEventData(SummitEvent $event, SummitEventType $event_type, array $data)
    {
        if (!$event instanceof SummitGroupEvent) return;

        if (!isset($data['groups']) || count($data['groups']) == 0)
            throw new ValidationException('groups is required');
        $event->clearGroups();

        foreach ($data['groups'] as $group_id) {
            $group = $this->group_repository->getById(intval($group_id));
            if (is_null($group)) throw new EntityNotFoundException(sprintf('group id %s', $group_id));
            $event->addGroup($group);
        }
    }

    /**
     * @param SummitEvent $event
     * @param SummitEventType $event_type
     * @param array $data
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    private function saveOrUpdatePresentationData(SummitEvent $event, SummitEventType $event_type, array $data)
    {
        if (!$event instanceof Presentation) return;

        // if we are creating the presentation from admin, then
        // we should mark it as received and complete
        $event->setStatus(Presentation::STATUS_RECEIVED);
        $event->setProgress(Presentation::PHASE_COMPLETE);

        // speakers

        if ($event_type instanceof PresentationType && $event_type->isUseSpeakers()) {

            $shouldClearSpeakers = isset($data['speakers']) && count($data['speakers']) == 0;
            $speakers = $data['speakers'] ?? [];

            if ($event_type->isAreSpeakersMandatory()) {
                if ($shouldClearSpeakers || ($event->isNew() && count($speakers) == 0))
                    throw new ValidationException('Speakers are mandatory.');
            }

            if ($shouldClearSpeakers) {
                $event->clearSpeakers();
            }

            if (count($speakers) > 0) {
                $event->clearSpeakers();
                foreach ($speakers as $speaker_id) {
                    $speaker = $this->speaker_repository->getById(intval($speaker_id));
                    if (is_null($speaker) || !$speaker instanceof PresentationSpeaker)
                        throw new EntityNotFoundException(sprintf('Speaker id %s.', $speaker_id));
                    $event->addSpeaker($speaker);
                }
            }
        }

        // moderator

        if ($event_type instanceof PresentationType && $event_type->isUseModerator()) {
            $shouldClearModerator = isset($data['moderator_speaker_id']) && intval($data['moderator_speaker_id']) == 0;
            $moderator_id = isset($data['moderator_speaker_id']) ? intval($data['moderator_speaker_id']) : 0;

            if ($event_type->isModeratorMandatory()) {
                if ($shouldClearModerator || ($event->isNew() && $moderator_id == 0))
                    throw new ValidationException('moderator_speaker_id is mandatory.');
            }

            if ($shouldClearModerator) $event->unsetModerator();

            if ($moderator_id > 0) {
                $moderator = $this->speaker_repository->getById($moderator_id);
                if (is_null($moderator) || !$moderator instanceof PresentationSpeaker)
                    throw new EntityNotFoundException(sprintf('Moderator %s not found', $moderator_id));
                $event->setModerator($moderator);
            }
        }

        PresentationFactory::populate($event, $data, true);
    }

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param array $data
     * @return SummitEvent
     */
    public function publishEvent(Summit $summit, $event_id, array $data): SummitEvent
    {
        return $this->tx_service->transaction(function () use ($summit, $data, $event_id) {

            $event = $this->event_repository->getById($event_id);

            if (is_null($event) || !$event instanceof SummitEvent)
                throw new EntityNotFoundException(sprintf("Event id %s does not exists!", $event_id));

            if (!$event->hasType())
                throw new EntityNotFoundException(sprintf("Event type its not assigned to event id %s!", $event_id));

            $type = $event->getType();

            if (is_null($event->getSummit()))
                throw new EntityNotFoundException(sprintf("Summit its not assigned to event id %s!", $event_id));

            if ($event->getSummit()->getIdentifier() !== $summit->getIdentifier())
                throw new ValidationException(sprintf("Event %s does not belongs to summit id %s", $event_id, $summit->getIdentifier()));

            $this->updateEventDates($data, $summit, $event);

            if ($type->isAllowsPublishingDates()) {
                $start_datetime = $event->getStartDate();
                $end_datetime = $event->getEndDate();

                if (is_null($start_datetime))
                    throw new ValidationException(sprintf("start_date its not assigned to event id %s!", $event_id));

                if (is_null($end_datetime))
                    throw new ValidationException(sprintf("end_date its not assigned to event id %s!", $event_id));
            }

            if (isset($data['location_id']) && $type->isAllowsLocation()) {
                $location_id = intval($data['location_id']);
                $event->clearLocation();
                if ($location_id > 0) {
                    $location = $summit->getLocation($location_id);
                    if (is_null($location))
                        throw new EntityNotFoundException(sprintf("location id %s does not exists!", $data['location_id']));
                    $event->setLocation($location);
                }
            }

            $location = $event->getLocation();
            $opening_hour = null;
            $closing_hour = null;

            if (!is_null($location)) {
                $opening_hour = $location->getOpeningHour();
                $closing_hour = $location->getClosingHour();
            }

            $this->validateBlackOutTimesAndTimes($event, $opening_hour, $closing_hour);
            $event->unPublish();
            $event->publish();
            $event->setUpdatedBy(ResourceServerContext::getCurrentUser(false));
            $this->event_repository->add($event);
            return $event;
        });
    }

    /**
     * @param Summit $summit
     * @param int $event_id
     * @return mixed
     * @throws Exception
     */
    public function unPublishEvent(Summit $summit, $event_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $event_id) {

            $event = $this->event_repository->getById($event_id);

            if (is_null($event))
                throw new EntityNotFoundException(sprintf("event id %s does not exists!", $event_id));

            if ($event->getSummit()->getIdentifier() !== $summit->getIdentifier())
                throw new ValidationException(sprintf("event %s does not belongs to summit id %s", $event_id, $summit->getIdentifier()));

            $event->unPublish();

            $event->setUpdatedBy(ResourceServerContext::getCurrentUser(false));

            $this->event_repository->cleanupScheduleAndFavoritesForEvent($event_id);

            return $event;
        });
    }

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param Member|null $current_user
     * @return mixed
     * @throws Exception
     */
    public function deleteEvent(Summit $summit, $event_id, ?Member $current_user = null)
    {
        return $this->tx_service->transaction(function () use ($summit, $event_id, $current_user) {

            $event = $this->event_repository->getById($event_id);

            if (!$event instanceof SummitEvent)
                throw new EntityNotFoundException(sprintf("Event id %s does not exists!", $event_id));

            if ($event->getSummit()->getIdentifier() !== $summit->getIdentifier())
                throw new ValidationException(sprintf("Event %s does not belongs to summit id %s.", $event_id, $summit->getIdentifier()));

            if ($event instanceof Presentation) {
                $can_delete_closed_submissions = is_null($current_user) || $current_user->isAdmin();
                if ($event->isSubmissionClosed() && !$can_delete_closed_submissions)
                    throw new ValidationException(sprintf("Presentation %s can not be deleted because the submission is closed.", $event_id));

                $event->clearMediaUploads();
            }

            $this->event_repository->delete($event);

            $this->event_repository->cleanupScheduleAndFavoritesForEvent($event_id);
            return true;
        });
    }

    /**
     * @param Summit $summit
     * @param $external_order_id
     * @return array
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function getExternalOrder(Summit $summit, $external_order_id)
    {
        try {
            $external_order = $this->eventbrite_api->getOrder($external_order_id);

            if (isset($external_order['attendees'])) {
                $status = $external_order['status'];
                $summit_external_id = $external_order['event_id'];

                if (intval($summit->getSummitExternalId()) !== intval($summit_external_id))
                    throw new ValidationException('order %s does not belongs to current summit!', $external_order_id);

                if ($status !== 'placed')
                    throw new ValidationException(sprintf('invalid order status %s for order %s', $status, $external_order_id));

                $attendees = array();
                foreach ($external_order['attendees'] as $a) {

                    $ticket_external_id = intval($a['ticket_class_id']);
                    $ticket_type = $summit->getTicketTypeByExternalId($ticket_external_id);
                    $external_attendee_id = $a['id'];

                    if (is_null($ticket_type))
                        throw new EntityNotFoundException(sprintf('external ticket type %s not found!', $ticket_external_id));

                    $old_ticket = $this->ticket_repository->getByExternalOrderIdAndExternalAttendeeId
                    (
                        trim($external_order_id), $external_attendee_id
                    );

                    if (!is_null($old_ticket)) continue;

                    $attendees[] = [
                        'external_id' => intval($a['id']),
                        'first_name' => $a['profile']['first_name'],
                        'last_name' => $a['profile']['last_name'],
                        'email' => $a['profile']['email'],
                        'company' => isset($a['profile']['company']) ? $a['profile']['company'] : null,
                        'job_title' => isset($a['profile']['job_title']) ? $a['profile']['job_title'] : null,
                        'status' => $a['status'],
                        'ticket_type' => [
                            'id' => intval($ticket_type->getId()),
                            'name' => $ticket_type->getName(),
                            'external_id' => $ticket_external_id,
                        ]
                    ];
                }
                if (count($attendees) === 0)
                    throw new ValidationException(sprintf('order %s already redeem!', $external_order_id));

                return array('id' => intval($external_order_id), 'attendees' => $attendees);
            }
        } catch (ClientException $ex1) {
            if ($ex1->getCode() === 400)
                throw new EntityNotFoundException('external order does not exists!');
            if ($ex1->getCode() === 403)
                throw new EntityNotFoundException('external order does not exists!');
            throw $ex1;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param ConfirmationExternalOrderRequest $request
     * @return SummitAttendee
     */
    public function confirmExternalOrderAttendee(ConfirmationExternalOrderRequest $request)
    {
        return $this->tx_service->transaction(function () use ($request) {

            try {

                $external_order = $this->eventbrite_api->getOrder($request->getExternalOrderId());

                if (isset($external_order['attendees'])) {

                    $summit_external_id = $external_order['event_id'];

                    if (intval($request->getSummit()->getSummitExternalId()) !== intval($summit_external_id))
                        throw new ValidationException('order %s does not belongs to current summit!', $request->getExternalOrderId());

                    $external_attendee = null;
                    foreach ($external_order['attendees'] as $a) {
                        if (intval($a['id']) === intval($request->getExternalAttendeeId())) {
                            $external_attendee = $a;
                            break;
                        }
                    }

                    if (is_null($external_attendee))
                        throw new EntityNotFoundException(sprintf('attendee %s not found!', $request->getExternalAttendeeId()));

                    $ticket_external_id = intval($external_attendee['ticket_class_id']);
                    $ticket_type = $request->getSummit()->getTicketTypeByExternalId($ticket_external_id);

                    if (is_null($ticket_type))
                        throw new EntityNotFoundException(sprintf('ticket type %s not found!', $ticket_external_id));;

                    $status = $external_order['status'];
                    if ($status !== 'placed')
                        throw new ValidationException(sprintf('invalid order status %s for order %s', $status, $request->getExternalOrderId()));

                    $old_attendee = $request->getSummit()->getAttendeeByMemberId($request->getMemberId());

                    if (!is_null($old_attendee))
                        throw new ValidationException
                        (
                            'Attendee already exists for current summit.'
                        );

                    $old_ticket = $this->ticket_repository->getByExternalOrderIdAndExternalAttendeeId(
                        $request->getExternalOrderId(),
                        $request->getExternalAttendeeId()
                    );

                    if (!is_null($old_ticket))
                        throw new ValidationException
                        (
                            sprintf
                            (
                                'order %s already redeem for attendee id %s !',
                                $request->getExternalOrderId(),
                                $request->getExternalAttendeeId()
                            )
                        );

                    $ticket = new SummitAttendeeTicket;
                    $ticket->setExternalOrderId($request->getExternalOrderId());
                    $ticket->setExternalAttendeeId($request->getExternalAttendeeId());
                    $ticket->setBoughtDate(new DateTime($external_attendee['created']));
                    $ticket->setChangedDate(new DateTime($external_attendee['changed']));
                    $ticket->setTicketType($ticket_type);

                    $attendee = new SummitAttendee;
                    $attendee->setMember($this->member_repository->getById($request->getMemberId()));
                    $attendee->setSummit($request->getSummit());
                    $attendee->addTicket($ticket);
                    $attendee->updateStatus();
                    $this->attendee_repository->add($attendee);

                    return $attendee;
                }
            } catch (ClientException $ex1) {
                if ($ex1->getCode() === 400)
                    throw new EntityNotFoundException('external order does not exists!');
                if ($ex1->getCode() === 403)
                    throw new EntityNotFoundException('external order does not exists!');
                throw $ex1;
            } catch (Exception $ex) {
                throw $ex;
            }

        });
    }

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return File
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addEventAttachment(Summit $summit, $event_id, UploadedFile $file, $max_file_size = 10485760)
    {
        return $this->tx_service->transaction(function () use ($summit, $event_id, $file, $max_file_size) {

            $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'pdf'];

            $event = $summit->getEvent($event_id);

            if (is_null($event)) {
                throw new EntityNotFoundException('event not found on summit!');
            }

            if (!$event instanceof SummitEventWithFile) {
                throw new ValidationException(sprintf("event id %s does not allow attachments!", $event_id));
            }

            if (!in_array($file->extension(), $allowed_extensions)) {
                throw new ValidationException("file does not has a valid extension ('png','jpg','jpeg','gif','pdf').");
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("file exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $attachment = $this->file_uploader->build($file, 'summit-event-attachments', true);
            $event->setAttachment($attachment);

            return $attachment;
        });
    }

    /**
     * @param Summit $summit
     * @param Filter $filter
     * @return SummitScheduleEmptySpot[]
     */
    public function getSummitScheduleEmptySpots
    (
        Summit $summit,
        Filter $filter
    )
    {
        return $this->tx_service->transaction(function () use (
            $summit,
            $filter
        ) {
            $gaps = [];
            $order = new Order([
                OrderElement::buildAscFor("location_id"),
                OrderElement::buildAscFor("start_date"),
            ]);

            // parse locations ids

            if (!$filter->hasFilter('location_id'))
                throw new ValidationException("missing required filter location_id");

            $location_ids = $filter->getFilterCollectionByField('location_id');

            // parse start_date filter
            $start_datetime_filter = $filter->getFilter('start_date');
            if (is_null($start_datetime_filter))
                throw new ValidationException("missing required filter start_date");
            $start_datetime_unix = intval($start_datetime_filter[0]->getValue());
            $start_datetime = new \DateTime("@$start_datetime_unix");
            // parse end_date filter
            $end_datetime_filter = $filter->getFilter('end_date');
            if (is_null($end_datetime_filter))
                throw new ValidationException("missing required filter end_date");
            $end_datetime_unix = intval($end_datetime_filter[0]->getValue());
            $end_datetime = new \DateTime("@$end_datetime_unix");
            // gap size filter

            $gap_size_filter = $filter->getFilter('gap');
            if (is_null($end_datetime_filter))
                throw new ValidationException("missing required filter gap");

            $gap_size = $gap_size_filter[0];

            $summit_time_zone = $summit->getTimeZone();
            $start_datetime->setTimezone($summit_time_zone);
            $end_datetime->setTimezone($summit_time_zone);

            $intervals = IntervalParser::getInterval($start_datetime, $end_datetime);

            foreach ($location_ids as $location_id) {

                foreach ($intervals as $interval) {

                    $events_filter = new Filter();
                    $events_filter->addFilterCondition(FilterParser::buildFilter('published', '==', '1'));
                    $events_filter->addFilterCondition(FilterParser::buildFilter('summit_id', '==', $summit->getId()));
                    $events_filter->addFilterCondition(FilterParser::buildFilter('location_id', '==', intval($location_id)));

                    $events_filter->addFilterCondition(FilterParser::buildFilter('start_date', '<', $interval[1]->getTimestamp()));
                    $events_filter->addFilterCondition(FilterParser::buildFilter('end_date', '>', $interval[0]->getTimestamp()));

                    $paging_response = $this->event_repository->getAllByPage
                    (
                        new PagingInfo(1, PHP_INT_MAX),
                        $events_filter,
                        $order
                    );

                    $gap_start_date = $interval[0];
                    $gap_end_date = clone $gap_start_date;
                    // check published items
                    foreach ($paging_response->getItems() as $event) {

                        while
                        (
                            (
                                $gap_end_date->getTimestamp() + (SummitEvent::MIN_EVENT_MINUTES * 60)
                            )
                            <= $event->getLocalStartDate()->getTimestamp()
                        ) {
                            $max_gap_end_date = clone $gap_end_date;
                            $max_gap_end_date->setTime(23, 59, 59);
                            if ($gap_end_date->getTimestamp() + (SummitEvent::MIN_EVENT_MINUTES * 60) > $max_gap_end_date->getTimestamp()) break;
                            $gap_end_date->add(new DateInterval('PT' . SummitEvent::MIN_EVENT_MINUTES . 'M'));
                        }

                        if ($gap_start_date->getTimestamp() == $gap_end_date->getTimestamp()) {
                            // no gap!
                            $gap_start_date = $event->getLocalEndDate();
                            $gap_end_date = clone $gap_start_date;
                            continue;
                        }

                        // check min gap ...
                        if (self::checkGapCriteria($gap_size, $gap_end_date->diff($gap_start_date)))
                            $gaps[] = new SummitScheduleEmptySpot($location_id, $gap_start_date, $gap_end_date);
                        $gap_start_date = $event->getLocalEndDate();
                        $gap_end_date = clone $gap_start_date;
                    }

                    // check last possible gap ( from last $gap_start_date till $interval[1]

                    if ($gap_start_date < $interval[1]) {
                        // last possible gap
                        if (self::checkGapCriteria($gap_size, $interval[1]->diff($gap_start_date)))
                            $gaps[] = new SummitScheduleEmptySpot($location_id, $gap_start_date, $interval[1]);
                    }
                }
            }

            return $gaps;

        });
    }


    /**
     * @param FilterElement $gap_size_criteria
     * @param DateInterval $interval
     * @return bool
     */
    private static function checkGapCriteria
    (
        FilterElement $gap_size_criteria,
        DateInterval  $interval
    )
    {
        $total_minutes = $interval->days * 24 * 60;
        $total_minutes += $interval->h * 60;
        $total_minutes += $interval->i;

        switch ($gap_size_criteria->getOperator()) {
            case '=':
                {
                    return intval($gap_size_criteria->getValue()) == $total_minutes;
                }
                break;
            case '<':
                {
                    return $total_minutes < intval($gap_size_criteria->getValue());
                }
                break;
            case '>':
                {
                    return $total_minutes > intval($gap_size_criteria->getValue());
                }
                break;
            case '<=':
                {
                    return $total_minutes <= intval($gap_size_criteria->getValue());
                }
                break;
            case '>=':
                {
                    return $total_minutes >= intval($gap_size_criteria->getValue());
                }
                break;
        }
        return false;
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return bool
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function unPublishEvents(Summit $summit, array $data)
    {
        return $this->tx_service->transaction(function () use (
            $summit,
            $data
        ) {
            foreach ($data['events'] as $event_id) {
                $this->unPublishEvent($summit, intval($event_id));
            }

            return true;
        });
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return bool
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateAndPublishEvents(Summit $summit, array $data)
    {
        return $this->tx_service->transaction(function () use (
            $summit,
            $data
        ) {
            foreach ($data['events'] as $event_data) {
                $this->updateEvent($summit, intval($event_data['id']), $event_data);
                $this->publishEvent($summit, intval($event_data['id']), $event_data);
            }

            return true;
        });
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return bool
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateEvents(Summit $summit, array $data)
    {
        return $this->tx_service->transaction(function () use (
            $summit,
            $data
        ) {
            foreach ($data['events'] as $event_data) {
                $this->updateEvent($summit, intval($event_data['id']), $event_data);
            }

            return true;
        });
    }

    /**
     * @param array $data
     * @return Summit
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSummit(array $data)
    {
        return $this->tx_service->transaction(function () use ($data) {

            $name = trim($data['name']);
            $former_summit = $this->summit_repository->getByName($name);
            if (!is_null($former_summit)) {
                throw new ValidationException
                (
                    trans
                    (
                        'validation_errors.SummitService.AddSummit.NameAlreadyExists',
                        ['name' => $name]
                    )
                );
            }

            $slug = $data['slug'] ?? null;
            if (!empty($slug)) {
                // check if exist another summit with that slug

                $old_summit = $this->summit_repository->getBySlug(trim($slug));
                if (!is_null($old_summit)) {
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "Slug %s already belongs to another summit (%s).",
                            $slug,
                            $old_summit->getId()
                        )
                    );
                }
            }

            $registration_slug_prefix = $data['registration_slug_prefix'] ?? null;
            if (!empty($registration_slug_prefix)) {
                // check if exist another summit with that registration slug

                $old_summit =
                    $this->summit_repository->getByRegistrationSlugPrefix(Summit::formatSlug($registration_slug_prefix));
                if (!is_null($old_summit)) {
                    throw new ValidationException(sprintf(
                        "Registration slug prefix %s already belongs to summit (%s).",
                        $registration_slug_prefix, $old_summit->getId()
                    ));
                }
            }

            $summit = SummitFactory::build($data);

            // seed default event types
            foreach ($this->default_event_types_repository->getAll() as $default_event_type) {
                $summit->addEventType($default_event_type->buildType($summit));
            }

            $summit->seedDefaultEmailFlowEvents();

            $summit->seedDefaultAccessLevelTypes();

            SummitBadgeViewType::buildDefaultCardType($summit);

            $this->summit_repository->add($summit);

            return $summit;

        });
    }

    /**
     * @param int $summit_id
     * @param array $data
     * @return Summit
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateSummit($summit_id, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit_id, $data) {

            if (isset($data['name'])) {

                $former_summit = $this->summit_repository->getByName(trim($data['name']));
                if (!is_null($former_summit) && $former_summit->getId() != $summit_id) {
                    throw new ValidationException
                    (
                        trans
                        (
                            'validation_errors.SummitService.updateSummit.NameAlreadyExists',
                            ['name' => $data['name']]
                        )
                    );
                }
            }

            if (isset($data['active'])) {
                $active = boolval($data['active']);
                $active_summit = $this->summit_repository->getActive();
                if ($active && !is_null($active_summit) && $active_summit->getId() != $summit_id) {
                    throw new ValidationException
                    (
                        trans
                        (
                            'validation_errors.SummitService.updateSummit.SummitAlreadyActive',
                            ['active_summit_id' => $active_summit->getId()]
                        )
                    );
                }
            }

            $slug = $data['slug'] ?? null;
            if (!empty($slug)) {
                // check if exist another summit with that slug

                $old_summit = $this->summit_repository->getBySlug(trim($slug));
                if (!is_null($old_summit) && $summit_id != $old_summit->getId()) {
                    throw new ValidationException(sprintf("Slug %s already belongs to another summit (%s)/", $slug, $old_summit->getId()));
                }
            }

            $registration_slug_prefix = $data['registration_slug_prefix'] ?? null;
            if (!empty($registration_slug_prefix)) {
                // check if exist another summit with that registration slug

                $old_summit =
                    $this->summit_repository->getByRegistrationSlugPrefix(Summit::formatSlug($registration_slug_prefix));
                if (!is_null($old_summit) && $summit_id != $old_summit->getId()) {
                    throw new ValidationException(sprintf(
                        "Registration slug prefix %s already belongs to summit (%s).",
                        $registration_slug_prefix, $old_summit->getId()
                    ));
                }
            }

            $summit = $this->summit_repository->getById($summit_id);

            if (is_null($summit)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.SummitService.updateSummit.SummitNotFound',
                        ['summit_id' => $summit_id]
                    )
                );
            }

            $summit = SummitFactory::populate($summit, $data);

            return $summit;
        });
    }

    /**
     * @param int $summit_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSummit($summit_id)
    {
        return $this->tx_service->transaction(function () use ($summit_id) {

            $summit = $this->summit_repository->getById($summit_id);

            if (is_null($summit)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.SummitService.deleteSummit.SummitNotFound',
                        ['summit_id' => $summit_id]
                    )
                );
            }

            Log::debug(sprintf("SummitService::deleteSummit summit_id %s", $summit_id));
            $summit->markAsDeleted();

        });
    }

    /**
     * @param int $current_member_id
     * @param int $speaker_id
     * @param int $presentation_id
     * @return Presentation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSpeaker2Presentation(int $current_member_id, int $speaker_id, int $presentation_id): Presentation
    {
        return $this->tx_service->transaction(function () use ($current_member_id, $speaker_id, $presentation_id) {
            $current_member = $this->member_repository->getById($current_member_id);
            if (is_null($current_member) || !($current_member instanceof Member))
                throw new EntityNotFoundException(sprintf("Member %s not found.", $current_member_id));

            $current_speaker = $this->speaker_repository->getByMember($current_member);
            if (is_null($current_speaker))
                throw new EntityNotFoundException(sprintf("Member %s does not has a speaker profile.", $current_member_id));

            $presentation = $this->event_repository->getById($presentation_id);
            if (is_null($presentation))
                throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

            if (!$presentation->canEdit($current_speaker))
                throw new ValidationException(sprintf("Member %s can not edit presentation %s.",
                    $current_member_id,
                    $presentation_id
                ));

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (!$speaker instanceof PresentationSpeaker)
                throw new EntityNotFoundException(sprintf('Speaker %s not found.', $speaker_id));
            if (!$presentation->isCompleted())
                $presentation->setProgress(Presentation::PHASE_SPEAKERS);

            $presentation->addSpeaker($speaker);

            // check is selection plan is private, if so add moderator to allowed members

            $selection_plan = $presentation->getSelectionPlan();

            if (!is_null($selection_plan) && $selection_plan->isPrivate() && !$selection_plan->containsMember($speaker->getEmail())) {
                $selection_plan->addAllowedMember($speaker->getEmail());
            }

            if ($speaker->getMemberId() != $presentation->getCreatedById())
                PresentationSpeakerNotificationEmail::dispatch($speaker, $presentation);

            return $presentation;
        });
    }

    /**
     * @param int $current_member_id
     * @param int $speaker_id
     * @param int $presentation_id
     * @return Presentation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removeSpeakerFromPresentation(int $current_member_id, int $speaker_id, int $presentation_id): Presentation
    {
        return $this->tx_service->transaction(function () use ($current_member_id, $speaker_id, $presentation_id) {

            $current_member = $this->member_repository->getById($current_member_id);
            if (is_null($current_member) || !($current_member instanceof Member))
                throw new EntityNotFoundException(sprintf("Member %s not found.", $current_member_id));

            $current_speaker = $this->speaker_repository->getByMember($current_member);
            if (is_null($current_speaker))
                throw new EntityNotFoundException(sprintf("Member %s does not has a speaker profile.", $current_member_id));

            $presentation = $this->event_repository->getById($presentation_id);
            if (is_null($presentation))
                throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

            if (!$presentation->canEdit($current_speaker))
                throw new ValidationException(sprintf("Member %s can not edit presentation %s.",
                    $current_member_id,
                    $presentation_id
                ));

            $speaker = $this->speaker_repository->getById(intval($speaker_id));
            if (!$speaker instanceof PresentationSpeaker)
                throw new EntityNotFoundException(sprintf('Speaker %s not found.', $speaker_id));

            if (!$presentation->isCompleted())
                $presentation->setProgress(Presentation::PHASE_SPEAKERS);

            $presentation->removeSpeaker($speaker);

            return $presentation;
        });
    }

    /**
     * @param int $current_member_id
     * @param int $speaker_id
     * @param int $presentation_id
     * @return Presentation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addModerator2Presentation(int $current_member_id, int $speaker_id, int $presentation_id): Presentation
    {
        return $this->tx_service->transaction(function () use ($current_member_id, $speaker_id, $presentation_id) {
            $current_member = $this->member_repository->getById($current_member_id);
            if (is_null($current_member) || !($current_member instanceof Member))
                throw new EntityNotFoundException(sprintf("Member %s not found.", $current_member_id));

            $current_speaker = $this->speaker_repository->getByMember($current_member);
            if (is_null($current_speaker))
                throw new EntityNotFoundException(sprintf("Member %s does not has a speaker profile.", $current_member_id));

            $presentation = $this->event_repository->getById($presentation_id);
            if (is_null($presentation))
                throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

            if (!$presentation->canEdit($current_speaker))
                throw new ValidationException(sprintf("Member %s can not edit presentation %s.",
                    $current_member_id,
                    $presentation_id
                ));

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker) || !($speaker instanceof PresentationSpeaker))
                throw new EntityNotFoundException(sprintf('Speaker %s not found.', $speaker_id));

            if (!$presentation->isCompleted())
                $presentation->setProgress(Presentation::PHASE_SPEAKERS);

            $presentation->setModerator($speaker);

            // check is selection plan is private, if so add moderator to allowed members

            $selection_plan = $presentation->getSelectionPlan();

            if (!is_null($selection_plan) && $selection_plan->isPrivate() && !$selection_plan->containsMember($speaker->getEmail())) {
                $selection_plan->addAllowedMember($speaker->getEmail());
            }

            if ($speaker->getMemberId() != $presentation->getCreatedById())
                PresentationModeratorNotificationEmail::dispatch($speaker, $presentation);

            return $presentation;
        });
    }

    /**
     * @param int $current_member_id
     * @param int $speaker_id
     * @param int $presentation_id
     * @return Presentation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removeModeratorFromPresentation(int $current_member_id, int $speaker_id, int $presentation_id): Presentation
    {
        return $this->tx_service->transaction(function () use ($current_member_id, $speaker_id, $presentation_id) {

            $current_member = $this->member_repository->getById($current_member_id);
            if (is_null($current_member) || !($current_member instanceof Member))
                throw new EntityNotFoundException(sprintf("Member %s not found.", $current_member_id));

            $current_speaker = $this->speaker_repository->getByMember($current_member);
            if (is_null($current_speaker))
                throw new EntityNotFoundException(sprintf("Member %s does not has a speaker profile.", $current_member_id));

            $presentation = $this->event_repository->getById($presentation_id);
            if (is_null($presentation))
                throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

            if (!$presentation instanceof Presentation)
                throw new EntityNotFoundException(sprintf("Presentation %s not found.", $presentation_id));

            if (!$presentation->canEdit($current_speaker))
                throw new ValidationException(sprintf("Member %s can not edit presentation %s.",
                    $current_member_id,
                    $presentation_id
                ));

            $speaker = $this->speaker_repository->getById(intval($speaker_id));
            if (is_null($speaker) || !($speaker instanceof PresentationSpeaker))
                throw new EntityNotFoundException(sprintf('Speaker %s not found.', $speaker_id));

            if (!$presentation->isCompleted())
                $presentation->setProgress(Presentation::PHASE_SPEAKERS);

            $presentation->unsetModerator();

            return $presentation;
        });
    }

    /**
     * @param Summit $summit
     * @param int $event_id
     * @return SummitEvent
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function cloneEvent(Summit $summit, int $event_id): SummitEvent
    {
        return $this->tx_service->transaction(function () use ($summit, $event_id) {

            $event = $this->event_repository->getById($event_id);
            if (is_null($event))
                throw new EntityNotFoundException(sprintf("Event %s not found.", $event_id));

            $event_clone = SummitEventFactory::build($event->getType(), $summit);

            $event_clone->setTitle($event->getTitle());
            $event_clone->setAbstract($event->getAbstract());
            $event_clone->setAllowFeedBack($event->getAllowFeedback());
            $event_clone->setSocialSummary($event->getSocialSummary());
            $event_clone->setHeadCount($event->getHeadCount());
            if ($event->hasRSVPTemplate()) {
                $event_clone->setRSVPTemplate($event->getRSVPTemplate());
            }
            $event_clone->setRSVPLink($event->getRSVPLink());
            $event_clone->setRSVPMaxUserNumber($event->getRSVPMaxUserNumber());
            $event_clone->setRSVPMaxUserWaitListNumber($event->getRSVPMaxUserWaitListNumber());

            $occupancy = $event->getOccupancy();
            if ($occupancy != null) {
                $event_clone->setOccupancy($occupancy);
            }

            $external_id = $event->getExternalId();
            if ($external_id != null) {
                $event_clone->setExternalId($external_id);
            }

            $location = $event->getLocation();
            if ($location != null) {
                $event_clone->setLocation($location);
            }

            $local_start_date = $event->getLocalStartDate();
            if ($local_start_date != null) {
                $event_clone->setStartDate($local_start_date);
            }

            $local_end_date = $event->getLocalEndDate();
            if ($local_end_date != null) {
                $event_clone->setEndDate($local_end_date);
            }

            $category = $event->getCategory();
            if ($category != null) {
                $event_clone->setCategory($category);
            }

            $event_clone->setStreamingUrl($event->getStreamingUrl());

            $etherpad_link = $event->getEtherpadLink();
            if ($etherpad_link != null) {
                $event_clone->setEtherpadLink($etherpad_link);
            }

            $meeting_url = $event->getMeetingUrl();
            if ($meeting_url != null) {
                $event_clone->setMeetingUrl($meeting_url);
            }

            if ($event->hasImage()) {
                $event_clone->setImage($event->getImage());
            }

            $mux_playback_id = $event->getMuxPlaybackId();
            if ($mux_playback_id != null) {
                $event_clone->setMuxPlaybackId($mux_playback_id);
            }

            $mux_asset_id = $event->getMuxAssetId();
            if ($mux_asset_id != null) {
                $event_clone->setMuxAssetId($mux_asset_id);
            }

            $level = $event->getLevel();
            if ($level != null) {
                $event_clone->setLevel($level);
            }

            $current_user = ResourceServerContext::getCurrentUser(false);

            $event_clone->setCreatedBy($current_user);
            $event_clone->setUpdatedBy($current_user);
            $event_clone->setStreamIsSecure($event->IsSecureStream());

            foreach ($event->getSponsors() as $sponsor) {
                $event_clone->addSponsor($sponsor);
            }

            foreach ($event->getTags() as $tag) {
                $event_clone->addTag($tag);
            }

            // check if SummitEventWithFile

            if ($event instanceof SummitEventWithFile && $event->hasAttachment()) {
                $event_clone->setAttachment($event->getAttachment());
            }

            if ($event instanceof SummitGroupEvent) {
                $group_ids = $event->getGroupsIds();

                foreach ($group_ids as $group_id) {
                    $group = $this->group_repository->getById(intval($group_id));
                    if (is_null($group)) throw new EntityNotFoundException(sprintf('group id %s', $group_id));
                    $event_clone->addGroup($group);
                }
            }

            if ($event_clone instanceof Presentation && $event instanceof Presentation) {
                $event_clone->setStatus($event->getStatus());
                $event_clone->setProgress($event->getProgress());
                $event_clone->setProblemAddressed($event->getProblemAddressed());
                $event_clone->setAttendeesExpectedLearnt($event->getAttendeesExpectedLearnt());
                $event_clone->setToRecord($event->getToRecord());
                $event_clone->setAttendingMedia($event->getAttendingMedia());
                $event_clone->setSlug($event->getSlug());
                $event_clone->setSelectionPlan($event->getSelectionPlan());

                $last_order = $this->event_repository->getLastPresentationOrderBySummit($summit->getId());
                if ($last_order > 0) {
                    $event_clone->setCustomOrder($last_order + 1);
                }

                $moderator = $event->getModerator();
                if ($moderator != null) {
                    $event_clone->setModerator($moderator);
                }

                $disclaimer_accepted_date = $event->getDisclaimerAcceptedDate();
                if ($disclaimer_accepted_date != null) {
                    $event_clone->setDisclaimerAcceptedDate($disclaimer_accepted_date);
                }

                foreach ($event->getSpeakers() as $speaker) {
                    $event_clone->addSpeaker($speaker);
                }

                foreach ($event->getPresentationActions() as $action) {
                    $event_clone->setActionByType($action->getType());
                }

                foreach ($event->getMaterials() as $material) {
                    $material_clone = $material->clone();
                    if ($material_clone != null) {
                        $event_clone->addMaterial($material_clone);
                    }
                }
            }
            $this->event_repository->add($event_clone);

            return $event_clone;
        });
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitBookableVenueRoomAttributeType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addBookableRoomAttribute(Summit $summit, array $payload): SummitBookableVenueRoomAttributeType
    {
        return $this->tx_service->transaction(function () use ($summit, $payload) {

            $type_name = trim($payload['type']);
            $former_type = $summit->getBookableAttributeTypeByTypeName($type_name);
            if (!is_null($former_type))
                throw new ValidationException(sprintf("bookable room attr type %s already exists on summit %s", $type_name, $summit->getId()));

            $type = new SummitBookableVenueRoomAttributeType();
            $type->setType($type_name);

            $summit->addMeetingBookingRoomAllowedAttribute($type);

            return $type;

        });
    }

    /**
     * @param Summit $summit
     * @param int $type_id
     * @param array $payload
     * @return SummitBookableVenueRoomAttributeType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateBookableRoomAttribute(Summit $summit, int $type_id, array $payload): SummitBookableVenueRoomAttributeType
    {
        return $this->tx_service->transaction(function () use ($summit, $type_id, $payload) {
            $type = $summit->getBookableAttributeTypeById($type_id);
            if (is_null($type))
                throw new EntityNotFoundException();

            $type_name = trim($payload['type']);
            $former_type = $summit->getBookableAttributeTypeByTypeName($type_name);
            if (!is_null($former_type) && $type_id != $former_type->getId())
                throw new ValidationException(sprintf("bookable room attr type %s already exists on summit %s", $type_name, $summit->getId()));

            $type->setType($type_name);

            return $type;

        });
    }

    /**
     * @param Summit $summit
     * @param int $type_id
     * @return void
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteBookableRoomAttribute(Summit $summit, int $type_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $type_id) {
            $type = $summit->getBookableAttributeTypeById($type_id);
            if (is_null($type))
                throw new EntityNotFoundException();

            $summit->removeMeetingBookingRoomAllowedAttribute($type);
        });
    }

    /**
     * @param Summit $summit
     * @param int $type_id
     * @param array $payload
     * @return SummitBookableVenueRoomAttributeValue
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addBookableRoomAttributeValue(Summit $summit, int $type_id, array $payload): SummitBookableVenueRoomAttributeValue
    {
        return $this->tx_service->transaction(function () use ($summit, $type_id, $payload) {

            $type = $summit->getBookableAttributeTypeById($type_id);
            if (is_null($type))
                throw new EntityNotFoundException();

            $value_name = trim($payload['value']);
            $former_value = $type->getValueByValue($value_name);
            if (!is_null($former_value))
                throw new ValidationException(sprintf("bookable room attr value %s already exists on summit %s", $value_name, $summit->getId()));

            $value = new SummitBookableVenueRoomAttributeValue();
            $value->setValue($value_name);
            $type->addValue($value);

            return $value;

        });
    }

    /**
     * @param Summit $summit
     * @param int $type_id
     * @param int $value_id
     * @param array $payload
     * @return SummitBookableVenueRoomAttributeValue
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateBookableRoomAttributeValue(Summit $summit, int $type_id, int $value_id, array $payload): SummitBookableVenueRoomAttributeValue
    {
        return $this->tx_service->transaction(function () use ($summit, $type_id, $value_id, $payload) {

            $type = $summit->getBookableAttributeTypeById($type_id);
            if (is_null($type))
                throw new EntityNotFoundException();

            $value = $type->getValueById($value_id);
            if (is_null($value))
                throw new EntityNotFoundException();

            $value_name = trim($payload['value']);
            $former_value = $type->getValueByValue($value_name);
            if (!is_null($former_value) && $value_id != $former_value->getId())
                throw new ValidationException(sprintf("bookable room attr value %s already exists on summit %s", $value_name, $summit->getId()));

            $value->setValue($value_name);

            return $value;

        });
    }

    /**
     * @param Summit $summit
     * @param int $type_id
     * @param int $value_id
     * @return void
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteBookableRoomAttributeValue(Summit $summit, int $type_id, int $value_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $type_id, $value_id) {

            $type = $summit->getBookableAttributeTypeById($type_id);
            if (is_null($type))
                throw new EntityNotFoundException();

            $value = $type->getValueById($value_id);
            if (is_null($value))
                throw new EntityNotFoundException();

            $type->removeValue($value);

        });
    }

    /**
     * @param int $summit_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return File
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSummitLogo(int $summit_id, UploadedFile $file, $max_file_size = 10485760)
    {
        return $this->tx_service->transaction(function () use ($summit_id, $file, $max_file_size) {

            $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'jfif'];

            $summit = $this->summit_repository->getById($summit_id);

            if (!$summit instanceof Summit) {
                throw new EntityNotFoundException('Summit not found.');
            }

            if (!in_array($file->extension(), $allowed_extensions)) {
                throw new ValidationException(sprintf("file does not has a valid extension (%s).", implode(",", $allowed_extensions)));
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("file exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $photo = $this->file_uploader->build($file, sprintf('summits/%s', $summit->getId()), true);
            $summit->setLogo($photo);

            return $photo;
        });
    }

    /**
     * @param int $summit_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteSummitLogo(int $summit_id): void
    {
        $this->tx_service->transaction(function () use ($summit_id) {

            $summit = $this->summit_repository->getById($summit_id);

            if (!$summit instanceof Summit) {
                throw new EntityNotFoundException('Summit not found.');
            }

            $summit->clearLogo();
        });
    }

    /**
     * @param int $summit_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return File
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSummitSecondaryLogo(int $summit_id, UploadedFile $file, $max_file_size = 10485760)
    {
        return $this->tx_service->transaction(function () use ($summit_id, $file, $max_file_size) {

            $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'jfif'];

            $summit = $this->summit_repository->getById($summit_id);

            if (!$summit instanceof Summit) {
                throw new EntityNotFoundException('Summit not found.');
            }

            if (!in_array($file->extension(), $allowed_extensions)) {
                throw new ValidationException(sprintf("file does not has a valid extension (%s).", implode(",", $allowed_extensions)));
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("file exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $photo = $this->file_uploader->build($file, sprintf('summits/%s', $summit->getId()), true);
            $summit->setSecondaryLogo($photo);

            return $photo;
        });
    }

    /**
     * @param int $summit_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteSummitSecondaryLogo(int $summit_id): void
    {
        $this->tx_service->transaction(function () use ($summit_id) {

            $summit = $this->summit_repository->getById($summit_id);

            if (!$summit instanceof Summit) {
                throw new EntityNotFoundException('Summit not found.');
            }

            $summit->clearSecondaryLogo();
        });
    }

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @param array $data
     * @return RSVP
     * @throws Exception
     */
    public function addRSVP(Summit $summit, Member $member, int $event_id, array $data): RSVP
    {
        $rsvp = $this->tx_service->transaction(function () use ($summit, $member, $event_id, $data) {

            $event = $this->event_repository->getByIdExclusiveLock($event_id);

            if (is_null($event) || !$event instanceof SummitEvent) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            if ($event->getSummitId() != $summit->getId()) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            if (!Summit::allowToSee($event, $member))
                throw new EntityNotFoundException('Event not found on summit.');

            if (!$event->hasRSVPTemplate()) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            // add to schedule the RSVP event
            if (!$member->isOnSchedule($event)) {
                $this->addEventToMemberSchedule($summit, $member, $event_id, false);
            }

            $old_rsvp = $member->getRsvpByEvent($event_id);

            if (!is_null($old_rsvp))
                throw new ValidationException
                (
                    sprintf
                    (
                        "Member %s already submitted an rsvp for event %s on summit %s.",
                        $member->getId(),
                        $event_id,
                        $summit->getId()
                    )
                );

            // create RSVP

            return SummitRSVPFactory::build($event, $member, $data);
        });

        Event::dispatch(new RSVPCreated($rsvp));

        return $rsvp;
    }

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @param array $data
     * @return RSVP
     * @throws Exception
     */
    public function updateRSVP(Summit $summit, Member $member, int $event_id, array $data): RSVP
    {
        return $this->tx_service->transaction(function () use ($summit, $member, $event_id, $data) {

            $event = $this->event_repository->getByIdExclusiveLock($event_id);

            if (is_null($event) || !$event instanceof SummitEvent) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            if ($event->getSummitId() != $summit->getId()) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            if (!Summit::allowToSee($event, $member))
                throw new EntityNotFoundException('Event not found on summit.');

            if (!$event->hasRSVPTemplate()) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            // add to schedule the RSVP event
            if (!$member->isOnSchedule($event)) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            $rsvp = $member->getRsvpByEvent($event->getId());

            if (is_null($rsvp))
                throw new ValidationException
                (
                    sprintf
                    (
                        "Member %s did not submitted an rsvp for event %s on summit %s.",
                        $member->getId(),
                        $event_id,
                        $summit->getId()
                    )
                );

            // update RSVP

            $rsvp = SummitRSVPFactory::populate($rsvp, $event, $member, $data);

            Event::dispatch(new RSVPUpdated($rsvp));

            return $rsvp;
        });
    }

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @return bool|mixed
     * @throws Exception
     */
    public function unRSVPEvent(Summit $summit, Member $member, int $event_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $member, $event_id) {

            $event = $this->event_repository->getByIdExclusiveLock($event_id);

            if (is_null($event) || !$event instanceof SummitEvent) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            if ($event->getSummitId() != $summit->getId()) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            if (!Summit::allowToSee($event, $member))
                throw new EntityNotFoundException('Event not found on summit.');

            $rsvp = $member->getRsvpByEvent($event_id);

            if (is_null($rsvp))
                throw new ValidationException(sprintf("RSVP for event id %s does not exist for your member.", $event_id));

            $this->rsvp_repository->delete($rsvp);

            $this->removeEventFromMemberSchedule($summit, $member, $event_id, false);

            return true;
        });
    }

    /**
     * @param Summit $summit
     * @param Member $member
     * @return PersonalCalendarShareInfo|null
     * @throws Exception
     */
    public function createScheduleShareableLink(Summit $summit, Member $member): ?PersonalCalendarShareInfo
    {
        return $this->tx_service->transaction(function () use ($summit, $member) {
            return $member->createScheduleShareableLink($summit);
        });
    }

    /**
     * @param Summit $summit
     * @param Member $member
     * @return PersonalCalendarShareInfo|null
     * @throws Exception
     */
    public function revokeScheduleShareableLink(Summit $summit, Member $member): ?PersonalCalendarShareInfo
    {
        return $this->tx_service->transaction(function () use ($summit, $member) {
            $link = $member->getScheduleShareableLinkBy($summit);
            if (is_null($link)) {
                throw new EntityNotFoundException("Schedule shareable link not found for member.");
            }
            $link->revoke();
            return $link;
        });
    }

    /**
     * @param Summit $summit
     * @param string $cid
     * @return string
     * @throws Exception
     */
    public function buildICSFeed(Summit $summit, string $cid): string
    {

        return $this->tx_service->transaction(function () use ($summit, $cid) {
            $link = $summit->getScheduleShareableLinkById($cid);
            if (is_null($link)) {
                throw new EntityNotFoundException("Schedule shareable link not found for member.");
            }
            $owner = $link->getOwner();
            $timeZone = $summit->getTimeZone();

            $vCalendar = ICalTimeZoneBuilder::build($timeZone, $summit->getName(), true);
            foreach ($owner->getScheduleBySummit($summit) as $scheduled) {
                $summitEvent = $scheduled->getEvent();
                $local_start_time = new DateTime($summitEvent->getStartDateNice(), $timeZone);
                $local_end_time = new DateTime($summitEvent->getEndDateNice(), $timeZone);
                $vEvent = new \Eluceo\iCal\Component\Event($summitEvent->getId());

                $vEvent
                    ->setCreated(new DateTime())
                    ->setDtStart($local_start_time)
                    ->setDtEnd($local_end_time)
                    ->setNoTime(false)
                    ->setSummary($summitEvent->getTitle())
                    ->setDescription(strip_tags($summitEvent->getAbstract()))
                    ->setDescriptionHTML($summitEvent->getAbstract());

                if ($timeZone->getName() == 'UTC') {
                    $vEvent->setUseUtc(true)
                        ->setUseTimezone(false);
                } else {
                    $vEvent->setUseUtc(false)
                        ->setUseTimezone(true);
                }

                if ($summitEvent->hasLocation()) {
                    $location = $summitEvent->getLocation();
                    $geo = null;
                    if ($location instanceof SummitGeoLocatedLocation) {
                        $geo = sprintf("%s;%s", $location->getLat(), $location->getLng());
                    }
                    $vEvent->setLocation($location->getName(), $location->getName(), $geo);
                }

                $vCalendar->addComponent($vEvent);
            }

            return $vCalendar->render();

        });
    }

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param array $data
     * @return void`
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function shareEventByEmail(Summit $summit, int $event_id, array $data): void
    {

        $this->tx_service->transaction(function () use ($summit, $event_id, $data) {

            $event = $summit->getScheduleEvent($event_id);
            if (is_null($event)) {
                throw new EntityNotFoundException(sprintf("Event %s not found.", $event_id));
            }

            $event_uri = $data['event_uri'] ?? null;

            if (empty($event_uri)) {
                Log::debug("event_uri not set on payload. trying to get from default one (summit)");
                $default_event_uri = $summit->getScheduleDefaultEventDetailUrl();
                if (!empty($default_event_uri)) {
                    Log::debug("default_event_uri set at summit level using it.");
                    $event_uri = str_replace(":event_id", $event_id, $default_event_uri);
                }
            }

            if (empty($event_uri)) {
                throw new ValidationException(sprintf("Property event_url is empty."));
            }

            ShareEventEmail::dispatch(
                trim($data['from']),
                trim($data['to']),
                $event_uri,
                $event
            );
        });
    }


    public function calculateFeedbackAverageForOngoingSummits(): void
    {
        $ongoing_summits = $this->tx_service->transaction(function () {
            return $this->summit_repository->getOnGoing();
        });

        foreach ($ongoing_summits as $summit) {

            Log::debug(sprintf("SummitService::calculateFeedbackAverageForOngoingSummits processing summit %s", $summit->getId()));

            $event_ids = $this->tx_service->transaction(function () use ($summit) {
                return $summit->getScheduleEventsIds();
            });

            foreach ($event_ids as $event_id) {
                $event_id = $event_id['id'];
                $this->tx_service->transaction(function () use ($event_id) {
                    try {
                        Log::debug(sprintf("SummitService::calculateFeedbackAverageForOngoingSummits processing event %s", $event_id));
                        $event = $this->event_repository->getById($event_id);
                        if (is_null($event) || !$event instanceof SummitEvent) {
                            Log::debug(sprintf("SummitService::calculateFeedbackAverageForOngoingSummits event %s not found", $event_id));
                            return;
                        }

                        $rate_sum = 0;
                        $rate_count = 0;
                        foreach ($event->getFeedback() as $feedback) {
                            $rate_count++;
                            $rate_sum = $rate_sum + $feedback->getRate();
                        }

                        $avg_rate = ($rate_count > 0) ? ($rate_sum / $rate_count) : 0;
                        $avg_rate = round($avg_rate, 2);
                        $old_avg_rate = $event->getAvgFeedbackRate();
                        Log::debug(sprintf("SummitService::calculateFeedbackAverageForOngoingSummits new avg rate %s - old avg rate %s - for event id %s", $avg_rate, $old_avg_rate, $event->getId()));
                        $event->setAvgFeedbackRate($avg_rate);
                    } catch (Exception $ex) {
                        Log::error($ex);
                    }
                });
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function addEventImage(Summit $summit, $event_id, UploadedFile $file, $max_file_size = 10485760)
    {
        return $this->tx_service->transaction(function () use ($summit, $event_id, $file, $max_file_size) {

            $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'pdf'];

            $event = $summit->getEvent($event_id);

            if (is_null($event) || !$event instanceof SummitEvent) {
                throw new EntityNotFoundException('event not found on summit!');
            }

            if (!in_array($file->extension(), $allowed_extensions)) {
                throw new ValidationException("file does not has a valid extension ('png','jpg','jpeg','gif','pdf').");
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("file exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $file = $this->file_uploader->build($file, 'summit-event-images', true);
            $event->setImage($file);

            return $file;
        });
    }

    /**
     * @inheritDoc
     */
    public function removeEventImage(Summit $summit, $event_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $event_id) {

            $event = $summit->getEvent($event_id);

            if (is_null($event) || !$event instanceof SummitEvent) {
                throw new EntityNotFoundException('event not found on summit!');
            }
            $event->clearImage();

        });
    }

    /**
     * @param int $summit_id
     * @param int $days
     * @param bool $negative
     * @param bool $check_summit_ends
     * @throws Exception
     */
    public function advanceSummit(int $summit_id, int $days, bool $negative = false, $check_summit_ends = true): void
    {
        $interval = new DateInterval(sprintf("P%sD", $days));

        Log::debug(sprintf("SummitService::advanceSummit summit_id %s days %s negative %s check_summit_ends %s", $summit_id, $days, $negative, $check_summit_ends));

        $events_ids = $this->tx_service->transaction(function () use ($summit_id, $interval, $negative, $check_summit_ends) {

            $summit = $this->summit_repository->getByIdExclusiveLock($summit_id);

            if (is_null($summit) || !$summit instanceof Summit)
                throw new EntityNotFoundException("Summit not found");
            if ($check_summit_ends && !$summit->isEnded()) {
                Log::debug(sprintf("SummitService::advanceSummit summit %s  has not ended !.", $summit_id));
                return [];
            }

            // summit period
            $summitBeginDate = $summit->getBeginDate();
            $summitEndDate = $summit->getEndDate();

            if (!is_null($summitBeginDate)) {
                Log::debug(sprintf("SummitService::advanceSummit Current Summit begin date for summit %s is %s", $summit_id, $summitBeginDate->format("Ymd His")));
                if ($negative) {
                    $summit->setRawBeginDate(clone $summitBeginDate->sub($interval));
                } else {
                    $summit->setRawBeginDate(clone $summitBeginDate->add($interval));
                }
                Log::debug(sprintf("SummitService::advanceSummit New Summit begin date for summit %s is %s", $summit_id, $summit->getBeginDate()->format("Ymd His")));
            }

            if (!is_null($summitEndDate)) {
                Log::debug(sprintf("SummitService::advanceSummit Current Summit end date for summit %s is %s", $summit_id, $summitEndDate->format("Ymd His")));
                if ($negative) {
                    $summit->setRawEndDate(clone $summitEndDate->sub($interval));
                } else {
                    $summit->setRawEndDate(clone $summitEndDate->add($interval));
                }
                Log::debug(sprintf("SummitService::advanceSummit New Summit end date for summit %s is %s", $summit_id, $summit->getEndDate()->format("Ymd His")));
            }

            // registration period

            $summitRegistrationBeginDate = $summit->getRegistrationBeginDate();
            $summitRegistrationEndDate = $summit->getRegistrationEndDate();

            if (!is_null($summitRegistrationBeginDate)) {
                Log::debug(sprintf("SummitService::advanceSummit Current Summit registration begin date for summit %s is %s", $summit_id, $summitRegistrationBeginDate->format("Ymd His")));
                if ($negative) {
                    $summit->setRawRegistrationBeginDate(clone $summitRegistrationBeginDate->add($interval));
                } else {
                    $summit->setRawRegistrationBeginDate(clone $summitRegistrationBeginDate->sub($interval));
                }
                Log::debug(sprintf("SummitService::advanceSummit New Summit registration begin date for summit %s is %s", $summit_id, $summit->getRegistrationBeginDate()->format("Ymd His")));
            }

            if (!is_null($summitRegistrationEndDate)) {
                Log::debug(sprintf("SummitService::advanceSummit Current Summit registration end date for summit %s is %s", $summit_id, $summitRegistrationEndDate->format("Ymd His")));
                $summit->setRawRegistrationEndDate(clone $summitRegistrationEndDate->add($interval));
                Log::debug(sprintf("SummitService::advanceSummit New Summit registration end date for summit %s is %s", $summit_id, $summit->getRegistrationEndDate()->format("Ymd His")));
            }

            // random dates

            $summitReassignTicketTillDate = $summit->getReassignTicketTillDate();
            if (!is_null($summitReassignTicketTillDate)) {
                if ($negative) {
                    $summit->setRawReassignTicketTillDate(clone $summitReassignTicketTillDate->sub($interval));
                } else {
                    $summit->setRawReassignTicketTillDate(clone $summitReassignTicketTillDate->add($interval));
                }
            }

            $summitStartShowingVenuesDate = $summit->getStartShowingVenuesDate();
            if (!is_null($summitStartShowingVenuesDate)) {
                if ($negative) {
                    $summit->setRawStartShowingVenuesDate(clone $summitStartShowingVenuesDate->sub($interval));
                } else {
                    $summit->setRawStartShowingVenuesDate(clone $summitStartShowingVenuesDate->add($interval));
                }
            }

            $summitScheduleDefaultStartDate = $summit->getScheduleDefaultStartDate();
            if (!is_null($summitScheduleDefaultStartDate)) {
                if ($negative) {
                    $summit->setRawScheduleDefaultStartDate(clone $summitScheduleDefaultStartDate->sub($interval));
                } else {
                    $summit->setRawScheduleDefaultStartDate(clone $summitScheduleDefaultStartDate->add($interval));
                }
            }

            $summitBeginAllowBookingDate = $summit->getBeginAllowBookingDate();
            if (!is_null($summitBeginAllowBookingDate)) {
                if ($negative) {
                    $summit->setRawBeginAllowBookingDate(clone $summitBeginAllowBookingDate->sub($interval));
                } else {
                    $summit->setRawBeginAllowBookingDate(clone $summitBeginAllowBookingDate->add($interval));
                }
            }

            $summitEndAllowBookingDate = $summit->getEndAllowBookingDate();
            if (!is_null($summitEndAllowBookingDate)) {
                if ($negative) {
                    $summit->setRawEndAllowBookingDate(clone $summitEndAllowBookingDate->sub($interval));
                } else {
                    $summit->setRawEndAllowBookingDate(clone $summitEndAllowBookingDate->add($interval));
                }

            }

            // schedule
            $event_ids = [];
            foreach ($summit->getPublishedEvents() as $event) {
                if (!$event instanceof SummitEvent) continue;
                $event_ids[] = $event->getId();
            }

            return $event_ids;
        });

        foreach ($events_ids as $event_id) {
            $this->tx_service->transaction(function () use ($summit_id, $event_id, $interval, $negative) {
                $event = $this->event_repository->getByIdExclusiveLock($event_id);
                if (!$event instanceof SummitEvent) return;

                $eventBeginDate = $event->getStartDate();
                $eventEndDate = $event->getEndDate();

                if (is_null($eventBeginDate) || is_null($eventEndDate)) {
                    Log::debug(sprintf("SummitService::advanceSummit summit id %s event id %s ( has not set dates but is published!), skipping it", $summit_id, $event->getId()));
                    return;
                }
                Log::debug(sprintf("SummitService::advanceSummit summit id %s event id %s current start date %s", $summit_id, $event->getId(), $eventBeginDate->format("Ymd His")));
                if ($negative) {
                    $event->setRawStartDate(clone $eventBeginDate->sub($interval));
                } else {
                    $event->setRawStartDate(clone $eventBeginDate->add($interval));
                }
                Log::debug(sprintf("SummitService::advanceSummit summit id %s event id %s new start date %s", $summit_id, $event->getId(), $event->getStartDate()->format("Ymd His")));

                Log::debug(sprintf("SummitService::advanceSummit summit id %s event id %s current end date %s", $summit_id, $event->getId(), $eventEndDate->format("Ymd His")));
                if ($negative) {
                    $event->setRawEndDate(clone $eventEndDate->sub($interval));
                } else {
                    $event->setRawEndDate(clone $eventEndDate->add($interval));
                }

                Log::debug(sprintf("SummitService::advanceSummit summit id %s event id %s new end date %s", $summit_id, $event->getId(), $event->getEndDate()->format("Ymd His")));

                // set duration
                $cloneEndDate = clone $event->getEndDate();
                $cloneStartDate = clone $event->getStartDate();
                $event->setDuration(($cloneEndDate->getTimestamp() - $cloneStartDate->getTimestamp()), true);
            });
        }
    }

    /**
     * @param Summit $summit
     * @param UploadedFile $csv_file
     * @param array $payload
     * @throws ValidationException
     */
    public function importEventData(Summit $summit, UploadedFile $csv_file, array $payload): void
    {
        Log::debug(sprintf("SummitService::importEventData summit %s", $summit->getId()));

        $allowed_extensions = ['txt','csv'];

        if (!in_array($csv_file->extension(), $allowed_extensions)) {
            Log::warning
            (
                sprintf
                (
                    "SummitService::importEventData %s is not allowed extension",
                    $csv_file->extension()
                )
            );
            throw new ValidationException("file does not has a valid extension ('csv').");
        }

        $real_path = $csv_file->getRealPath();
        $filename = pathinfo($real_path);
        $filename = $filename['filename'] ?? sprintf("file%s", time());
        $basename = sprintf("%s_%s.csv", $filename, time());

        Log::debug(sprintf("SummitService::importEventData trying to read file data from %s", $real_path));
        $csv_data = \Illuminate\Support\Facades\File::get($real_path);

        if (empty($csv_data)) {
            Log::warning(sprintf("SummitService::importEventData file %s has empty content.", $real_path));
            throw new ValidationException("file content is empty!");
        }

        // upload to distribute storage
        Log::debug(sprintf("SummitService::importEventData uploading file %s to storage %s", $basename, $this->upload_strategy->getDriver()));

        $this->upload_strategy->save($csv_file, "tmp/events_imports", $basename);

        $csv = Reader::createFromString($csv_data);
        $csv->setHeaderOffset(0);

        $header = $csv->getHeader(); //returns the CSV header record

        Log::debug(sprintf("SummitService::importEventData validating header %s", json_encode($header)));

        // check needed columns (headers names)
        /*
            columns (min)
            * title
            * description
            * type_id (int) or type (string type name)
            * track_id (int) or track ( string track name)
         */

        if (!in_array("id", $header)) {
            // validate format with col names
            if (!in_array("title", $header)) {
                Log::warning("SummitService::importEventData title column is missing.");
                throw new ValidationException('title column missing');
            }

            if (!in_array("description", $header)) {
                Log::warning("SummitService::importEventData description column is missing.");
                throw new ValidationException('description column missing');
            }

            $type_data_present = in_array("type_id", $header) ||
                in_array("type", $header);

            if (!$type_data_present) {
                Log::warning("SummitService::importEventData type_id / type  column is missing.");
                throw new ValidationException('type_id / type column missing');
            }

            $track_present = in_array("track_id", $header) ||
                in_array("track", $header);

            if (!$track_present) {
                Log::warning("SummitService::importEventData track_id / track column is missing.");
                throw new ValidationException('track_id / track column missing');
            }
        }

        ProcessEventDataImport::dispatch($summit->getId(), $basename, $payload);
    }

    /**
     * @param int $summit_id
     * @param string $filename
     * @param bool $send_speaker_email
     * @throws ValidationException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function processEventData(int $summit_id, string $filename, bool $send_speaker_email): void
    {
        Log::debug(sprintf("SummitService::processEventData summit %s filename %s", $summit_id, $filename));
        $path = sprintf("tmp/events_imports/%s", $filename);

        if (!$this->download_strategy->exists($path)) {
            Log::warning(sprintf("SummitService::processEventData file %s does not exists on storage %s.", $filename, $this->download_strategy->getDriver()));
            throw new ValidationException(sprintf("file %s does not exists.", $filename));
        }

        $csv_data = $this->download_strategy->get($path);
        if (empty($csv_data)) {
            Log::warning(sprintf("SummitService::processEventData file %s is empty.", $filename));
            throw new ValidationException(sprintf("file %s does not exists.", $filename));
        }

        $summit = $this->tx_service->transaction(function () use ($summit_id) {
            $summit = $this->summit_repository->getById($summit_id);
            if (is_null($summit) || !$summit instanceof Summit)
                throw new EntityNotFoundException(sprintf("summit %s does not exists.", $summit_id));
            return $summit;
        });

        $csv = Reader::createFromString($csv_data);
        $csv->setHeaderOffset(0);

        $header = $csv->getHeader(); //returns the CSV header record
        $records = $csv->getRecords();

        foreach ($records as $idx => $row) {
            try {
                // variable to store only added speakers to event
                $new_speakers = [];

                $event = $this->tx_service->transaction(function () use ($summit, $row, &$new_speakers) {

                    Log::debug(sprintf("SummitService::processEventData processing row %s", json_encode($row)));

                    // event type
                    $event_type = null;
                    if (isset($row['type_id']))
                        $event_type = $summit->getEventType(intval($row['type_id']));
                    if (isset($row['type']))
                        $event_type = $summit->getEventTypeByType($row['type']);

                    // track
                    $track = null;
                    if (isset($row['track_id']))
                        $track = $summit->getPresentationCategory(intval($row['track_id']));
                    if (isset($row['track']))
                        $track = $summit->getPresentationCategoryByTitle($row['track']);

                    if (is_null($event_type) && !isset($row['id']))
                        throw new EntityNotFoundException("event type not found.");

                    if (is_null($track) && !isset($row['id']))
                        throw new EntityNotFoundException("track not found.");

                    $event = null;
                    if (isset($row['id']) && !empty($row['id'])) {
                        Log::debug(sprintf("SummitService::processEventData trying to get event %s", $row['id']));
                        $event = $summit->getEventById(intval($row['id']));
                        if (is_null($event)) {
                            throw new EntityNotFoundException(sprintf("event %s not found.", $row['id']));
                        }
                        if (is_null($event_type)) {
                            $event_type = $event->getType();
                        }
                        if (is_null($track)) {
                            $track = $event->getCategory();
                        }
                    }

                    if (is_null($event)) // new event
                        $event = SummitEventFactory::build($event_type, $summit);

                    // main data

                    if (isset($row['title'])) {
                        $title = trim($row['title']);
                        Log::debug(sprintf("SummitService::processEventData setting title %s", $title));
                        $event->setTitle(html_entity_decode($title));
                    }

                    if (isset($row['description'])) {
                        $abstract = trim($row['description']);
                        Log::debug(sprintf("SummitService::processEventData setting description %s", $abstract));
                        $event->setAbstract(html_entity_decode($abstract));
                    }

                    if (isset($row['level']))
                        $event->setLevel($row['level']);

                    if (isset($row['social_summary']))
                        $event->setSocialSummary($row['social_summary']);

                    if (isset($row['allow_feedback']))
                        $event->setAllowFeedBack(boolval($row['allow_feedback']));

                    if (!is_null($event_type))
                        $event->setType($event_type);

                    if (!is_null($track))
                        $event->setCategory($track);

                    if (isset($row['location']) && !empty($row['location'])) {
                        Log::debug(sprintf("SummitService::processEventData processing location %s", $row['location']));
                        $location = $summit->getLocation(intval($row['location']));
                        if (is_null($location))
                            $location = $summit->getLocationByName(trim($row['location']));

                        if (is_null($location))
                            throw new EntityNotFoundException("location not found.");
                        Log::debug(sprintf("SummitService::processEventData setting location %s", $location));
                        $event->setLocation($location);
                    }

                    if (isset($row['start_date']) && !empty($row['start_date']) && isset($row['end_date']) && !empty($row['end_date'])) {
                        Log::debug
                        (
                            sprintf
                            (
                                "SummitService::processEventData publishing event start_date %s end_date %s",
                                $row['start_date'],
                                $row['end_date']
                            )
                        );
                        $start_date = DateTime::createFromFormat('Y-m-d H:i:s', $row['start_date'], $summit->getTimeZone());
                        $end_date = DateTime::createFromFormat('Y-m-d H:i:s', $row['end_date'], $summit->getTimeZone());

                        // set local time from UTC
                        $event->setStartDate($start_date);
                        $event->setEndDate($end_date);
                    }

                    // tags

                    if (isset($row['tags'])) {
                        Log::debug(sprintf("SummitService::processEventData processing tags %s", $row['tags']));
                        $tags = explode('|', $row['tags']);
                        $event->clearTags();
                        foreach ($tags as $val) {
                            $tag = $this->tag_repository->getByTag($val);
                            if ($tag == null) {
                                Log::debug(sprintf("SummitService::processEventData creating tag %s", $val));
                                $tag = new Tag($val);
                            }
                            $event->addTag($tag);
                        }
                    }

                    // sponsors
                    if (!is_null($event_type)) {
                        $sponsors = ($event_type->isUseSponsors() && isset($row['sponsors'])) ?
                            $row['sponsors'] : '';
                        $sponsors = explode('|', $sponsors);
                        if ($event_type->isAreSponsorsMandatory() && count($sponsors) == 0) {
                            throw new ValidationException('sponsors are mandatory!');
                        }

                        if (isset($row['sponsors'])) {
                            $event->clearSponsors();
                            foreach ($sponsors as $sponsor_name) {
                                $sponsor = $this->company_repository->getByName(trim($sponsor_name));
                                if (is_null($sponsor)) throw new EntityNotFoundException(sprintf('sponsor %s', $sponsor_name));
                                $event->addSponsor($sponsor);
                            }
                        }
                    }

                    if ($event instanceof Presentation) {
                        Log::debug(sprintf("SummitService::processEventData event %s is a presentation", $event->getId()));
                        if (isset($row['to_record']))
                            $event->setToRecord(boolval($row['to_record']));

                        if (isset($row['attendees_expected_learnt']))
                            $event->setAttendeesExpectedLearnt($row['attendees_expected_learnt']);

                        if (isset($row['problem_addressed']))
                            $event->setProblemAddressed($row['problem_addressed']);

                        // speakers

                        if (!is_null($event_type) && $event_type instanceof PresentationType && $event_type->isUseSpeakers()) {

                            $speakers = isset($row['speaker_emails']) ? $row['speaker_emails'] : '';
                            Log::debug(sprintf("SummitService::processEventData event %s processing speaker_emails %s", $event->getId(), $speakers));
                            $speakers = explode('|', $speakers);

                            $speakers_names = [];
                            if (isset($row["speaker_fullnames"])) {
                                $speakers_names = isset($row['speaker_fullnames']) ?
                                    $row['speaker_fullnames'] : '';
                                Log::debug(sprintf("SummitService::processEventData event %s processing speaker_fullnames %s", $event->getId(), $speakers_names));
                                $speakers_names = explode('|', $speakers_names);
                            }

                            $speakers_companies = [];
                            if (isset($row["speaker_companies"])) {
                                $speakers_companies = isset($row['speaker_companies']) ?
                                    $row['speaker_companies'] : '';
                                Log::debug(sprintf("SummitService::processEventData event %s processing speaker_companies %s", $event->getId(), $speakers_companies));
                                $speakers_companies = explode('|', $speakers_companies);
                            }

                            $speakers_titles = [];
                            if (isset($row["speaker_titles"])) {
                                $speakers_titles = isset($row['speaker_titles']) ?
                                    $row['speaker_titles'] : '';
                                Log::debug(sprintf("SummitService::processEventData event %s processing speaker_titles %s", $event->getId(), $speakers_titles));
                                $speakers_titles = explode('|', $speakers_titles);
                            }

                            if (count($speakers_names) == 0) {
                                $speakers_names = $speakers;
                            }

                            if (count($speakers_names) != count($speakers))
                                throw new ValidationException("count of speakers and speakers_name should match.");

                            if ($event_type->isAreSpeakersMandatory() && count($speakers) == 0) {
                                throw new ValidationException('speakers are mandatory!');
                            }

                            if (count($speakers) > 0) {

                                foreach ($speakers as $idx => $speaker_email) {
                                    $speaker_full_name = $speakers_names[$idx];
                                    $speaker_full_name_comps = explode(" ", $speaker_full_name, 2);
                                    $speaker_first_name = trim($speaker_full_name_comps[0]);
                                    $speaker_last_name = null;

                                    if (count($speaker_full_name_comps) > 1) {
                                        $speaker_last_name = trim($speaker_full_name_comps[1]);
                                    }
                                    if (empty($speaker_last_name))
                                        $speaker_last_name = $speaker_first_name;

                                    Log::debug(sprintf("SummitService::processEventData processing speaker email %s speaker fullname %s", $speaker_email, $speaker_full_name));
                                    $speaker = $this->speaker_repository->getByEmail(trim($speaker_email));
                                    if (is_null($speaker)) {
                                        Log::debug(sprintf("SummitService::processEventData speaker %s fname %s lname %s does not exists", $speaker_email, $speaker_first_name, $speaker_last_name));
                                        $payload = [
                                            'first_name' => $speaker_first_name,
                                            'last_name' => $speaker_last_name,
                                            'email' => $speaker_email
                                        ];

                                        if (array_key_exists($idx, $speakers_companies)) {
                                            $payload['company'] = $speakers_companies[$idx];
                                        }

                                        if (array_key_exists($idx, $speakers_titles)) {
                                            $payload['title'] = $speakers_titles[$idx];
                                        }

                                        Log::debug(sprintf("SummitService::processEventData adding speaker %s", json_encode($payload)));
                                        $speaker = $this->speaker_service->addSpeaker($payload, null, false);
                                    } else {
                                        Log::debug(sprintf("SummitService::processEventData speaker %s already exists, updating ", $speaker_email));

                                        $payload = [
                                            'email' => $speaker_email
                                        ];

                                        if (!empty($speaker_first_name) && $speaker_first_name != $speaker_email) {
                                            $payload['first_name'] = $speaker_first_name;
                                        }

                                        if (!empty($speaker_last_name) && $speaker_last_name != $speaker_email) {
                                            $payload['last_name'] = $speaker_last_name;
                                        }

                                        if (array_key_exists($idx, $speakers_companies) && !empty($speakers_companies[$idx])) {
                                            $payload['company'] = $speakers_companies[$idx];
                                        }

                                        if (array_key_exists($idx, $speakers_titles) && !empty($speakers_titles[$idx])) {
                                            $payload['title'] = $speakers_titles[$idx];
                                        }

                                        Log::debug(sprintf("SummitService::processEventData updating speaker %s", json_encode($payload)));

                                        $this->speaker_service->updateSpeaker($speaker, $payload);
                                    }

                                    if (!$event->isSpeaker($speaker)) {
                                        $new_speakers[] = $speaker;
                                        Log::debug(sprintf("SummitService::processEventData adding speaker %s to event %s", $speaker->getEmail(), $event->getTitle()));
                                        $event->addSpeaker($speaker);
                                    }
                                }
                            }
                        }

                        // moderator

                        if (!is_null($event_type) && $event_type instanceof PresentationType && $event_type->isUseModerator() && isset($row['moderator'])) {
                            $moderator_email = trim($row['moderator']);

                            if ($event_type->isModeratorMandatory() && !$event->hasModerator() && empty($moderator_email)) {
                                throw new ValidationException('moderator is mandatory!');
                            }

                            if (!empty($moderator_email)) {

                                Log::debug(sprintf("SummitService::processEventData processing moderator %s", $moderator_email));
                                $moderator = $this->speaker_repository->getByEmail($moderator_email);
                                if (is_null($moderator)) {
                                    Log::debug(sprintf("SummitService::processEventData moderator %s does not exists", $moderator_email));
                                    $moderator = $this->speaker_service->addSpeaker(['email' => $moderator_email], null, false);
                                }

                                $event->setModerator($moderator);
                            }
                        }

                        // selection plan

                        if (isset($row['selection_plan'])) {
                            $selection_plan = $summit->getSelectionPlanByName($row['selection_plan']);
                            if (!is_null($selection_plan)) {
                                Log::debug(sprintf("SummitService::processEventData processing selection plan %s", $row['selection_plan']));
                                $track = $event->getCategory();
                                if (!$selection_plan->hasTrack($track)) {
                                    throw new ValidationException(sprintf("Track %s (%s) does not belongs to Selection Plan %s (%s)", $track->getTitle(), $track->getId(), $selection_plan->getName(), $selection_plan->getId()));
                                }
                                $event->setSelectionPlan($selection_plan);
                            }
                        }
                    }

                    if (isset($row['is_published'])) {
                        $is_published = boolval($row['is_published']);
                        if ($is_published) {
                            if (!isset($row['start_date'])) throw new ValidationException("start_date is required.");
                            if (!isset($row['end_date'])) throw new ValidationException("end_date is required.");
                            if (!$event->isPublished())
                                $event->publish();
                        } else {
                            $event->unPublish();
                        }
                    }

                    $summit->addEvent($event);

                    return $event;
                });

                if ($send_speaker_email && $event instanceof Presentation) {
                    // only send emails to added speakers
                    foreach ($new_speakers as $speaker)
                        $this->tx_service->transaction(function () use ($speaker, $event) {
                            $setPasswordLink = null;
                            if ($speaker instanceof PresentationSpeaker) {
                                if (!$speaker->hasMember()) {
                                    Log::debug(sprintf("SummitService::processEventData speaker %s has not member set, checking at idp", $speaker->getEmail()));
                                    $user = $this->member_service->checkExternalUser($speaker->getEmail());
                                    if (is_null($user)) {

                                        // user does not exist at idp so we need to generate a registration request
                                        // and create the magic links to complete the registration request
                                        Log::debug(sprintf("SummitService::processEventData speaker %s user not found at idp, creating registration request", $speaker->getEmail()));
                                        $userRegistrationRequest = $this->member_service->emitRegistrationRequest
                                        (
                                            $speaker->getEmail(),
                                            $speaker->getFirstName(),
                                            $speaker->getLastName(),
                                            $speaker->getCompany()
                                        );

                                        $setPasswordLink = $userRegistrationRequest['set_password_link'];
                                        $speaker_management_base_url = Config::get('cfp.base_url');

                                        $setPasswordLink = sprintf(
                                            "%s?client_id=%s&redirect_uri=%s",
                                            $setPasswordLink,
                                            Config::get("cfp.client_id"),
                                            sprintf("%s/app/profile", $speaker_management_base_url)
                                        );
                                    }
                                }
                            }
                            ImportEventSpeakerEmail::dispatch($event, $speaker, $setPasswordLink);
                        });
                }
            } catch (Exception $ex) {
                Log::warning($ex);
            }
        }

        Log::debug(sprintf("SummitService::processEventData deleting file %s from cloud storage %s", $path, $this->download_strategy->getDriver()));
        $this->download_strategy->delete($path);
    }

    /**
     * @param int $summit_id
     * @param int $speaker_id
     * @return FeaturedSpeaker|null
     * @throws Exception
     */
    public function addFeaturedSpeaker(int $summit_id, int $speaker_id): ?FeaturedSpeaker
    {
        return $this->tx_service->transaction(function () use ($summit_id, $speaker_id) {
            $summit = $this->summit_repository->getById($summit_id);
            if (is_null($summit) || !$summit instanceof Summit)
                throw new EntityNotFoundException("summit not found");

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker) || !$speaker instanceof PresentationSpeaker)
                throw new EntityNotFoundException("speaker not found");

            // validate it
            if (!$this->speaker_repository->speakerBelongsToSummitSchedule($speaker_id, $summit_id)) {
                throw new ValidationException(sprintf("Speaker %s does not belongs to Summit %s schedule.", $speaker_id, $summit_id));
            }

            return $summit->addFeaturedSpeaker($speaker);
        });
    }

    /**
     * @param int $summit_id
     * @param int $speaker_id
     * @param array $payload
     * @return FeaturedSpeaker|null
     * @throws Exception
     */
    public function updateFeaturedSpeaker(int $summit_id, int $speaker_id, array $payload): ?FeaturedSpeaker
    {
        return $this->tx_service->transaction(function () use ($summit_id, $speaker_id, $payload) {
            $summit = $this->summit_repository->getById($summit_id);
            if (is_null($summit) || !$summit instanceof Summit)
                throw new EntityNotFoundException("summit not found");

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker) || !$speaker instanceof PresentationSpeaker)
                throw new EntityNotFoundException("speaker not found");

            // validate it
            if (!$this->speaker_repository->speakerBelongsToSummitSchedule($speaker_id, $summit_id)) {
                throw new ValidationException(sprintf("Speaker %s does not belongs to Summit %s schedule.", $speaker_id, $summit_id));
            }

            $featured = $summit->getFeatureSpeaker($speaker);

            if (is_null($featured))
                throw new EntityNotFoundException("Feature Speaker not found");

            if (isset($payload['order']) && intval($payload['order']) != $featured->getOrder()) {
                // request to update order
                $summit->recalculateFeaturedSpeakerOrder($featured, intval($payload['order']));
            }

            return $featured;
        });
    }

    /**
     * @inheritDoc
     */
    public function removeFeaturedSpeaker(int $summit_id, int $speaker_id): void
    {
        $this->tx_service->transaction(function () use ($summit_id, $speaker_id) {
            $summit = $this->summit_repository->getById($summit_id);
            if (is_null($summit) || !$summit instanceof Summit)
                throw new EntityNotFoundException("summit not found");

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker) || !$speaker instanceof PresentationSpeaker)
                throw new EntityNotFoundException("speaker not found");

            $summit->removeFeaturedSpeaker($speaker);
        });
    }

    /**
     * @inheritDoc
     */
    public function addCompany(int $summit_id, int $company_id): ?Company
    {
        return $this->tx_service->transaction(function () use ($summit_id, $company_id) {
            $summit = $this->summit_repository->getById($summit_id);
            if (is_null($summit) || !$summit instanceof Summit)
                throw new EntityNotFoundException("summit not found");

            $company = $summit->getRegistrationCompanyById($company_id);
            if (!is_null($company))
                throw new ValidationException(sprintf("summit %s already has a company with id %s.", $summit_id, $company_id));

            $company = $this->company_repository->getById($company_id);
            if (is_null($company) || !$company instanceof Company)
                throw new EntityNotFoundException("company not found");

            $summit->addRegistrationCompany($company);
            return $company;
        });
    }

    /**
     * @inheritDoc
     */
    public function removeCompany(int $summit_id, int $company_id): void
    {
        $this->tx_service->transaction(function () use ($summit_id, $company_id) {
            $summit = $this->summit_repository->getById($summit_id);
            if (is_null($summit) || !$summit instanceof Summit)
                throw new EntityNotFoundException("summit not found");

            $company = $this->company_repository->getById($company_id);
            if (is_null($company) || !$company instanceof Company)
                throw new EntityNotFoundException("company not found");

            $summit->removeRegistrationCompany($company);
        });
    }

    /**
     * @param int $summit_id
     * @param int $media_upload_type_id
     * @param string $default_public_storage
     * @return int
     * @throws Exception
     */
    public function migratePrivateStorage2PublicStorage(int $summit_id, int $media_upload_type_id, string $default_public_storage = IStorageTypesConstants::S3): int
    {
        Log::debug
        (
            sprintf
            (
                "SummitService::migratePrivateStorage2PublicStorage summit id %s media_upload_type_id %s default_public_storage %s",
                $summit_id,
                $media_upload_type_id,
                $default_public_storage
            )
        );

        return $this->tx_service->transaction(function () use ($summit_id, $media_upload_type_id, $default_public_storage) {
            $summit = $this->summit_repository->getById($summit_id);
            if (is_null($summit) || !$summit instanceof Summit)
                throw new EntityNotFoundException("Summit not found.");

            $media_upload_type = $summit->getMediaUploadTypeById($media_upload_type_id);

            if (is_null($media_upload_type) || !$media_upload_type instanceof SummitMediaUploadType)
                throw new EntityNotFoundException("Media upload type not found.");

            if (!$media_upload_type->hasPublicStorageSet()) {
                Log::debug
                (
                    sprintf
                    (
                        "SummitService::migratePrivateStorage2PublicStorage summit id %s media_upload_type_id %s setting public storage type to %s",
                        $summit_id,
                        $media_upload_type_id,
                        $default_public_storage
                    )
                );

                $media_upload_type->setPublicStorageType($default_public_storage);
            }

            $processed = 0;

            foreach ($media_upload_type->getMediaUploadsToDisplayOnSite() as $media_upload) {
                if (!$media_upload instanceof PresentationMediaUpload) continue;
                try {
                    Log::debug(sprintf("SummitService::migratePrivateStorage2PublicStorage processing media upload %s file %s", $media_upload->getId(), $media_upload->getFilename()));
                    $strategy = FileDownloadStrategyFactory::build($media_upload_type->getPrivateStorageType());
                    if (!is_null($strategy)) {

                        $file = $strategy->readStream
                        (
                            $media_upload->getRelativePath(IStorageTypesConstants::PrivateType)
                        );

                        if (is_null($file)) continue;

                        $uploadStrategy = FileUploadStrategyFactory::build($media_upload_type->getPublicStorageType());

                        if (!is_null($uploadStrategy)) {
                            $path = sprintf("%s/%s", $media_upload->getPath(IStorageTypesConstants::PublicType), $media_upload->getFilename());
                            Log::debug
                            (
                                sprintf
                                (
                                    "SummitService::migratePrivateStorage2PublicStorage uploading file %s to public storage type", $path
                                )
                            );
                            $res = $uploadStrategy->saveFromStream($file, $path, "public");

                            Log::debug
                            (
                                sprintf
                                (
                                    "SummitService::migratePrivateStorage2PublicStorage uploading file %s to public storage type res %b",
                                    $path,
                                    $res
                                )
                            );
                            if ($res) {
                                $processed = $processed + 1;
                            }
                        }
                    }
                } catch (\Exception $ex) {
                    Log::warning($ex);
                }
            }

            return $processed;
        });
    }

    /**
     * @param int $summit_id
     * @throws Exception
     */
    public function regenerateTemporalUrlsForMediaUploads(int $summit_id): void
    {

        Log::debug(sprintf("SummitService::regenerateTemporalUrlsForMediaUploads processing summit %s", $summit_id));

        $mediaUploadTypes = $this->tx_service->transaction(function () use ($summit_id) {
            $summit = $this->summit_repository->getById($summit_id);
            if (is_null($summit) || !$summit instanceof Summit)
                throw new EntityNotFoundException("Summit not found.");
            $res = [];
            foreach ($summit->getMediaUploadTypes() as $mediaUploadType) {
                if ($mediaUploadType->hasPublicStorageSet() && $mediaUploadType->isUseTemporaryLinksOnPublicStorage()) {
                    $res[] = $mediaUploadType;
                }
            }
            return $res;
        });

        foreach ($mediaUploadTypes as $mediaUploadType) {
            $page = 1;
            $filter = new Filter();
            $filter->addFilterCondition(FilterElement::makeEqual('type_id', $mediaUploadType->getId()));
            Log::debug(sprintf("SummitService::regenerateTemporalUrlsForMediaUploads processing media upload type %s", $mediaUploadType->getId()));
            do {
                $res = $this->presentation_media_upload_repository->getAllByPage(new PagingInfo($page, 100), $filter);
                Log::debug(sprintf("SummitService::regenerateTemporalUrlsForMediaUploads processing media upload type %s page %s got %s items", $mediaUploadType->getId(), $page, count($res->getItems())));
                foreach ($res->getItems() as $item) {
                    if (!$item instanceof PresentationMediaUpload) continue;
                    Log::debug(sprintf("SummitService::regenerateTemporalUrlsForMediaUploads processing media upload %s", $item->getId()));
                    try {
                        $strategy = FileDownloadStrategyFactory::build($mediaUploadType->getPublicStorageType());
                        Log::debug(sprintf("SummitService::regenerateTemporalUrlsForMediaUploads media upload %s trying to regenerate public url for %s", $item->getId(), $item->getRelativePath()));
                        if (!is_null($strategy)) {
                            $strategy->getUrl
                            (
                                $item->getRelativePath(),
                                $mediaUploadType->isUseTemporaryLinksOnPublicStorage(),
                                $mediaUploadType->getTemporaryLinksPublicStorageTtl() * 60, // convert to seconds
                                true
                            );
                            Log::debug(sprintf("SummitService::regenerateTemporalUrlsForMediaUploads media upload %s regenerated public url for %s", $item->getId(), $item->getRelativePath()));
                        }
                    } catch (\Exception $ex) {
                        Log::warning($ex);
                    }
                }
                if (!$res->hasMoreItems()) break;
                $page++;
            } while (true);
        }
        Log::debug(sprintf("SummitService::regenerateTemporalUrlsForMediaUploads processed summit %s", $summit_id));
    }

    /**
     * @param Summit $summit
     * @param UploadedFile $csv_file
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function importRegistrationCompanies(Summit $summit, UploadedFile $csv_file): void
    {
        Log::debug(sprintf("SummitService::importRegistrationCompanies summit %s", $summit->getId()));

        $allowed_extensions = ['txt','csv'];

        if (!in_array($csv_file->extension(), $allowed_extensions)) {
            Log::warning
            (
                sprintf
                (
                    "SummitService::importRegistrationCompanies %s is not allowed extension",
                    $csv_file->extension()
                )
            );
            throw new ValidationException("file does not has a valid extension ('csv').");
        }

        $real_path = $csv_file->getRealPath();
        $filename = pathinfo($real_path);
        $filename = $filename['filename'] ?? sprintf("file%s", time());
        $basename = sprintf("%s_%s.csv", $filename, time());

        Log::debug(sprintf("SummitService::importRegistrationCompanies trying to read file data from %s", $real_path));
        $csv_data = \Illuminate\Support\Facades\File::get($real_path);

        if (empty($csv_data)) {
            Log::warning(sprintf("SummitService::importRegistrationCompanies file %s has empty content.", $real_path));
            throw new ValidationException("file content is empty!");
        }

        // upload to distribute storage
        Log::debug(sprintf("SummitService::importRegistrationCompanies uploading file %s to storage %s", $basename, $this->upload_strategy->getDriver()));

        $this->upload_strategy->save($csv_file, "tmp/registration_companies_import", $basename);

        $csv = Reader::createFromString($csv_data);
        $csv->setHeaderOffset(0);

        $header = $csv->getHeader(); //returns the CSV header record

        Log::debug(sprintf("SummitService::importRegistrationCompanies validating header %s", json_encode($header)));

        // check needed columns (headers names)
        /*
            columns (min)
            * name
         */

        if (!in_array("name", $header)) {
            Log::warning("SummitService::importRegistrationCompanies name column is missing.");
            throw new ValidationException('name column missing.');
        }

        ProcessRegistrationCompaniesDataImport::dispatch($summit->getId(), $basename);
    }

    /**
     * @param int $summit_id
     * @param string $filename
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function processRegistrationCompaniesData(int $summit_id, string $filename): void
    {
        Log::debug(sprintf("SummitService::processRegistrationCompaniesData summit %s filename %s", $summit_id, $filename));
        $path = sprintf("tmp/registration_companies_import/%s", $filename);

        if (!$this->download_strategy->exists($path)) {
            Log::warning(sprintf("SummitService::processRegistrationCompaniesData file %s does not exists on storage %s.", $filename, $this->download_strategy->getDriver()));
            throw new ValidationException(sprintf("file %s does not exists.", $filename));
        }

        $csv_data = $this->download_strategy->get($path);
        if (empty($csv_data)) {
            Log::warning(sprintf("SummitService::processRegistrationCompaniesData file %s is empty.", $filename));
            throw new ValidationException(sprintf("file %s does not exists.", $filename));
        }

        $csv = Reader::createFromString($csv_data);
        $csv->setHeaderOffset(0);

        $header = $csv->getHeader(); //returns the CSV header record
        $records = $csv->getRecords();

        foreach ($records as $idx => $row) {
            try {
                $this->tx_service->transaction(function () use ($row, $summit_id) {

                    $companyName = $row['name'];

                    $company = $this->company_repository->getByName(trim($companyName));

                    if (is_null($company)) {
                        Log::debug
                        (
                            sprintf
                            (
                                "SummitService::processRegistrationCompaniesData company %s does not exists. creating it...",
                                $companyName
                            )
                        );

                        $company = CompanyFactory::build(['name' => $companyName]);

                        $this->company_repository->add($company, true);
                    }

                    Log::debug
                    (
                        sprintf
                        (
                            "SummitService::processRegistrationCompaniesData adding company %s to summit %s",
                            $company->getId(),
                            $summit_id
                        )
                    );

                    $this->addCompany($summit_id, $company->getId());
                });
            } catch (Exception $ex) {
                Log::warning($ex);
            }
        }

        Log::debug(sprintf("SummitService::processRegistrationCompaniesData deleting file %s from cloud storage %s", $path, $this->download_strategy->getDriver()));
        $this->download_strategy->delete($path);
    }

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param int $feedback_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteEventFeedback(Summit $summit, int $event_id, int $feedback_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $event_id, $feedback_id) {

            $event = $summit->getScheduleEvent($event_id);
            if (is_null($event))
                throw new EntityNotFoundException("Event not found.");

            if (!$event->isAllowFeedback())
                throw new ValidationException(sprintf("Event id %s does not allow feedback.", $event->getIdentifier()));

            // check older feedback
            $feedback = $event->getFeedbackById($feedback_id);

            if (!$feedback instanceof SummitEventFeedback)
                throw new EntityNotFoundException("Feedback not found.");

            $event->removeFeedback($feedback);

        });
    }

    /**
     * @param Summit $summit
     * @return string
     * @throws ValidationException
     */
    public function generateQREncKey(Summit $summit): string
    {
        Log::debug(sprintf("SummitService::generateQREncKey summit %s", $summit->getId()));
        $res = $this->tx_service->transaction(function () use ($summit) {

            if (!is_null($summit->getQRCodesEncKey()))
                throw new ValidationException("There is already a QR encryption key configured for this Summit");

            do {
                $enc_key = $this->encryption_key_generator->generate();
            } while (!is_null($this->summit_repository->getByQREncryptionKey($enc_key)));

            $summit->setQRCodesEncKey($enc_key);

            return $enc_key;
        });

        if (!is_null($res)) {
            EncryptAllSummitBadgeQRCodes::dispatch($summit->getId());
        }

        return $res;
    }

    /**
     * @param int $summit_id
     * @return void
     * @throws Exception
     */
    public function regenerateBadgeQRCodes(int $summit_id): void
    {
        Log::debug(sprintf("SummitService::regenerateBadgeQRCodes summit %s", $summit_id));

        $page = 1;
        $count = 0;

        do {
            Log::debug(sprintf("SummitService::regenerateBadgeQRCodes summit %s processing page %s", $summit_id, $page));
            $page_response = $this->summit_attendee_badge_repository->getBadgeIdsBySummit($summit_id, new PagingInfo($page, 500));
            $has_more = count($page_response->getItems()) > 0;

            if (!$has_more) {
                Log::debug(sprintf("SummitService::regenerateBadgeQRCodes summit %s no more pages", $summit_id));
                continue;
            }

            foreach ($page_response->getItems() as $page_response_item) {

                $attendee_badge_id = $page_response_item['id'];
                $count++;

                Log::debug(sprintf("SummitService::regenerateBadgeQRCodes summit %s processing badge %s", $summit_id, $attendee_badge_id));

                $this->tx_service->transaction(function () use ($attendee_badge_id) {
                    try {
                        $attendee_badge = $this->summit_attendee_badge_repository->getById(intval($attendee_badge_id));
                        if (!$attendee_badge instanceof SummitAttendeeBadge) {
                            Log::warning(sprintf("SummitService::regenerateBadgeQRCodes badge %s not found", $attendee_badge_id));
                            return;
                        }
                        $attendee_badge->generateQRCode();
                    } catch (Exception $ex) {
                        Log::error($ex);
                    }
                });
            }

            $page++;

        } while ($has_more);
        Log::debug(sprintf("SummitService::regenerateBadgeQRCodes summit %s processed %s badges", $summit_id, $count));
    }

    /**
     * @param int $summit_id
     * @return void
     * @throws Exception
     */
    public function generateMUXPrivateKey(int $summit_id): void
    {
        $this->tx_service->transaction(function () use ($summit_id) {
            try {
                Log::debug(sprintf("SummitService::generateMUXPrivateKey summit %s", $summit_id));

                $summit = $this->summit_repository->getById($summit_id);
                if (!$summit instanceof Summit)
                    throw new EntityNotFoundException("Summit not found.");

                $this->mux_api->setCredentials(new MuxCredentials(
                    $summit->getMuxTokenId(),
                    $summit->getMuxTokenSecret()
                ));

                $key = $this->mux_api->createUrlSigningKey();

                Log::debug(sprintf("SummitService::generateMUXPrivateKey summit %s key %s", $summit_id, json_encode($key)));

                $summit->setMUXPrivateKey($key['private_key']);

                $summit->setMuxPrivateKeyId($key['id']);
            } catch (Exception $ex) {
                Log::error($ex);
                throw $ex;
            }
        });
    }

    /**
     * @param int $summit_id
     * @return void
     * @throws Exception
     */
    public function generateMuxPlaybackRestriction(int $summit_id): void
    {
        $this->tx_service->transaction(function () use ($summit_id) {
            try {
                Log::debug(sprintf("SummitService::generateMuxPlaybackRestriction summit %s", $summit_id));
                $summit = $this->summit_repository->getById($summit_id);
                if (!$summit instanceof Summit)
                    throw new EntityNotFoundException("Summit not found.");

                $this->mux_api->setCredentials(new MuxCredentials(
                    $summit->getMuxTokenId(),
                    $summit->getMuxTokenSecret()
                ));

                $former_playback_restriction_id = $summit->getMuxPlaybackRestrictionId();
                if (!empty($former_playback_restriction_id)) {
                    $this->mux_api->deletePlaybackRestriction($former_playback_restriction_id);
                    $summit->clearMuxPlaybackRestrictionId();
                }

                $allowed_domains = $summit->getMuxAllowedDomains();
                if (count($allowed_domains) > 0) {

                    Log::debug
                    (
                        sprintf
                        (
                            "SummitService::generateMuxPlaybackRestriction trying to create playback restriction for summit %s allowed_domains %s",
                            $summit_id,
                            json_encode($allowed_domains)
                        )
                    );

                    $playback_restriction = $this->mux_api->createPlaybackRestriction
                    (
                        $allowed_domains
                    );

                    Log::debug(sprintf("SummitService::generateMuxPlaybackRestriction summit %s playback_restriction %s", $summit_id, json_encode($playback_restriction)));

                    $summit->setMuxPlaybackRestrictionId($playback_restriction['id']);
                }
            } catch (Exception $ex) {
                Log::error($ex);
                throw $ex;
            }

        });
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitRegistrationFeedMetadata
     * @throws Exception
     */
    public function addRegistrationFeedMetadata(Summit $summit, array $payload): SummitRegistrationFeedMetadata
    {
        return $this->tx_service->transaction(function () use ($summit, $payload) {
            return $summit->addRegistrationFeedMetadata($payload['key'], $payload['value']);
        });
    }

    /**
     * @param Summit $summit
     * @param int $metadata_id
     * @return void
     * @throws Exception
     */
    public function removeRegistrationFeedMetadata(Summit $summit, int $metadata_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $metadata_id) {
            $metadata = $summit->getRegistrationFeedMetadataById($metadata_id);
            if (is_null($metadata))
                throw new EntityNotFoundException(sprintf("Metadata %s not found,", $metadata_id));
            $summit->removeRegistrationFeedMetadata($metadata);
        });
    }

    public function updateRegistrationFeedMetadata(Summit $summit, int $metadata_id, array $payload): SummitRegistrationFeedMetadata
    {
        return $this->tx_service->transaction(function () use ($summit, $metadata_id, $payload) {
            $metadata = $summit->getRegistrationFeedMetadataById($metadata_id);
            if (is_null($metadata))
                throw new EntityNotFoundException(sprintf("Metadata %s not found,", $metadata_id));

            if (isset($payload['key'])) {
                $formerMetadata = $summit->getRegistrationFeedMetadataByKey($payload['key']);
                if (!is_null($formerMetadata) && $formerMetadata->getId() != $metadata->getId())
                    throw new ValidationException(sprintf("Metadata with key %s already exists", $payload['key']));
            }


            if (isset($payload['value']))
                $metadata->setValue($payload['value']);
            if (isset($payload['key']))
                $metadata->setKey($payload['key']);

            return $metadata;
        });
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitLeadReportSetting
     * @throws Exception
     */
    public function addLeadReportSettings(Summit $summit, array $payload): SummitLeadReportSetting
    {
        return $this->tx_service->transaction(function () use ($summit, $payload) {
            $former_setting = $summit->getLeadReportSettingFor();
            if (!is_null($former_setting)) {
                throw new ValidationException(sprintf("Lead report settings already exists for summit %s", $summit->getId()));
            }
            $lead_report_settings = LeadReportSettingsFactory::build($payload);

            $lead_report_settings->validateFor($summit);

            $lead_report_settings->setSummit($summit);
            $summit->addLeadReportSetting($lead_report_settings);

            return $lead_report_settings;
        });
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitLeadReportSetting
     * @throws Exception
     */
    public function updateLeadReportSettings(Summit $summit, array $payload): SummitLeadReportSetting
    {
        return $this->tx_service->transaction(function () use ($summit, $payload) {
            $former_setting = $summit->getLeadReportSettingFor();
            $lead_report_settings = LeadReportSettingsFactory::populate($former_setting, $payload);

            $lead_report_settings->validateFor($summit);

            $summit->removeLeadReportSetting($former_setting);
            $summit->addLeadReportSetting($lead_report_settings);

            return $lead_report_settings;
        });
    }

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param int $type_id
     * @return SummitEvent
     * @throws Exception
     */
    public function upgradeSummitEvent(Summit $summit, int $event_id, int $type_id): SummitEvent
    {
        // check first if there is an upgrade or not

        return $this->tx_service->transaction(function () use ($summit, $event_id, $type_id) {

            $event_type = $summit->getEventType($type_id);
            if (is_null($event_type)) {
                throw new EntityNotFoundException(sprintf("Event type id %s does not exists.", $type_id));
            }

            $event = $this->event_repository->getByIdRefreshed($event_id);
            if (is_null($event))
                throw new ValidationException(sprintf("Event id %s does not exists,", $event_id));

            $old_event_type = $event->getType();

            if ($old_event_type->getClassName() != $event_type->getClassName() && $event_type instanceof PresentationType) {
                Log::debug(sprintf("SummitService::upgradeSummitEvent promoting event %s 2 presentation ...", $event_id));
                $event->promote2Presentation($event_type);
                return $event;
            }

            throw new ValidationException("Event type is the same or not a presentation type.");
        });
    }

    /**\
     * @param int $minutes
     * @return void
     * @throws Exception
     */
    public function publishStreamUpdatesStartInXMinutes(int $minutes): void
    {
        Log::debug(sprintf("SummitService::publishStreamUpdatesStartInXMinutes minutes %s", $minutes));

        $ongoing_summits = $this->tx_service->transaction(function () {
            return $this->summit_repository->getOnGoing();
        });

        foreach ($ongoing_summits as $summit) {

            Log::debug
            (
                sprintf
                (
                    "SummitService::publishStreamUpdatesStartInXMinutes processing summit %s (%s)",
                    $summit->getName(),
                    $summit->getId()
                )
            );

            $event_ids = $this->tx_service->transaction(function () use ($summit, $minutes) {
                return $summit->getScheduleEventsIdsStartingInXMinutesOrLessWithStream($minutes);
            });

            Log::debug(sprintf("SummitService::publishStreamUpdatesStartInXMinutes summit %s events %s", $summit->getId(), json_encode($event_ids)));
            foreach ($event_ids as $event_id) {
                $event_id = intval($event_id['id']);
                $this->tx_service->transaction(function () use ($event_id) {
                    try {
                        Log::debug(sprintf("SummitService::publishStreamUpdatesStartInXMinutes processing event %s", $event_id));
                        $event = $this->event_repository->getByIdRefreshed($event_id);
                        if (!$event instanceof SummitEvent) {
                            Log::debug(sprintf("SummitService::publishStreamUpdatesStartInXMinutes event %s not found", $event_id));
                            return;
                        }

                        $start_time = $event->getStartDate();
                        if(is_null($start_time)){
                            Log::warning(sprintf("SummitService::publishStreamUpdatesStartInXMinutes event %s has no start date", $event_id));
                            return;
                        }

                        $now = new DateTime('now', new \DateTimeZone('UTC'));
                        $diff = $start_time->getTimestamp() - $now->getTimestamp();

                        Log::debug(sprintf("SummitService::publishStreamUpdatesStartInXMinutes event %s diff %s", $event_id, $diff));

                        $processing_key = sprintf("summit_event_stream_update_%s", $event_id);
                        if($this->cache_service->exists($processing_key)){
                            Log::warning(sprintf("SummitService::publishStreamUpdatesStartInXMinutes event %s already processed", $event_id));
                            return;
                        }

                        ProcessScheduleEntityLifeCycleEvent::dispatch
                        (
                            "UPDATE",
                            $event->getSummitId(),
                            $event->getId(),
                            $event->getClassName()
                        )->delay(now()->addSeconds(abs($diff)));

                        Log::debug(sprintf("SummitService::publishStreamUpdatesStartInXMinutes processed event id %s", $event->getId()));

                        $this->cache_service->addSingleValue($processing_key, $event->getId(), abs($diff));

                    } catch (Exception $ex) {
                        Log::error($ex);
                    }
                });
            }
        }
    }

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param array $payload
     * @return SummitEvent
     * @throws Exception
     */
    public function updateOverflowInfo(Summit $summit, int $event_id, array $payload): SummitEvent
    {
        Log::debug(sprintf("SummitService::updateOverflowInfo %s", json_encode($payload)));

        return $this->tx_service->transaction(function () use ($summit, $event_id, $payload) {

            $event = $this->event_repository->getByIdRefreshed($event_id);
            if (is_null($event))
                throw new ValidationException(sprintf("Event id %s does not exists,", $event_id));

            do {
                $overflow_key = $event->generateOverflowKey();
            } while (!is_null($this->event_repository->getByOverflowStreamKey($overflow_key)));

            $event->setOverflowStreamKey($overflow_key);
            $event->setOverflow(
                $payload['overflow_streaming_url'],
                boolval($payload['overflow_stream_is_secure']));

            $params = [
                'overflow_url' => $event->getOverflowUrl()
            ];

            ProcessScheduleEntityLifeCycleEvent::dispatch
            (
                "UPDATE",
                $summit->getId(),
                $event->getId(),
                Presentation::PresentationOverflowEntityType,
                $params
            );

            return $event;
        });
    }

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param array $payload
     * @return void
     * @throws Exception
     */
    public function removeOverflowState(Summit $summit, int $event_id, array $payload): SummitEvent
    {
        Log::debug(sprintf("SummitService::removeOverflowState event id %s", $event_id));

        return $this->tx_service->transaction(function () use ($summit, $event_id, $payload) {

            $event = $this->event_repository->getByIdRefreshed($event_id);
            if (is_null($event))
                throw new ValidationException(sprintf("Event id %s does not exists,", $event_id));

            $occupancy = SummitEvent::OccupancyEmpty;
            if (isset($payload['occupancy'])) {
                $occupancy = $payload['occupancy'];
            }

            $event->clearOverflow($occupancy);

            return $event;
        });
    }

    /**
     * @param Summit $summit
     * @param Member $current_user
     * @param int $event_id
     * @return SummitEvent|null
     * @throws EntityNotFoundException
     */
    public function getEventForStreamingInfo(Summit $summit, Member $current_user, int $event_id): ?SummitEvent
    {

        Log::debug
        (
            sprintf
            (
                "SummitService::getEventForStreamingInfo summit %s member %s(%s) event %s",
                $summit->getId(),
                $current_user->getEmail(),
                $current_user->getId(),
                $event_id
            )
        );

        $event = $summit->getScheduleEvent($event_id);
        if (is_null($event))
            throw new EntityNotFoundException(sprintf("Event %s not found.", $event_id));

        if (!$event->isPublished())
            throw new EntityNotFoundException(sprintf("Event %s not found.", $event_id));

        if (!$event->hasAccess($current_user))
            throw new EntityNotFoundException(sprintf("Event %s not found.", $event_id));

        return $event;

    }

    /**
     * @param Summit $summit
     * @param string $badge_qr_code
     * @return SummitAttendeeBadge
     * @throws ValidationException
     */
    public function validateBadge(Summit $summit, string $badge_qr_code): SummitAttendeeBadge
    {
        Log::debug(
            sprintf("SummitService::validateBadge summit %s badge qr code %s", $summit->getId(), $badge_qr_code));

        $qr_code = SummitAttendeeBadge::decodeQRCodeFor($summit, $badge_qr_code);
        $qr_code_components = SummitAttendeeBadge::parseQRCode($qr_code);

        $prefix = $qr_code_components['prefix'];

        if($summit->getBadgeQRPrefix() != $prefix)
            throw new ValidationException(sprintf("%s qr code is not valid for summit %s.", $qr_code, $summit->getId()));

        $ticket_number = $qr_code_components['ticket_number'];

        $badge = $this->summit_attendee_badge_repository->getBadgeByTicketNumber($ticket_number);

        if (is_null($badge))
            throw new ValidationException(sprintf("Badge for ticket number %s does not exists.", $ticket_number));

        return $badge;
    }
}