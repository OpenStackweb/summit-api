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
 * @ORM\Entity
 * @ORM\Table(name="endpoint_api_authz_groups")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE", region="resource_server_region")
 * Class ApiEndpointAuthzGroup
 * @package App\Models\ResourceServer
 */
class ApiEndpointAuthzGroup extends ResourceServerEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="ApiEndpoint", inversedBy="authz_groups")
     * @ORM\JoinColumn(name="api_endpoint_id", referencedColumnName="id")
     * @var ApiEndpoint
     */
    private $api_endpoint;

    /**
     * @ORM\Column(name="group_slug", type="string")
     * @var string
     */
    private $slug;

    /**
     * @return ApiEndpoint
     */
    public function getApiEndpoint(): ApiEndpoint
    {
        return $this->api_endpoint;
    }

    /**
     * @param ApiEndpoint $api_endpoint
     */
    public function setApiEndpoint(ApiEndpoint $api_endpoint): void
    {
        $this->api_endpoint = $api_endpoint;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }
}