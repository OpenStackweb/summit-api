<?php namespace App\Models\Foundation\Marketplace;
/**
 * Copyright 2017 OpenStack Foundation
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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @package App\Models\Foundation\Marketplace
 */
#[ORM\Table(name: 'RegionalSupport')]
#[ORM\Entity]
class RegionalSupport extends SilverstripeBaseModel
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'Order', type: 'integer')]
    private $order;

    /**
     * @var Region
     */
    #[ORM\JoinColumn(name: 'RegionID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \App\Models\Foundation\Marketplace\Region::class, fetch: 'EXTRA_LAZY')]
    private $region;

    /**
     * @var RegionalSupportedCompanyService
     */
    #[ORM\JoinColumn(name: 'ServiceID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \RegionalSupportedCompanyService::class, inversedBy: 'regional_supports', fetch: 'LAZY')]
    private $company_service;

    /**
     * @var SupportChannelType[]
     */
    #[ORM\JoinTable(name: '`RegionalSupport_SupportChannelTypes`')]
    #[ORM\JoinColumn(name: 'RegionalSupportID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'SupportChannelTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \SupportChannelType::class, cascade: ['persist'])]
    private $supported_channel_types;

    public function __construct()
    {
        $this->supported_channel_types = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return Region
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @return RegionalSupportedCompanyService
     */
    public function getCompanyService()
    {
        return $this->company_service;
    }

    /**
     * @return SupportChannelType[]
     */
    public function getSupportedChannelTypes()
    {
        return $this->supported_channel_types->toArray();
    }
}