<?php namespace models\summit;
/*
 * Copyright 2022 OpenStack Foundation
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

use App\Models\Foundation\Main\OrderableChilds;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\Common\Collections\ArrayCollection;
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitScheduleConfigRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="schedule_settings"
 *     )
 * })
 * @ORM\Table(name="SummitScheduleConfig")
 * Class SummitScheduleConfig
 * @package models\summit
 */
class SummitScheduleConfig extends SilverstripeBaseModel
{
    use SummitOwned;
    use OrderableChilds;

    const ColorSource_EventType = 'EVENT_TYPES';
    const ColorSource_Track = 'TRACK';
    const ColorSource_TrackGroup = 'TRACK_GROUP';
    const AllowedColorSource = [self::ColorSource_EventType, self::ColorSource_Track, self::ColorSource_TrackGroup];

    const TimeFormat_12 = '12h';
    const TimeFormat_24 = '24h';
    const AllowedTimeFormats = [self::TimeFormat_12, self::TimeFormat_24];

    /**
     * @ORM\Column(name="Key", type="string")
     * @var string
     */
    private $key;

    /**
     * @ORM\Column(name="IsEnabled", type="boolean")
     * @var bool
     */
    private $is_enabled;

    /**
     * @ORM\Column(name="IsMySchedule", type="boolean")
     * @var bool
     */
    private $is_my_schedule;

    /**
     * @ORM\Column(name="IsDefault", type="boolean")
     * @var bool
     */
    private $is_default;

    /**
     * @ORM\Column(name="OnlyEventsWithAttendeeAccess", type="boolean")
     * @var bool
     */
    private $only_events_with_attendee_access;

    /**
     * @ORM\Column(name="ColorSource", type="string")
     * @var string
     */
    private $color_source;

    /**
     * @ORM\OneToMany(targetEntity="SummitScheduleFilterElementConfig", mappedBy="config", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitScheduleFilterElementConfig[]
     */
    private $filters;

    /**
     * @ORM\OneToMany(targetEntity="SummitSchedulePreFilterElementConfig", mappedBy="config", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SummitSchedulePreFilterElementConfig[]
     */
    private $pre_filters;

    /**
     * @ORM\Column(name="HidePastEventsWithShowAlwaysOnSchedule", type="boolean")
     * @var bool
     */
    private $hide_past_events_with_show_always_on_schedule;

    /**
     * @ORM\Column(name="TimeFormat", type="string")
     * @var string
     */
    private $time_format;

    public function __construct()
    {
        parent::__construct();
        $this->is_enabled = false;
        $this->is_my_schedule = false;
        $this->only_events_with_attendee_access = false;
        $this->color_source = self::ColorSource_EventType;
        $this->is_default = false;
        $this->hide_past_events_with_show_always_on_schedule = false;
        $this->filters = new ArrayCollection();
        $this->pre_filters = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * @param bool $is_enabled
     */
    public function setIsEnabled(bool $is_enabled): void
    {
        $this->is_enabled = $is_enabled;
    }

    /**
     * @return bool
     */
    public function isMySchedule(): bool
    {
        return $this->is_my_schedule;
    }

    /**
     * @param bool $is_my_schedule
     */
    public function setIsMySchedule(bool $is_my_schedule): void
    {
        $this->is_my_schedule = $is_my_schedule;
    }

    /**
     * @return bool
     */
    public function isOnlyEventsWithAttendeeAccess(): bool
    {
        return $this->only_events_with_attendee_access;
    }

    /**
     * @param bool $only_events_with_attendee_access
     */
    public function setOnlyEventsWithAttendeeAccess(bool $only_events_with_attendee_access): void
    {
        $this->only_events_with_attendee_access = $only_events_with_attendee_access;
    }

    /**
     * @return string
     */
    public function getColorSource(): string
    {
        return $this->color_source;
    }

    /**
     * @param string $color_source
     * @throws ValidationException
     */
    public function setColorSource(string $color_source): void
    {
        if(!in_array($color_source, self::AllowedColorSource))
            throw new ValidationException(sprintf("Color Source %s is not allowed.", $color_source));
        $this->color_source = $color_source;
    }

    /**
     * @return SummitScheduleFilterElementConfig[]
     */
    public function getFilters()
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'ASC']);
        return $this->filters->matching($criteria);
    }

    public function clearFilters():void{
        $this->filters->clear();
    }

    /**
     * @param SummitScheduleFilterElementConfig $filter
     * @throws ValidationException
     */
    public function addFilter(SummitScheduleFilterElementConfig $filter){
        if($this->filters->contains($filter)) return;
        // check type
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', trim($filter->getType())));
        if($this->filters->matching($criteria)->count() > 0)
            throw new ValidationException(sprintf("Type %s already exists", $filter->getType()));
        $filter->setOrder($this->getFilterMaxOrder() + 1);
        $this->filters->add($filter);
        $filter->setConfig($this);
    }

    /**
     * @param SummitScheduleFilterElementConfig $filter
     */
    public function removeFilter(SummitScheduleFilterElementConfig $filter){
        if(!$this->filters->contains($filter)) return;
        $this->filters->removeElement($filter);
        self::resetOrderForSelectable($this->filters);
        $filter->clearConfig();
    }

    /**
     * @return SummitSchedulePreFilterElementConfig[]
     */
    public function getPreFilters()
    {
        return $this->pre_filters;
    }

    public function clearPreFilters():void{
        $this->pre_filters->clear();
    }

    /**
     * @param SummitSchedulePreFilterElementConfig $filter
     * @throws ValidationException
     */
    public function addPreFilter(SummitSchedulePreFilterElementConfig $filter){
        if($this->pre_filters->contains($filter)) return;
        // check type
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('type', trim($filter->getType())));
        if($this->pre_filters->matching($criteria)->count() > 0)
            throw new ValidationException(sprintf("Type %s already exists", $filter->getType()));

        $this->pre_filters->add($filter);
        $filter->setConfig($this);
    }

    public function removePreFilter(SummitSchedulePreFilterElementConfig $filter){
        if(!$this->pre_filters->contains($filter)) return;
        $this->pre_filters->removeElement($filter);
        $filter->clearConfig();
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * @param bool $is_default
     */
    public function setIsDefault(bool $is_default): void
    {
        $this->is_default = $is_default;
    }


    /**
     * @return int
     */
    private function getFilterMaxOrder(): int
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $filter = $this->filters->matching($criteria)->first();
        return $filter === false ? 0 : $filter->getOrder();
    }

    /**
     * @param SummitScheduleFilterElementConfig $filter
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateFilterOrder(SummitScheduleFilterElementConfig $filter, int $new_order)
    {
        self::recalculateOrderForSelectable($this->filters, $filter, $new_order);
    }

    /**
     * @return bool
     */
    public function isHidePastEventsWithShowAlwaysOnSchedule(): bool
    {
        return $this->hide_past_events_with_show_always_on_schedule;
    }

    /**
     * @param bool $hide_past_events_with_show_always_on_schedule
     */
    public function setHidePastEventsWithShowAlwaysOnSchedule(bool $hide_past_events_with_show_always_on_schedule): void
    {
        $this->hide_past_events_with_show_always_on_schedule = $hide_past_events_with_show_always_on_schedule;
    }

    /**
     * @return string
     */
    public function getTimeFormat(): string
    {
        return $this->time_format;
    }

    /**
     * @param string $time_format
     * @throws ValidationException
     */
    public function setTimeFormat(string $time_format): void
    {
        if(!in_array($time_format, self::AllowedTimeFormats))
            throw new ValidationException(sprintf("Time Format %s is not allowed.", $time_format));
        $this->time_format = $time_format;
    }
}