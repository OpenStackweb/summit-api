<?php namespace models\summit;
/**
 * Copyright 2016 OpenStack Foundation
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
use App\Models\Foundation\Summit\ScheduleEntity;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\Common\Collections\ArrayCollection;
use models\exceptions\ValidationException;
use models\main\File;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitVenueFloor')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks] // Class Summit
class SummitVenueFloor extends SilverstripeBaseModel
{

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getVenueId' => 'venue',
        'getImageId' => 'image',
    ];

    protected $hasPropertyMappings = [
        'hasVenue' => 'venue',
        'hasImage' => 'image',
    ];

    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    #[ORM\Column(name: 'Description', type: 'string')]
    private $description;

    #[ORM\Column(name: 'Number', type: 'integer')]
    private $number;

    /**
     *
     * @var SummitVenue
     */
    #[ORM\JoinColumn(name: 'VenueID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitVenue::class, inversedBy: 'floors')]
    private $venue;

    /**
     * @var SummitVenueRoom[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\SummitVenueRoom::class, mappedBy: 'floor', cascade: ['persist'])]
    private $rooms;

    /**
     * @var File
     */
    #[ORM\JoinColumn(name: 'ImageID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\File::class, fetch: 'EAGER', cascade: ['persist', 'remove'])]
    private $image;

    /**
     * @return string
     */
    public function getClassName(){
        return 'SummitVenueFloor';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return SummitVenue
     */
    public function getVenue()
    {
        return $this->venue;
    }

    /**
     * @return File
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return SummitVenueRoom[]
     */
    public function getRooms(){
        return $this->rooms;
    }

    /**
     * @param int $room_id
     * @return SummitVenueRoom
     */
    public function getRoom($room_id){
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($room_id)));
        $room = $this->rooms->matching($criteria)->first();
        return $room === false ? null : $room;
    }

    public function __construct()
    {
        parent::__construct();
        $this->rooms = new ArrayCollection;
    }

    /**
     * @param SummitVenue|null $venue
     */
    public function setVenue($venue)
    {
        $this->venue = $venue;
    }

    public function clearVenue(){
        $this->venue = null;
    }

    /**
     * @param File $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    public function clearImage(){
        $this->image = null;
    }

    /**
     * @param SummitVenueRoom $room
     */
    public function addRoom(SummitVenueRoom $room){
        $this->rooms->add($room);
        $room->setFloor($this);
    }

    /**
     * @param SummitVenueRoom $room
     */
    public function removeRoom(SummitVenueRoom $room){
        $this->rooms->removeElement($room);
        $room->clearFloor();
    }

    use OrderableChilds;

    /**
     * @param SummitVenueRoom $room
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateRoomsOrder(SummitVenueRoom $room, $new_order){
        self::recalculateOrderForSelectable($this->rooms, $room, $new_order);
    }

    use ScheduleEntity;

    /**
     * @return string|null
     */
    public function getImageUrl():?string{
        if($this->hasImage()){
            return $this->image->getUrl();
        }
        return null;
    }
}