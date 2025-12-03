<?php

namespace App\Http\Controllers;

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

use App\Models\Foundation\Main\IGroup;
use App\Rules\Boolean;
use App\Security\CompanyScopes;
use App\Security\SummitScopes;
use App\Services\Model\ICompanyService;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response;
use models\exceptions\ValidationException;
use models\main\ICompanyRepository;
use models\oauth2\IResourceServerContext;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;

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
    ) {
        parent::__construct($resource_server_context);
        $this->repository = $company_repository;
        $this->service = $service;
    }

    #[OA\Get(
        path: "/api/v1/companies/{id}",
        operationId: "getCompany",
        summary: "Get a specific company",
        description: "Returns detailed information about a specific company",
        security: [
            [
                "companies_oauth2" => [
                    CompanyScopes::Read,
                ]
            ]
        ],
        tags: ["Companies"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Company ID",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                description: "Expand related entities. Available expansions: sponsorships, project_sponsorships",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "relations",
                in: "query",
                required: false,
                description: "Load relations. Available: sponsorships, project_sponsorships",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Success",
                content: new OA\JsonContent(ref: "#/components/schemas/Company")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Company not found"),
        ]
    )]
    /**
     * Class OAuth2CompaniesApiController
     * @package App\Http\Controllers
     */
    #[OA\Get(
        path: "/api/public/v1/companies/{id}",
        operationId: "getCompanyPublic",
        summary: "Get a specific company (Public)",
        description: "Returns detailed information about a specific company",
        tags: ["Companies (Public)"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Company ID",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                description: "Expand related entities. Available expansions: sponsorships, project_sponsorships",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "relations",
                in: "query",
                required: false,
                description: "Load relations. Available: sponsorships, project_sponsorships",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Success",
                content: new OA\JsonContent(ref: "#/components/schemas/Company")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Company not found"),
        ]
    )]
    #[OA\Post(
        path: "/api/v1/companies",
        operationId: "createCompany",
        summary: "Create a new company",
        description: "Creates a new company",
        security: [
            [
                "companies_oauth2" => [
                    CompanyScopes::Write,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        tags: ["Companies"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CompanyCreateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/Company")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
    #[OA\Put(
        path: "/api/v1/companies/{id}",
        operationId: "updateCompany",
        summary: "Update a company",
        description: "Updates an existing company",
        security: [
            [
                "companies_oauth2" => [
                    CompanyScopes::Write,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        tags: ["Companies"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Company ID",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CompanyUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Success",
                content: new OA\JsonContent(ref: "#/components/schemas/Company")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Company not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
    #[OA\Delete(
        path: "/api/v1/companies/{id}",
        operationId: "deleteCompany",
        summary: "Delete a company",
        description: "Deletes a company",
        security: [
            [
                "companies_oauth2" => [
                    CompanyScopes::Write,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        tags: ["Companies"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Company ID",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Deleted successfully"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Company not found"),
        ]
    )]
    #[OA\Get(
        path: "/api/v1/companies",
        operationId: "getAllCompanies",
        summary: "Get all companies",
        description: "Returns a paginated list of companies. Allows ordering, filtering and pagination.",
        security: [
            [
                "companies_oauth2" => [
                    CompanyScopes::Read,
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        tags: ["Companies"],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'The page number'
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'The number of pages in each page',
            ),
            new OA\Parameter(
                name: "filter[]",
                in: "query",
                required: false,
                description: "Filter companies. Available filters: name (=@, ==, @@), member_level (=@, ==, @@), display_on_site (==)",
                schema: new OA\Schema(type: "array", items: new OA\Items(type: "string")),
                explode: true
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                description: "Order by field. Valid fields: id, name, member_level",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                description: "Expand related entities. Available expansions: sponsorships, project_sponsorships",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "relations",
                in: "query",
                required: false,
                description: "Load relations. Available: sponsorships, project_sponsorships",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Success",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedCompaniesResponse")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
        ]
    )]

    #[OA\Get(
        path: "/api/public/v1/companies",
        operationId: "getAllCompaniesPublic",
        summary: "Get all companies (Public)",
        description: "Returns a paginated list of companies. Allows ordering, filtering and pagination.",
        tags: ["Companies (Public)"],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'The page number'
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'The number of pages in each page',
            ),
            new OA\Parameter(
                name: "filter[]",
                in: "query",
                required: false,
                description: "Filter companies. Available filters: name (=@, ==, @@), member_level (=@, ==, @@), display_on_site (==)",
                schema: new OA\Schema(type: "array", items: new OA\Items(type: "string")),
                explode: true
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                description: "Order by field. Valid fields: id, name, member_level",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                description: "Expand related entities. Available expansions: sponsorships, project_sponsorships",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "relations",
                in: "query",
                required: false,
                description: "Load relations. Available: sponsorships, project_sponsorships",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Success",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedCompaniesResponse")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
        ]
    )]
    public function getAllCompanies()
    {

        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '==', '@@'],
                    'member_level' => ['=@', '==', '@@'],
                    'display_on_site' => ['=='],
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|string',
                    'member_level' => 'sometimes|string',
                    'display_on_site' => ['sometimes', new Boolean],
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

    protected function addEntitySerializerType()
    {
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

    protected function updateEntitySerializerType()
    {
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

    #[OA\Post(
        path: "/api/v1/companies/{id}/logo",
        operationId: "addCompanyLogo",
        summary: "Add company logo",
        description: "Uploads a logo image for the company",
        security: [
            [
                "companies_oauth2" => [
                    CompanyScopes::Write,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        tags: ["Companies"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Company ID",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["file"],
                    properties: [
                        new OA\Property(
                            property: "file",
                            type: "string",
                            format: "binary",
                            description: "Logo image file"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Logo uploaded successfully",
                content: new OA\JsonContent(type: "object")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Company not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "File parameter not set"),
        ]
    )]
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

    #[OA\Delete(
        path: "/api/v1/companies/{id}/logo",
        operationId: "deleteCompanyLogo",
        summary: "Delete company logo",
        description: "Removes the logo image from the company",
        security: [
            [
                "companies_oauth2" => [
                    CompanyScopes::Write,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        tags: ["Companies"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Company ID",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Logo deleted successfully"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Company not found"),
        ]
    )]
    public function deleteCompanyLogo($company_id)
    {
        return $this->processRequest(function () use ($company_id) {

            $this->service->deleteCompanyLogo(intval($company_id));

            return $this->deleted();

        });
    }

    #[OA\Post(
        path: "/api/v1/companies/{id}/logo/big",
        operationId: "addCompanyBigLogo",
        summary: "Add company big logo",
        description: "Uploads a big logo image for the company",
        security: [
            [
                "companies_oauth2" => [
                    CompanyScopes::Write,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        tags: ["Companies"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Company ID",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["file"],
                    properties: [
                        new OA\Property(
                            property: "file",
                            type: "string",
                            format: "binary",
                            description: "Big logo image file"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Big logo uploaded successfully",
                content: new OA\JsonContent(type: "object")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Company not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "File parameter not set"),
        ]
    )]
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

    #[OA\Delete(
        path: "/api/v1/companies/{id}/logo/big",
        operationId: "deleteCompanyBigLogo",
        summary: "Delete company big logo",
        description: "Removes the big logo image from the company",
        security: [
            [
                "companies_oauth2" => [
                    CompanyScopes::Write,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        tags: ["Companies"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Company ID",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Big logo deleted successfully"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Company not found"),
        ]
    )]
    public function deleteCompanyBigLogo($company_id)
    {
        return $this->processRequest(function () use ($company_id) {
            $this->service->deleteCompanyBigLogo(intval($company_id));
            return $this->deleted();
        });
    }
}