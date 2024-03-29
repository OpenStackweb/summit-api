<?php namespace App\Services\Model;
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

use App\Facades\ResourceServerContext;
use App\Jobs\Emails\ProposedSchedule\SubmitForReviewEmail;
use App\Jobs\Emails\ProposedSchedule\UnsubmitForReviewEmail;
use App\Models\Exceptions\AuthzException;
use App\Models\Foundation\Summit\IPublishableEvent;
use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedSchedule;
use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleAllowedLocation;
use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleLock;
use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleSummitEvent;
use App\Models\Foundation\Summit\SelectionPlan;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\ISummitEventRepository;
use models\summit\ISummitProposedScheduleEventRepository;
use models\summit\ISummitProposedScheduleLockRepository;
use models\summit\ISummitProposedScheduleRepository;
use models\summit\Presentation;
use models\summit\PresentationCategory;
use models\summit\Summit;
use models\summit\SummitEvent;
use models\summit\SummitTrackChair;
use services\model\ISummitService;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class ScheduleService
 * @package App\Services\Model
 */
final class ScheduleService
    extends AbstractPublishService implements IScheduleService
{
    /**
     * @var ISummitProposedScheduleRepository
     */
    private $schedule_repository;

    /**
     * @var ISummitProposedScheduleEventRepository
     */
    private $proposed_events_repository;
    /**
     * @var ISummitEventRepository
     */
    private $event_repository;

    /**
     * @var ISummitService
     */
    private $summit_service;

    /**
     * ScheduleService constructor.
     * @param ISummitProposedScheduleRepository $schedule_repository
     * @param ISummitEventRepository $event_repository
     * @param ISummitProposedScheduleEventRepository $proposed_events_repository
     * @param ISummitService $summit_service
     * @param ITransactionService $tx_service
     */
    public function __construct(ISummitProposedScheduleRepository      $schedule_repository,
                                ISummitEventRepository                 $event_repository,
                                ISummitProposedScheduleEventRepository $proposed_events_repository,
                                ISummitService                         $summit_service,
                                ITransactionService                    $tx_service)
    {
        parent::__construct($schedule_repository, $tx_service);
        $this->schedule_repository = $schedule_repository;
        $this->event_repository = $event_repository;
        $this->proposed_events_repository = $proposed_events_repository;
        $this->summit_service = $summit_service;
    }

    /**
     * @param Member $member
     * @param Summit $summit
     * @param PresentationCategory|null $category
     * @return bool
     */
    private function isAuthorizedUser(Member $member, Summit $summit, ?PresentationCategory $category = null): bool
    {
        if ($member->isAdmin()) return true;
        if ($summit->isSummitAdmin($member)) return true;
        if (!is_null($category))
            return $summit->isTrackChair($member, $category);
        return false;
    }

    /**
     * @param IPublishableEvent $event
     * @param SummitProposedSchedule $schedule
     * @return void
     * @throws ValidationException
     */
    private function checkTransitionTime(IPublishableEvent $event, SummitProposedSchedule $schedule):void{

        /*
        *  Room 1: <track 1 activity> <track 2 transition time> <track 2 activity> <track 1 transition time> <track 1 activity>
        *  Room 2: <track 3 activity> <track 1 transition time> <track 1 activity> <track 4 transition time> <track 4 activity>
        */

        $transition_time = $event->getTrackTransitionTime();


        if (!is_null($transition_time)) { // check immediate previous one event

            Log::debug
            (
                sprintf
                (
                    "ScheduleService::checkTransitionTime checking immediate previous event %s transition time %s",
                    $event->getSummitEventId(),
                    $transition_time
                )
            );

            $prevProposedScheduledEvent = $schedule->getProposedPublishedEventBeforeThan($event);

            if (!is_null($prevProposedScheduledEvent)) {

                Log::debug
                (
                    sprintf
                    (
                        "ScheduleService::checkTransitionTime prevProposedScheduledEvent %s end date %s start date %s transition time %s",
                        $prevProposedScheduledEvent->getSummitEventId(),
                        $prevProposedScheduledEvent->getEndDate()->format("Y-m-d H:i:s"),
                        $event->getStartDate()->format("Y-m-d H:i:s"),
                        $transition_time
                    )
                );

                if(($event->getStartDate()->getTimestamp() - $prevProposedScheduledEvent->getEndDate()->getTimestamp()) / 60 < $transition_time)
                    throw new ValidationException(
                        "There must be a transition time of at least {$transition_time} " .
                        "minutes between the end of the previous event {$prevProposedScheduledEvent->getSummitEventId()} ( {$prevProposedScheduledEvent->getLocalEndDate()->format("Y-m-d H:i:s")} ) " .
                        "and the start of the current one {$event->getSummitEventId()} ( {$event->getLocalStartDate()->format("Y-m-d H:i:s")} ).");
            }
        }

        // check immediate posterior ...

        $nextProposedScheduledEvent = $schedule->getProposedPublishedEventAfterThan($event);

        if (!is_null($nextProposedScheduledEvent)) {

            Log::debug(sprintf("ScheduleService::checkTransitionTime nextProposedScheduledEvent %s", $nextProposedScheduledEvent->getSummitEventId()));

            $transition_time = $nextProposedScheduledEvent->getSummitEvent()->getTrackTransitionTime();

            if (!is_null($transition_time)) {

                Log::debug
                (
                    sprintf
                    (
                        "ScheduleService::checkTransitionTime nextProposedScheduledEvent %s start date %s end date %s transition time %s",
                        $nextProposedScheduledEvent->getSummitEventId(),
                        $nextProposedScheduledEvent->getStartDate()->format("Y-m-d H:i:s"),
                        $event->getEndDate()->format("Y-m-d H:i:s"),
                        $transition_time
                    )
                );

                if (($nextProposedScheduledEvent->getStartDate() <= $event->getEndDate()) ||
                    ($nextProposedScheduledEvent->getStartDate()->getTimestamp() - $event->getEndDate()->getTimestamp()) / 60 < $transition_time) {
                    throw new ValidationException(
                        "There must be a transition time of at least {$transition_time} " .
                        "minutes between the end of the current event {$event->getSummitEventId()} ( {$event->getLocalEndDate()->format("Y-m-d H:i:s")} ) and the start of the next " .
                        "one {$nextProposedScheduledEvent->getSummitEventId()} ( {$nextProposedScheduledEvent->getLocalStartDate()->format("Y-m-d H:i:s")} ).");
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function publishProposedActivityToSource
    (
        string $source, int $presentation_id, array $payload
    ): SummitProposedScheduleSummitEvent
    {

        Log::debug
        (
            sprintf
            (
                "ScheduleService::publishProposedActivityToSource source %s presentation_id %s payload %s",
                $source,
                $presentation_id,
                json_encode($payload)
            )
        );

        $schedule = $this->tx_service->transaction(function () use ($source, $presentation_id, $payload) {

            $event = $this->event_repository->getById($presentation_id);
            if (!$event instanceof SummitEvent)
                throw new EntityNotFoundException("event id {$presentation_id} does not exists!");

            $schedule = $this->schedule_repository->getBySourceAndSummitId($source, $event->getSummitId());

            if ($event instanceof Presentation) {

                $selection_plan = $event->getSelectionPlan();
                if (!$selection_plan instanceof SelectionPlan)
                    throw new EntityNotFoundException("presentation id {$presentation_id} does not have a selection plan");

                if (!$selection_plan->isAllowProposedSchedules())
                    throw new ValidationException("selection plan id {$selection_plan->getId()} does not allow proposed schedules");
            }

            if (is_null($schedule)) {
                $member = ResourceServerContext::getCurrentUser(false);
                $schedule = new SummitProposedSchedule();
                $schedule->setSource($source);
                $schedule->setSummit($event->getSummit());
                if (isset($payload['schedule_name'])) {
                    $schedule->setName($payload['schedule_name']);
                } else {
                    $schedule->setName("{$source} Proposed Schedule");
                }
                $schedule->setCreatedBy($member);
                $this->schedule_repository->add($schedule);
            }
            return $schedule;
        });

        return $this->publishProposedActivity($schedule->getId(), $presentation_id, $payload);
    }


    /**
     * @param IPublishableEvent $publishable_event
     * @param int|null $opening_hour
     * @param int|null $closing_hour
     * @return void
     * @throws ValidationException
     */
    protected function validateBlackOutTimesAndTimes(IPublishableEvent $publishable_event, ?int $opening_hour = null, ?int $closing_hour = null): void
    {

        $location = $publishable_event->getLocation();
        $track = $publishable_event->getCategory();

        Log::debug
        (
            sprintf
            (
                "ScheduleService::validateBlackOutTimesAndTimes event %s location %s track %s",
                $publishable_event->getSummitEventId(),
                is_null($location) ? "TBD" : $location->getId(),
                $track->getId()
            )
        );

        if (!$track->isProposedScheduleAllowedLocation($location))
            throw new ValidationException
            (
                sprintf
                (
                    "Location %s is not allowed for track %s on proposed schedule.",
                    $location->getName(),
                    $track->getTitle()
                )
            );

        // try to get the allowed location
        $allowed_location = $track->getProposedScheduleAllowedLocationByLocation($location);
        // try to get the opening hour restrictions from main location
        $opening_hour = is_null($location) ? null : $location->getOpeningHour();
        $closing_hour = is_null($location) ? null : $location->getClosingHour();

        Log::debug
        (
            sprintf
            (
                "ScheduleService::validateBlackOutTimesAndTimes event %s opening_hour %s closing_hour %s",
                $publishable_event->getSummitEventId(),
                $opening_hour,
                $closing_hour
            )
        );

        if ($allowed_location instanceof SummitProposedScheduleAllowedLocation && $allowed_location->hasTimeFrameRestrictions()) {
            // check if we have a time frame custom restriction
            $time_frame = $allowed_location->getAllowedTimeFrameForDates
            (
                $publishable_event->getStartDate(),
                $publishable_event->getEndDate()
            );

            if (!is_null($time_frame)) {
                $opening_hour = $time_frame->getOpeningHour();
                $closing_hour = $time_frame->getClosingHour();

                Log::debug
                (
                    sprintf
                    (
                        "ScheduleService::validateBlackOutTimesAndTimes location %s has custom restriction for date %s opening_hour %s closing_hour %s",
                        $location->getId(),
                        $publishable_event->getStartDate()->format("Y-m-d H:i:s"),
                        $opening_hour,
                        $closing_hour
                    )
                );
            }
        }

        parent::validateBlackOutTimesAndTimes($publishable_event, $opening_hour, $closing_hour);
    }

    /**
     * @inheritDoc
     */
    public function publishProposedActivity
    (
        int $schedule_id, int $presentation_id, array $payload
    ): SummitProposedScheduleSummitEvent
    {

        return $this->tx_service->transaction(function () use ($schedule_id, $presentation_id, $payload) {

            Log::debug
            (
                sprintf
                (
                    "ScheduleService::publishProposedActivity schedule_id %s presentation_id %s payload %s",
                    $schedule_id,
                    $presentation_id,
                    json_encode($payload)
                )
            );

            $schedule = $this->schedule_repository->getById($schedule_id);

            if (!$schedule instanceof SummitProposedSchedule)
                throw new EntityNotFoundException(sprintf("schedule id %s does not exists!", $schedule_id));

            $event = $this->event_repository->getById($presentation_id);

            if (!$event instanceof SummitEvent)
                throw new EntityNotFoundException(sprintf("event id %s does not exists!", $presentation_id));

            $member = ResourceServerContext::getCurrentUser(false);
            $summit = $schedule->getSummit();
            $category = $event->getCategory();

            if (!$this->isAuthorizedUser($member, $summit, $category))
                throw new AuthzException("User is not authorized to perform this action.");

            if ($schedule->hasLockFor($category))
                throw new ValidationException("This track is locked, its has been already submited for review.");

            $schedule_event = $schedule->getScheduledSummitEventByEvent($event);

            if (is_null($schedule_event)) {
                $schedule_event = new SummitProposedScheduleSummitEvent();
                $schedule_event->setSummitEvent($event);
                $schedule_event->setCreatedBy($member);
                $schedule_event->setSchedule($schedule);
                // only on creation
                $payload = $this->overrideEndDate($schedule_event, $payload);
            }

            $schedule_event = $this->updateLocation($payload, $summit, $schedule_event);
            $schedule_event = $this->updateEventDates($payload, $summit, $schedule_event);
            $this->checkTransitionTime($schedule_event, $schedule);
            $this->validateBlackOutTimesAndTimes($schedule_event);
            $schedule->addScheduledSummitEvent($schedule_event);
            $schedule_event->setUpdatedBy($member);
            return $schedule_event;
        });
    }

    /**
     * @inheritDoc
     */
    public function unPublishProposedActivity(string $source, int $presentation_id): void
    {
        $this->tx_service->transaction(function () use ($source, $presentation_id) {

            $event = $this->event_repository->getById($presentation_id);

            if (!$event instanceof SummitEvent)
                throw new EntityNotFoundException(sprintf("event id %s does not exists!", $presentation_id));

            if ($event instanceof Presentation) {
                $selection_plan = $event->getSelectionPlan();
                if (!$selection_plan instanceof SelectionPlan)
                    throw new EntityNotFoundException("presentation id {$presentation_id} does not have a selection plan");

                if (!$selection_plan->isAllowProposedSchedules())
                    throw new ValidationException("selection plan id {$selection_plan->getId()} does not allow proposed schedules");
            }

            $member = ResourceServerContext::getCurrentUser(false);

            if (!$this->isAuthorizedUser($member, $event->getSummit(), $event->getCategory()))
                throw new AuthzException("User is not authorized to perform this action");

            $schedule = $this->schedule_repository->getBySourceAndSummitId($source, $event->getSummitId());

            if (!$schedule instanceof SummitProposedSchedule)
                throw new EntityNotFoundException("schedule with source {$source} does not exists!");

            $category = $event->getCategory();

            if ($schedule->hasLockFor($category))
                throw new ValidationException("This track is locked, its has been already submited for review.");

            $schedule_event = $schedule->getScheduledSummitEventByEvent($event);

            if (is_null($schedule_event))
                throw new EntityNotFoundException(sprintf("schedule event for event id %s does not exists!", $presentation_id));

            $schedule->removeScheduledSummitEvent($schedule_event);
        });
    }

    /**
     * @inheritDoc
     */
    public function publishAll(string $source, int $summit_id, array $payload, ?Filter $filter = null): SummitProposedSchedule
    {
        Log::debug(sprintf("ScheduleService::publishAll summit id %s filter %s", $summit_id, is_null($filter) ? '' : $filter->__toString()));
        $member = ResourceServerContext::getCurrentUser(false);

        $schedule = $this->schedule_repository->getBySourceAndSummitId($source, $summit_id);

        if (!$schedule instanceof SummitProposedSchedule)
            throw new EntityNotFoundException("schedule with source {$source} does not exists!");

        if (!$this->isAuthorizedUser($member, $schedule->getSummit()))
            throw new AuthzException("User is not authorized to perform this action.");

        $done = isset($payload['event_ids']); // we have provided only ids and not a criteria
        $page = 1;
        $maxPageSize = 100;
        $errors = [];

        do {

            Log::debug(sprintf("ScheduleService::publishAll summit id %s filter %s processing page %s", $summit_id, is_null($filter) ? '' : $filter->__toString(), $page));

            $ids = $this->tx_service->transaction(function () use ($summit_id, $source, $payload, $filter, $page, $maxPageSize) {
                if (isset($payload['event_ids'])) {
                    Log::debug(sprintf("ScheduleService::publishAll summit id %s event_ids %s", $summit_id, json_encode($payload['event_ids'])));
                    $res = [];
                    foreach ($payload["event_ids"] as $event_id) {
                        $proposed = $this->proposed_events_repository->getBySummitSourceAndEventId($summit_id, $source, $event_id);
                        if (!is_null($proposed)) $res[] = $proposed->getId();
                    }
                    return $res;
                }

                Log::debug(sprintf("ScheduleService::publishAll summit id %s getting by filter", $summit_id));
                if (is_null($filter)) {
                    $filter = new Filter();
                }
                if (!$filter->hasFilter("summit_id"))
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit_id));
                if (!$filter->hasFilter("source"))
                    $filter->addFilterCondition(FilterElement::makeEqual('source', trim($source)));

                Log::debug(sprintf("ScheduleService::publishAll page %s", $page));
                return $this->proposed_events_repository->getAllIdsByPage(new PagingInfo($page, $maxPageSize), $filter);
            });

            Log::debug(sprintf("ScheduleService::publishAll summit id %s filter %s page %s got %s records", $summit_id, is_null($filter) ? '' : $filter->__toString(), $page, count($ids)));
            if (!count($ids)) {
                // if we are processing a page , then break it
                Log::debug(sprintf("ScheduleService::publishAll summit id %s page is empty, ending processing.", $summit_id));
                break;
            }

            foreach ($ids as $proposed_id) {
                try {
                    $this->tx_service->transaction(function () use ($source, $summit_id, $proposed_id, $payload) {

                        Log::debug(sprintf("ScheduleService::publishAll processing proposed id  %s", $proposed_id));

                        $proposed = $this->proposed_events_repository->getById($proposed_id);
                        if (!$proposed instanceof SummitProposedScheduleSummitEvent) {
                            Log::debug(sprintf("ScheduleService::publishAll skipping processing of proposed_id %s.", $proposed_id));
                            return;
                        }

                        $event = $proposed->getSummitEvent();

                        $this->summit_service->publishEvent($proposed->getSummit(), $event->getId(), [
                            'location_id' => $proposed->getLocationId(),
                            'start_date' => $proposed->getLocalStartDate()->getTimestamp(),
                            'end_date' => $proposed->getLocalEndDate()->getTimestamp(),
                            'duration' => $proposed->getDuration()
                        ]);

                    });
                } catch (\Exception $ex) {
                    $errors[] = $ex;
                }
            }

            $page++;

        } while (!$done);

        // error consolidation
        if (count($errors) > 0) {
            $msg = '';
            foreach ($errors as $error) {
                $msg .= $error->getMessage() . PHP_EOL;
            }
            throw new ValidationException($msg);
        }

        return $schedule;
    }

    /**
     * @inheritDoc
     */
    public function send2Review(Summit $summit, Member $member, string $source, int $track_id, array $payload): SummitProposedSchedule
    {
        Log::debug(sprintf("ScheduleService::send2Review summit id %s track id %s payload %s", $summit->getId(), $track_id, json_encode($payload)));

        return $this->tx_service->transaction(function () use ($summit, $member, $source, $track_id, $payload) {

            $track = $summit->getPresentationCategory($track_id);

            if (!$track instanceof PresentationCategory) {
                throw new EntityNotFoundException("Can not find a presentation category with id {$track_id} for summit id {$summit->getId()}.");
            }

            $schedule = $this->schedule_repository->getBySourceAndSummitId($source, $summit->getId());

            if (!$schedule instanceof SummitProposedSchedule) {
                throw new EntityNotFoundException("Can not find a proposed schedule for summit id {$summit->getId()} and source {$source}.");
            }

            $former_lock = $schedule->getLockFor($track);

            if (!is_null($former_lock)) {
                throw new ValidationException("This track already has a review submission.");
            }

            $track_chair = $summit->getTrackChairByMember($member);

            if (!$track_chair instanceof SummitTrackChair) {
                throw new EntityNotFoundException("Can not find a track chair for current member {$member->getId()}.");
            }

            if(!$summit->isTrackChair($member, $track)){
                throw new ValidationException("Current member is not a track chair for track id {$track->getId()}.");
            }

            $lock = new SummitProposedScheduleLock();

            if (isset($payload['message']))
                $lock->setReason($payload['message']);

            $lock->setTrack($track);
            $lock->setCreatedBy($track_chair);
            $schedule->addProposedScheduleLock($lock);

            SubmitForReviewEmail::dispatch($lock);

            return $schedule;
        });
    }

    /**
     * @inheritDoc
     */
    public function removeReview(Summit $summit, string $source, int $track_id, array $payload): SummitProposedSchedule
    {
        Log::debug(sprintf("ScheduleService::removeReview summit id %s track id %s payload %s", $summit->getId(), $track_id, json_encode($payload)));

        return $this->tx_service->transaction(function () use ($summit, $source, $track_id, $payload) {

            $track = $summit->getPresentationCategory($track_id);

            if (!$track instanceof PresentationCategory) {
                throw new EntityNotFoundException("Can not find a presentation category with id {$track_id} for summit id {$summit->getId()}.");
            }

            $schedule = $this->schedule_repository->getBySourceAndSummitId($source, $summit->getId());

            if (!$schedule instanceof SummitProposedSchedule) {
                throw new EntityNotFoundException("Can not find a proposed schedule for summit id {$summit->getId()} and source {$source}.");
            }

            $lock = $schedule->getLockFor($track);

            if (is_null($lock)) {
                throw new EntityNotFoundException("This track does not have a review submission.");
            }

            $schedule->removeProposedScheduleLock($lock);

            $message = $payload['message'] ?? "";

            UnsubmitForReviewEmail::dispatch($lock, $message);

            return $schedule;
        });
    }
}