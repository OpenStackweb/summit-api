<?php namespace models\summit;
/**
 * Copyright 2019 OpenStack Foundation
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
use Doctrine\ORM\Mapping AS ORM;
use models\exceptions\ValidationException;
use models\summit\SummitOwned;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity
 * @ORM\Table(name="SummitBookableVenueRoomAttributeType")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="booking_room_allowed_attributes"
 *     )
 * })
 * Class SummitBookableVenueRoomAttributeType
 * @package models\summit
 */
class SummitBookableVenueRoomAttributeType extends SilverstripeBaseModel
{
    /**
     * @ORM\Column(name="Type", type="string")
     * @var string
     */
    private $type;

    use SummitOwned;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitBookableVenueRoomAttributeValue", mappedBy="type", cascade={"persist"}, orphanRemoval=true)
     * @var ArrayCollection
     */
    private $values;

    public function __construct()
    {
        parent::__construct();
        $this->values = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return ArrayCollection
     */
    public function getValues(): ArrayCollection
    {
        return $this->values;
    }

    /**
     * @param ArrayCollection $values
     */
    public function setValues(ArrayCollection $values): void
    {
        $this->values = $values;
    }
}