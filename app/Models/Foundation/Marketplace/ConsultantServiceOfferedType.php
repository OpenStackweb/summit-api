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
use App\Models\Utils\BaseEntity;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package App\Models\Foundation\Marketplace
 */
#[ORM\Table(name: 'Consultant_ServicesOffered')]
#[ORM\Entity]
class ConsultantServiceOfferedType extends BaseEntity
{
    /**
     * @var Consultant
     */
    #[ORM\JoinColumn(name: 'ConsultantID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \Consultant::class, inversedBy: 'services_offered')]
    private $consultant;

    /**
     * @var ServiceOfferedType
     */
    #[ORM\JoinColumn(name: 'ConsultantServiceOfferedTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \ServiceOfferedType::class, fetch: 'LAZY')]
    private $service_offered;

    /**
     * @var Region
     */
    #[ORM\JoinColumn(name: 'RegionID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \Region::class, fetch: 'LAZY')]
    private $region;

    /**
     * @return Consultant
     */
    public function getConsultant()
    {
        return $this->consultant;
    }

    /**
     * @return ServiceOfferedType
     */
    public function getServiceOffered()
    {
        return $this->service_offered;
    }

    /**
     * @return Region
     */
    public function getRegion()
    {
        return $this->region;
    }
}