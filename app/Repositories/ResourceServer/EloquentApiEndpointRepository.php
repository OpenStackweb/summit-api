<?php namespace repositories\resource_server;
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
use App\Models\ResourceServer\IApiEndpoint;
use App\Models\ResourceServer\IApiEndpointRepository;
use models\utils\EloquentBaseRepository;
use models\utils\IEntity;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;
/**
 * Class EloquentApiEndpointRepository
 * @package repositories\resource_server
 */
class EloquentApiEndpointRepository extends EloquentBaseRepository implements IApiEndpointRepository
{

    /**
     * @param IApiEndpoint $endpoint
     */
    public function __construct(IApiEndpoint $endpoint)
    {
        $this->entity = $endpoint;
    }

    /**
     * @param string $url
     * @param string $http_method
     * @return IApiEndpoint
     */
    public function getApiEndpointByUrlAndMethod($url, $http_method)
    {
        return $this->entity->Filter(array(
            array(
                'name' => 'route',
                'op' => '=',
                'value' => $url
            ),
            array(
                'name' => 'http_method',
                'op' => '=',
                'value' => $http_method
            )
        ))->firstOrFail();
    }

    /**
     * @param int $id
     * @return IEntity
     */
    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    /**
     * @param IEntity $entity
     * @return void
     */
    public function add($entity)
    {
        // TODO: Implement add() method.
    }

    /**
     * @param IEntity $entity
     * @return void
     */
    public function delete($entity)
    {
        // TODO: Implement delete() method.
    }

    /**
     * @return IEntity[]
     */
    public function getAll()
    {
        // TODO: Implement getAll() method.
    }

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null)
    {
        // TODO: Implement getAllByPage() method.
    }
}