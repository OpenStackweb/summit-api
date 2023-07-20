<?php namespace App\Models\Foundation\Summit\ProposedSchedule;
/*
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

use App\Models\Foundation\Summit\Events\SummitEventTypeConstants;
use App\Models\Foundation\Summit\IPublishableEventWithSpeakerConstraint;
use App\Models\Foundation\Summit\TimeDurationRestrictedEvent;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\PresentationCategory;
use models\summit\Summit;
use models\summit\SummitAbstractLocation;
use models\summit\SummitEvent;
use models\summit\SummitEventType;
use models\utils\SilverstripeBaseModel;

/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitProposedScheduleEventRepository")
 * @ORM\HasLifecycleCallbacks
 * Class SummitProposedScheduleSummitEvent
 * @ORM\Table(name="SummitProposedScheduleSummitEvent")
 * @package App\Models\Foundation\Summit\ProposedSchedule
 */
class SummitProposedScheduleSummitEvent
    extends SilverstripeBaseModel
    implements IPublishableEventWithSpeakerConstraint
{
    use TimeDurationRestrictedEvent;

    /**
     *  minimum number of minutes that an event must last
     */
    const MIN_EVENT_MINUTES = 1;

    /**
     * @ORM\Column(name="StartDate", type="datetime")
     * @var DateTime
     */
    protected $start_date;

    /**
     * @ORM\Column(name="EndDate", type="datetime")
     * @var DateTime
     */
    protected $end_date;

    /**
     * @ORM\Column(name="Duration", type="integer")
     * @var int
     */
    protected $duration;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitEvent", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="SummitEventID", referencedColumnName="ID", onDelete="SET NULL")
     * @var SummitEvent
     */
    protected $summit_event;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitAbstractLocation", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="LocationID", referencedColumnName="ID", onDelete="SET NULL")
     * @var SummitAbstractLocation
     */
    protected $location = null;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="CreatedByID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Member
     */
    protected $created_by = null;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="UpdatedByID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Member
     */
    protected $updated_by = null;

    /**
     * @ORM\ManyToOne(targetEntity="SummitProposedSchedule", fetch="EXTRA_LAZY", inversedBy="scheduled_summit_events")
     * @ORM\JoinColumn(name="ScheduleID", referencedColumnName="ID")
     * @var SummitProposedSchedule
     */
    protected $summit_proposed_schedule;

    /**
     * SummitProposedScheduleSummitEvent constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->start_date = null;
        $this->end_date = null;
        $this->duration = 0;
    }

    /**
     * @return DateTime|null
     */
    public function getStartDate(): ?DateTime
    {
        return $this->start_date;
    }

    /**
     * @return DateTime|null
     */
    public function getLocalStartDate(): ?DateTime
    {
        $summit = null;
        $summit_event = $this->summit_event;
        if (!is_null($summit_event))
            $summit = $summit_event->getSummit();

        return $this->_getLocalStartDate($summit);
    }

    /**
     * @param DateTime $value
     */
    public function setStartDate(DateTime $value)
    {
        $summit = null;
        $summit_event = $this->summit_event;
        if (!is_null($summit_event))
            $summit = $summit_event->getSummit();

        $this->_setStartDate($value, $summit);
    }

    /**
     * @return DateTime|null
     */
    public function getEndDate(): ?DateTime
    {
        return $this->end_date;
    }

    /**
     * @return DateTime|null
     */
    public function getLocalEndDate():?DateTime
    {
        $summit = null;
        $summit_event = $this->summit_event;
        if (!is_null($summit_event))
            $summit = $summit_event->getSummit();

        return $this->_getLocalEndDate($summit);
    }

    /**
     * @param DateTime $value
     */
    public function setEndDate(DateTime $value)
    {
        $summit = null;
        $summit_event = $this->summit_event;
        if (!is_null($summit_event))
            $summit = $summit_event->getSummit();

        $this->_setEndDate($value, $summit);
    }

    /**
     * @return int
     */
    public function getDuration(): int
    {
        Log::debug(sprintf("SummitProposedScheduleSummitEvent::getDuration event id %s", $this->id));
        if (!$this->duration) {
            if (!is_null($this->start_date) && !is_null(!is_null($this->end_date))) {
                Log::debug
                (
                    sprintf
                    (
                        "SummitProposedScheduleSummitEvent::getDuration event id %s start date %s end date %s",
                        $this->id,
                        $this->start_date->format("Y-m-d H:i:s"),
                        $this->end_date->format("Y-m-d H:i:s")
                    )
                );
                $this->duration = $this->end_date->getTimestamp() - $this->start_date->getTimestamp();
            }
            else {
                // if there is no dates set, default duration is the one from the summit event
                $this->duration = $this->summit_event->getDuration();
                Log::debug(sprintf("SummitProposedScheduleSummitEvent::getDuration overriding with summit event setting event id %s duration %s", $this->id, $this->duration));
            }
        }
        return $this->duration;
    }

    /**
     * @param int $duration_in_seconds
     * @param bool $skipDatesSetting
     * @throws ValidationException
     * @throws \Exception
     */
    public function setDuration(int $duration_in_seconds, bool $skipDatesSetting = false): void
    {
        $this->_setDuration($this->getSummit(), $duration_in_seconds, $skipDatesSetting);
    }

    /**
     * @return SummitEvent
     */
    public function getSummitEvent(): SummitEvent
    {
        return $this->summit_event;
    }

    /**
     * @return bool
     */
    public function hasSummitEvent(){
        return $this->getSummitEventId() > 0;
    }

    /**
     * @return int
     */
    public function getSummitEventId():int{
        try{
            return is_null($this->summit_event) ? 0 : $this->summit_event->getId();
        }
        catch (\Exception $ex){
            return 0;
        }
    }

    /**
     * @param SummitEvent $summit_event
     */
    public function setSummitEvent(SummitEvent $summit_event): void
    {
        $this->summit_event = $summit_event;
    }

    /**
     * @return SummitAbstractLocation|null
     */
    public function getLocation(): ?SummitAbstractLocation
    {
        return $this->location;
    }

    /**
     * @return bool
     */
    public function hasLocation(){
        return $this->getLocationId() > 0;
    }

    /**
     * @return int
     */
    public function getLocationId(){
        try{
            return is_null($this->location) ? 0 : $this->location->getId();
        }
        catch (\Exception $ex){
            return 0;
        }
    }

    /**
     * @param SummitAbstractLocation $location
     */
    public function setLocation(SummitAbstractLocation $location): void
    {
        $this->location = $location;
    }

    public function clearLocation()
    {
        $this->location = null;
        return $this;
    }

    /**
     * @return Member|null
     */
    public function getCreatedBy(): ?Member
    {
        return $this->created_by;
    }

    /**
     * @return bool
     */
    public function hasCreatedBy(){
        return $this->getCreatedById() > 0;
    }

    /**
     * @return int
     */
    public function getCreatedById(){
        try{
            return is_null($this->created_by) ? 0 : $this->created_by->getId();
        }
        catch (\Exception $ex){
            return 0;
        }
    }

    /**
     * @param Member $created_by
     */
    public function setCreatedBy(Member $created_by): void
    {
        $this->created_by = $created_by;
    }

    /**
     * @return Member
     */
    public function getUpdatedBy(): ?Member
    {
        return $this->updated_by;
    }

    /**
     * @return bool
     */
    public function hasUpdatedBy(){
        return $this->getUpdatedById() > 0;
    }

    /**
     * @return int
     */
    public function getUpdatedById(){
        try{
            return is_null($this->updated_by) ? 0 : $this->updated_by->getId();
        }
        catch (\Exception $ex){
            return 0;
        }
    }

    /**
     * @param Member $updated_by
     */
    public function setUpdatedBy(Member $updated_by): void
    {
        $this->updated_by = $updated_by;
    }

    /**
     * @return SummitProposedSchedule
     */
    public function getSchedule(): SummitProposedSchedule
    {
        return $this->summit_proposed_schedule;
    }

    /**
     * @return int
     */
    public function getScheduleId(){
        try{
            return is_null($this->summit_proposed_schedule) ? 0 : $this->summit_proposed_schedule->getId();
        }
        catch (\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasSchedule(){
        return $this->getScheduleId() > 0;
    }

    /**
     * @param SummitProposedSchedule $schedule
     */
    public function setSchedule(SummitProposedSchedule $schedule): void
    {
        $this->summit_proposed_schedule = $schedule;
    }

    public function clearSchedule(){
        $this->summit_proposed_schedule = null;
    }

    /**
     * @return SummitEventType|null
     */
    public function getType(): ?SummitEventType
    {
        return $this->summit_event->getType();
    }

    /**
     * @return bool
     */
    public function hasType(): bool
    {
        if (is_null($this->summit_event)) return false;
        return $this->summit_event->hasType();
    }

    public function getSummit(): Summit
    {
        return $this->summit_proposed_schedule->getSummit();
    }

    public function getTitle(): string
    {
        return $this->summit_event->getTitle();
    }

    public function getSpeakers()
    {
        return $this->summit_event->getSpeakers();
    }

    public function getLocationName(): string
    {
        return $this->summit_event->getLocationName();
    }

    /**
     * @return string
     */
    public function getEndDateNice(): string
    {
        $end_date = $this->getLocalEndDate();
        if (empty($end_date)) return 'TBD';
        return $end_date->format("Y-m-d H:i:s");
    }

    public function getStartDateNice(): string
    {
        $start_date = $this->getLocalStartDate();
        if (empty($start_date)) return 'TBD';
        return $start_date->format("Y-m-d H:i:s");
    }

    public function getCategory(): ?PresentationCategory
    {
        return $this->summit_event->getCategory();
    }

    public function getTrackTransitionTime(): ?int
    {
        return $this->summit_event->getTrackTransitionTime();
    }

    public function getSource(): string
    {
        return SummitEventTypeConstants::BLACKOUT_TIME_PROPOSED;
    }

    public function clearPublishingDates(): void
    {
        $this->start_date = null;
        $this->end_date = null;
    }
}