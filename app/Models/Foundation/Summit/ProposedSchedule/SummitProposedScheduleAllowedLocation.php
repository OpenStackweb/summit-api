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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use models\exceptions\ValidationException;
use models\summit\PresentationCategory;
use models\summit\SummitAbstractLocation;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;

/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitProposedScheduleAllowedLocationRepository")
 * @ORM\Table(name="SummitProposedScheduleAllowedLocation")
 * Class SummitProposedScheduleAllowedLocation
 * @package App\Models\Foundation\Summit\ProposedSchedule
 */
class SummitProposedScheduleAllowedLocation extends SilverstripeBaseModel
{

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getLocationId' => 'location',
        'getTrackId' => 'track',
    ];

    protected $hasPropertyMappings = [
        'hasLocation' => 'location',
        'hasTrack' => 'track',
    ];
    /**
     * @ORM\ManyToOne(targetEntity="models\summit\PresentationCategory", fetch="EXTRA_LAZY", cascade={"persist"}, inversedBy="proposed_schedule_allowed_locations")
     * @ORM\JoinColumn(name="PresentationCategoryID", referencedColumnName="ID", onDelete="SET NULL")
     * @var PresentationCategory
     */
    private $track;

    /**
     * @return PresentationCategory
     */
    public function getTrack(): PresentationCategory
    {
        return $this->track;
    }

    /**
     * @param PresentationCategory $track
     */
    public function setTrack(PresentationCategory $track): void
    {
        $this->track = $track;
    }

    /**
     * @return SummitAbstractLocation
     */
    public function getLocation(): SummitAbstractLocation
    {
        return $this->location;
    }

    /**
     * @param SummitAbstractLocation $location
     */
    public function setLocation(SummitAbstractLocation $location): void
    {
        $this->location = $location;
    }

    /**
     * @return ArrayCollection
     */
    public function getAllowedTimeframes()
    {
        return $this->allowed_timeframes;
    }

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitAbstractLocation", fetch="EXTRA_LAZY", cascade={"persist"})
     * @ORM\JoinColumn(name="LocationID", referencedColumnName="ID", onDelete="SET NULL")
     * @var SummitAbstractLocation
     */
    private $location;

    /**
     * @ORM\OneToMany(targetEntity="SummitProposedScheduleAllowedDay", mappedBy="allowed_location", cascade={"persist"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private $allowed_timeframes;

    /**
     * @param PresentationCategory $track
     * @param SummitAbstractLocation $location
     */
    public function __construct(PresentationCategory $track, SummitAbstractLocation $location)
    {
        parent::__construct();
        $this->track = $track;
        $this->location = $location;
        $this->allowed_timeframes = new ArrayCollection();
    }

    public function clearAllowedTimeFrames(){
        $this->allowed_timeframes->clear();
    }

    /**
     * @param \DateTime $day
     * @param int|null $opening_hour
     * @param int|null $to
     * @return SummitProposedScheduleAllowedDay|null
     * @throws ValidationException
     */
    public function addAllowedTimeFrame(\DateTime $day, ?int $opening_hour = null, ?int $closing_hour = null):?SummitProposedScheduleAllowedDay{
        if(!$this->location->getSummit()->dayIsOnSummitPeriod($day))
            throw new ValidationException
            (
                sprintf
                (
                    "Day %s is not on summit period( %s - %s).",
                    $day->format("Y-m-d"),
                    $this->location->getSummit()->getLocalBeginDate()->format("Y-m-d"),
                    $this->location->getSummit()->getLocalEndDate()->format("Y-m-d"),
                )
            );

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('day', $day));

        if($this->allowed_timeframes->matching($criteria)->count() > 0 ){
            throw new ValidationException(sprintf("Day %s already exists for location %s.", $day->format("Y-m-d"), $this->location->getId()));
        }

        $time_frame = new SummitProposedScheduleAllowedDay($this, $day, $opening_hour, $closing_hour);
        $this->allowed_timeframes->add($time_frame);
        return $time_frame;
    }

    /**
     * @param SummitProposedScheduleAllowedDay $time_frame
     * @return void
     * @throws ValidationException
     */
    public function removeAllowedTimeFrame(SummitProposedScheduleAllowedDay $time_frame):void{
        if(!$this->allowed_timeframes->contains($time_frame)) return;
        $this->allowed_timeframes->removeElement($time_frame);
    }

    /**
     * @param int $time_frame_id
     * @return SummitProposedScheduleAllowedDay|null
     */
    public function getAllowedTimeFrameById(int $time_frame_id):?SummitProposedScheduleAllowedDay{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $time_frame_id));
        $res =  $this->allowed_timeframes->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    public function getAllowedTimeFrameForDates(\DateTime $from, \DateTime $to):?SummitProposedScheduleAllowedDay{
        $criteria = Criteria::create();
        $day = clone $from;
        $day = $day->setTime(0,0,0);
        $criteria->where(Criteria::expr()->eq('day', $day));
        $res =  $this->allowed_timeframes->matching($criteria)->first();
        return $res === false ? null : $res;
    }

    public function hasTimeFrameRestrictions():bool{
        return $this->allowed_timeframes->count() > 0;
    }

}