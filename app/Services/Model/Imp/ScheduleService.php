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
use App\Models\Exceptions\AuthzException;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\SelectionPlan;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\ISummitEventRepository;
use models\summit\ISummitProposedScheduleRepository;
use models\summit\Presentation;
use models\summit\PresentationCategory;
use models\summit\Summit;
use models\summit\SummitEvent;
use models\summit\SummitProposedSchedule;
use models\summit\SummitProposedScheduleSummitEvent;

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
     * @var ISummitEventRepository
     */
    private $event_repository;

    /**
     * ScheduleService constructor.
     * @param ISummitProposedScheduleRepository $schedule_repository
     * @param ISummitEventRepository $event_repository
     * @param ITransactionService $tx_service
     */
    public function __construct(ISummitProposedScheduleRepository $schedule_repository,
                                ISummitEventRepository $event_repository,
                                ITransactionService $tx_service)
    {
        parent::__construct($schedule_repository, $tx_service);
        $this->schedule_repository = $schedule_repository;
        $this->event_repository = $event_repository;
    }

    private function isAuthorizedUser(Member $member, Summit $summit, PresentationCategory $category): bool {
        if ($member->isAdmin()) return true;
        if ($member->isSummitAdmin()) return true;
        return $summit->isTrackChair($member, $category);
    }

    /**
     *@inheritDoc
     */
    public function publishProposedActivityToSource(
        string $source, int $presentation_id, array $payload):SummitProposedScheduleSummitEvent {

        $schedule = $this->tx_service->transaction(function () use ($source, $presentation_id, $payload) {

            $event = $this->event_repository->getById($presentation_id);
            if (!$event instanceof Presentation)
                throw new EntityNotFoundException("event id {$presentation_id} does not exists!");

            $schedule = null;
            $schedules = $this->schedule_repository->getBySourceAndSummitId($source, $event->getSummitId());

            $selection_plan = $event->getSelectionPlan();
            if (!$selection_plan instanceof SelectionPlan)
                throw new EntityNotFoundException("event id {$presentation_id} does not have a selection plan");

            if (!$selection_plan->isAllowProposedSchedules())
                throw new ValidationException("selection plan id {$selection_plan->getId()} does not allow proposed schedules");

            if (count($schedules) == 0) {
                $member = ResourceServerContext::getCurrentUser(false);
                $schedule = new SummitProposedSchedule();
                $schedule->setSource($source);
                $schedule->setSummit($event->getSummit());
                if (isset($data['schedule_name'])) {
                    $schedule->setName($data['schedule_name']);
                } else {
                    $schedule->setName("{$source} Proposed Schedule");
                }
                $schedule->setCreatedBy($member);
                $this->schedule_repository->add($schedule);
            } else {
                $schedule = $schedules[0];
            }
            return $schedule;
        });

        return $this->publishProposedActivity($schedule->getId(), $presentation_id, $payload);
    }

    /**
     *@inheritDoc
     */
    public function publishProposedActivity(
        int $schedule_id, int $presentation_id, array $payload):SummitProposedScheduleSummitEvent {

        return $this->tx_service->transaction(function () use ($schedule_id, $presentation_id, $payload) {

            $schedule = $this->schedule_repository->getById($schedule_id);

            if (!$schedule instanceof SummitProposedSchedule)
                throw new EntityNotFoundException(sprintf("schedule id %s does not exists!", $schedule_id));

            $event = $this->event_repository->getById($presentation_id);

            if (!$event instanceof SummitEvent)
                throw new EntityNotFoundException(sprintf("event id %s does not exists!", $presentation_id));

            $member = ResourceServerContext::getCurrentUser(false);
            $summit = $schedule->getSummit();

            if (!$this->isAuthorizedUser( $member, $summit, $event->getCategory()))
                throw new AuthzException("User is not authorized to perform this action");

            $schedule_event = $schedule->getScheduledSummitEventByEvent($event);

            if (is_null($schedule_event)) {
                $default_date = new \DateTime("now", new \DateTimeZone("UTC"));
                $schedule_event = new SummitProposedScheduleSummitEvent();
                $schedule_event->setSummitEvent($event);
                $schedule_event->setCreatedBy($member);
                $schedule_event->setSchedule($schedule);
                $schedule_event->setStartDate($event->getStartDate() ?? $default_date);
                $schedule_event->setEndDate($event->getEndDate() ?? $default_date);
            }
            $schedule_event = $this->updateLocation($payload, $summit, $schedule_event);
            $schedule_event = $this->updateEventDates($payload, $summit, $schedule_event);
            $this->validateBlackOutTimesAndTimes($schedule_event);
            $schedule->addScheduledSummitEvent($schedule_event);
            $schedule_event->setUpdatedBy($member);
            return $schedule_event;
        });
    }

    /**
     *@inheritDoc
     */
    public function unPublishProposedActivity(string $source, int $presentation_id):SummitProposedScheduleSummitEvent {
        return $this->tx_service->transaction(function () use ($source, $presentation_id) {

            $event = $this->event_repository->getById($presentation_id);

            if (!$event instanceof SummitEvent)
                throw new EntityNotFoundException(sprintf("event id %s does not exists!", $presentation_id));

            $selection_plan = $event->getSelectionPlan();
            if (!$selection_plan instanceof SelectionPlan)
                throw new EntityNotFoundException("event id {$presentation_id} does not have a selection plan");

            if (!$selection_plan->isAllowProposedSchedules())
                throw new ValidationException("selection plan id {$selection_plan->getId()} does not allow proposed schedules");

            $member = ResourceServerContext::getCurrentUser(false);

            if (!$this->isAuthorizedUser( $member, $event->getSummit(), $event->getCategory()))
                throw new AuthzException("User is not authorized to perform this action");

            $schedules = $this->schedule_repository->getBySourceAndSummitId($source, $event->getSummitId());

            if (count($schedules) == 0)
                throw new EntityNotFoundException("schedule with source {$source} does not exists!");

            $schedule = $schedules[0];

            $schedule_event = $schedule->getScheduledSummitEventByEvent($event);

            if (is_null($schedule_event))
                throw new EntityNotFoundException(sprintf("schedule event for event id %s does not exists!", $presentation_id));

            $schedule->removeScheduledSummitEvent($schedule_event);

            return $schedule_event;
        });
    }

    /**
     *@inheritDoc
     */
    public function publishAll(string $source, int $summit_id, array $payload):SummitProposedSchedule
    {
        return $this->tx_service->transaction(function () use ($source, $summit_id, $payload) {

            $schedules = $this->schedule_repository->getBySourceAndSummitId($source, $summit_id);

            if (count($schedules) == 0) {
                throw new EntityNotFoundException("schedule with source {$source} does not exists!");
            }

            $schedule = $schedules[0];

            $filtered_schedule_events = [];

            if (isset($payload['presentation_ids'])) {
                //filter criteria to promote schedule events by event_ids
                $event_ids = $payload['presentation_ids'];
                foreach ($event_ids as $event_id) {
                    $event = $this->event_repository->getById(intval($event_id));
                    if (!$event instanceof SummitEvent) continue;
                    $schedule_event = $schedule->getScheduledSummitEventByEvent($event);
                    if (is_null($schedule_event)) continue;
                    $filtered_schedule_events[] = $schedule_event;
                }
            } else {
                //filter criteria to promote schedule events by start_date, end_date and location_id
                $summit = $schedule->getSummit();
                $start_date = null;
                $end_date = null;
                $location = null;
                if (isset($payload['start_date'])) {
                    $start_date = $summit->parseDateTime(intval($payload['start_date']));
                }
                if (isset($payload['end_date'])) {
                    $end_date = $summit->parseDateTime(intval($payload['end_date']));
                }
                if (isset($payload['location_id'])) {
                    $location = $summit->getLocation(intval($payload['location_id']));
                }
                $filtered_schedule_events =
                    $schedule->getScheduledSummitEventsByLocationAndDateRange($start_date, $end_date, $location);
            }

            if (count($filtered_schedule_events) > 0) {
                $schedule->clearScheduledSummitEvents();

                foreach ($filtered_schedule_events as $filtered_schedule_event) {
                    $event = $filtered_schedule_event->getSummitEvent();
                    if ($event instanceof SummitEvent) {
                        $event->setStartDate($filtered_schedule_event->getStartDate());
                        $event->setEndDate($filtered_schedule_event->getEndDate());
                        $event->setLocation($filtered_schedule_event->getLocation());
                    }
                    $schedule->addScheduledSummitEvent($filtered_schedule_event);
                }
            }

            return $schedule;
        });
    }
}