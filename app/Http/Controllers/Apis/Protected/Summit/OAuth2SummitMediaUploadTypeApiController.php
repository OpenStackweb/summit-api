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
use HTTP401UnauthorizedException;
use App\Http\Exceptions\HTTP403ForbiddenException;
use App\Models\Foundation\Summit\Repositories\ISummitMediaUploadTypeRepository;
use App\Services\Model\ISummitMediaUploadTypeService;
use Illuminate\Http\Response;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;

/**
 * Class OAuth2SummitMediaUploadTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitMediaUploadTypeApiController extends OAuth2ProtectedController
{
    use GetAllBySummit;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    use GetSummitChildElementById;

    use RequestProcessor;

    /**
     * @var ISummitMediaUploadTypeService
     */
    private $service;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    public function __construct
    (
        ISummitMediaUploadTypeRepository $repository,
        ISummitRepository $summit_repository,
        ISummitMediaUploadTypeService  $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->service = $service;
        $this->summit_repository = $summit_repository;
        $this->repository = $repository;
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/media-upload-types",
        summary: "Get all media upload types for a summit",
        description: "Returns a paginated list of media upload types configured for a specific summit. Allows ordering, filtering and pagination.",
        security: [["oauth2_security_scope" => ["openid", "profile", "email"]]],
        tags: ["Summit Media Upload Types"],
        parameters: [
            new OA\Parameter(
                name: 'summit_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit ID'
            ),
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
                description: 'The number of items per page',
            ),
            new OA\Parameter(
                name: "filter[]",
                in: "query",
                required: false,
                description: "Filter media upload types. Available filters: name (=@, ==)",
                schema: new OA\Schema(type: "array", items: new OA\Items(type: "string")),
                explode: true
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                description: "Order by field. Valid fields: id, name",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                description: "Expand related entities. Available expansions: type, summit, presentation_types",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "relations",
                in: "query",
                required: false,
                description: "Load relations. Available: presentation_types",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Success",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedSummitMediaUploadTypesResponse")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
        ]
    )]
    public function getAllBySummit($summit_id)
    {
        $this->summit_id = $summit_id;
        $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($this->summit_id);
        if (is_null($summit)) return $this->error404();
        return $this->getAll();
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/media-upload-types/{media_upload_type_id}",
        summary: "Get a specific media upload type",
        description: "Returns detailed information about a specific media upload type",
        security: [["oauth2_security_scope" => ["openid", "profile", "email"]]],
        tags: ["Summit Media Upload Types"],
        parameters: [
            new OA\Parameter(
                name: 'summit_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit ID'
            ),
            new OA\Parameter(
                name: "media_upload_type_id",
                in: "path",
                required: true,
                description: "Media upload type ID",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                description: "Expand related entities. Available expansions: type, summit, presentation_types",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "relations",
                in: "query",
                required: false,
                description: "Load relations. Available: presentation_types",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Success",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitMediaUploadType")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
        ]
    )]
    public function get($summit_id, $media_upload_type_id)
    {
        return $this->getById($summit_id, $media_upload_type_id);
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/media-upload-types",
        summary: "Create a new media upload type",
        description: "Creates a new media upload type for the specified summit",
        security: [["oauth2_security_scope" => ["openid", "profile", "email"]]],
        tags: ["Summit Media Upload Types"],
        parameters: [
            new OA\Parameter(
                name: 'summit_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit ID'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SummitMediaUploadTypeCreateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitMediaUploadType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
    public function add($summit_id)
    {
        return $this->addChild($summit_id);
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/media-upload-types/{media_upload_type_id}",
        summary: "Update a media upload type",
        description: "Updates an existing media upload type",
        security: [["oauth2_security_scope" => ["openid", "profile", "email"]]],
        tags: ["Summit Media Upload Types"],
        parameters: [
            new OA\Parameter(
                name: 'summit_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit ID'
            ),
            new OA\Parameter(
                name: "media_upload_type_id",
                in: "path",
                required: true,
                description: "Media upload type ID",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SummitMediaUploadTypeUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Success",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitMediaUploadType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
        ]
    )]
    public function update($summit_id, $media_upload_type_id)
    {
        return $this->updateChild($summit_id, $media_upload_type_id);
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/media-upload-types/{media_upload_type_id}",
        summary: "Delete a media upload type",
        description: "Deletes a media upload type from the summit",
        security: [["oauth2_security_scope" => ["openid", "profile", "email"]]],
        tags: ["Summit Media Upload Types"],
        parameters: [
            new OA\Parameter(
                name: 'summit_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit ID'
            ),
            new OA\Parameter(
                name: "media_upload_type_id",
                in: "path",
                required: true,
                description: "Media upload type ID",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Deleted successfully"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
        ]
    )]
    public function delete($summit_id, $media_upload_type_id)
    {
        return $this->deleteChild($summit_id, $media_upload_type_id);
    }

    /**
     * @return array
     */
    protected function getFilterRules():array
    {
        return [
            'name' => ['=@', '=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'name' => 'sometimes|required|string',
        ];
    }
    /**
     * @return array
     */
    protected function getOrderRules():array{
        return [
            'id',
            'name',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        // authz
        // check that we have a current member ( not service account )
        $current_member = $this->getResourceServerContext()->getCurrentUser();
        if(is_null($current_member))
            throw new HTTP401UnauthorizedException();
        // check summit access
        if(!$current_member->isSummitAllowed($summit))
            throw new HTTP403ForbiddenException();
        return $this->service->add($summit, $payload);
    }

    /**
     * @inheritDoc
     */
    function getAddValidationRules(array $payload): array
    {
        return SummitMediaUploadTypeValidationRulesFactory::buildForAdd($payload);
    }

    /**
     * @inheritDoc
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @inheritDoc
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        // authz
        // check that we have a current member ( not service account )
        $current_member = $this->getResourceServerContext()->getCurrentUser();
        if(is_null($current_member))
            throw new HTTP401UnauthorizedException();
        // check summit access
        if(!$current_member->isSummitAllowed($summit))
            throw new HTTP403ForbiddenException();

        $this->service->delete($summit, $child_id);
    }

    /**
     * @inheritDoc
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        // authz
        // check that we have a current member ( not service account )
        $current_member = $this->getResourceServerContext()->getCurrentUser();
        if(is_null($current_member))
            throw new HTTP401UnauthorizedException();
        // check summit access
        if(!$current_member->isSummitAllowed($summit))
            throw new HTTP403ForbiddenException();

       return $summit->getMediaUploadTypeById($child_id);
    }

    /**
     * @inheritDoc
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SummitMediaUploadTypeValidationRulesFactory::buildForUpdate($payload);
    }

    /**
     * @inheritDoc
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        // authz
        // check that we have a current member ( not service account )
        $current_member = $this->getResourceServerContext()->getCurrentUser();
        if(is_null($current_member))
            throw new HTTP401UnauthorizedException();
        // check summit access
        if(!$current_member->isSummitAllowed($summit))
            throw new HTTP403ForbiddenException();

        return $this->service->update($summit, $child_id, $payload);
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/media-upload-types/{media_upload_type_id}/presentation-types/{event_type_id}",
        summary: "Add media upload type to presentation type",
        description: "Associates a media upload type with a specific presentation type",
        security: [["oauth2_security_scope" => ["openid", "profile", "email"]]],
        tags: ["Summit Media Upload Types"],
        parameters: [
            new OA\Parameter(
                name: 'summit_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit ID'
            ),
            new OA\Parameter(
                name: "media_upload_type_id",
                in: "path",
                required: true,
                description: "Media upload type ID",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "event_type_id",
                in: "path",
                required: true,
                description: "Presentation type ID",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Success - Returns the updated presentation type",
                content: new OA\JsonContent(type: "object")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
        ]
    )]
    public function addToPresentationType($summit_id, $media_upload_type_id, $presentation_type_id){
       return $this->processRequest(function() use($summit_id, $media_upload_type_id, $presentation_type_id){
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

           // authz
           // check that we have a current member ( not service account )
           $current_member = $this->getResourceServerContext()->getCurrentUser();
           if(is_null($current_member))
               throw new HTTP401UnauthorizedException();
           // check summit access
           if(!$current_member->isSummitAllowed($summit))
               throw new HTTP403ForbiddenException();

            $presentation_type = $this->service->addToPresentationType($summit, intval($media_upload_type_id), intval($presentation_type_id));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $presentation_type
            )->serialize());
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/media-upload-types/{media_upload_type_id}/presentation-types/{event_type_id}",
        summary: "Remove media upload type from presentation type",
        description: "Removes the association between a media upload type and a presentation type",
        security: [["oauth2_security_scope" => ["openid", "profile", "email"]]],
        tags: ["Summit Media Upload Types"],
        parameters: [
            new OA\Parameter(
                name: 'summit_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit ID'
            ),
            new OA\Parameter(
                name: "media_upload_type_id",
                in: "path",
                required: true,
                description: "Media upload type ID",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "event_type_id",
                in: "path",
                required: true,
                description: "Presentation type ID",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Success - Returns the updated presentation type",
                content: new OA\JsonContent(type: "object")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
        ]
    )]
    public function deleteFromPresentationType($summit_id, $media_upload_type_id, $presentation_type_id){
        return $this->processRequest(function() use($summit_id, $media_upload_type_id, $presentation_type_id){
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            // authz
            // check that we have a current member ( not service account )
            $current_member = $this->getResourceServerContext()->getCurrentUser();
            if(is_null($current_member))
                throw new HTTP401UnauthorizedException();
            // check summit access
            if(!$current_member->isSummitAllowed($summit))
                throw new HTTP403ForbiddenException();

            $presentation_type = $this->service->deleteFromPresentationType($summit, intval($media_upload_type_id), intval($presentation_type_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $presentation_type
            )->serialize());
        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/media-upload-types/all/clone/{to_summit_id}",
        summary: "Clone media upload types to another summit",
        description: "Clones all media upload types from one summit to another summit",
        security: [["oauth2_security_scope" => ["openid", "profile", "email"]]],
        tags: ["Summit Media Upload Types"],
        parameters: [
            new OA\Parameter(
                name: 'summit_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit ID'
            ),
            new OA\Parameter(
                name: "to_summit_id",
                in: "path",
                required: true,
                description: "Target summit ID to clone media upload types to",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Success - Returns the target summit with cloned media upload types",
                content: new OA\JsonContent(type: "object")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Source or target summit not found"),
        ]
    )]
    public function cloneMediaUploadTypes($summit_id, $to_summit_id){
        return $this->processRequest(function() use($summit_id, $to_summit_id){
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $to_summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($to_summit_id);
            if (is_null($to_summit)) return $this->error404();

            // authz
            // check that we have a current member ( not service account )
            $current_member = $this->getResourceServerContext()->getCurrentUser();
            if(is_null($current_member))
                throw new HTTP401UnauthorizedException();
            // check summit access
            if(!$current_member->isSummitAllowed($summit))
                throw new HTTP403ForbiddenException();

            // check summit access
            if(!$current_member->isSummitAllowed($to_summit))
                throw new HTTP403ForbiddenException();

            $to_summit = $this->service->cloneMediaUploadTypes($summit, $to_summit);

            return $this->created(
                SerializerRegistry::getInstance()->getSerializer
                (
                    $to_summit
                )->serialize()
            );
        });
    }

}
