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
use App\Models\Foundation\Main\IOrderable;
use Doctrine\ORM\Mapping AS ORM;
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SponsorshipType')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSponsorshipTypeRepository::class)]
class SponsorshipType extends SilverstripeBaseModel implements IOrderable
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Label', type: 'string')]
    private $label;

    /**
     * @var int
     */
    #[ORM\Column(name: '`Order`', type: 'integer')]
    private $order;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Size', type: 'string')]
    private $size;

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
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * @param int $order
     */
    public function setOrder($order): void
    {
        $this->order = $order;
    }

    /**
     * @return string
     */
    public function getSize(): string
    {
        return $this->size;
    }

    /**
     * @param string $size
     * @throws ValidationException
     */
    public function setSize(string $size): void
    {
        if(!in_array($size, ISponsorshipTypeConstants::AllowedSizes))
            throw new ValidationException("invalid size");
        $this->size = $size;
    }

    public function __construct()
    {
        parent::__construct();
        $this->order = 1;
    }

}