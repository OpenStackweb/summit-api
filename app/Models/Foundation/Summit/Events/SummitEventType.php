<?php namespace models\summit;
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

use App\Models\Foundation\Summit\Events\SummitEventTypeConstants;
use App\Models\Foundation\Summit\IPublishableEvent;
use App\Models\Foundation\Summit\ScheduleEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitEventTypeRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="event_types"
 *     )
 * })
 * @ORM\Table(name="SummitEventType")
 * @ORM\InheritanceType("JOINED")
 * @ORM\HasLifecycleCallbacks
 * @ORM\DiscriminatorColumn(name="ClassName", type="string")
 * @ORM\DiscriminatorMap({"SummitEventType" = "SummitEventType", "PresentationType" = "PresentationType"})
 */
class SummitEventType extends SilverstripeBaseModel
{
    use SummitOwned;

    /**
     * @ORM\Column(name="Type", type="string")
     * @var string
     */
    protected $type;

    /**
     * @ORM\Column(name="Color", type="string")
     * @var string
     */
    protected $color;

    /**
     * @ORM\Column(name="BlackoutTimes", type="string")
     * @var string
     */
    protected $blackout_times;

    /**
     * @ORM\Column(name="UseSponsors", type="boolean")
     * @var bool
     */
    protected $use_sponsors;

    /**
     * @ORM\Column(name="AreSponsorsMandatory", type="boolean")
     * @var bool
     */
    protected $are_sponsors_mandatory;

    /**
     * @ORM\Column(name="AllowsAttachment", type="boolean")
     * @var bool
     */
    protected $allows_attachment;

    /**
     * @ORM\Column(name="AllowsLevel", type="boolean")
     * @var bool
     */
    protected $allows_level;

    /**
     * @ORM\Column(name="IsDefault", type="boolean")
     * @var bool
     */
    protected $is_default;

    /**
     * @ORM\Column(name="AllowsPublishingDates", type="boolean")
     * @var bool
     */
    protected $allows_publishing_dates;

    /**
     * @ORM\Column(name="AllowsLocation", type="boolean")
     * @var bool
     */
    protected $allows_location;

    /**
     * @ORM\Column(name="IsPrivate", type="boolean")
     * @var bool
     */
    protected $is_private;

    /**
     * @ORM\Column(name="AllowsLocationAndTimeFrameCollision", type="boolean")
     * @var bool
     */
    protected $allows_location_timeframe_collision;

    /**
     * @ORM\Column(name="ShowAlwaysOnSchedule", type="boolean")
     * @var bool
     */
    protected $show_always_on_schedule;

    /**
     * @ORM\ManyToMany(targetEntity="SummitDocument", mappedBy="event_types")
     */
    protected $summit_documents;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * @return string
     */
    public function getBlackoutTimes()
    {
        return $this->blackout_times;
    }

    /**
     * @return bool
     */
    public function isBlackoutAppliedTo(IPublishableEvent $publishable_event){
        Log::debug(sprintf("SummitEventType::isBlackoutAppliedTo blackout_times %s event %s event source %s",
            $this->blackout_times,
            $publishable_event->getId(),
            $publishable_event->getSource())
        );
        if ($this->blackout_times === SummitEventTypeConstants::BLACKOUT_TIME_ALL) return true;
        if ($this->blackout_times === SummitEventTypeConstants::BLACKOUT_TIME_NONE) return false;
        return $this->blackout_times === $publishable_event->getSource();
    }

    /**
     * @param string|null $blackout_times
     * @throws ValidationException
     */
    public function setBlackoutTimes(?string $blackout_times)
    {
        if (!in_array($blackout_times, SummitEventTypeConstants::$valid_blackout_times))
            throw new ValidationException("{$blackout_times} is not a valid blackout time target");
        $this->blackout_times = $blackout_times;
    }



    /**
     * @param Summit $summit
     * @param string $type
     * @return bool
     */
    static public function IsSummitEventType(Summit $summit, $type){
        return !PresentationType::IsPresentationEventType($summit, $type);
    }

    /**
     * @return bool
     */
    public function isUseSponsors()
    {
        return $this->use_sponsors;
    }

    /**
     * @return bool
     */
    public function isAreSponsorsMandatory()
    {
        return $this->are_sponsors_mandatory;
    }

    /**
     * @return bool
     */
    public function isAllowsAttachment()
    {
        return $this->allows_attachment;
    }

    public function getClassName(){
        return 'SummitEventType';
    }

    const ClassName = 'EVENT_TYPE';

    /**
     * @return boolean
     */
    public function isDefault()
    {
        return $this->is_default;
    }

    public function setAsDefault()
    {
        $this->is_default = true;
    }

    public function setAsNonDefault()
    {
        $this->is_default = false;
    }

    public function setIsDefault(bool $is_default){
        $this->is_default = $is_default;
    }

    /**
     * @param bool $use_sponsors
     */
    public function setUseSponsors($use_sponsors)
    {
        $this->use_sponsors = $use_sponsors;
    }

    /**
     * @param bool $are_sponsors_mandatory
     */
    public function setAreSponsorsMandatory($are_sponsors_mandatory)
    {
        $this->are_sponsors_mandatory = $are_sponsors_mandatory;
    }

    /**
     * @param bool $allows_attachment
     */
    public function setAllowsAttachment($allows_attachment)
    {
        $this->allows_attachment = $allows_attachment;
    }

    public function __construct()
    {
        parent::__construct();
        $this->is_default              = false;
        $this->use_sponsors            = false;
        $this->blackout_times          = false;
        $this->are_sponsors_mandatory  = false;
        $this->allows_attachment       = false;
        $this->is_private              = false;
        $this->allows_level            = false;
        $this->allows_location         = true;
        $this->allows_publishing_dates = true;
        $this->allows_location_timeframe_collision = false;
        $this->show_always_on_schedule = false;
        $this->summit_documents        = new ArrayCollection();

    }

    /**
     * @return bool
     */
    public function isPrivate()
    {
        return $this->is_private;
    }

    /**
     * @param bool $is_private
     */
    public function setIsPrivate($is_private)
    {
        $this->is_private = $is_private;
    }

    /**
     * @return SummitEvent[]
     */
    public function getRelatedPublishedSummitEvents(){
        $query = <<<SQL
SELECT e  
FROM  models\summit\SummitEvent e
WHERE 
e.published = 1
AND e.summit = :summit
AND e.type = :type
SQL;

        $native_query = $this->getEM()->createQuery($query);

        $native_query->setParameter("summit", $this->summit);
        $native_query->setParameter("type", $this);

        $res =  $native_query->getResult();

        return $res;
    }

    /**
     * @return int[]
     */
    public function getRelatedPublishedSummitEventsIds(){
        $query = <<<SQL
SELECT e.id  
FROM  models\summit\SummitEvent e
WHERE 
e.published = 1
AND e.summit = :summit
AND e.type = :type
SQL;

        $native_query = $this->getEM()->createQuery($query);

        $native_query->setParameter("summit", $this->summit);
        $native_query->setParameter("type", $this);

        $res =  $native_query->getResult();

        return $res;
    }

    public function hasSummitDocuments():bool{
        return $this->summit_documents->count() > 0;
    }

    public function getSummitDocuments(){
        return $this->summit_documents;
    }

    public function addSummitDocument(SummitDocument $doc){
        if($this->summit_documents->contains($doc)) return;
        $this->summit_documents->add($doc);
    }

    public function removeSummitDocument(SummitDocument $doc){
        if(!$this->summit_documents->contains($doc)) return;
        $this->summit_documents->removeElement($doc);
    }

    public function clearSummitDocuments(){
        $this->summit_documents->clear();
    }

    /**
     * @return bool
     */
    public function isAllowsLevel(): bool
    {
        return $this->allows_level;
    }

    /**
     * @param bool $allows_level
     */
    public function setAllowsLevel(bool $allows_level): void
    {
        $this->allows_level = $allows_level;
    }

    /**
     * @return bool
     */
    public function isAllowsPublishingDates(): bool
    {
        return $this->allows_publishing_dates;
    }

    /**
     * @param bool $allows_publishing_dates
     */
    public function setAllowsPublishingDates(bool $allows_publishing_dates): void
    {
        $this->allows_publishing_dates = $allows_publishing_dates;
    }

    /**
     * @return bool
     */
    public function isAllowsLocation(): bool
    {
        return $this->allows_location;
    }

    /**
     * @param bool $allows_location
     */
    public function setAllowsLocation(bool $allows_location): void
    {
        $this->allows_location = $allows_location;
    }

    /**
     * @return bool
     */
    public function isAllowsLocationTimeframeCollision(): bool
    {
        return $this->allows_location_timeframe_collision;
    }

    /**
     * @param bool $allows_location_timeframe_collision
     */
    public function setAllowsLocationTimeframeCollision(bool $allows_location_timeframe_collision): void
    {
        $this->allows_location_timeframe_collision = $allows_location_timeframe_collision;
    }

    /**
     * @return bool
     */
    public function isShowAlwaysOnSchedule(): bool
    {
        return $this->show_always_on_schedule;
    }

    /**
     * @param bool $show_always_on_schedule
     */
    public function setShowAlwaysOnSchedule(bool $show_always_on_schedule): void
    {
        $this->show_always_on_schedule = $show_always_on_schedule;
    }

    use ScheduleEntity;
}
