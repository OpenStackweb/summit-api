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
use App\Models\Foundation\Summit\IPublishableEvent;
use App\Models\Foundation\Summit\IPublishableEventWithSpeakerConstraint;
use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleSummitEvent;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\ISummitEventPublishRepository;
use models\summit\Summit;
use models\summit\SummitEvent;

/**
 * Class AbstractPublishService
 * @package App\Services\Model
 */
abstract class AbstractPublishService extends AbstractService
{
    /**
     * @var ISummitEventPublishRepository
     */
    protected $publish_repository;

    /**
     * AbstractPublishService constructor.
     * @param ISummitEventPublishRepository $event_repository
     * @param ITransactionService $tx_service
     */
    public function __construct(ISummitEventPublishRepository $event_repository,
                                ITransactionService $tx_service)
    {
        parent::__construct($tx_service);
        $this->publish_repository = $event_repository;
    }

    /**
     * @param IPublishableEvent $publishable_event
     * @param array $payload
     * @return array
     * @throws \Exception
     */
    protected function overrideEndDate(IPublishableEvent $publishable_event, array $payload):array{
        // override end_date if duration is set
        $duration_in_seconds = $publishable_event->getDuration();
        $summit = $publishable_event->getSummit();
        Log::debug
        (
            sprintf
            (
                "AbstractPublishService::overrideEndDate event %s duration %s",
                $publishable_event->getId(),
                $duration_in_seconds
            )
        );

        if($duration_in_seconds > 0) {
            $start_datetime = $summit->parseDateTime(intval($payload['start_date']));
            $value = $start_datetime->add(new \DateInterval('PT' . $duration_in_seconds . 'S'));
            $value = $summit->convertDateFromTimeZone2UTC($value);
            $payload['end_date'] = $value->getTimestamp();

            Log::debug
            (
                sprintf
                (
                    "AbstractPublishService::overrideEndDate event %s duration %s start_date %s end_date %s",
                    $publishable_event->getId(),
                    $duration_in_seconds,
                    $payload['start_date'],
                    $payload['end_date']
                )
            );
        }

        return $payload;
    }

    /**
     * @param array $data
     * @param Summit $summit
     * @param IPublishableEvent $publishable_event
     * @return IPublishableEvent
     * @throws \Exception
     */
    public function updateDuration(array $data, Summit $summit, IPublishableEvent $publishable_event): IPublishableEvent
    {
        return $this->tx_service->transaction(function () use ($data, $summit, $publishable_event) {
            Log::debug(sprintf("AbstractPublishService::updateDuration data %s summit %s event %s",
                json_encode($data), $summit->getId(), $publishable_event->getId()));
            if (isset($data['duration'])) {
                $current_member = ResourceServerContext::getCurrentUser(false);
                $publishable_event->setDuration(intval($data['duration']), false, $current_member);
            }
            return $publishable_event;
        });
    }

    /**
     * @param array $data
     * @param Summit $summit
     * @param IPublishableEvent $publishable_event
     * @return IPublishableEvent
     * @throws ValidationException
     * @throws \Exception
     */
    protected function updateEventDates
    (
        array $data, Summit $summit, IPublishableEvent $publishable_event
    ): IPublishableEvent
    {
        Log::debug
        (
            sprintf(
                "AbstractPublishService:updateEventDates summit %s event %s payload %s",
                $summit->getId(),
                $publishable_event->getId(),
                json_encode($data)
            )
        );

        $formerDuration = $publishable_event->getDuration();

        Log::debug
        (
            sprintf(
                "AbstractPublishService:updateEventDates summit %s event %s former duration %s",
                $summit->getId(),
                $publishable_event->getId(),
                $formerDuration
            )
        );

        if (isset($data['start_date']) && isset($data['end_date'])) {

            // we are setting dates

            if (!$publishable_event->hasType()) {
                throw new ValidationException("To be able to set schedule dates event type must be set First.");
            }

            $type = $publishable_event->getType();
            if (!$type->isAllowsPublishingDates())
                throw new ValidationException("Event Type does not allow schedule dates.");

            if ($publishable_event instanceof SummitEvent) {
                $publishable_event->setSummit($summit);
            }

            $start_datetime = $summit->parseDateTime(intval($data['start_date']));
            $end_datetime = $summit->parseDateTime(intval($data['end_date']));

            $interval_seconds = $end_datetime->getTimestamp() - $start_datetime->getTimestamp();
            $minutes = $interval_seconds / 60;

            if ($minutes < SummitProposedScheduleSummitEvent::MIN_EVENT_MINUTES)
                throw new ValidationException
                (
                    sprintf
                    (
                        "Event should last at least %s minutes - current duration %s",
                        SummitEvent::MIN_EVENT_MINUTES,
                        $minutes
                    )
                );

            Log::debug
            (
                sprintf(
                    "AbstractPublishService:updateEventDates summit %s event %s new duration %s",
                    $summit->getId(),
                    $publishable_event->getId(),
                    $minutes
                )
            );

            // set local time from UTC
            $publishable_event->setStartDate($start_datetime);
            $publishable_event->setEndDate($end_datetime);
        }

        return $this->updateDuration($data, $summit, $publishable_event);
    }

    /**
     * @param array $data
     * @param Summit $summit
     * @param IPublishableEvent $publishable_event
     * @return IPublishableEvent
     * @throws EntityNotFoundException
     */
    protected function updateLocation(
        array $data, Summit $summit, IPublishableEvent $publishable_event): IPublishableEvent {

        $location_id = intval($data['location_id']);
        $publishable_event->clearLocation();
        if ($location_id > 0) {
            $location = $summit->getLocation($location_id);
            if (is_null($location))
                throw new EntityNotFoundException("location id {$data['location_id']} does not exists!");
            $publishable_event->setLocation($location);
        }
        return $publishable_event;
    }

    /**
     * @param IPublishableEvent $publishable_event
     * @throws ValidationException
     */
    protected function validateBlackOutTimesAndTimes(IPublishableEvent $publishable_event, ?int $opening_hour = null, ?int $closing_hour= null):void
    {
        Log::debug(sprintf("AbstractPublishService::validateBlackOutTimesAndTimes event %s", $publishable_event->getSummitEventId()));

        $current_event_location = $publishable_event->getLocation();
        $eventType = $publishable_event->getType();

        if (!$eventType->isAllowsPublishingDates()) return;

        // validate current timeframe restriction

        if (!is_null($opening_hour)) {

            $closing_hour = $closing_hour ?? 2359;
            $event_opening_hour = intval($publishable_event->getLocalStartDate()->format('Hi'));
            $event_closing_hour = intval($publishable_event->getLocalEndDate()->format('Hi'));

            Log::debug
            (
                sprintf
                (
                    "AbstractPublishService::validateBlackOutTimesAndTimes event %s opening_hour %s closing_hour %s event_opening_hour %s event_closing_hour %s",
                    $publishable_event->getSummitEventId(),
                    $opening_hour,
                    $closing_hour,
                    $event_opening_hour,
                    $event_closing_hour
                )
            );

            if ($event_closing_hour < $opening_hour || $event_opening_hour > $event_closing_hour) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "You can't publish event %s  (from %s to %s) out of this time frame (from %s to %s) due to event location time restrictions.",
                        $publishable_event->getSummitEventId(),
                        $event_opening_hour,
                        $event_closing_hour,
                        $opening_hour,
                        $closing_hour
                    )
                );
            }
        }

        // validate blackout times
        $conflict_events = $this->publish_repository->getPublishedOnSameTimeFrame($publishable_event);
        if (count($conflict_events) > 0) {


            $apply_blackout_to_current_event = $eventType->isBlackoutAppliedTo($publishable_event);

            Log::debug
            (
                sprintf
                (
                    "AbstractPublishService::validateBlackOutTimesAndTimes event %s apply_blackout_to_current_event %b conflict events %s",
                    $publishable_event->getSummitEventId(),
                    $apply_blackout_to_current_event,
                    count($conflict_events)
                )
            );

            foreach ($conflict_events as $c_event) {

                $is_blackout_applied_2_conflicting_event  = $c_event->getType()->isBlackoutAppliedTo($c_event);
                Log::debug
                (
                    sprintf
                    (
                        "AbstractPublishService::validateBlackOutTimesAndTimes event %s conflict event %s "
                        ."current event location %s current event location override blackouts %b is_blackout_applied_2_conflicting_event %b",
                        $publishable_event->getSummitEventId(),
                        $c_event->getSummitEventId(),
                        is_null($current_event_location) ? 'TBD': $current_event_location->getId(),
                        is_null($current_event_location) ? false: $current_event_location->isOverrideBlackouts(),
                        $is_blackout_applied_2_conflicting_event
                    )
                );

                // if the published event is BlackoutTime or if there is a BlackoutTime event in this timeframe
                if ((!is_null($current_event_location) && !$current_event_location->isOverrideBlackouts()) &&
                    ($apply_blackout_to_current_event || $is_blackout_applied_2_conflicting_event) &&
                    $publishable_event->getSummitEventId() != $c_event->getSummitEventId()) {

                    throw new ValidationException
                    (
                        sprintf
                        (
                            "You can't publish event %s on this time frame (%s - %s), it conflicts with event %s (%s - %s) [BLACKOUT TIMEFRAME COLLISION].",
                            $publishable_event->getSummitEventId(),
                            $publishable_event->getStartDateNice(),
                            $publishable_event->getEndDateNice(),
                            $c_event->getSummitEventId(),
                            $c_event->getStartDateNice(),
                            $c_event->getEndDateNice()
                        )
                    );
                }

                if (!$eventType->isAllowsLocationTimeframeCollision()) {
                    // if trying to publish an event on a slot occupied by another event
                    // event collision ( same timeframe , same location)

                    if (!is_null($current_event_location) && !is_null($c_event->getLocation()) &&
                        $current_event_location->getId() == $c_event->getLocation()->getId() &&
                        $publishable_event->getSummitEventId() != $c_event->getSummitEventId()) {
                        throw new ValidationException
                        (
                            sprintf
                            (
                                "You can't publish event %s on this time frame (%s - %s), it conflicts with event %s (%s - %s) on location %s [LOCATION TIMEFRAME COLLISION].",
                                $publishable_event->getSummitEventId(),
                                $publishable_event->getStartDateNice(),
                                $publishable_event->getEndDateNice(),
                                $c_event->getSummitEventId(),
                                $c_event->getStartDateNice(),
                                $c_event->getEndDateNice(),
                                $c_event->getLocation()->getName()
                            )
                        );
                    }
                }

                // check speakers collisions
                if ($publishable_event instanceof IPublishableEventWithSpeakerConstraint &&
                    $c_event instanceof IPublishableEventWithSpeakerConstraint &&
                    $publishable_event->getSummitEventId() != $c_event->getSummitEventId()) {
                    if (!$eventType->isAllowsSpeakerEventCollision()) {
                        foreach ($publishable_event->getSpeakers() as $current_speaker) {
                            foreach ($c_event->getSpeakers() as $c_speaker) {
                                if (intval($c_speaker->getId()) === intval($current_speaker->getId())) {
                                    throw new ValidationException
                                    (
                                        sprintf
                                        (
                                            "You can't publish event %s (%s - %s) on this timeframe, speaker %s (%s) its present in room %s at this time for event %s (%s - %s) [SPEAKERS COLLISION].",
                                            $publishable_event->getSummitEventId(),
                                            $publishable_event->getStartDateNice(),
                                            $publishable_event->getEndDateNice(),
                                            $current_speaker->getFullName(),
                                            $current_speaker->getId(),
                                            $c_event->getLocationName(),
                                            $c_event->getSummitEventId(),
                                            $c_event->getStartDateNice(),
                                            $c_event->getEndDateNice()
                                        )
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}