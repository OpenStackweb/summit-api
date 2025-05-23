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
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitBadgeViewType')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitBadgeViewTypeRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'badge_view_types')])]
class SummitBadgeViewType extends SilverstripeBaseModel
{
    use SummitOwned;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Description', type: 'string')]
    private $description;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'IsDefault', type: 'boolean')]
    private $default;

    public function __construct()
    {
        parent::__construct();
        $this->default = false;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * @param bool $default
     */
    public function setDefault(bool $default): void
    {
        $this->default = $default;
    }

    /**
     * @param Summit $summit
     * @return SummitBadgeViewType
     */
    public static function buildDefaultCardType(Summit $summit):SummitBadgeViewType{
        $card = new SummitBadgeViewType;
        $card->setName("Card");
        $card->setDefault(true);
        $card->setDescription("Badge Card View Type");
        $summit->addBadgeViewType($card);
        return $card;
    }
}