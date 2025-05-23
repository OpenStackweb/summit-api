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
#[ORM\Table(name: 'endpoint_api_authz_groups')]
#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE', region: 'resource_server_region')] // Class ApiEndpointAuthzGroup
class ApiEndpointAuthzGroup extends ResourceServerEntity
{
    /**
     * @var ApiEndpoint
     */
    #[ORM\JoinColumn(name: 'api_endpoint_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ApiEndpoint::class, inversedBy: 'authz_groups')]
    private $api_endpoint;

    /**
     * @var string
     */
    #[ORM\Column(name: 'group_slug', type: 'string')]
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

    public function clearApiEndpoint():void{
        $this->api_endpoint = null;
    }
}