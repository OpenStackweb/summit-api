<?php namespace App\Models\Foundation\Software;
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
use models\utils\SilverstripeBaseModel;
/**
 * @package App\Models\Foundation\Software
 */
#[ORM\Table(name: 'OpenStackApiVersion')]
#[ORM\Entity]
class OpenStackApiVersion extends SilverstripeBaseModel
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'ReleaseVersion', type: 'string')]
    private $version;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Status', type: 'string')]
    private $status;

    /**
        /**
    * @var OpenStackComponent
    */
    #[ORM\JoinColumn(name: 'OpenStackComponentID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \App\Models\Foundation\Software\OpenStackComponent::class, fetch: 'EXTRA_LAZY')]
    private $component;

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return OpenStackComponent
     */
    public function getComponent()
    {
        return $this->component;
    }

    /**
     * @return bool
     */
    public function hasComponent(){
        try{
            if(is_null($this->component)) return false;
            return $this->component->getId() > 0 ;
        }
        catch (\Exception $ex){
            return false;
        }
    }

}