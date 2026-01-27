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

use App\Models\Foundation\Main\IGroup;
use App\Security\SummitScopes;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Validator;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use services\model\ISummitService;
use Symfony\Component\HttpFoundation\Response;
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

    #[OA\Get(
        path: "/api/v1/summits/{id}/registration-companies",
        summary: "Get all registration companies for a summit",
        description: "Returns list of companies that have registered attendees for this summit",
        operationId: "getAllRegistrationCompaniesBySummit",
        security: [
            [
                "registration_companies_oauth2" => [
                    SummitScopes::ReadAllSummitData,
                    SummitScopes::ReadSummitData,
                ]
            ]
        ],
        tags: ["Registration Companies"],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID or slug", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "page", description: "Page number", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", description: "Items per page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "filter", description: "Filter query (name==value, name=@value, name@@value)", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", description: "Order by (+name, -name)", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedCompaniesResponse")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllBySummit($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function(){
                return [
                    'name' => ['=@', '@@', '=='],
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

    #[OA\Put(
        path: "/api/v1/summits/{id}/registration-companies/{company_id}",
        summary: "Add a company to summit registration companies",
        description: "Associates a company with the summit for registration purposes (requires admin privileges)",
        operationId: "addRegistrationCompany",
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairsAdmins,
            ]
        ],
        security: [
            [
                "registration_companies_oauth2" => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        tags: ["Registration Companies"],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID or slug", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "company_id", description: "Company ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: "Created"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function add($summit_id, $company_id)
    {
        return $this->processRequest(function() use($summit_id, $company_id){
            $this->summit_service->addCompany(intval($summit_id), intval($company_id));
            return $this->created();
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/registration-companies/{company_id}",
        summary: "Remove a company from summit registration companies",
        description: "Disassociates a company from the summit registration (requires admin privileges)",
        operationId: "deleteRegistrationCompany",
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairsAdmins,
            ]
        ],
        security: [
            [
                "registration_companies_oauth2" => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        tags: ["Registration Companies"],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID or slug", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "company_id", description: "Company ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "No Content"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function delete($summit_id, $company_id)
    {
        return $this->processRequest(function() use($summit_id, $company_id){
            $this->summit_service->removeCompany(intval($summit_id), intval($company_id));
            return $this->deleted();
        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/registration-companies/csv",
        summary: "Import registration companies from CSV file",
        description: "Bulk import companies for summit registration from a CSV file (requires admin privileges)",
        operationId: "importRegistrationCompanies",
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairsAdmins,
            ]
        ],
        security: [
            [
                "registration_companies_oauth2" => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        tags: ["Registration Companies"],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID or slug", in: "path", required: true, schema: new OA\Schema(type: "string")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(ref: "#/components/schemas/ImportRegistrationCompaniesRequest")
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "OK - Companies imported successfully"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
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
