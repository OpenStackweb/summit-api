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
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Marketplace\DoctrineRemoteCloudServiceRepository")
 * @ORM\Table(name="RemoteCloudService")
 * Class RemoteCloudService
 * @package App\Models\Foundation\Marketplace
 */
class RemoteCloudService extends OpenStackImplementation
{
    const ClassName = 'RemoteCloudService';

    /**
     * @ORM\Column(name="HardwareSpecifications", type="string")
     * @var string
     */
    private $hardware_spec;

    /**
     * @ORM\Column(name="PricingModels", type="string")
     * @var string
     */
    private $pricing_models;

    /**
     * @ORM\Column(name="PublishedSLAs", type="string")
     * @var string
     */
    private $published_sla;

    /**
     * @ORM\Column(name="VendorManagedUpgrades", type="boolean")
     * @var bool
     */
    private $vendor_managed_upgrades;

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
    public function getHardwareSpec()
    {
        return $this->hardware_spec;
    }

    /**
     * @return string
     */
    public function getPricingModels()
    {
        return $this->pricing_models;
    }

    /**
     * @return string
     */
    public function getPublishedSla()
    {
        return $this->published_sla;
    }

    /**
     * @return bool
     */
    public function isVendorManagedUpgrades()
    {
        return $this->vendor_managed_upgrades;
    }
}