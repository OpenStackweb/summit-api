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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use models\main\Company;
use models\main\Member;
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
     * @ORM\ManyToMany(targetEntity="models\main\Member", inversedBy="sponsor_memberships")
     * @ORM\JoinTable(name="Sponsor_Users",
     *      joinColumns={@ORM\JoinColumn(name="SponsorID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="MemberID", referencedColumnName="ID")}
     *      )
     * @var Member[]
     */
    protected $members;

    /**
     * Sponsor constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->members = new ArrayCollection();
        $this->user_info_grants = new ArrayCollection();
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

}