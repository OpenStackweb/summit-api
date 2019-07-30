<?php namespace models\main;
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

use Doctrine\Common\Collections\ArrayCollection;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity(repositoryClass="repositories\main\DoctrineCompanyRepository")
 * @ORM\Table(name="Company")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="sponsors_region")
 * Class Company
 * @package models\main
 */
class Company extends SilverstripeBaseModel
{
    /**
     * @ORM\Column(name="Name", type="string")
     */
    private $name;

    /**
     * @ORM\Column(name="Description", type="string")
     */
    private $description;

    /**
     * @ORM\Column(name="Industry", type="string")
     */
    private $industry;

    /**
     * @ORM\Column(name="City", type="string")
     */
    private $city;

    /**
     * @ORM\Column(name="State", type="string")
     */
    private $state;

    /**
     * @ORM\Column(name="Country", type="string")
     */
    private $country;

    /**
     * @ORM\Column(name="URL", type="string")
     */
    private $url;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\SummitEvent", mappedBy="sponsors")
     */
    private $sponsorships;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\File")
     * @ORM\JoinColumn(name="LogoID", referencedColumnName="ID")
     * @var File
     */
    private $logo;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\File")
     * @ORM\JoinColumn(name="BigLogoID", referencedColumnName="ID")
     * @var File
     */
    private $big_logo;

    /**
     * Company constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->sponsorships = new ArrayCollection();
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getIndustry(): ?string
    {
        return $this->industry;
    }

    /**
     * @param string $industry
     */
    public function setIndustry(string $industry): void
    {
        $this->industry = $industry;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState(string $state): void
    {
        $this->state = $state;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return File
     */
    public function getLogo(): File
    {
        return $this->logo;
    }

    /**
     * @param File $logo
     */
    public function setLogo(File $logo): void
    {
        $this->logo = $logo;
    }

    /**
     * @return File
     */
    public function getBigLogo(): File
    {
        return $this->big_logo;
    }

    /**
     * @param File $big_logo
     */
    public function setBigLogo(File $big_logo): void
    {
        $this->big_logo = $big_logo;
    }

    /**
     * @return bool
     */
    public function hasLogo()
    {
        return $this->getLogoId() > 0;
    }

    /**
     * @return int
     */
    public function getLogoId()
    {
        try {
            if (is_null($this->logo)) return 0;
            return $this->logo->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasBigLogo()
    {
        return $this->getBigLogoId() > 0;
    }

    /**
     * @return int
     */
    public function getBigLogoId()
    {
        try {
            if (is_null($this->big_logo)) return 0;
            return $this->big_logo->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return string|null
     */
    public function getLogoUrl(): ?string
    {
        $logoUrl = null;
        if ($this->hasLogo() && $logo = $this->getLogo()) {
            $logoUrl = $logo->getUrl();
        }
        return $logoUrl;
    }

    /**
     * @return string|null
     */
    public function getBigLogoUrl(): ?string
    {
        $logoUrl = null;
        if ($this->hasBigLogo() && $logo = $this->getBigLogo()) {
            $logoUrl = $logo->getUrl();
        }
        return $logoUrl;
    }
}