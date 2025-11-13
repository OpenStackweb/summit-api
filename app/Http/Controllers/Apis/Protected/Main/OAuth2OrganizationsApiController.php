<?php

namespace App\Http\Controllers;

/**
 * Copyright 2018 OpenStack Foundation
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

use App\Security\OrganizationScopes;
use App\Security\SummitScopes;
use App\Services\Model\IOrganizationService;
use Illuminate\Http\Response;
use models\main\IOrganizationRepository;
use models\oauth2\IResourceServerContext;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;


/**
 * Class OAuth2OrganizationsApiController
 * @package App\Http\Controllers
 */
final class OAuth2OrganizationsApiController extends OAuth2ProtectedController
{
    /**
     * @var IOrganizationService
     */
    private $service;

    use ParametrizedGetAll;
    use AddEntity;

    #[OA\Post(
        path: '/api/v1/organizations',
        summary: 'Creates a new organization',
        security: [['organizations_oauth2' => [
                OrganizationScopes::WriteOrganizationData
            ]
        ]],
        tags: ['organizations'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/OrganizationCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Organization created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/Organization')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    /**
     * OAuth2OrganizationsApiController constructor.
     * @param IOrganizationRepository $company_repository
     * @param IResourceServerContext $resource_server_context
     * @param IOrganizationService $service
     */
    public function __construct
    (
        IOrganizationRepository $company_repository,
        IResourceServerContext  $resource_server_context,
        IOrganizationService    $service
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $company_repository;
        $this->service = $service;
    }

    #[OA\Get(
        path: "/api/v1/organizations",
        description: "Get all organizations with filtering and pagination. Organizations represent companies, foundations, or entities in the system. Requires OAuth2 authentication with appropriate scope.",
        summary: 'Get all organizations',
        operationId: 'getAllOrganizations',
        tags: ['Organizations'],
        security: [['organizations_oauth2' => [
            OrganizationScopes::ReadOrganizationData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number for pagination',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Items per page',
                schema: new OA\Schema(type: 'integer', example: 10, maximum: 100)
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions. Format: field<op>value. Available field: name (=@, ==, @@). Operators: == (equals), =@ (starts with), @@ (contains)',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'name@@OpenStack')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s). Available fields: name, id. Use "-" prefix for descending order.',
                schema: new OA\Schema(type: 'string', example: 'name')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success - Returns paginated list of organizations',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedOrganizationsResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getAll()
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


    /**
     * @inheritDoc
     */
    function getAddValidationRules(array $payload): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function addEntity(array $payload): IEntity
    {
        return $this->service->addOrganization($payload);
    }
}
