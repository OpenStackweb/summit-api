<?php namespace App\Models\ResourceServer;
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

use Doctrine\ORM\Mapping AS ORM;

/**
 * @package App\Models\ResourceServer
 */
#[ORM\Table(name: 'api_scopes')]
#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE', region: 'resource_server_region')] // Class ApiScope
class ApiScope extends ResourceServerEntity implements IApiScope
{

    /**
     * @var Api
     */
    #[ORM\JoinColumn(name: 'api_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Api::class, inversedBy: 'scopes')]
    private $api;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string')]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'string')]
    private $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'short_description', type: 'string')]
    private $short_description;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'active', type: 'boolean')]
    private $active;

    /**
     * @var bool
     */
    #[ORM\Column(name: '`default`', type: 'boolean')]
    private $default;

    /**
     * @return IApi
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * @param Api $api
     */
    public function setApi(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getShortDescription()
    {
        return $this->short_description;
    }

    /**
     * @param string $short_description
     */
    public function setShortDescription($short_description)
    {
        $this->short_description = $short_description;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }



    /**
     * @return boolean
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * @param boolean $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    public function __construct()
    {
        parent::__construct();
        $this->active = false;
        $this->default = false;
    }
}