<?php namespace App\Http\Controllers;
/**
 * Copyright 2020 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISummitMediaFileTypeRepository;
use App\Services\Model\ISummitMediaFileTypeService;
use models\oauth2\IResourceServerContext;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
/**
 * Class OAuth2SummitMediaFileTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitMediaFileTypeApiController extends OAuth2ProtectedController
{
    use AddEntity;

    use UpdateEntity;

    use DeleteEntity;

    use GetEntity;

    use ParametrizedGetAll;

    /**
     * @var ISummitMediaFileTypeService
     */
    private $service;

    /**
     * OAuth2SummitMediaFileTypeApiController constructor.
     * @param ISummitMediaFileTypeService $service
     * @param ISummitMediaFileTypeRepository $repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitMediaFileTypeService $service,
        ISummitMediaFileTypeRepository $repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->service = $service;
        $this->repository = $repository;
    }


    /**
     * @inheritDoc
     */
    function getAddValidationRules(array $payload): array
    {
        return [
            'name'  => 'required|string|max:255',
            'description'  => 'sometimes|string|max:255',
            'allowed_extensions'=> 'required|string_array',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function addEntity(array $payload): IEntity
    {
        return $this->service->add($payload);
    }

    /**
     * @inheritDoc
     */
    protected function deleteEntity(int $id): void
    {
       $this->service->delete($id);
    }

    /**
     * @inheritDoc
     */
    protected function getEntity(int $id): IEntity
    {
        return $this->repository->getById($id);
    }

    /**
     * @inheritDoc
     */
    function getUpdateValidationRules(array $payload): array
    {
        return [
            'name'  => 'sometimes|string|max:255',
            'description'  => 'sometimes|string|max:255',
            'allowed_extensions'=> 'required|string_array',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function updateEntity($id, array $payload): IEntity
    {
       return $this->service->update($id, $payload);
    }

    public function getAll(){
        return $this->_getAll(
            function(){
                return [
                    'name' => ['=@', '=='],
                ];
            },
            function(){
                return [
                    'name' => 'sometimes|string',
                ];
            },
            function()
            {
                return [
                    'name',
                    'id',
                ];
            },
            function($filter){
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }
}