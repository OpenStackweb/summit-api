<?php namespace App\Http\Controllers;
/**
 * Copyright 2022 OpenStack Foundation
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

use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Validator;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use services\model\ISummitService;
use utils\PagingInfo;

/**
 * Class OAuth2SummitRegistrationCompaniesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitRegistrationCompaniesApiController extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;
    use RequestProcessor;

    /**
     * @var ISummitService
     */
    private $summit_service;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    use ParametrizedGetAll;

    /**
     * OAuth2SummitRegistrationCompaniesApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitService $summit_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitService $summit_service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->summit_repository = $summit_repository;
        $this->summit_service = $summit_service;
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllBySummit($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

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
                ];
            },
            function($filter) use($summit){
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use($summit) {
                return $this->summit_repository->getRegistrationCompanies
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
        );
    }

    /**
     * @param $summit_id
     * @param $company_id
     * @return mixed
     */
    public function add($summit_id, $company_id)
    {

        return $this->processRequest(function() use($summit_id, $company_id){
            $this->summit_service->addCompany(intval($summit_id), intval($company_id));
            return $this->created();
        });
    }

    /**
     * @param $summit_id
     * @param $company_id
     * @return mixed
     */
    public function delete($summit_id, $company_id)
    {
        return $this->processRequest(function() use($summit_id, $company_id){
            $this->summit_service->removeCompany(intval($summit_id), intval($company_id));
            return $this->deleted();
        });
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @return mixed
     */
    public function import(LaravelRequest $request,$summit_id){
        return $this->processRequest(function() use($request, $summit_id){
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $request->all();

            $rules = [
                'file' => 'required',
            ];
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                throw new ValidationException($validation->messages()->toArray());
            }

            $file = $request->file('file');

            $this->summit_service->importRegistrationCompanies($summit, $file);

            return $this->ok();

        });
    }
}