<?php

namespace App\Http\Controllers;

/**
 * Copyright 2024 OpenStack Foundation
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

use App\Models\Foundation\Main\Repositories\IUserStoryRepository;
use Illuminate\Http\Response;
use models\oauth2\IResourceServerContext;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;

/**
 * Class OAuth2UserStoriesApiController
 * @package App\Http\Controllers
 */
final class OAuth2UserStoriesApiController extends OAuth2ProtectedController
{

    use ParametrizedGetAll;

    /**
     * OAuth2UserStoriesApiController constructor.
     * @param IUserStoryRepository $repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IUserStoryRepository   $repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
    }

    // OpenAPI Documentation

    #[OA\Get(
        path: '/api/public/v1/user-stories',
        summary: 'Get all user stories',
        description: 'Retrieves a paginated list of user stories showcasing real-world use cases and success stories from the OpenStack community. User stories highlight how organizations use OpenStack in production. This is a public endpoint that can return different data based on authentication (more details for authenticated users).',
        tags: ['User Stories'],
        parameters: [
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
                    items: new OA\Items(type: 'string', example: 'name@@cloud')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s). Available fields: name, id. Use "-" prefix for descending order.',
                schema: new OA\Schema(type: 'string', example: 'name')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Expand relationships. Available: organization, industry, location, image, tags',
                schema: new OA\Schema(type: 'string', example: 'organization,tags')
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                description: 'Relations to load. Available: tags',
                schema: new OA\Schema(type: 'string', example: 'tags')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User stories retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedUserStoriesResponse')
            ),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    /**
     * @return mixed
     */
    public function getAllUserStories()
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
              return $this->getEntitySerializerType();
            }
        );
    }

    protected function getEntitySerializerType(): string
    {
        $currentUser = $this->resource_server_context->getCurrentUser();
        return !is_null($currentUser) ? SerializerRegistry::SerializerType_Private :
            SerializerRegistry::SerializerType_Public;
    }
}
