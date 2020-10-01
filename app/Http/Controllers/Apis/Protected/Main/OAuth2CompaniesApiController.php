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
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\main\ICompanyRepository;
use models\oauth2\IResourceServerContext;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use models\exceptions\ValidationException;
use Illuminate\Http\Request as LaravelRequest;
use Exception;
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
        ICompanyRepository $company_repository,
        IResourceServerContext $resource_server_context,
        ICompanyService $service
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $company_repository;
        $this->service = $service;
    }

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

    // Logos

    /**
     * @param LaravelRequest $request
     * @param $speaker_id
     * @return mixed
     */
    public function addCompanyLogo(LaravelRequest $request, $company_id)
    {
        try {

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $logo = $this->service->addCompanyLogo($company_id, $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($logo)->serialize());

        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412(array($ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $company_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteCompanyLogo($company_id){
        try {

            $this->service->deleteCompanyLogo($company_id);

            return $this->deleted();

        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412(array($ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param LaravelRequest $request
     * @param $speaker_id
     * @return mixed
     */
    public function addCompanyBigLogo(LaravelRequest $request, $company_id)
    {
        try {

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $logo = $this->service->addCompanyLogo($company_id, $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($logo)->serialize());

        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412(array($ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $company_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteCompanyBigLogo($company_id){
        try {

            $this->service->deleteCompanyLogo($company_id);

            return $this->deleted();

        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412(array($ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}