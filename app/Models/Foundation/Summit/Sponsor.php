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

use App\Http\Controllers\SponsorMaterialValidationRulesFactory;
use App\Models\Foundation\Main\IOrderable;
use App\Models\Foundation\Main\OrderableChilds;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\File;
use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSponsorRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="summit_sponsors"
 *     )
 * })
 * @ORM\Table(name="Sponsor")
 * Class Sponsor
 * @package models\summit
 */
class Sponsor extends SilverstripeBaseModel implements IOrderable
{
    use SummitOwned;

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getSideImageId' => 'side_image',
        'getHeaderImageId' => 'header_image',
    ];

    protected $hasPropertyMappings = [
        'hasSideImage' => 'side_image',
        'hasHeaderImage' => 'header_image',
    ];
    /**
     * @ORM\Column(name="`Order`", type="integer")
     * @var int
     */
    private $order;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Company")
     * @ORM\JoinColumn(name="CompanyID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Company
     */
    protected $company;

    /**
     * @ORM\Column(name="IsPublished", type="boolean")
     * @var boolean
     */
    protected $is_published;

    /**
     * @ORM\ManyToOne(targetEntity="SponsorshipType")
     * @ORM\JoinColumn(name="SponsorshipTypeID", referencedColumnName="ID", onDelete="SET NULL")
     * @var SponsorshipType
     */
    protected $sponsorship;

    /**
     * @ORM\OneToMany(targetEntity="SponsorUserInfoGrant", mappedBy="sponsor", cascade={"persist"}, orphanRemoval=true)
     * @var SponsorUserInfoGrant[]
     */
    protected $user_info_grants;

    /**
     * @ORM\OneToMany(targetEntity="SponsorAd", mappedBy="sponsor", cascade={"persist"}, orphanRemoval=true)
     * @var SponsorAd[]
     */
    protected $ads;

    /**
     * @ORM\OneToMany(targetEntity="SponsorMaterial", mappedBy="sponsor", cascade={"persist"}, orphanRemoval=true)
     * @var SponsorMaterial[]
     */
    protected $materials;

    /**
     * @ORM\OneToMany(targetEntity="SponsorSocialNetwork", mappedBy="sponsor", cascade={"persist"}, orphanRemoval=true)
     * @var SponsorSocialNetwork[]
     */
    protected $social_networks;

    /**
     * @ORM\ManyToMany(targetEntity="models\main\Member", inversedBy="sponsor_memberships")
     * @ORM\JoinTable(name="Sponsor_Users",
     *      joinColumns={@ORM\JoinColumn(name="SponsorID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="MemberID", referencedColumnName="ID")}
     *      )
     * @var Member[]
     */
    protected $members;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\File",cascade={"persist"})
     * @ORM\JoinColumn(name="SideImageID", referencedColumnName="ID")
     * @var File
     */
    protected $side_image;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\File", cascade={"persist"})
     * @ORM\JoinColumn(name="HeaderImageID", referencedColumnName="ID")
     * @var File
     */
    protected $header_image;

    /**
     * @ORM\Column(name="Marquee", type="string")
     * @var string
     */
    private $marquee;

    /**
     * @ORM\Column(name="Intro", type="string")
     * @var string
     */
    private $intro;

    /**
     * @ORM\Column(name="ExternalLink", type="string")
     * @var string
     */
    private $external_link;

    /**
     * @ORM\Column(name="VideoLink", type="string")
     * @var string
     */
    private $video_link;

    /**
     * @ORM\Column(name="ChatLink", type="string")
     * @var string
     */
    private $chat_link;

    /**
     * Sponsor constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->members = new ArrayCollection();
        $this->user_info_grants = new ArrayCollection();
        $this->materials = new ArrayCollection();
        $this->social_networks = new ArrayCollection();
        $this->ads= new ArrayCollection();
        $this->is_published = true;
    }

    /**
     * @return int
     */
    public function getOrder(): ?int
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
     * @return Company
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     * @param Company $company
     */
    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    /**
     * @return SponsorshipType
     */
    public function getSponsorship():?SponsorshipType
    {
        return $this->sponsorship;
    }

    /**
     * @param SponsorshipType $sponsorship
     */
    public function setSponsorship(SponsorshipType $sponsorship): void
    {
        $this->sponsorship = $sponsorship;
    }

    /**
     * @return Member[]
     */
    public function getMembers()
    {
        return $this->members;
    }

    public function hasSponsorship():bool{
        return $this->getSponsorshipId() > 0;
    }

    /**
     * @return int
     */
    public function getSponsorshipId(){
        try {
            return is_null($this->sponsorship) ? 0 : $this->sponsorship->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return int
     */
    public function getCompanyId(){
        try {
            return is_null($this->company) ? 0 : $this->company->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @param SponsorUserInfoGrant $grant
     */
    public function addUserInfoGrant(SponsorUserInfoGrant $grant){
        if($this->user_info_grants->contains($grant)) return;
        $this->user_info_grants->add($grant);
        $grant->setSponsor($this);
    }

    public function getUserInfoGrants(){
        return $this->user_info_grants;
    }

    /**
     * @param int $grant_id
     * @return SponsorUserInfoGrant|null
     */
    public function getUserInfoGrantById(int $grant_id):?SponsorUserInfoGrant{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $grant_id));
        $grant = $this->user_info_grants->matching($criteria)->first();
        return $grant === false ? null : $grant;
    }

    /**
     * @param Member $member
     * @return bool
     */
    public function hasGrant(Member $member):bool {
        return !is_null($this->getGrant($member));
    }

    /**
     * @param Member $member
     * @return SponsorUserInfoGrant|null
     */
    public function getGrant(Member $member):?SponsorUserInfoGrant {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('allowed_user', $member));
        $grant = $this->user_info_grants->matching($criteria)->first();
        return $grant === false ? null : $grant;
    }

    public function hasCompany():bool{
        return $this->getCompanyId() > 0;
    }

    /**
     * @param Member $user
     */
    public function addUser(Member $user){
        if($this->members->contains($user)) return;
        $this->members->add($user);
    }

    /**
     * @param Member $user
     */
    public function removeUser(Member $user){
        if(!$this->members->contains($user)) return;
        $this->members->removeElement($user);
    }

    /**
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->is_published;
    }

    /**
     * @param bool $is_published
     */
    public function setIsPublished(bool $is_published): void
    {
        $this->is_published = $is_published;
    }

    /**
     * @return string
     */
    public function getMarquee(): ?string
    {
        return $this->marquee;
    }

    /**
     * @param string $marquee
     */
    public function setMarquee(string $marquee): void
    {
        $this->marquee = $marquee;
    }

    /**
     * @return string|null
     */
    public function getIntro(): ?string
    {
        return $this->intro;
    }

    /**
     * @param string $intro
     */
    public function setIntro(string $intro): void
    {
        $this->intro = $intro;
    }

    /**
     * @return string|null
     */
    public function getExternalLink():?string
    {
        return $this->external_link;
    }

    /**
     * @param string $external_link
     */
    public function setExternalLink(string $external_link): void
    {
        $this->external_link = $external_link;
    }

    /**
     * @return string|null
     */
    public function getVideoLink():?string
    {
        return $this->video_link;
    }

    /**
     * @param string $video_link
     */
    public function setVideoLink(string $video_link): void
    {
        $this->video_link = $video_link;
    }

    /**
     * @return string|null
     */
    public function getChatLink():?string
    {
        return $this->chat_link;
    }

    /**
     * @param string $chat_link
     */
    public function setChatLink(string $chat_link): void
    {
        $this->chat_link = $chat_link;
    }

    public function getSideImageUrl():?string{
        if($this->hasSideImage())
            return $this->side_image->getUrl();
        return null;
    }

    public function getHeaderImageUrl():?string{
        if($this->hasHeaderImage())
            return $this->header_image->getUrl();
        return null;
    }

    /**
     * @param File $side_image
     */
    public function setSideImage(File $side_image): void
    {
        $this->side_image = $side_image;
    }

    public function clearSideImage():void{
        $this->side_image = null;
    }

    /**
     * @param File $header_image
     */
    public function setHeaderImage(File $header_image): void
    {
        $this->header_image = $header_image;
    }

    public function clearHeaderImage():void{
        $this->header_image = null;
    }

    use OrderableChilds;

    /**
     * @return int
     */
    private function getAdMaxOrder():int
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $ad = $this->ads->matching($criteria)->first();
        $res = $ad === false ? 0 : $ad->getOrder();
        return is_null($res) ? 0 : $res;
    }

    /**
     * @param SponsorAd $ad
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateAdOrder(SponsorAd $ad, int $new_order)
    {
        self::recalculateOrderForSelectable($this->ads, $ad, $new_order);
    }
    /**
     * @param int $ad_id
     * @return SponsorAd|null
     */
    public function getAdById(int $ad_id):?SponsorAd{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($ad_id)));
        $ad = $this->ads->matching($criteria)->first();
        return $ad === false ? null : $ad;
    }
    /**
     * @param SponsorAd $ad
     */
    public function addAd(SponsorAd $ad):void{
        if($this->ads->contains($ad)) return;
        $ad->setOrder($this->getAdMaxOrder() + 1);
        $this->ads->add($ad);
        $ad->setSponsor($this);
    }

    /**
     * @param SponsorAd $ad
     */
    public function removeAd(SponsorAd $ad):void{
        if(!$this->ads->contains($ad)) return;
        $this->ads->removeElement($ad);
        $ad->clearSponsor();
        self::resetOrderForSelectable($this->ads);
    }

    public function clearAds():void{
        $this->ads->clear();
    }

    public function getAds(){
        return $this->ads;
    }

    /**
     * @param int $material_id
     * @return SponsorMaterial|null
     */
    public function getMaterialById(int $material_id):?SponsorMaterial{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($material_id)));
        $material = $this->materials->matching($criteria)->first();
        return $material === false ? null : $material;
    }

    /**
     * @param string $name
     * @return SponsorMaterial|null
     */
    public function getMaterialByName(string $name):?SponsorMaterial{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('name', trim($name)));
        $material = $this->materials->matching($criteria)->first();
        return $material === false ? null : $material;
    }

    public function getMaterials(){
        return $this->materials;
    }

    /**
     * @param SponsorMaterial $material
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateMaterialOrder(SponsorMaterial $material, int $new_order)
    {
        self::recalculateOrderForSelectable($this->materials, $material, $new_order);
    }

    /**
     * @return int
     */
    private function getMaterialMaxOrder():int
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $material = $this->materials->matching($criteria)->first();
        $res = $material === false ? 0 : $material->getOrder();
        return is_null($res) ? 0 : $res;
    }

    /**
     * @param SponsorMaterial $material
     */
    public function addMaterial(SponsorMaterial $material):void{
        if($this->materials->contains($material)) return;
        $material->setOrder($this->getMaterialMaxOrder() + 1);
        $this->materials->add($material);
        $material->setSponsor($this);
    }

    /**
     * @param SponsorMaterial $material
     */
    public function removeMaterial(SponsorMaterial $material):void{
        if(!$this->materials->contains($material)) return;
        $this->materials->removeElement($material);
        $material->clearSponsor();
        self::resetOrderForSelectable($this->materials);
    }

    /**
     * @param int $social_network_id
     * @return SponsorSocialNetwork|null
     */
    public function getSocialNetworkById(int $social_network_id):?SponsorSocialNetwork{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($social_network_id)));
        $social_network = $this->social_networks->matching($criteria)->first();
        return $social_network === false ? null : $social_network;
    }

    /**
     * @param SponsorSocialNetwork $social_network
     */
    public function addSocialNetwork(SponsorSocialNetwork $social_network):void{
        if($this->social_networks->contains($social_network)) return;
        $this->social_networks->add($social_network);
        $social_network->setSponsor($this);
    }

    /**
     * @param SponsorSocialNetwork $social_network
     */
    public function removeSocialNetwork(SponsorSocialNetwork $social_network):void{
        if(!$this->social_networks->contains($social_network)) return;
        $this->social_networks->removeElement($social_network);
        $social_network->clearSponsor();
    }

    public function getSocialNetworks(){
        return $this->social_networks;
    }
}