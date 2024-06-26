<?php namespace App\Models\Foundation\Marketplace;

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
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
use models\main\Company;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Marketplace\DoctrineCompanyServiceRepository")
 * @ORM\Table(name="CompanyService")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="ClassName", type="string")
 * @ORM\DiscriminatorMap({
 *     "CompanyService" = "CompanyService",
 *     "RegionalSupportedCompanyService" = "RegionalSupportedCompanyService",
 *     "OpenStackImplementation" = "OpenStackImplementation",
 *     "Appliance" = "Appliance",
 *     "Distribution" = "Distribution",
 *     "Consultant" = "Consultant",
 *     "CloudService", "CloudService",
 *     "PrivateCloudService" = "PrivateCloudService",
 *     "PublicCloudService" = "PublicCloudService",
 *     "RemoteCloudService" = "RemoteCloudService",
 *     "TrainingService" = "TrainingService",
 * } )
 * Class CompanyService
 * @package App\Models\Foundation\Marketplace
 */
class CompanyService extends SilverstripeBaseModel
{
    const ClassName = 'CompanyService';

    /**
     * @ORM\Column(name="Name", type="string")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(name="Slug", type="string")
     * @var string
     */
    protected $slug;

    /**
     * @ORM\Column(name="Overview", type="string")
     * @var string
     */
    protected $overview;

    /**
     * @ORM\Column(name="Call2ActionUri", type="string")
     * @var string
     */
    protected $call_2_action_url;

    /**
     * @ORM\Column(name="Active", type="boolean")
     * @var bool
     */
    protected $is_active;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Company", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="CompanyID", referencedColumnName="ID")
     * @var Company
     */
    protected $company;

    /**
     * @ORM\ManyToOne(targetEntity="MarketPlaceType", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="MarketPlaceTypeID", referencedColumnName="ID")
     * @var MarketPlaceType
     */
    protected $type;

    /**
     * @ORM\OneToMany(targetEntity="MarketPlaceReview", mappedBy="company_service", cascade={"persist"}, orphanRemoval=true)
     * @var MarketPlaceReview[]
     */
    protected $reviews;

    /**
     * @ORM\OneToMany(targetEntity="MarketPlaceVideo", mappedBy="company_service", cascade={"persist"}, orphanRemoval=true)
     * @var MarketPlaceVideo[]
     */
    protected $videos;

    /**
     * @ORM\OneToMany(targetEntity="CompanyServiceResource", mappedBy="company_service", cascade={"persist"}, orphanRemoval=true)
     * @ORM\OrderBy({"name" = "order"})
     * @var CompanyServiceResource[]
     */
    protected $resources;

    /**
     * CompanyService constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->reviews   = new ArrayCollection();
        $this->videos    = new ArrayCollection();
        $this->resources = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getClassName():string
    {
        return self::ClassName;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function getOverview()
    {
        return $this->overview;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @return Company
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getCompanyId()
    {
        try {
            return !is_null($this->company)? $this->company->getId():0;
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return int
     */
    public function getTypeId()
    {
        try {
            return !is_null($this->type)? $this->type->getId():0;
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return MarketPlaceReview[]
     */
    public function getApprovedReviews(){
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('is_approved', true));
        return $this->reviews->matching($criteria)->toArray();
    }

    /**
     * @return string
     */
    public function getCall2ActionUrl()
    {
        return $this->call_2_action_url;
    }

    /**
     * @return MarketPlaceReview[]
     */
    public function getReviews()
    {
        return $this->reviews->toArray();
    }

    /**
     * @return MarketPlaceVideo[]
     */
    public function getVideos()
    {
        return $this->videos->toArray();
    }

    /**
     * @return CompanyServiceResource[]
     */
    public function getResources()
    {
        return $this->resources->toArray();
    }
}