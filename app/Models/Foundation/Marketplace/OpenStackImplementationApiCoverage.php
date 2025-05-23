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
use App\Models\Foundation\Software\OpenStackReleaseSupportedApiVersion;
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @package App\Models\Foundation\Marketplace
 */
#[ORM\Table(name: 'OpenStackImplementationApiCoverage')]
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'ClassName', type: 'string')]
#[ORM\DiscriminatorMap(['OpenStackImplementationApiCoverage' => 'OpenStackImplementationApiCoverage', 'CloudServiceOffered' => 'CloudServiceOffered'])] // Class OpenStackImplementationApiCoverage
class OpenStackImplementationApiCoverage
    extends SilverstripeBaseModel
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'CoveragePercent', type: 'integer')]
    protected $percent;

    /**
     * @var OpenStackReleaseSupportedApiVersion
     */
    #[ORM\JoinColumn(name: 'ReleaseSupportedApiVersionID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \App\Models\Foundation\Software\OpenStackReleaseSupportedApiVersion::class, fetch: 'EXTRA_LAZY')]
    protected $release_supported_api_version;

    /**
     * @var OpenStackImplementation
     */
    #[ORM\JoinColumn(name: 'ImplementationID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \App\Models\Foundation\Marketplace\OpenStackImplementation::class, fetch: 'EXTRA_LAZY', inversedBy: 'capabilities')]
    protected $implementation;

    /**
     * @return int
     */
    public function getPercent()
    {
        return $this->percent;
    }

    /**
     * @return OpenStackReleaseSupportedApiVersion
     */
    public function getReleaseSupportedApiVersion()
    {
        return $this->release_supported_api_version;
    }

    /**
     * @return OpenStackImplementation
     */
    public function getImplementation()
    {
        return $this->implementation;
    }

    /**
     * @return bool
     */
    public function hasReleaseSupportedApiVersion(){
        try{
            if(is_null($this->release_supported_api_version)) return false;
            return $this->release_supported_api_version->getId() > 0;
        }
        catch (\Exception $ex){
            return false;
        }
    }
}