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

use App\Security\SummitScopes;
use Illuminate\Http\Response;
use models\main\IGroupRepository;
use models\oauth2\IResourceServerContext;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;


#[OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'groups_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                    SummitScopes::ReadSummitData => 'Read Summit Data',
                    '%s/groups/read' => 'Read Groups Data',
                ],
            ),
        ],
    )
]
class RSVPAuthSchema{}

/**
 * Class OAuth2GroupsApiController
 * @package App\Http\Controllers
 */
final class OAuth2GroupsApiController extends OAuth2ProtectedController
{

    use ParametrizedGetAll;

    /**
     * OAuth2GroupsApiController constructor.
     * @param IGroupRepository $group_repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IGroupRepository       $group_repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $group_repository;
    }

    #[OA\Get(
        path: "/api/v1/groups",
        description: "Get all groups with filtering and pagination. Groups are used for access control and organization of members. Requires OAuth2 authentication with appropriate scope.",
        summary: 'Get all groups',
        operationId: 'getAllGroups',
        tags: ['Groups'],
        security: [['groups_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadSummitData,
            '%s/groups/read',
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
                description: 'Filter expressions. Format: field<op>value. Available fields: code (=@, ==, @@), title (=@, ==, @@). Operators: == (equals), =@ (starts with), @@ (contains)',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'code==administrators')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s). Available fields: code, title, id. Use "-" prefix for descending order.',
                schema: new OA\Schema(type: 'string', example: 'title')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to include. Available relations: members',
                schema: new OA\Schema(type: 'string', example: 'members')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success - Returns paginated list of groups',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedGroupsResponse')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request - Invalid parameters"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized - Invalid or missing access token"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden - Insufficient permissions"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getAll()
    {
        return $this->_getAll(
            function () {
                return [
                    'code' => ['=@', '==', '@@'],
                    'title' => ['=@', '==', '@@'],
                ];
            },
            function () {
                return [
                    'code' => 'sometimes|string',
                    'title' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'code',
                    'title',
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

}
