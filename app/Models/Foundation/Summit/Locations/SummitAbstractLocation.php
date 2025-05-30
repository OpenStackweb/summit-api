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
use App\Models\Foundation\Summit\Locations\Banners\ScheduledSummitLocationBanner;
use App\Models\Foundation\Summit\Locations\Banners\SummitLocationBanner;
use App\Models\Foundation\Summit\ScheduleEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @see https://github.com/doctrine/doctrine2/commit/ff28507b88ffd98830c44762
 * //@ORM\AssociationOverrides({
 * //     @ORM\AssociationOverride(
 * //         name="summit",
 * //         inversedBy="locations"
 * //     )
 * // })
 */
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitAbstractLocation')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitLocationRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'locations')])]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'ClassName', type: 'string')]
#[ORM\DiscriminatorMap(['SummitAbstractLocation' => 'SummitAbstractLocation', 'SummitGeoLocatedLocation' => 'SummitGeoLocatedLocation', 'SummitExternalLocation' => 'SummitExternalLocation', 'SummitVenue' => 'SummitVenue', 'SummitHotel' => 'SummitHotel', 'SummitAirport' => 'SummitAirport', 'SummitVenueRoom' => 'SummitVenueRoom', 'SummitBookableVenueRoom' => 'SummitBookableVenueRoom'])]
#[ORM\HasLifecycleCallbacks] // Class SummitAbstractLocation
class SummitAbstractLocation extends SilverstripeBaseModel implements IOrderable
{
    const TypeExternal = 'External';
    const TypeInternal = 'Internal';
    const TypeNone     = 'None';

    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    protected $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ShortName', type: 'string')]
    protected $short_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Description', type: 'string')]
    protected $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'LocationType', type: 'string')]
    protected $type;

    /**
     * @var int
     */
    #[ORM\Column(name: '`Order`', type: 'integer')]
    protected $order;

    /**
     * @var int
     */
    #[ORM\Column(name: 'OpeningHour', type: 'integer')]
    protected $opening_hour;

    /**
     * @var int
     */
    #[ORM\Column(name: 'ClosingHour', type: 'integer')]
    protected $closing_hour;

    /**
     * @var SummitLocationBanner[]
     */
    #[ORM\OneToMany(targetEntity: \App\Models\Foundation\Summit\Locations\Banners\SummitLocationBanner::class, mappedBy: 'location', cascade: ['persist'], orphanRemoval: true)]
    protected $banners;

    /**
     * @var SummitEvent[]
     */
    #[ORM\OneToMany(targetEntity: \models\summit\SummitEvent::class, mappedBy: 'location', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    protected $events;


    public static $metadata = [
        'name'         => 'string',
        'short_name'   => 'string',
        'description'  => 'string',
        'type'         => [ self::TypeExternal, self::TypeInternal],
        'banners'      => 'array',
        'order'        => 'integer',
        'opening_hour' => 'integer',
        'closing_hour' => 'integer',
    ];

    /**
     * @return array
     */
    public static function getMetadata(){
        return self::$metadata;
    }

    public function __construct()
    {
        parent::__construct();
        $this->order   = 0;
        $this->type    = self::TypeNone;
        $this->banners = new ArrayCollection();
        $this->events  = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getClassName(){
        return 'SummitAbstractLocation';
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param int $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getLocationType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setLocationType($type)
    {
        $this->type = $type;
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
     * @return int|null
     */
    public function getOpeningHour(): ?int
    {
        return $this->opening_hour;
    }

    /**
     * @param int|null $opening_hour
     * @return void
     * @throws ValidationException
     */
    public function setOpeningHour(?int $opening_hour)
    {
        if(!is_null($opening_hour) && $opening_hour < 0)
            throw new ValidationException(sprintf("opening_hour %s is invalid", $opening_hour));
        $this->opening_hour = $opening_hour;
    }

    /**
     * @return int|null
     */
    public function getClosingHour(): ?int
    {
        return $this->closing_hour;
    }

    /**
     * @param int|null $closing_hour
     * @return void
     * @throws ValidationException
     */
    public function setClosingHour(?int $closing_hour)
    {
        if(!is_null($closing_hour)) {
            if($closing_hour < 0)
                throw new ValidationException(sprintf("closing_hour %s is invalid", $closing_hour));
            if(!is_null($this->opening_hour) && $closing_hour <= $this->opening_hour)
                throw new ValidationException(sprintf("closing_hour %s must be greater than opening_hour %s", $closing_hour, $this->opening_hour));
        }

        $this->closing_hour = $closing_hour;
    }

    use SummitOwned;

    /**
     * @return boolean
     */
    public function isOverrideBlackouts()
    {
        return false;
    }

    /**
     * @param SummitLocationBanner $banner
     * @throws ValidationException
     */
    public function validateBanner(SummitLocationBanner $banner){

        if($banner->getClassName() == SummitLocationBanner::ClassName){
            // only one static banner could exist at the same time
            foreach ($this->banners as $old_banner){
                if($old_banner->getClassName() == SummitLocationBanner::ClassName && $old_banner->isEnabled() && $old_banner->getId() != $banner->getId()){
                    throw new ValidationException
                    (
                        sprintf
                        ('already exist an enabled static banner for location %s', $this->id)
                    );
                }
            }
        }

        if($banner instanceof ScheduledSummitLocationBanner){
            // do not overlap enabled ones
            $new_start = $banner->getLocalStartDate();
            $new_end   = $banner->getLocalEndDate();

            foreach ($this->banners as $old_banner){
                if($old_banner instanceof ScheduledSummitLocationBanner
                    && $old_banner->isEnabled()
                    && $old_banner->getId() != $banner->getId()
                    && $old_banner->getType() == $banner->getType()){
                    $old_start = $old_banner->getLocalStartDate();
                    $old_end   = $old_banner->getLocalEndDate();
                    // (StartA <= EndB)  and  (EndA >= StartB)
                    if($new_start <= $old_end && $new_end >= $old_start){
                        // overlap!!!
                        throw new ValidationException
                        (
                            sprintf
                            (
                                'schedule time range (%s to %s) overlaps with an existent scheduled time range (%s to %s) - banner id %s',
                                $new_start->format('Y-m-d H:i:s'),
                                $new_end->format('Y-m-d H:i:s'),
                                $old_start->format('Y-m-d H:i:s'),
                                $old_end->format('Y-m-d H:i:s'),
                                $old_banner->id
                            )
                        );
                    }
                }
            }
        }

    }
    /**
     * @param SummitLocationBanner $banner
     * @return $this
     * @throws ValidationException
     */
    public function addBanner(SummitLocationBanner $banner){

        $this->validateBanner($banner);
        $this->banners->add($banner);
        $banner->setLocation($this);
        return $this;
    }

    /**
     * @param SummitLocationBanner $banner
     * @return $this
     */
    public function removeBanner(SummitLocationBanner $banner){
        $this->banners->removeElement($banner);
        $banner->clearLocation();
        return $this;
    }

    /**
     * @param int $banner_id
     * @return SummitLocationBanner|null
     */
    public function getBannerById($banner_id){
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($banner_id)));
        $banner = $this->banners->matching($criteria)->first();
        return $banner === false ? null : $banner;
    }

    /**
     * @param string $class_name
     * @return SummitLocationBanner|null
     */
    public function getBannerByClass($class_name){

    }

    /**
     * @return string
     */
    public function getShortName(): ?string
    {
        return $this->short_name;
    }

    /**
     * @param string $short_name
     */
    public function setShortName(string $short_name): void
    {
        $this->short_name = $short_name;
    }

    /**
     * @return ArrayCollection|SummitEvent[]
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection
     */
    public function getPublishedEvents(){
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('published', true));
        return $this->events->matching($criteria);
    }

    use ScheduleEntity;


}