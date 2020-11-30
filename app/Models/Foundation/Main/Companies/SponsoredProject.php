<?php namespace models\main;
/**
 * Copyright 2020 OpenStack Foundation
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
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
use Cocur\Slugify\Slugify;
use App\Models\Foundation\Main\OrderableChilds;
/**
 * @ORM\Entity(repositoryClass="repositories\main\DoctrineSponsoredProjectRepository")
 * @ORM\Table(name="SponsoredProject")
 * Class SponsoredProject
 * @package models\main
 */
class SponsoredProject extends SilverstripeBaseModel
{
    /**
     * @ORM\Column(name="Name", type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="Description", type="string")
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(name="IsActive", type="boolean")
     * @var bool
     */
    private $is_active;

    /**
     * @ORM\Column(name="Slug", type="string")
     * @var string
     */
    private $slug;

    /**
     * @param string $name
     */
    public function setName(string $name):void{
        $this->name = $name;
        $slugify    = new Slugify();
        $this->slug = $slugify->slugify($name);
    }

    /**
     * @return string|null
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    use OrderableChilds;

    /**
     * @ORM\OneToMany(targetEntity="ProjectSponsorshipType", mappedBy="sponsored_project", cascade={"persist"}, orphanRemoval=true)
     * @ORM\OrderBy({"order" = "ASC"})
     * @var ArrayCollection
     */
    private $sponsorship_types;


    /**
     * SponsoredProject constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->sponsorship_types = new ArrayCollection;
        $this->is_active = false;
    }

    /**
     * @param ProjectSponsorshipType $value
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateProjectSponsorshipTypeOrder(ProjectSponsorshipType $value, $new_order){
        self::recalculateOrderForSelectable($this->sponsorship_types, $value, $new_order);
    }

    /**
     * @return ProjectSponsorshipType[]
     */
    public function getSponsorshipTypes(): array
    {
        return $this->sponsorship_types;
    }

    /**
     * @return array|int[]
     */
    public function getSponsorshipTypesIds(): array {
        $res = [];
        foreach($this->sponsorship_types as $item){
            $res[] = $item->getId();
        }
        return $res;
    }

    /**
     * @param string $name
     * @return ProjectSponsorshipType|null
     */
    public function getSponsorshipTypeByName(string $name):?ProjectSponsorshipType{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($name)));
        $sponsorshipType = $this->sponsorship_types->matching($criteria)->first();
        return $sponsorshipType === false ? null : $sponsorshipType;
    }

    /**
     * @param int $id
     * @return ProjectSponsorshipType|null
     */
    public function getSponsorshipTypeById(int $id):?ProjectSponsorshipType{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
        $sponsorshipType = $this->sponsorship_types->matching($criteria)->first();
        return $sponsorshipType === false ? null : $sponsorshipType;
    }

    public function addSponsorshipType(ProjectSponsorshipType $sponsorshipType){
        if($this->sponsorship_types->contains($sponsorshipType)) return;
        $this->sponsorship_types->add($sponsorshipType);
        $sponsorshipType->setSponsoredProject($this);
        $sponsorshipType->setOrder($this->sponsorship_types->count());
    }

    public function removeSponsorshipType(ProjectSponsorshipType $sponsorshipType){
        if(!$this->sponsorship_types->contains($sponsorshipType)) return;
        $this->sponsorship_types->removeElement($sponsorshipType);
        $sponsorshipType->clearSponsoredProject();
    }

}