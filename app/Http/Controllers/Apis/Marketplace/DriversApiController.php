<?php namespace App\Http\Controllers;
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
use App\Models\Foundation\Marketplace\IDriverRepository;
use models\oauth2\IResourceServerContext;
use models\utils\IBaseRepository;
use ModelSerializers\SerializerRegistry;

/**
 * Class DriversApiController
 * @package App\Http\Controllers
 */
final class DriversApiController extends JsonController
{
    use ParametrizedGetAll;

    /**
     * @var IDriverRepository
     */
    private $repository;

    /**
     * @var IResourceServerContext
     */
    private $resource_server_context;

    /**
     * DriversApiController constructor.
     * @param IDriverRepository $repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct(IDriverRepository $repository, IResourceServerContext $resource_server_context)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->resource_server_context = $resource_server_context;
    }

    protected function getResourceServerContext(): IResourceServerContext
    {
        return $this->resource_server_context;
    }

    protected function getRepository(): IBaseRepository
    {
        return $this->repository;
    }

    /**
     * @return mixed
     */
    public function getAll()
    {
        return $this->_getAll(
            function () {
                return [
                    'name'    => ['=@', '==', '@@'],
                    'project' => ['=@', '==', '@@'],
                    'vendor'  => ['=@', '==', '@@'],
                    'release' => ['=@', '==', '@@'],
                ];
            },
            function () {
                return [
                    'name'    => 'sometimes|string',
                    'project' => 'sometimes|string',
                    'vendor'  => 'sometimes|string',
                    'release' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'project',
                    'vendor',
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }
}
