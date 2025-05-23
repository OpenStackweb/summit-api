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
/**
 * @package App\Models\Foundation\Marketplace
 */
#[ORM\Table(name: 'CloudServiceOffered')]
#[ORM\Entity]
class CloudServiceOffered extends OpenStackImplementationApiCoverage
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'Type', type: 'string')]
    private $type;

    /**
     * @var PricingSchemaType[]
     */
    #[ORM\JoinTable(name: 'CloudServiceOffered_PricingSchemas')]
    #[ORM\JoinColumn(name: 'CloudServiceOfferedID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'PricingSchemaTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \App\Models\Foundation\Marketplace\PricingSchemaType::class, cascade: ['persist'])]
    private $pricing_schemas;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function __construct()
    {
        parent::__construct();
        $this->pricing_schemas = new ArrayCollection();
    }

    /**
     * @return PricingSchemaType[]
     */
    public function getPricingSchemas()
    {
        return $this->pricing_schemas->toArray();
    }
}