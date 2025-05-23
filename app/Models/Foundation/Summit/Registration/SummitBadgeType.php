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
use models\utils\SilverstripeBaseModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitBadgeType')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitBadgeTypeRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'badge_types')])]
class SummitBadgeType extends SilverstripeBaseModel
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
     * @var string
     */
    #[ORM\Column(name: 'TemplateContent', type: 'string')]
    private $template_content;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'IsDefault', type: 'boolean')]
    private $default;

    /**
     * @var SummitAccessLevelType[]
     */
    #[ORM\JoinTable(name: 'SummitBadgeType_AccessLevels')]
    #[ORM\JoinColumn(name: 'SummitBadgeTypeID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'SummitAccessLevelTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \SummitAccessLevelType::class)]
    private $access_levels;

    /**
     * @var SummitBadgeFeatureType[]
     */
    #[ORM\JoinTable(name: 'SummitBadgeType_BadgeFeatures')]
    #[ORM\JoinColumn(name: 'SummitBadgeTypeID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'SummitBadgeFeatureTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \SummitBadgeFeatureType::class)]
    private $badge_features;

    /**
     * @var SummitBadgeViewType[]
     */
    #[ORM\JoinTable(name: 'SummitBadgeViewType_SummitBadgeType')]
    #[ORM\JoinColumn(name: 'SummitBadgeTypeID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'SummitBadgeViewTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \SummitBadgeViewType::class)]
    private $allowed_view_types;

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
    public function getDescription(): string
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
     * @return string
     */
    public function getTemplateContent(): ?string
    {
        return $this->template_content;
    }

    /**
     * @param string $template_content
     */
    public function setTemplateContent(string $template_content): void
    {
        $this->template_content = $template_content;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * @param bool $is_default
     */
    public function setIsDefault(bool $is_default): void
    {
        $this->default = $is_default;
    }

    public function __construct()
    {
        parent::__construct();
        $this->template_content = '';
        $this->default          = false;
        $this->badge_features   = new ArrayCollection();
        $this->access_levels    = new ArrayCollection();
        $this->allowed_view_types = new ArrayCollection();
    }

    /**
     * @return SummitAccessLevelType[]
     */
    public function getAccessLevels()
    {
        return $this->access_levels;
    }

    /**
     * @return SummitBadgeFeatureType[]
     */
    public function getBadgeFeatures()
    {
        return $this->badge_features;
    }

    /**
     * @param SummitBadgeFeatureType $feature_type
     */
    public function addBadgeFeatureType(SummitBadgeFeatureType $feature_type){
        if($this->badge_features->contains($feature_type)) return;
        $this->badge_features->add($feature_type);
    }

    /**
     * @param SummitBadgeFeatureType $feature_type
     */
    public function removeBadgeFeatureType(SummitBadgeFeatureType $feature_type){
        if(!$this->badge_features->contains($feature_type)) return;
        $this->badge_features->removeElement($feature_type);
    }

    /**
     * @param SummitAccessLevelType $access_level
     */
    public function addAccessLevel(SummitAccessLevelType $access_level){
        if($this->access_levels->contains($access_level)) return;
        $this->access_levels->add($access_level);
    }

    /**
     * @param SummitAccessLevelType $access_level
     */
    public function removeAccessLevel(SummitAccessLevelType $access_level){
        if(!$this->access_levels->contains($access_level)) return;
        $this->access_levels->removeElement($access_level);
    }

    /**
     * @param int $access_level_id
     * @return SummitAccessLevelType|null
     */
    public function getAccessLevelById(int $access_level_id):?SummitAccessLevelType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($access_level_id)));
        $access_level = $this->access_levels->matching($criteria)->first();
        return $access_level === false ? null : $access_level;
    }

    /**
     * @param string $access_level_name
     * @return SummitAccessLevelType|null
     */
    public function getAccessLevelByName(string $access_level_name):?SummitAccessLevelType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', $access_level_name));
        $access_level = $this->access_levels->matching($criteria)->first();
        return $access_level === false ? null : $access_level;
    }

    /**
     * @param int $badge_feature_id
     * @return SummitBadgeFeatureType|null
     */
    public function getBadgeFeatureById(int $badge_feature_id):?SummitBadgeFeatureType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($badge_feature_id)));
        $badge_feature = $this->badge_features->matching($criteria)->first();
        return $badge_feature === false ? null : $badge_feature;
    }

    /**
     * @param string $feature_name
     * @return bool
     */
    public function hasFeatureByName(string $feature_name):bool{
        return $this->getBadgeFeatureByName($feature_name) !== null;
    }

    /**
     * @param string $badge_feature_name
     * @return SummitBadgeFeatureType|null
     */
    public function getBadgeFeatureByName(string $badge_feature_name):?SummitBadgeFeatureType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', $badge_feature_name));
        $badge_feature = $this->badge_features->matching($criteria)->first();
        return $badge_feature === false ? null : $badge_feature;
    }

    /**
     * @param SummitBadgeType $type
     * @return SummitAttendeeBadge
     */
    public static function buildBadgeFromType(SummitBadgeType $type):SummitAttendeeBadge{
        $badge = new SummitAttendeeBadge();
        $badge->setType($type);
        return $badge;
    }

    public function getAllowedViewTypes(){
        return $this->allowed_view_types;
    }

    public function addAllowedViewType(SummitBadgeViewType $viewType){
        if($this->allowed_view_types->contains($viewType)) return;
        $this->allowed_view_types->add($viewType);
    }

    /**
     * @param SummitBadgeViewType $viewType
     * @return bool
     */
    public function allowsViewType(SummitBadgeViewType $viewType):bool{
        return $this->allowed_view_types->contains($viewType);
    }

    public function removeAllowedViewType(SummitBadgeViewType $viewType){
        if(!$this->allowed_view_types->contains($viewType)) return;
        $this->allowed_view_types->removeElement($viewType);
    }
}