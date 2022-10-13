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
use App\Models\Foundation\Main\IOrderable;
use Doctrine\ORM\Mapping AS ORM;
use models\main\File;

/**
 * @ORM\Entity
 * @ORM\Table(name="SummitVenueRoom")
 * @ORM\HasLifecycleCallbacks
 * Class SummitVenueRoom
 * @package models\summit
 */
class SummitVenueRoom extends SummitAbstractLocation implements IOrderable
{
    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitVenue", inversedBy="rooms")
     * @ORM\JoinColumn(name="VenueID", referencedColumnName="ID")
     * @var SummitVenue
     */
    private $venue;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitVenueFloor", inversedBy="rooms")
     * @ORM\JoinColumn(name="FloorID", referencedColumnName="ID")
     * @var SummitVenueFloor
     */
    private $floor;

    /**
     * @ORM\Column(name="Capacity", type="integer")
     * @var int
     */
    private $capacity;

    /**
     * @ORM\Column(name="OverrideBlackouts", type="boolean")
     * @var bool
     */
    private $override_blackouts;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\File", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="ImageID", referencedColumnName="ID")
     * @var File
     */
    protected $image;

    /**
     * @return File
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return bool
     */
    public function hasImage(){
        return $this->getImageId() > 0;
    }

    /**
     * @return int
     */
    public function getImageId(){
        try{
            return !is_null($this->image) ? $this->image->getId() : 0;
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    public function clearImage(){
        $this->image = null;
    }

    /**
     * @param File $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getClassName(){
        return self::ClassName;
    }

    const ClassName = 'SummitVenueRoom';

    /**
     * @return SummitVenue
     */
    public function getVenue()
    {
        return $this->venue;
    }

    /**
     * @return string
     */
    public function getCompleteName():string{
        $name = $this->venue->getSummit()->getName();
        $name .= ' '.$this->venue->getName();
        $name .= ' '.$this->getName();
        return $name;
    }

    /**
     * @return int
     */
    public function getVenueId(){
        try{
            return is_null($this->venue) ? 0 : $this->venue->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasVenue(){
        return $this->getVenueId() > 0;
    }

    /**
     * @return SummitVenueFloor
     */
    public function getFloor()
    {
        return $this->floor;
    }

    /**
     * @return int
     */
    public function getFloorId(){
        try{
            return is_null($this->floor) ? 0 : $this->floor->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasFloor(){
        return $this->getFloorId() > 0;
    }

    /**
     * @return int
     */
    public function getCapacity()
    {
        return $this->capacity;
    }

    /**
     * @param int $capacity
     */
    public function setCapacity($capacity)
    {
        $this->capacity = $capacity;
    }

    /**
     * @return boolean
     */
    public function isOverrideBlackouts()
    {
        return $this->override_blackouts;
    }

    /**
     * @param boolean $override_blackouts
     */
    public function setOverrideBlackouts($override_blackouts)
    {
        $this->override_blackouts = $override_blackouts;
    }


    /**
     * SummitVenueRoom constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->override_blackouts = false;
        $this->capacity           = 0;
        $this->type               = self::TypeInternal;
    }


    /**
     * @param SummitVenue|null $venue
     */
    public function setVenue(SummitVenue $venue)
    {
        $this->venue = $venue;
    }

    public function clearVenue(){
        $this->venue = null;
    }

    public function clearFloor(){
        $this->floor = null;
    }

    /**
     * @param SummitVenueFloor $floor
     */
    public function setFloor(SummitVenueFloor $floor)
    {
        $this->floor = $floor;
    }

}