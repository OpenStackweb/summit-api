<?php namespace App\Models\Foundation\UserStories;
/*
 * Copyright 2024 OpenStack Foundation
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

use App\Models\Foundation\Main\Continent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
use models\main\File;
use models\main\Organization;
use models\main\Tag;
use models\summit\SummitAbstractLocation;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;

/**
 * @ORM\Entity(repositoryClass="repositories\main\DoctrineUserStoryRepository")
 * @ORM\Table(name="UserStoryDO")
 * Class File
 * @package App\Models\Foundation\UserStories
 */
class UserStory extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getOrganizationId' => 'organization',
        'getIndustryId'     => 'industry',
        'getLocationId'     => 'location',
        'getImageId'        => 'image',
    ];

    protected $hasPropertyMappings = [
        'hasOrganization' => 'organization',
        'hasIndustry'     => 'industry',
        'hasLocation'     => 'location',
        'hasImage'        => 'image',
    ];

    /**
     * @ORM\Column(name="Name", type="string")
     */
    private $name;
    /**
     * @ORM\Column(name="Description", type="string")
     */
    private $description;
    /**
     * @ORM\Column(name="ShortDescription", type="string")
     */
    private $short_description;
    /**
     * @ORM\Column(name="Link", type="string")
     */
    private $link;

    /**
     * @ORM\Column(name="Active", type="boolean")
     * @var bool
     */
    private $is_active;

    /**
     * @ORM\Column(name="MillionCoreClub", type="boolean")
     * @var bool
     */
    private $is_million_core_club;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Organization")
     * @ORM\JoinColumn(name="OrganizationID", referencedColumnName="ID")
     * @var Organization
     */
    private $organization;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Foundation\UserStories\UserStoriesIndustry", inversedBy="user_stories")
     * @ORM\JoinColumn(name="IndustryID", referencedColumnName="ID")
     * @var UserStoriesIndustry
     */
    protected $industry;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Foundation\Main\Continent")
     * @ORM\JoinColumn(name="LocationID", referencedColumnName="ID")
     * @var Continent
     */
    protected $location;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\File")
     * @ORM\JoinColumn(name="ImageID", referencedColumnName="ID")
     * @var File
     */
    private $image;

    /**
     * @ORM\ManyToMany(targetEntity="models\main\Tag", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="UserStoryDO_Tags",
     *      joinColumns={@ORM\JoinColumn(name="UserStoryDOID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="TagID", referencedColumnName="ID")}
     *      )
     * @var Tag[]
     */
    private $tags;

    public function __construct()
    {
        parent::__construct();
        $this->tags = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
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
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getShortDescription()
    {
        return $this->short_description;
    }

    /**
     * @param mixed $short_description
     */
    public function setShortDescription($short_description): void
    {
        $this->short_description = $short_description;
    }

    /**
     * @return mixed
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param mixed $link
     */
    public function setLink($link): void
    {
        $this->link = $link;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * @param bool $is_active
     */
    public function setIsActive(bool $is_active): void
    {
        $this->is_active = $is_active;
    }

    /**
     * @return bool
     */
    public function isMillionCoreClub(): bool
    {
        return $this->is_million_core_club;
    }

    /**
     * @param bool $is_million_core_club
     */
    public function setIsMillionCoreClub(bool $is_million_core_club): void
    {
        $this->is_million_core_club = $is_million_core_club;
    }

    /**
     * @return Organization|null
     */
    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     */
    public function setOrganization(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function clearOrganization(): void
    {
        $this->organization = null;
    }

    /**
     * @return UserStoriesIndustry|null
     */
    public function getIndustry(): ?UserStoriesIndustry
    {
        return $this->industry;
    }

    /**
     * @param UserStoriesIndustry $industry
     */
    public function setIndustry(UserStoriesIndustry $industry): void
    {
        $this->industry = $industry;
    }

    public function clearIndustry(): void
    {
        $this->industry = null;
    }

    /**
     * @return File|null
     */
    public function getImage(): ?File
    {
        return $this->image;
    }

    /**
     * @return bool
     */
    public function hasImage(): bool
    {
        return $this->getImageId() > 0;
    }

    /**
     * @return int
     */
    public function getImageId(): int
    {
        try{
            return !is_null($this->image) ? $this->image->getId() : 0;
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @param File $image
     */
    public function setImage(File $image): void
    {
        $this->image = $image;
    }

    public function clearImage(): void
    {
        $this->image = null;
    }

    /**
     * @return Continent|null
     */
    public function getLocation(): ?Continent
    {
        return $this->location;
    }

    /**
     * @param Continent $continent
     */
    public function setLocation(Continent $continent): void
    {
        $this->location = $continent;
    }

    public function clearLocation(): void
    {
        $this->location = null;
    }

    /**
     * @return mixed
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param Tag $tag
     */
    public function addTag(Tag $tag)
    {
        if ($this->tags->contains($tag)) return;
        $this->tags->add($tag);
    }

    public function clearTags()
    {
        $this->tags->clear();
    }
}