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

use App\Rules\Boolean;
use App\Services\Model\ICompanyService;
use Illuminate\Http\Request as LaravelRequest;
use models\exceptions\ValidationException;
use models\main\ICompanyRepository;
use models\oauth2\IResourceServerContext;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;

/**
 * Class OAuth2CompaniesApiController
 * @package App\Http\Controllers
 */
final class OAuth2CompaniesApiController extends OAuth2ProtectedController
{

    use ParametrizedGetAll;

    use AddEntity;

    use UpdateEntity;

    use DeleteEntity;

    use GetEntity;

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
        ICompanyRepository     $company_repository,
        IResourceServerContext $resource_server_context,
        ICompanyService        $service
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $company_repository;
        $this->service = $service;
    }

    /**
     * @return mixed
     */
    public function getAllCompanies()
    {

        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '==', '@@'],
                    'member_level' => ['=@', '==', '@@'],
                    'display_on_site' => [ '=='],
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|string',
                    'member_level' => 'sometimes|string',
                    'display_on_site' =>  ['sometimes', new Boolean],
                ];
            },
            function () {
                return [
                    'name',
                    'id',
                    'member_level',
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
              return $this->getEntitySerializerType();
            }
        );
    }

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
        return CompanyValidationRulesFactory::build($payload);
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

    protected function addEntitySerializerType(){
        return $this->getEntitySerializerType();
    }

    /**
     * @inheritDoc
     */
    protected function deleteEntity(int $id): void
    {
        $this->service->deleteCompany($id);
    }

    /**
     * @inheritDoc
     */
    protected function getEntity(int $id): IEntity
    {
        return $this->repository->getById($id);
    }


    protected function getEntitySerializerType()
    {
        $currentUser = $this->resource_server_context->getCurrentUser();
        return !is_null($currentUser) ? SerializerRegistry::SerializerType_Private :
            SerializerRegistry::SerializerType_Public;
    }

    protected function updateEntitySerializerType(){
        return $this->getEntitySerializerType();
    }
    /**
     * @inheritDoc
     */
    function getUpdateValidationRules(array $payload): array
    {
        return CompanyValidationRulesFactory::build($payload, true);
    }

    /**
     * @inheritDoc
     */
    protected function updateEntity($id, array $payload): IEntity
    {
        return $this->service->updateCompany($id, $payload);
    }

    use RequestProcessor;

    // Logos

    /**
     * @param LaravelRequest $request
     * @param $speaker_id
     * @return mixed
     */
    public function addCompanyLogo(LaravelRequest $request, $company_id)
    {
        return $this->processRequest(function () use ($request, $company_id) {

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $logo = $this->service->addCompanyLogo(intval($company_id), $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($logo)->serialize());
        });
    }

    /**
     * @param $company_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteCompanyLogo($company_id)
    {
        return $this->processRequest(function () use ($company_id) {

            $this->service->deleteCompanyLogo(intval($company_id));

            return $this->deleted();

        });
    }

    /**
     * @param LaravelRequest $request
     * @param $speaker_id
     * @return mixed
     */
    public function addCompanyBigLogo(LaravelRequest $request, $company_id)
    {
        return $this->processRequest(function () use ($request, $company_id) {
            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $logo = $this->service->addCompanyBigLogo(intval($company_id), $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($logo)->serialize());
        });
    }

    /**
     * @param $company_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteCompanyBigLogo($company_id)
    {
        return $this->processRequest(function () use ($company_id) {
            $this->service->deleteCompanyBigLogo(intval($company_id));
            return $this->deleted();
        });
    }
}