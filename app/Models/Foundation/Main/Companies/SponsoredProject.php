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
use Doctrine\Common\Collections\Collection;
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
     * @ORM\Column(name="ShouldShowOnNavBar", type="boolean")
     * @var bool
     */
    private $should_show_on_nav_bar;

    /**
     * @ORM\Column(name="SiteURL", type="string")
     * @var string
     */
    private $site_url;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\File", cascade={"persist","remove"})
     * @ORM\JoinColumn(name="LogoID", referencedColumnName="ID")
     * @var File
     */
    protected $logo;

    /**
     * @ORM\OneToMany(targetEntity="SponsoredProject", mappedBy="parent_project", cascade={"persist"})
     * @var ArrayCollection
     */
    private $sub_projects;

    /**
     * @ORM\ManyToOne(targetEntity="SponsoredProject", inversedBy="sub_projects", cascade={"persist"})
     * @ORM\JoinColumn(name="ParentProjectID", referencedColumnName="ID")
     * @var SponsoredProject
     */
    private $parent_project;

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

    /**
     * @return bool
     */
    public function getShouldShowOnNavBar(): bool
    {
        return $this->should_show_on_nav_bar;
    }

    /**
     * @param bool $should_show_on_nav_bar
     */
    public function setShouldShowOnNavBar(bool $should_show_on_nav_bar): void
    {
        $this->should_show_on_nav_bar = $should_show_on_nav_bar;
    }

    /**
     * @return string
     */
    public function getSiteUrl(): ?string
    {
        return $this->site_url;
    }

    /**
     * @param string $site_url
     */
    public function setSiteUrl(string $site_url): void
    {
        $this->site_url = $site_url;
    }

    /**
     * @return File|null
     */
    public function getLogo(): ?File
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

    public function clearLogo():void
    {
        $this->logo = null;
    }

    /**
     * @return bool
     */
    public function hasLogo(): bool {
        return $this->getLogoId() > 0;
    }

    /**
     * @return int
     */
    public function getLogoId(): int {
        try {
            return !is_null($this->logo) ? $this->logo->getId() : 0;
        } catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return string|null
     */
    public function getLogoUrl(): ?string {
        $logoUrl = null;
        if ($this->hasLogo() && $logo = $this->getLogo()) {
            $logoUrl = $logo->getUrl();
        }
        return $logoUrl;
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
        $this->sub_projects = new ArrayCollection;
        $this->is_active = false;
        $this->logo = null;
        $this->should_show_on_nav_bar = true;
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
     * @return ArrayCollection
     */
    public function getSponsorshipTypes()
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

    /**
     * @return int
     */
    public function getParentProjectId(): int {
        try {
            return !is_null($this->parent_project) ? $this->parent_project->getId() : 0;
        } catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return SponsoredProject|null
     */
    public function getParentProject():?SponsoredProject{
        return $this->parent_project;
    }

    /**
     * @param SponsoredProject $sponsored_project
     */
    public function setParentProject(SponsoredProject $sponsored_project): void{
        $this->parent_project = $sponsored_project;
    }

    public function clearParentProject(){
        $this->parent_project = null;
    }

    /**
     * @return Collection
     */
    public function getSubProjects(): Collection{
        return $this->sub_projects;
    }

    /**
     * @return array|int[]
     */
    public function getSubProjectIds(): array {
        $res = [];
        foreach($this->sub_projects as $item){
            $res[] = $item->getId();
        }
        return $res;
    }

    /**
     * @param int $id
     * @return SponsoredProject|null
     */
    public function getSubprojectById(int $id):?SponsoredProject{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
        $subProject = $this->sub_projects->matching($criteria)->first();
        return $subProject === false ? null : $subProject;
    }

    public function addSubProject(SponsoredProject $subProject){
        if($this->sub_projects->contains($subProject)) return;
        $this->sub_projects->add($subProject);
        $subProject->setParentProject($this);
    }
}