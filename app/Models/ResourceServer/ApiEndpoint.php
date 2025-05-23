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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @package App\Models\ResourceServer
*/
#[ORM\Table(name: 'api_endpoints')]
#[ORM\Entity(repositoryClass: \repositories\resource_server\DoctrineApiEndpointRepository::class)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE', region: 'resource_server_region')] // Class ApiEndpoint
class ApiEndpoint extends ResourceServerEntity implements IApiEndpoint
{

    /**
     * @return Api
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * @param Api $api
     */
    public function setApi($api)
    {
        $this->api = $api;
    }

    /**
     * @return ApiScope[]
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @param ApiScope[] $scopes
     */
    public function setScopes($scopes)
    {
        $this->scopes = $scopes;
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
    public function isAllowCors()
    {
        return $this->allow_cors;
    }

    /**
     * @param boolean $allow_cors
     */
    public function setAllowCors($allow_cors)
    {
        $this->allow_cors = $allow_cors;
    }

    /**
     * @return boolean
     */
    public function isAllowCredentials()
    {
        return $this->allow_credentials;
    }

    /**
     * @param boolean $allow_credentials
     */
    public function setAllowCredentials($allow_credentials)
    {
        $this->allow_credentials = $allow_credentials;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param string $route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     * @return string
     */
    public function getHttpMethod()
    {
        return $this->http_method;
    }

    /**
     * @param string $http_method
     */
    public function setHttpMethod($http_method)
    {
        $this->http_method = $http_method;
    }
    /**
     * @var Api
     */
    #[ORM\JoinColumn(name: 'api_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Api::class, cascade: ['persist'], inversedBy: 'endpoints')]
    private $api;

    /**
     * @var ApiScope[]
     */
    #[ORM\JoinTable(name: 'endpoint_api_scopes')]
    #[ORM\JoinColumn(name: 'api_endpoint_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'scope_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: \ApiScope::class)]
    private $scopes;

    #[ORM\OneToMany(targetEntity: \ApiEndpointAuthzGroup::class, mappedBy: 'api_endpoint', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $authz_groups;

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
     * @var bool
     */
    #[ORM\Column(name: 'active', type: 'boolean')]
    private $active;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'allow_cors', type: 'boolean')]
    private $allow_cors;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'allow_credentials', type: 'boolean')]
    private $allow_credentials;

    /**
     * @var string
     */
    #[ORM\Column(name: 'route', type: 'string')]
    private $route;

    /**
     * @var string
     */
    #[ORM\Column(name: 'http_method', type: 'string')]
    private $http_method;

    /**
     * @var int
     */
    #[ORM\Column(name: 'rate_limit', type: 'integer')]
    private $rate_limit;

    /**
     * @var int
     */
    #[ORM\Column(name: 'rate_limit_decay', type: 'integer')]
    private $rate_limit_decay;

    /**
     * ApiEndpoint constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->rate_limit       = 0;
        $this->rate_limit_decay = 0;
        $this->scopes           = new ArrayCollection();
        $this->authz_groups     = new ArrayCollection();
    }

    /**
	* @return string
	*/
	public function getFriendlyScopes():string
	{
	    $scopes = $this->getScopesNames();
	    return implode(' ', $scopes);
	}

    /**
     * @return string[]
     */
	public function getScopesNames(): array {
        $scopes = [];

        foreach ($this->scopes as $s) {
            if (!$s->isActive()) {
                continue;
            }
            $scopes[] = $s->getName();
        }

        return $scopes;
    }

    /**
     * @param IApiScope $scope
     */
	public function addScope(IApiScope $scope){
        $this->scopes->add($scope);
    }

    /**
     * @return int
     */
    public function getRateLimit()
    {
        return $this->rate_limit;
    }

    /**
     * @param int $rate_limit
     */
    public function setRateLimit($rate_limit)
    {
        $this->rate_limit = $rate_limit;
    }

    /**
     * @return int
     */
    public function getRateLimitDecay()
    {
        return $this->rate_limit_decay;
    }

    /**
     * @param int $rate_limit_decay
     */
    public function setRateLimitDecay($rate_limit_decay)
    {
        $this->rate_limit_decay = $rate_limit_decay;
    }

    /**
     * @return mixed
     */
    public function getAuthzGroups()
    {
        return $this->authz_groups;
    }

    /**
     * @param string $slug
     * @return ApiEndpointAuthzGroup
     */
    public function addAuthGroup(string $slug):ApiEndpointAuthzGroup{
        $authz_group = new ApiEndpointAuthzGroup();
        $authz_group->setSlug(trim($slug));
        $authz_group->setApiEndpoint($this);
        $this->authz_groups->add($authz_group);
        return $authz_group;
    }


    /**
     * @return int[]
     */
    public function getScopeIds():array {
        $ids = [];
        foreach ($this->getScopes() as $e) {
            $ids[] = intval($e->getId());
        }
        return $ids;
    }

    /**
     * @return int[]
     */
    public function getAuthGroupIds():array {
        $ids = [];
        foreach ($this->getAuthzGroups() as $e) {
            $ids[] = intval($e->getId());
        }
        return $ids;
    }

    public function clearScopes():void
    {

        $this->scopes->clear();
    }

    public function clearAuthzGroups():void
    {
        foreach($this->authz_groups as $authz_group)
        {
            $authz_group->clearApiEndpoint();
        }
        $this->authz_groups->clear();
    }
}