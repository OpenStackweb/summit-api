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
 * @package App\Models\Foundation\Marketplace
 */
#[ORM\Table(name: 'CompanyService')]
#[ORM\Entity(repositoryClass: \App\Repositories\Marketplace\DoctrineCompanyServiceRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'ClassName', type: 'string')]
#[ORM\DiscriminatorMap(['CompanyService' => 'CompanyService', 'RegionalSupportedCompanyService' => 'RegionalSupportedCompanyService', 'OpenStackImplementation' => 'OpenStackImplementation', 'Appliance' => 'Appliance', 'Distribution' => 'Distribution', 'Consultant' => 'Consultant', 'CloudService', 'CloudService', 'PrivateCloudService' => 'PrivateCloudService', 'PublicCloudService' => 'PublicCloudService', 'RemoteCloudService' => 'RemoteCloudService', 'TrainingService' => 'TrainingService'])] // Class CompanyService
class CompanyService extends SilverstripeBaseModel
{
    const ClassName = 'CompanyService';

    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    protected $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Slug', type: 'string')]
    protected $slug;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Overview', type: 'string')]
    protected $overview;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Call2ActionUri', type: 'string')]
    protected $call_2_action_url;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'Active', type: 'boolean')]
    protected $is_active;

    /**
     * @var Company
     */
    #[ORM\JoinColumn(name: 'CompanyID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Company::class, fetch: 'EXTRA_LAZY')]
    protected $company;

    /**
     * @var MarketPlaceType
     */
    #[ORM\JoinColumn(name: 'MarketPlaceTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \MarketPlaceType::class, fetch: 'EXTRA_LAZY')]
    protected $type;

    /**
     * @var MarketPlaceReview[]
     */
    #[ORM\OneToMany(targetEntity: \MarketPlaceReview::class, mappedBy: 'company_service', cascade: ['persist'], orphanRemoval: true)]
    protected $reviews;

    /**
     * @var MarketPlaceVideo[]
     */
    #[ORM\OneToMany(targetEntity: \MarketPlaceVideo::class, mappedBy: 'company_service', cascade: ['persist'], orphanRemoval: true)]
    protected $videos;

    /**
     * @var CompanyServiceResource[]
     */
    #[ORM\OneToMany(targetEntity: \CompanyServiceResource::class, mappedBy: 'company_service', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['name' => 'order'])]
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