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
use App\Models\Foundation\Summit\SelectionPlan;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\ISummitEventRepository;
use models\summit\ISummitProposedScheduleEventRepository;
use models\summit\ISummitProposedScheduleRepository;
use models\summit\Presentation;
use models\summit\PresentationCategory;
use models\summit\Summit;
use models\summit\SummitEvent;
use models\summit\SummitProposedSchedule;
use models\summit\SummitProposedScheduleSummitEvent;
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
     * @inheritDoc
     */
    public function publishProposedActivityToSource(
        string $source, int $presentation_id, array $payload): SummitProposedScheduleSummitEvent
    {

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
     * @inheritDoc
     */
    public function publishProposedActivity(
        int $schedule_id, int $presentation_id, array $payload): SummitProposedScheduleSummitEvent
    {

        return $this->tx_service->transaction(function () use ($schedule_id, $presentation_id, $payload) {

            $schedule = $this->schedule_repository->getById($schedule_id);

            if (!$schedule instanceof SummitProposedSchedule)
                throw new EntityNotFoundException(sprintf("schedule id %s does not exists!", $schedule_id));

            $event = $this->event_repository->getById($presentation_id);

            if (!$event instanceof SummitEvent)
                throw new EntityNotFoundException(sprintf("event id %s does not exists!", $presentation_id));

            $member = ResourceServerContext::getCurrentUser(false);
            $summit = $schedule->getSummit();

            if (!$this->isAuthorizedUser($member, $summit, $event->getCategory()))
                throw new AuthzException("User is not authorized to perform this action.");

            $schedule_event = $schedule->getScheduledSummitEventByEvent($event);

            if (is_null($schedule_event)) {
                $schedule_event = new SummitProposedScheduleSummitEvent();
                $schedule_event->setSummitEvent($event);
                $schedule_event->setCreatedBy($member);
                $schedule_event->setSchedule($schedule);
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
        $member = ResourceServerContext::getCurrentUser(false);

        $schedule = $this->schedule_repository->getBySourceAndSummitId($source, $summit_id);

        if (!$schedule instanceof SummitProposedSchedule)
            throw new EntityNotFoundException("schedule with source {$source} does not exists!");

        if (!$this->isAuthorizedUser($member, $schedule->getSummit()))
            throw new AuthzException("User is not authorized to perform this action");

        $done = isset($payload['event_ids']); // we have provided only ids and not a criteria
        $page = 1;
        $count = 0;
        $maxPageSize = 100;
        $validation_errors = [];

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

                $res = $this->tx_service->transaction(function () use ($source, $summit_id, $proposed_id, $payload) {
                    try {
                        Log::debug(sprintf("ScheduleService::publishAll processing proposed id  %s", $proposed_id));
                        $proposed = $this->proposed_events_repository->getById($proposed_id);
                        if (!$proposed instanceof SummitProposedScheduleSummitEvent) {
                            Log::debug(sprintf("ScheduleService::publishAll skippoing processing of proposed_id %s", $proposed_id));
                            return null;
                        }

                        $event = $proposed->getSummitEvent();
                        if ($event instanceof SummitEvent)
                            $this->summit_service->publishEvent($proposed->getSummit(), $event->getId(), [
                                'location_id' => $proposed->getLocationId(),
                                'start_date' => $proposed->getLocalStartDate()->getTimestamp(),
                                'end_date' => $proposed->getLocalEndDate()->getTimestamp(),
                                'duration' => $proposed->getDuration()
                            ]);

                        return $event;
                    } catch (ValidationException $ex) {
                        Log::warning($ex);
                        return $ex;
                    } catch (\Exception $ex) {
                        Log::error($ex);
                        return $ex;
                    }
                });
                if ($res instanceof ValidationException) {
                    $validation_errors[] = $res;
                }
                $count++;
            }

            $page++;

        } while (!$done);

        // error consolidation
        if (count($validation_errors) > 0) {
            $msg = '';
            foreach ($validation_errors as $error) {
                $msg .= $error->getMessage() . PHP_EOL;
            }
            throw new ValidationException($msg);
        }
        return $schedule;
    }
}