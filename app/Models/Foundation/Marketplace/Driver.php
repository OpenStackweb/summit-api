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
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;

/**
 * @ORM\Entity(repositoryClass="App\Repositories\Marketplace\DoctrineDriverRepository")
 * @ORM\Table(name="Driver")
 * Class Driver
 * @package App\Models\Foundation\Marketplace
 */
class Driver extends SilverstripeBaseModel
{
    const ClassName = 'Driver';

    /**
     * @ORM\Column(name="Name", type="string", length=255)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="Description", type="string", nullable=true)
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(name="Project", type="string", length=255, nullable=true)
     * @var string
     */
    private $project;

    /**
     * @ORM\Column(name="Vendor", type="string", length=255, nullable=true)
     * @var string
     */
    private $vendor;

    /**
     * @ORM\Column(name="Url", type="string", length=255, nullable=true)
     * @var string
     */
    private $url;

    /**
     * @ORM\Column(name="Tested", type="boolean")
     * @var bool
     */
    private $tested = false;

    /**
     * @ORM\Column(name="Active", type="boolean")
     * @var bool
     */
    private $active = false;

    /**
     * @ORM\ManyToMany(targetEntity="App\Models\Foundation\Marketplace\DriverRelease", inversedBy="drivers")
     * @ORM\JoinTable(name="Driver_Releases",
     *   joinColumns={@ORM\JoinColumn(name="DriverID", referencedColumnName="ID")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="DriverReleaseID", referencedColumnName="ID")}
     * )
     * @var ArrayCollection|DriverRelease[]
     */
    private $releases;

    public function __construct()
    {
        parent::__construct();
        $this->releases = new ArrayCollection();
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
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getProject(): ?string
    {
        return $this->project;
    }

    /**
     * @return string|null
     */
    public function getVendor(): ?string
    {
        return $this->vendor;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @return bool
     */
    public function isTested(): bool
    {
        return $this->tested;
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
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @param string|null $project
     */
    public function setProject(?string $project): void
    {
        $this->project = $project;
    }

    /**
     * @param string|null $vendor
     */
    public function setVendor(?string $vendor): void
    {
        $this->vendor = $vendor;
    }

    /**
     * @param string|null $url
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * @param bool $tested
     */
    public function setTested(bool $tested): void
    {
        $this->tested = $tested;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @param DriverRelease $release
     */
    public function addRelease(DriverRelease $release): void
    {
        if (!$this->releases->contains($release)) {
            $this->releases->add($release);
            $release->getDrivers()->add($this);
        }
    }

    /**
     * @return ArrayCollection|DriverRelease[]
     */
    public function getReleases()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('active', true));
        return $this->releases->matching($criteria);
    }

    /**
     * @return ArrayCollection|DriverRelease[]
     */
    public function getAllReleases()
    {
        return $this->releases;
    }
}
