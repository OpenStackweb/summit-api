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
use App\Models\Foundation\Main\IOrderable;
use App\Models\Foundation\Main\OrderableChilds;
use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use models\exceptions\ValidationException;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="repositories\main\DoctrineProjectSponsorshipTypeRepository")
 * @ORM\Table(name="ProjectSponsorshipType")
 * Class ProjectSponsorshipType
 * @package models\main
 */
class ProjectSponsorshipType extends SilverstripeBaseModel implements IOrderable
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
     * @ORM\Column(name="`Order`", type="integer")
     * @var int
     */
    private $order;

    /**
     * @ORM\ManyToOne(targetEntity="SponsoredProject", fetch="EXTRA_LAZY", inversedBy="sponsorship_types")
     * @ORM\JoinColumn(name="SponsoredProjectID", referencedColumnName="ID", onDelete="SET NULL")
     * @var SponsoredProject
     */
    private $sponsored_project;

    /**
     * @ORM\OneToMany(targetEntity="SupportingCompany", mappedBy="sponsorship_type", cascade={"persist"}, orphanRemoval=true)
     * @ORM\OrderBy({"order" = "ASC"})
     * @var SupportingCompany[]
     */
    private $supporting_companies;

    /**
     * @ORM\Column(name="Slug", type="string")
     * @var string
     */
    private $slug;

    /**
     * @inheritDoc
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @inheritDoc
     */
    public function getOrder()
    {
        return $this->order;
    }

    public function __construct()
    {
        parent::__construct();
        $this->supporting_companies = new ArrayCollection();
        $this->is_active = false;
    }

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
        $slugify    = new Slugify();
        $this->slug = $slugify->slugify($name);
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @return string
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
     * @return SponsoredProject
     */
    public function getSponsoredProject(): ?SponsoredProject
    {
        return $this->sponsored_project;
    }

    /**
     * @param SponsoredProject $sponsored_project
     */
    public function setSponsoredProject(SponsoredProject $sponsored_project): void
    {
        $this->sponsored_project = $sponsored_project;
    }

    public function clearSponsoredProject(){
        $this->sponsored_project = null;
    }

    use OrderableChilds;

    /**
     * @param SupportingCompany $value
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateSupportingCompanyOrder(SupportingCompany $value, $new_order){
        self::recalculateOrderForSelectable($this->supporting_companies, $value, $new_order);
    }


    /**
     * @param Company $company
     * @return SupportingCompany|null
     */
    public function addSupportingCompany(Company $company):?SupportingCompany {
        $supporting_company = new SupportingCompany();
        $supporting_company->setCompany($company);
        $supporting_company->setSponsorshipType($this);
        $this->supporting_companies->add($supporting_company);
        $supporting_company->setOrder($this->supporting_companies->count());
        return $supporting_company;
    }

    /**
     * @param Company $company
     * @return SupportingCompany|null
     */
    public function getSupportingCompanyByCompany(Company $company):?SupportingCompany {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('company', $company));
        $supporting_company = $this->supporting_companies->matching($criteria)->first();
        return !$supporting_company ? null : $supporting_company;
    }

    /**
     * @param Company $company
     */
    public function removeSupportingCompany(Company $company){
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('company', $company));
        $supporting_company = $this->supporting_companies->matching($criteria)->first();
        if(!$supporting_company) return;
        $this->supporting_companies->removeElement($supporting_company);
    }

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getSponsoredProjectId' => 'sponsored_project',
    ];

    protected $hasPropertyMappings = [
        'hasSponsoredProject' => 'sponsored_project',
    ];

    /**
     * @return array
     */
    public function getSupportingCompaniesIds():array {
        $res = [];
        foreach ($this->supporting_companies as $supporting_company){
            $res[] = $supporting_company->getCompany()->getId();
        }
        return $res;
    }

    public function getSupportingCompanies(){
        return $this->supporting_companies;
    }

    /**
     * @param int $id
     * @return SupportingCompany|null
     */
    public function getSupportingCompanyById(int $id):?SupportingCompany{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
        $res = $this->supporting_companies->matching($criteria)->first();
        return !$res ? null : $res;
    }

}