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
     * @ORM\ManyToOne(targetEntity="PresentationCategory", fetch="EXTRA_LAZY", cascade={"persist"}, inversedBy="proposed_schedule_allowed_locations"
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
    public function getAllowedTimeframes(): ArrayCollection
    {
        return $this->allowed_timeframes;
    }

    /**
     * @ORM\ManyToOne(targetEntity="SummitAbstractLocation", fetch="EXTRA_LAZY", cascade={"persist"})
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
     * @param string $day
     * @param int|null $from
     * @param int|null $to
     * @return void
     * @throws ValidationException
     */
    public function addAllowedTimeFrame(string $day, ?int $from=null, ?int $to=null):void{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('day', $day));

        if($this->allowed_timeframes->matching($criteria)->count() > 0 ){
            throw new ValidationException(sprintf("Day %s already exists for location %s.", $day, $this->location->getId()));
        }

        $this->allowed_timeframes->add(new SummitProposedScheduleAllowedDay($this, $day, $from, $to));
    }

    /**
     * @param string $day
     * @return void
     * @throws ValidationException
     */
    public function removeAllowedTimeFrame(string $day):void{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('day', $day));

        $time_frame = $this->allowed_timeframes->matching($criteria)->first();
        if(!$time_frame){
            throw new ValidationException(sprintf("Day %s does not exists for location %s.", $day, $this->location->getId()));
        }

        $this->allowed_timeframes->removeElement($time_frame);
    }

}