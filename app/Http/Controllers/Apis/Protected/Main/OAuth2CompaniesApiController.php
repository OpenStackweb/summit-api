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
use App\Services\Model\ICompanyService;
use models\main\ICompanyRepository;
use models\oauth2\IResourceServerContext;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use models\exceptions\ValidationException;
/**
 * Class OAuth2CompaniesApiController
 * @package App\Http\Controllers
 */
final class OAuth2CompaniesApiController extends OAuth2ProtectedController
{

    /**
     * @var ICompanyService
     */
    private $service;

    /**
     * OAuth2CompaniesApiController constructor.
     * @param ICompanyRepository $company_repository
     * @param IResourceServerContext $resource_server_context
     * @param ICompanyService $service
     */
    public function __construct
    (
        ICompanyRepository $company_repository,
        IResourceServerContext $resource_server_context,
        ICompanyService $service
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $company_repository;
        $this->service = $service;
    }

    use ParametrizedGetAll;

    /**
     * @return mixed
     */
    public function getAllCompanies(){

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

    use AddEntity;

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
        return [
            'name'        => 'required|string',
            'description' => 'nullable|string',
            'url'         => 'nullable|url',
            'industry'    => 'nullable|string',
            'city'        => 'nullable|string',
            'state'       => 'nullable|string',
            'country'     => 'nullable|string',
        ];
    }

    /**
     * @param array $payload
     * @return IEntity
     * @throws ValidationException
     */
    protected function addEntity(array $payload): IEntity
    {
        return $this->service->addCompany($payload);
    }
}