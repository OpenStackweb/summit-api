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
use App\Models\Foundation\Summit\Repositories\ISummitMediaFileTypeRepository;
use App\Security\SummitScopes;
use App\Services\Model\ISummitMediaFileTypeService;
use models\oauth2\IResourceServerContext;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
/**
 * Class OAuth2SummitMediaFileTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitMediaFileTypeApiController extends OAuth2ProtectedController
{
    use AddEntity;

    use UpdateEntity;

    use DeleteEntity;

    use GetEntity;

    use ParametrizedGetAll;

    /**
     * @var ISummitMediaFileTypeService
     */
    private $service;

    /**
     * OAuth2SummitMediaFileTypeApiController constructor.
     * @param ISummitMediaFileTypeService $service
     * @param ISummitMediaFileTypeRepository $repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitMediaFileTypeService $service,
        ISummitMediaFileTypeRepository $repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->service = $service;
        $this->repository = $repository;
    }

    // OpenAPI Documentation

    #[OA\Get(
        path: '/api/v1/summit-media-file-types',
        summary: 'Get all summit media file types',
        description: 'Retrieves a paginated list of summit media file types. Media file types define categories of files that can be uploaded to summits (e.g., presentations, videos, documents) along with their allowed file extensions.',
        security: [['Bearer' => [SummitScopes::ReadAllSummitData]]],
        tags: ['Summit Media File Types'],
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
                description: 'Filter expressions. Format: field<op>value. Available field: name (=@, ==). Operators: == (equals), =@ (starts with)',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'name@@presentation')
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
                description: 'Media file types retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitMediaFileTypesResponse')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    #[OA\Get(
        path: '/api/v1/summit-media-file-types/{id}',
        summary: 'Get a summit media file type by ID',
        description: 'Retrieves detailed information about a specific summit media file type.',
        security: [['oauth2_security_scope' => [SummitScopes::ReadAllSummitData]]],
        tags: ['Summit Media File Types'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit Media File Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Media file type retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitMediaFileType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    #[OA\Post(
        path: '/api/v1/summit-media-file-types',
        summary: 'Create a new summit media file type',
        description: 'Creates a new summit media file type with specified name, description, and allowed file extensions.',
        security: [['oauth2_security_scope' => [SummitScopes::WriteSummitData]]],
        tags: ['Summit Media File Types'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SummitMediaFileTypeCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Media file type created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitMediaFileType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    #[OA\Put(
        path: '/api/v1/summit-media-file-types/{id}',
        summary: 'Update a summit media file type',
        description: 'Updates an existing summit media file type.',
        security: [['oauth2_security_scope' => [SummitScopes::WriteSummitData]]],
        tags: ['Summit Media File Types'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit Media File Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SummitMediaFileTypeUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Media file type updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitMediaFileType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    #[OA\Delete(
        path: '/api/v1/summit-media-file-types/{id}',
        summary: 'Delete a summit media file type',
        description: 'Deletes an existing summit media file type. System-defined types cannot be deleted.',
        security: [['oauth2_security_scope' => [SummitScopes::WriteSummitData]]],
        tags: ['Summit Media File Types'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit Media File Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Media file type deleted successfully'
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    /**
     * @inheritDoc
     */
    function getAddValidationRules(array $payload): array
    {
        return [
            'name'  => 'required|string|max:255',
            'description'  => 'sometimes|string|max:255',
            'allowed_extensions'=> 'required|string_array',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function addEntity(array $payload): IEntity
    {
        return $this->service->add($payload);
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
        return [
            'name'  => 'sometimes|string|max:255',
            'description'  => 'sometimes|string|max:255',
            'allowed_extensions'=> 'required|string_array',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function updateEntity($id, array $payload): IEntity
    {
       return $this->service->update($id, $payload);
    }

    public function getAll(){
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
}
