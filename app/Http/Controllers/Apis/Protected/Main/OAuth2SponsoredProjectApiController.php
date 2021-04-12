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

use App\Models\Foundation\Main\Repositories\IProjectSponsorshipTypeRepository;
use App\Models\Foundation\Main\Repositories\ISponsoredProjectRepository;
use App\Models\Foundation\Main\Repositories\ISupportingCompanyRepository;
use App\Services\Model\ISponsoredProjectService;
use Illuminate\Support\Facades\Log;
use libs\utils\HTMLCleaner;
use models\exceptions\EntityNotFoundException;
use models\oauth2\IResourceServerContext;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use Psr\Log\LogLevel;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;
/**
 * Class OAuth2SponsoredProjectApiController
 * @package App\Http\Controllers
 */
final class OAuth2SponsoredProjectApiController extends OAuth2ProtectedController
{

    use ParametrizedGetAll;

    use AddEntity;

    use UpdateEntity;

    use DeleteEntity;

    use GetEntity;

    use ParametrizedAddEntity;

    use ParametrizedUpdateEntity;

    use ParametrizedDeleteEntity;

    use ParametrizedGetEntity;

    /**
     * @var ISponsoredProjectService
     */
    private $service;

    /**
     * @var IProjectSponsorshipTypeRepository
     */
    private $project_sponsorship_type_repository;

    /**
     * @var ISupportingCompanyRepository
     */
    private $supporting_company_repository;


    /**
     * OAuth2SponsoredProjectApiController constructor.
     * @param ISponsoredProjectRepository $company_repository
     * @param IProjectSponsorshipTypeRepository $project_sponsorship_type_repository
     * @param ISupportingCompanyRepository $supporting_company_repository
     * @param IResourceServerContext $resource_server_context
     * @param ISponsoredProjectService $service
     */
    public function __construct
    (
        ISponsoredProjectRepository $company_repository,
        IProjectSponsorshipTypeRepository $project_sponsorship_type_repository,
        ISupportingCompanyRepository $supporting_company_repository,
        IResourceServerContext $resource_server_context,
        ISponsoredProjectService $service
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $company_repository;
        $this->project_sponsorship_type_repository = $project_sponsorship_type_repository;
        $this->supporting_company_repository = $supporting_company_repository;
        $this->service = $service;
    }

    /**
     * @inheritDoc
     */
    function getAddValidationRules(array $payload): array
    {
        return SponsoredProjectValidationRulesFactory::build($payload);
    }

    /**
     * @inheritDoc
     */
    protected function addEntity(array $payload): IEntity
    {
        return $this->service->add(HTMLCleaner::cleanData($payload, ['description']));
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
        return SponsoredProjectValidationRulesFactory::build($payload, true);
    }

    /**
     * @inheritDoc
     */
    protected function updateEntity($id, array $payload): IEntity
    {
        return $this->service->update($id, HTMLCleaner::cleanData($payload, ['description']));
    }

    /**
     * @return mixed
     */
    public function getAll()
    {
        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '=='],
                    'slug' => ['=@', '=='],
                    'is_active' => ['==']
                ];
            },
            function () {
                return [
                    'is_active' => 'sometimes|boolean',
                    'name' => 'sometimes|string',
                    'slug' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'name',
                    'id',
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

    // sponsorship types

    /**
     * @param $id string|int
     */
    public function getAllSponsorshipTypes($id)
    {
        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '=='],
                    'slug' => ['=@', '=='],
                    'is_active' => ['==']
                ];
            },
            function () {
                return [
                    'is_active' => 'sometimes|boolean',
                    'name' => 'sometimes|string',
                    'slug' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'name',
                    'id',
                    'order'
                ];
            },
            function ($filter) use($id) {
                if($filter instanceof Filter){
                    if(is_numeric($id))
                        $filter->addFilterCondition(FilterElement::makeEqual('sponsored_project_id', intval($id)));
                    else
                        $filter->addFilterCondition(FilterElement::makeEqual('sponsored_project_slug', $id));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->project_sponsorship_type_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @param $id
     * @param $sponsorship_type_id
     */
    public function getSponsorshipType($id, $sponsorship_type_id){
        Log::debug(sprintf("OAuth2SponsoredProjectApiController::getSponsorshipType id %s sponsorship_type_id %s", $id, $sponsorship_type_id));
        return $this->_get($sponsorship_type_id, function($id){
            return $this->project_sponsorship_type_repository->getById(intval($id));
        });
    }

    /**
     * @param $id
     * @return mixed
     */
    public function addSponsorshipType($id)
    {
        $args = [intval($id)];
        return $this->_add(
            function ($payload) {
                return ProjectSponsorshipTypeValidationRulesFactory::build($payload);
            },
            function ($payload, $id){
                return $this->service->addProjectSponsorshipType($id, HTMLCleaner::cleanData($payload, ['description']));
            },
            ...$args
        );
    }

    /**
     * @param $id
     * @param $sponsorship_type_id
     * @return mixed
     */
    public function updateSponsorshipType($id, $sponsorship_type_id){
        $args = [ intval($id) ];
        return $this->_update(
            $sponsorship_type_id,
            function($payload){
                return ProjectSponsorshipTypeValidationRulesFactory::build($payload, true);
            },
            function($sponsorship_type_id, $payload, $project_id){
                return $this->service->updateProjectSponsorshipType($project_id, $sponsorship_type_id, HTMLCleaner::cleanData($payload, ['description']));
            },
            ...$args
        );
    }

    /**
     * @param $id
     * @param $sponsorship_type_id
     * @return mixed
     */
    public function deleteSponsorshipType($id, $sponsorship_type_id){
        $args = [ intval($id) ];

        return $this->_delete(
            $sponsorship_type_id,
            function ($sponsorship_type_id, $project_id){
                $this->service->deleteProjectSponsorshipType($project_id, $sponsorship_type_id);
            },
            ...$args
        );
    }

    //  supporting companies

    public function getSupportingCompanies($id, $sponsorship_type_id){
        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '=='],
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'name',
                    'order',
                ];
            },
            function ($filter) use($id, $sponsorship_type_id) {
                if($filter instanceof Filter){
                    if(is_numeric($id))
                        $filter->addFilterCondition(FilterElement::makeEqual('sponsored_project_id', intval($id)));
                    else
                        $filter->addFilterCondition(FilterElement::makeEqual('sponsored_project_slug', $id));

                    if(is_numeric($sponsorship_type_id))
                        $filter->addFilterCondition(FilterElement::makeEqual('sponsorship_type_id', intval($sponsorship_type_id)));
                    else
                        $filter->addFilterCondition(FilterElement::makeEqual('sponsorship_type_slug', $sponsorship_type_id));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->supporting_company_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }


    /**
     * @param $id
     * @param $sponsorship_type_id
     * @return mixed
     */
    public function addSupportingCompanies($id, $sponsorship_type_id){
        return $this->_add(
            function($payload){
                return [
                    'company_id' => 'required|integer',
                    'order' => 'sometimes|integer|min:1',
                ];
            },
            function($payload, $project_id, $sponsorship_type_id){
                return $this->service->addCompanyToProjectSponsorshipType
                (
                    $project_id,
                    $sponsorship_type_id,
                    $payload
                );
            },
            $id,
            $sponsorship_type_id
        );
    }

    /**
     * @param $id
     * @param $sponsorship_type_id
     * @param $company_id
     * @return mixed
     */
    public function updateSupportingCompanies($id, $sponsorship_type_id, $company_id){
        return $this->_update($company_id,
            function($payload){
                return [
                    'order' => 'sometimes|integer|min:1',
                ];
            },
            function($id, $payload, $project_id, $sponsorship_type_id){
                return $this->service->updateCompanyToProjectSponsorshipType
                (
                    $project_id,
                    $sponsorship_type_id,
                    $id,
                    $payload
                );
            },
            $id,
            $sponsorship_type_id
        );
    }

    /**
     * @param $id
     * @param $sponsorship_type_id
     * @param $company_id
     * @return mixed
     */
    public function deleteSupportingCompanies($id, $sponsorship_type_id, $company_id){
        return $this->_delete($company_id, function($id, $project_id, $sponsorship_type_id){
            $this->service->removeCompanyToProjectSponsorshipType($project_id, $sponsorship_type_id, $id);
        }, $id, $sponsorship_type_id);
    }

    /**
     * @param $id
     * @param $sponsorship_type_id
     * @param $company_id
     * @return mixed
     */
    public function getSupportingCompany($id, $sponsorship_type_id, $company_id){
        return $this->_get($sponsorship_type_id, function($id, $company_id){
            $sponsorship_type = $this->project_sponsorship_type_repository->getById(intval($id));
            if(is_null($sponsorship_type))
                throw new EntityNotFoundException();
            return $sponsorship_type->getSupportingCompanyById(intval($company_id));
        }, $company_id);
    }
}