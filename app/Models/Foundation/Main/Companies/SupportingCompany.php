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
use App\Models\Utils\BaseEntity;
use models\utils\One2ManyPropertyTrait;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="repositories\main\DoctrineSupportingCompanyRepository")
 * @ORM\Table(name="SupportingCompany")
 * Class SupportingCompany
 * @package models\main
 */
class SupportingCompany extends BaseEntity implements IOrderable
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getCompanyId' => 'company',
        'getSponsorshipId' => 'sponsorship_type',
    ];

    protected $hasPropertyMappings = [
        'hasCompany' => 'company',
        'hasSponsorshipType' => 'sponsorship_type',
    ];

    /**
     * @ORM\ManyToOne(targetEntity="Company", fetch="EXTRA_LAZY", inversedBy="project_sponsorships")
     * @ORM\JoinColumn(name="CompanyID", referencedColumnName="ID", onDelete="CASCADE")
     * @var Company
     */
    private $company;

    /**
     * @ORM\ManyToOne(targetEntity="ProjectSponsorshipType", fetch="EXTRA_LAZY", inversedBy="supporting_companies")
     * @ORM\JoinColumn(name="ProjectSponsorshipTypeID", referencedColumnName="ID", onDelete="CASCADE")
     * @var ProjectSponsorshipType
     */
    private $sponsorship_type;

    /**
     * @ORM\Column(name="`CustomOrder`", type="integer")
     * @var int
     */
    private $order;

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

    /**
     * @return Company
     */
    public function getCompany(): Company
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
     * @return ProjectSponsorshipType
     */
    public function getSponsorshipType(): ProjectSponsorshipType
    {
        return $this->sponsorship_type;
    }

    /**
     * @param ProjectSponsorshipType $sponsorship_type
     */
    public function setSponsorshipType(ProjectSponsorshipType $sponsorship_type): void
    {
        $this->sponsorship_type = $sponsorship_type;
    }

    public function clearCompany(){
        $this->company = null;
    }

    public function clearSponsorshipType(){
        $this->sponsorship_type = null;
    }

}