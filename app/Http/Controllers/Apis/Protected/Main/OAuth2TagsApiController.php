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

use App\ModelSerializers\SerializerUtils;
use models\main\ITagRepository;
use models\oauth2\IResourceServerContext;
use Illuminate\Support\Facades\Validator;
use ModelSerializers\SerializerRegistry;
use Illuminate\Support\Facades\Request;
use App\Services\Model\ITagService;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
/**
 * Class OAuth2TagsApiController
 * @package App\Http\Controllers
 */
final class OAuth2TagsApiController extends OAuth2ProtectedController
{
    use ParametrizedGetAll;

    use GetAndValidateJsonPayload;

    use RequestProcessor;

    /**
     * @var ITagService
     */
    private $tag_service;

    /**
     * OAuth2TagsApiController constructor.
     * @param ITagService $tag_service
     * @param ITagRepository $tag_repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ITagService $tag_service,
        ITagRepository $tag_repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $tag_repository;
        $this->tag_service = $tag_service;
    }

    #[OA\Get(
        path: "/api/v1/tags",
        summary: "Get all tags",
        description: "Returns a paginated list of tags. Allows ordering, filtering and pagination.",
        security: [["oauth2_security_scope" => ["openid", "profile", "email"]]],
        tags: ["Tags"],
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
                description: "Filter tags. Available filters: tag (=@, ==, @@)",
                schema: new OA\Schema(type: "array", items: new OA\Items(type: "string")),
                explode: true
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                description: "Order by field. Valid fields: id, tag",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Success",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedTagsResponse")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
        ]
    )]
    public function getAll(){
        return $this->_getAll(
            function () {
                return [
                    'tag' => ['=@', '==', '@@'],
                ];
            },
            function () {
                return [
                    'tag' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'tag',
                    'id',
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                $current_member = $this->resource_server_context->getCurrentUser();
                $serializer_type = SerializerRegistry::SerializerType_Public;

                if (!is_null($current_member) && ($current_member->isAdmin() || $current_member->isSummitAdmin())) {
                    $serializer_type = SerializerRegistry::SerializerType_Admin;
                }

                return $serializer_type;
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/tags/{id}",
        summary: "Get a specific tag",
        description: "Returns detailed information about a specific tag",
        tags: ["Tags"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Tag ID",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                description: "Expand related entities",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "fields",
                in: "query",
                required: false,
                description: "Fields to include in response",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "relations",
                in: "query",
                required: false,
                description: "Relations to include",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Success",
                content: new OA\JsonContent(ref: "#/components/schemas/Tag")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Tag not found"),
        ]
    )]
    public function getTag($tag_id){
        return $this->processRequest(function () use ($tag_id) {
            $tag = $this->repository->getById(intval($tag_id));

            if(is_null($tag)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()
                ->getSerializer($tag)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    #[OA\Post(
        path: "/api/v1/tags",
        summary: "Create a new tag",
        description: "Creates a new tag",
        security: [["oauth2_security_scope" => ["openid", "profile", "email"]]],
        tags: ["Tags"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/TagRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/Tag")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
    public function addTag(){
        return $this->processRequest(function () {
            if(!Request::isJson()) return $this->error400();
            $data = Request::json();

            $rules = [
                'tag' => 'required|string|max:100',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $tag = $this->tag_service->addTag($data->all());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($tag)->serialize
            (
                Request::input('expand','')
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/tags/{id}",
        summary: "Update a tag",
        description: "Updates an existing tag",
        security: [["oauth2_security_scope" => ["openid", "profile", "email"]]],
        tags: ["Tags"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Tag ID",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/TagRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Success",
                content: new OA\JsonContent(ref: "#/components/schemas/Tag")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Tag not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
    public function updateTag($tag_id){
        return $this->processRequest(function () use ($tag_id) {
            if(!Request::isJson()) return $this->error400();
            $data = Request::json();

            $rules = [
                'tag' => 'required|string|max:100',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $tag = $this->tag_service->updateTag(intval($tag_id), $data->all());

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($tag)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    #[OA\Delete(
        path: "/api/v1/tags/{id}",
        summary: "Delete a tag",
        description: "Deletes a tag",
        security: [["oauth2_security_scope" => ["openid", "profile", "email"]]],
        tags: ["Tags"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Tag ID",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Deleted successfully"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Tag not found"),
        ]
    )]
    public function deleteTag($tag_id){
        return $this->processRequest(function () use ($tag_id) {

            $this->tag_service->deleteTag(intval($tag_id));

            return $this->deleted();
        });
    }
}