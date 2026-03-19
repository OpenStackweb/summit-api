<?php namespace App\Models\Foundation\Marketplace;
/**
 * Copyright 2017 OpenStack Foundation
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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;

/**
 * @ORM\Entity
 * @ORM\Table(name="DriverRelease")
 * Class DriverRelease
 * @package App\Models\Foundation\Marketplace
 */
class DriverRelease extends SilverstripeBaseModel
{
    const ClassName = 'DriverRelease';

    /**
     * @ORM\Column(name="Name", type="string", length=255)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="Url", type="string", length=255, nullable=true)
     * @var string
     */
    private $url;

    /**
     * @ORM\Column(name="Start", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $start;

    /**
     * @ORM\Column(name="Active", type="boolean")
     * @var bool
     */
    private $active = false;

    /**
     * @ORM\ManyToMany(targetEntity="App\Models\Foundation\Marketplace\Driver", mappedBy="releases")
     * @var ArrayCollection|Driver[]
     */
    private $drivers;

    public function __construct()
    {
        parent::__construct();
        $this->drivers = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return self::ClassName;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @return \DateTime|null
     */
    public function getStart(): ?\DateTime
    {
        return $this->start;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string|null $url
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * @param \DateTime|null $start
     */
    public function setStart(?\DateTime $start): void
    {
        $this->start = $start;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return ArrayCollection|Driver[]
     */
    public function getDrivers()
    {
        return $this->drivers;
    }
}
