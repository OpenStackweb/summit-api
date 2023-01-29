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
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\ISummitEventRepository;
use models\summit\ISummitProposedScheduleRepository;
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

    /**
     *@inheritDoc
     */
    public function publishProposedActivityToSource(
        string $source, int $presentation_id, array $payload):SummitProposedScheduleSummitEvent {

        $schedule = $this->tx_service->transaction(function () use ($source, $presentation_id, $payload) {
            $schedule = null;
            $schedules = $this->schedule_repository->getBySource($source);

            if (count($schedules) == 0) {
                $event = $this->event_repository->getById($presentation_id);
                if (!$event instanceof SummitEvent)
                    throw new EntityNotFoundException(sprintf("event id %s does not exists!", $presentation_id));

                $schedule = new SummitProposedSchedule();
                $schedule->setSource($source);
                $schedule->setSummit($event->getSummit());
                if (isset($data['schedule_name'])) {
                    $schedule->setName($data['schedule_name']);
                } else {
                    $schedule->setName("{$source} Proposed Schedule");
                }
                $schedule->setCreatedBy(ResourceServerContext::getCurrentUser(false));
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
            $schedule_event = $schedule->getScheduledSummitEventByEvent($event);

            if (is_null($schedule_event)) {
                $schedule_event = new SummitProposedScheduleSummitEvent();
                $schedule_event->setSummitEvent($event);
                $schedule_event->setCreatedBy($member);
                $schedule_event->setSchedule($schedule);
                $schedule_event->setStartDate($event->getStartDate());
                $schedule_event->setEndDate($event->getEndDate());
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
    public function unPublishProposedActivity(int $schedule_id, int $presentation_id):SummitProposedScheduleSummitEvent {
        return $this->tx_service->transaction(function () use ($schedule_id, $presentation_id) {

            $schedule = $this->schedule_repository->getById($schedule_id);

            if (is_null($schedule))
                throw new EntityNotFoundException(sprintf("schedule id %s does not exists!", $schedule_id));

            $event = $this->event_repository->getById($presentation_id);

            if (!$event instanceof SummitEvent)
                throw new EntityNotFoundException(sprintf("event id %s does not exists!", $presentation_id));

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
    public function publishAll(int $schedule_id, array $payload)
    {
        return $this->tx_service->transaction(function () use ($schedule_id, $payload) {

            $schedule = $this->schedule_repository->getById($schedule_id);

            if (!$schedule instanceof SummitProposedSchedule)
                throw new EntityNotFoundException(sprintf("schedule id %s does not exists!", $schedule_id));

            $filtered_schedule_events = [];

            if (isset($payload['event_ids'])) {
                //filter criteria to promote schedule events by event_ids
                $event_ids = $payload['event_ids'];
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
                    $start_date = intval($payload['start_date']);
                    $start_date = new \DateTime("@$start_date");
                    $start_date->setTimezone($summit->getTimeZone());
                }
                if (isset($payload['end_date'])) {
                    $end_date = intval($payload['end_date']);
                    $end_date = new \DateTime("@$end_date");
                    $end_date->setTimezone($summit->getTimeZone());
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

            return $filtered_schedule_events;
        });
    }
}