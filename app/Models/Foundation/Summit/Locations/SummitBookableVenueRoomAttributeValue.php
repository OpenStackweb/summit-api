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
use models\utils\SilverstripeBaseModel;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitBookableVenueRoomAttributeValue')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitBookableVenueRoomAttributeValueRepository::class)]
class SummitBookableVenueRoomAttributeValue extends SilverstripeBaseModel
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'Value', type: 'string')]
    private $value;

    /**
     * @var SummitBookableVenueRoomAttributeType
     */
    #[ORM\JoinColumn(name: 'TypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitBookableVenueRoomAttributeType::class, inversedBy: 'values', cascade: ['persist'])]
    private $type;

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return SummitBookableVenueRoomAttributeType
     */
    public function getType(): SummitBookableVenueRoomAttributeType
    {
        return $this->type;
    }

    /**
     * @param SummitBookableVenueRoomAttributeType $type
     */
    public function setType(SummitBookableVenueRoomAttributeType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getTypeId():int{
        try {
            return is_null($this->type) ? 0 : $this->type->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

}