<?php
namespace App\Http\Controllers;
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

use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Main\Repositories\IProjectSponsorshipTypeRepository;
use App\Models\Foundation\Main\Repositories\ISponsoredProjectRepository;
use App\Models\Foundation\Main\Repositories\ISupportingCompanyRepository;
use App\Security\SponsoredProjectScope;
use App\Services\Model\ISponsoredProjectService;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use libs\utils\HTMLCleaner;
use models\exceptions\EntityNotFoundException;
use models\oauth2\IResourceServerContext;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;
use OpenApi\Attributes as OA;

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

    use RequestProcessor;

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
    ) {
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
        return SponsoredProjectValidationRulesFactory::buildForAdd($payload);
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
        return SponsoredProjectValidationRulesFactory::buildForUpdate($payload);
    }

    /**
     * @inheritDoc
     */
    protected function updateEntity($id, array $payload): IEntity
    {
        return $this->service->update(intval($id), HTMLCleaner::cleanData($payload, ['description']));
    }

    #[OA\Get(
        path: "/api/public/v1/sponsored-projects",
        description: "Get all sponsored projects (public endpoint)",
        summary: 'Read All Sponsored Projects (Public)',
        operationId: 'getAllSponsoredProjectsPublic',
        tags: ['Sponsored Projects (Public)'],
        parameters: [
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions in the format field<op>value, operators: =@ (starts with), == (equals), @@ (contains), fields: name, slug, is_active',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'name@@project')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'name,id')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List of sponsored projects',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSponsoredProjectsResponse')
            ),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    #[OA\Get(
        path: "/api/v1/sponsored-projects",
        description: "Get all sponsored projects",
        summary: 'Read All Sponsored Projects',
        operationId: 'getAllSponsoredProjects',
        tags: ['Sponsored Projects'],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Read,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions in the format field<op>value, operators: =@ (starts with), == (equals), @@ (contains), fields: name, slug, is_active',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'name@@project')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'name,id')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List of sponsored projects',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSponsoredProjectsResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @return mixed
     */
    public function getAll()
    {
        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '==', '@@'],
                    'slug' => ['=@', '==', '@@'],
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

    #[OA\Post(
        path: "/api/v1/sponsored-projects",
        description: "Add a new sponsored project",
        summary: 'Add Sponsored Project',
        operationId: 'addSponsoredProject',
        tags: ['Sponsored Projects'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Write,
                ]
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SponsoredProjectRequest')
        ),
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Sponsored project created',
                content: new OA\JsonContent(ref: '#/components/schemas/SponsoredProject')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]

    #[OA\Get(
        path: "/api/public/v1/sponsored-projects/{id}",
        description: "Get a specific sponsored project (public endpoint)",
        summary: 'Read Sponsored Project (Public)',
        operationId: 'getSponsoredProjectPublic',
        tags: ['Sponsored Projects (Public)'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The sponsored project id or slug'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Sponsored project details',
                content: new OA\JsonContent(ref: '#/components/schemas/SponsoredProject')
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    #[OA\Get(
        path: "/api/v1/sponsored-projects/{id}",
        description: "Get a specific sponsored project",
        summary: 'Read Sponsored Project',
        operationId: 'getSponsoredProject',
        tags: ['Sponsored Projects'],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Read,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsored project id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Sponsored project details',
                content: new OA\JsonContent(ref: '#/components/schemas/SponsoredProject')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    #[OA\Put(
        path: "/api/v1/sponsored-projects/{id}",
        description: "Update a sponsored project",
        summary: 'Update Sponsored Project',
        operationId: 'updateSponsoredProject',
        tags: ['Sponsored Projects'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Write,
                ]
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SponsoredProjectRequest')
        ),
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsored project id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Sponsored project updated',
                content: new OA\JsonContent(ref: '#/components/schemas/SponsoredProject')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]

    #[OA\Delete(
        path: "/api/v1/sponsored-projects/{id}",
        description: "Delete a sponsored project",
        summary: 'Delete Sponsored Project',
        operationId: 'deleteSponsoredProject',
        tags: ['Sponsored Projects'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Write,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsored project id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Sponsored project deleted',
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    // sponsorship types

    #[OA\Get(
        path: "/api/public/v1/sponsored-projects/{id}/sponsorship-types",
        description: "Get all sponsorship types for a sponsored project (public endpoint)",
        summary: 'Read All Sponsorship Types (Public)',
        operationId: 'getAllSponsorshipTypesPublic',
        tags: ['Sponsored Projects (Public)', 'Sponsorship Types (Public)'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The sponsored project id or slug'
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions in the format field<op>value, operators: =@ (starts with), == (equals), @@ (contains), fields: name, slug, is_active',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'name@@type')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'order,name')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List of sponsorship types',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedProjectSponsorshipTypesResponse')
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    #[OA\Get(
        path: "/api/v1/sponsored-projects/{id}/sponsorship-types",
        description: "Get all sponsorship types for a sponsored project",
        summary: 'Read All Sponsorship Types',
        operationId: 'getAllSponsorshipTypes',
        tags: ['Sponsored Projects', 'Sponsorship Types'],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Read,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The sponsored project id or slug'
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions in the format field<op>value, operators: =@ (starts with), == (equals), @@ (contains), fields: name, slug, is_active',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'name@@type')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'order,name')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List of sponsorship types',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedProjectSponsorshipTypesResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $id string|int
     */
    public function getAllSponsorshipTypes($id)
    {
        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '==', '@@'],
                    'slug' => ['=@', '==', '@@'],
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
            function ($filter) use ($id) {
                if ($filter instanceof Filter) {
                    if (is_numeric($id))
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

    #[OA\Get(
        path: "/api/public/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}",
        description: "Get a specific sponsorship type (public endpoint)",
        summary: 'Read Sponsorship Type (Public)',
        operationId: 'getSponsorshipTypePublic',
        tags: ['Sponsored Projects (Public)', 'Sponsorship Types (Public)'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The sponsored project id or slug'
            ),
            new OA\Parameter(
                name: 'sponsorship_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsorship type id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Sponsorship type details',
                content: new OA\JsonContent(ref: '#/components/schemas/ProjectSponsorshipType')
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    #[OA\Get(
        path: "/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}",
        description: "Get a specific sponsorship type",
        summary: 'Read Sponsorship Type',
        operationId: 'getSponsorshipTypeForSponsoredProject',
        tags: ['Sponsored Projects', 'Sponsorship Types'],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Read,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The sponsored project id or slug'
            ),
            new OA\Parameter(
                name: 'sponsorship_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsorship type id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Sponsorship type details',
                content: new OA\JsonContent(ref: '#/components/schemas/ProjectSponsorshipType')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $id
     * @param $sponsorship_type_id
     */
    public function getSponsorshipType($id, $sponsorship_type_id)
    {
        Log::debug(sprintf("OAuth2SponsoredProjectApiController::getSponsorshipType id %s sponsorship_type_id %s", $id, $sponsorship_type_id));
        return $this->_get($sponsorship_type_id, function ($id) {
            return $this->project_sponsorship_type_repository->getById(intval($id));
        });
    }

    #[OA\Post(
        path: "/api/v1/sponsored-projects/{id}/sponsorship-types",
        description: "Add a new sponsorship type to a sponsored project",
        summary: 'Add Sponsorship Type',
        operationId: 'addSponsorshipTypeForSponsoredProject',
        tags: ['Sponsored Projects', 'Sponsorship Types'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Write,
                ]
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ProjectSponsorshipTypeCreateRequest')
        ),
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsored project id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Sponsorship type created',
                content: new OA\JsonContent(ref: '#/components/schemas/ProjectSponsorshipType')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
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
            function ($payload, $id) {
                return $this->service->addProjectSponsorshipType($id, HTMLCleaner::cleanData($payload, ['description']));
            },
            ...$args
        );
    }

    #[OA\Put(
        path: "/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}",
        description: "Update a sponsorship type",
        summary: 'Update Sponsorship Type',
        operationId: 'updateSponsorshipTypeForSponsoredProject',
        tags: ['Sponsored Projects', 'Sponsorship Types'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Write,
                ]
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ProjectSponsorshipTypeUpdateRequest')
        ),
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsored project id'
            ),
            new OA\Parameter(
                name: 'sponsorship_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsorship type id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Sponsorship type updated',
                content: new OA\JsonContent(ref: '#/components/schemas/ProjectSponsorshipType')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
    /**
     * @param $id
     * @param $sponsorship_type_id
     * @return mixed
     */
    public function updateSponsorshipType($id, $sponsorship_type_id)
    {
        $args = [intval($id)];
        return $this->_update(
            $sponsorship_type_id,
            function ($payload) {
                return ProjectSponsorshipTypeValidationRulesFactory::build($payload, true);
            },
            function ($sponsorship_type_id, $payload, $project_id) {
                return $this->service->updateProjectSponsorshipType($project_id, $sponsorship_type_id, HTMLCleaner::cleanData($payload, ['description']));
            },
            ...$args
        );
    }

    #[OA\Delete(
        path: "/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}",
        description: "Delete a sponsorship type",
        summary: 'Delete Sponsorship Type',
        operationId: 'deleteSponsorshipTypeForSponsoredProject',
        tags: ['Sponsored Projects', 'Sponsorship Types'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Write,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsored project id'
            ),
            new OA\Parameter(
                name: 'sponsorship_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsorship type id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Sponsorship type deleted',
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $id
     * @param $sponsorship_type_id
     * @return mixed
     */
    public function deleteSponsorshipType($id, $sponsorship_type_id)
    {
        $args = [intval($id)];

        return $this->_delete(
            $sponsorship_type_id,
            function ($sponsorship_type_id, $project_id) {
                $this->service->deleteProjectSponsorshipType($project_id, $sponsorship_type_id);
            },
            ...$args
        );
    }

    //  supporting companies

    #[OA\Get(
        path: "/api/public/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}/supporting-companies",
        description: "Get all supporting companies for a sponsorship type (public endpoint)",
        summary: 'Read All Supporting Companies (Public)',
        operationId: 'getSupportingCompaniesPublic',
        tags: ['Sponsored Projects (Public)', 'Supporting Companies (Public)'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The sponsored project id or slug'
            ),
            new OA\Parameter(
                name: 'sponsorship_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The sponsorship type id or slug'
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions in the format field<op>value, operators: =@ (starts with), == (equals), @@ (contains), fields: name, slug, is_active',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'order,name')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List of supporting companies',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSupportingCompaniesResponse')
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    #[OA\Get(
        path: "/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}/supporting-companies",
        description: "Get all supporting companies for a sponsorship type",
        summary: 'Read All Supporting Companies',
        operationId: 'getSupportingCompanies',
        tags: ['Sponsored Projects', 'Supporting Companies'],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Read,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The sponsored project id or slug'
            ),
            new OA\Parameter(
                name: 'sponsorship_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The sponsorship type id or slug'
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions in the format field<op>value, operators: =@ (starts with), == (equals), @@ (contains), fields: name, slug, is_active',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'order,name')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List of supporting companies',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSupportingCompaniesResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getSupportingCompanies($id, $sponsorship_type_id)
    {
        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '==', '@@'],
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
            function ($filter) use ($id, $sponsorship_type_id) {
                if ($filter instanceof Filter) {
                    if (is_numeric($id))
                        $filter->addFilterCondition(FilterElement::makeEqual('sponsored_project_id', intval($id)));
                    else
                        $filter->addFilterCondition(FilterElement::makeEqual('sponsored_project_slug', $id));

                    if (is_numeric($sponsorship_type_id))
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


    #[OA\Post(
        path: "/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}/supporting-companies",
        description: "Add a supporting company to a sponsorship type",
        summary: 'Add Supporting Company',
        operationId: 'addSupportingCompany',
        tags: ['Sponsored Projects', 'Supporting Companies'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Write,
                ]
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AddSupportingCompanyRequest')
        ),
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsored project id'
            ),
            new OA\Parameter(
                name: 'sponsorship_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsorship type id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Supporting company added',
                content: new OA\JsonContent(ref: '#/components/schemas/SupportingCompany')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
    /**
     * @param $id
     * @param $sponsorship_type_id
     * @return mixed
     */
    public function addSupportingCompanies($id, $sponsorship_type_id)
    {
        return $this->_add(
            function ($payload) {
                return [
                    'company_id' => 'required|integer',
                    'order' => 'sometimes|integer|min:1',
                ];
            },
            function ($payload, $project_id, $sponsorship_type_id) {
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

    #[OA\Put(
        path: "/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}/supporting-companies/{company_id}",
        description: "Update a supporting company",
        summary: 'Update Supporting Company',
        operationId: 'updateSupportingCompany',
        tags: ['Sponsored Projects', 'Supporting Companies'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Write,
                ]
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateSupportingCompanyRequest')
        ),
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsored project id'
            ),
            new OA\Parameter(
                name: 'sponsorship_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsorship type id'
            ),
            new OA\Parameter(
                name: 'company_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The supporting company id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Supporting company updated',
                content: new OA\JsonContent(ref: '#/components/schemas/SupportingCompany')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
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

    #[OA\Delete(
        path: "/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}/supporting-companies/{company_id}",
        description: "Delete a supporting company from a sponsorship type",
        summary: 'Delete Supporting Company',
        operationId: 'deleteSupportingCompany',
        tags: ['Sponsored Projects', 'Supporting Companies'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Write,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsored project id'
            ),
            new OA\Parameter(
                name: 'sponsorship_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsorship type id'
            ),
            new OA\Parameter(
                name: 'company_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The supporting company id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Supporting company deleted',
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
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

    #[OA\Get(
        path: "/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}/supporting-companies/{company_id}",
        description: "Get a specific supporting company",
        summary: 'Read Supporting Company',
        operationId: 'getSupportingCompany',
        tags: ['Sponsored Projects', 'Supporting Companies'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Read,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsored project id'
            ),
            new OA\Parameter(
                name: 'sponsorship_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsorship type id'
            ),
            new OA\Parameter(
                name: 'company_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The supporting company id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Supporting company details',
                content: new OA\JsonContent(ref: '#/components/schemas/SupportingCompany')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $id
     * @param $sponsorship_type_id
     * @param $company_id
     * @return mixed
     */
    public function getSupportingCompany($id, $sponsorship_type_id, $company_id)
    {
        return $this->_get($sponsorship_type_id, function ($id, $company_id) {
            $sponsorship_type = $this->project_sponsorship_type_repository->getById(intval($id));
            if (is_null($sponsorship_type))
                throw new EntityNotFoundException();
            return $sponsorship_type->getSupportingCompanyById(intval($company_id));
        }, $company_id);
    }

    #[OA\Post(
        path: "/api/v1/sponsored-projects/{id}/logo",
        description: "Upload a logo for a sponsored project",
        summary: 'Add Sponsored Project Logo',
        operationId: 'addSponsoredProjectLogo',
        tags: ['Sponsored Projects'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Write,
                ]
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/UploadSponsoredProjectLogoRequest')
            )
        ),
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsored project id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Logo uploaded successfully',
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
    /**
     * @param LaravelRequest $request
     * @param $project_id
     * @return mixed
     */
    public function addSponsoredProjectLogo(LaravelRequest $request, $project_id)
    {
        return $this->processRequest(function () use ($request, $project_id) {
            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412('file param not set!');
            }
            $logo = $this->service->addLogo(intval($project_id), $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($logo)->serialize());
        });
    }

    #[OA\Delete(
        path: "/api/v1/sponsored-projects/{id}/logo",
        description: "Delete the logo of a sponsored project",
        summary: 'Delete Sponsored Project Logo',
        operationId: 'deleteSponsoredProjectLogo',
        tags: ['Sponsored Projects'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Write,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsored project id'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Logo deleted successfully',
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $project_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteSponsoredProjectLogo($project_id)
    {
        return $this->processRequest(function () use ($project_id) {
            $this->service->deleteLogo(intval($project_id));
            return $this->deleted();
        });
    }

    //  subprojects

    #[OA\Get(
        path: "/api/v1/sponsored-projects/{id}/subprojects",
        description: "Get all subprojects of a sponsored project",
        summary: 'Read Subprojects',
        operationId: 'getSubprojects',
        tags: ['Sponsored Projects'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        security: [
            [
                'sponsored_projects_oauth2' => [
                    SponsoredProjectScope::Read,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The sponsored project id'
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions in the format field<op>value, operators: =@ (starts with), == (equals), @@ (contains), fields: name, slug, is_active',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'name@@subproject')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'name,id')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List of subprojects',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSponsoredProjectsResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @param $id string|int
     */
    public function getSubprojects($id)
    {
        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '==', '@@'],
                    'slug' => ['=@', '==', '@@'],
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
                    'id'
                ];
            },
            function ($filter) use ($id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('parent_project_id', intval($id)));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }
}
