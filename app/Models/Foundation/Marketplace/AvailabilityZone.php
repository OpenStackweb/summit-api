<?php namespace App\Models\Foundation\Marketplace;
/**
 * Copyright 2026 OpenStack Foundation
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
use Doctrine\ORM\Mapping as ORM;
/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="AvailabilityZone",
 * )
 * Class AvailabilityZone
 * @package App\Models\Foundation\Marketplace
 */
class AvailabilityZone extends SilverstripeBaseModel
{
    const ClassName = 'AvailabilityZone';

    /**
     * @ORM\Column(name="Name", type="string", length=250)
     * @var string
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="DataCenterLocation", inversedBy="availability_zones", fetch="LAZY")
     * @ORM\JoinColumn(name="LocationID", referencedColumnName="ID", nullable=false, onDelete="CASCADE")
     * @var DataCenterLocation
     */
    private $location;

    public function getName():?string{
        return $this->name;
    }

    public function getLocation():?DataCenterLocation{
        return $this->location;
    }
}