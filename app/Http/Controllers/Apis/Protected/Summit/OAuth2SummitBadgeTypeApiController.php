<?php

namespace App\Http\Controllers;

/**
 * Copyright 2019 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISummitBadgeTypeRepository;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\ISummitBadgeTypeService;
use App\Models\Foundation\Main\IGroup;
use App\Security\SummitScopes;
use Illuminate\Http\Response;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IBaseRepository;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;

/**
 * Class OAuth2SummitBadgeTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitBadgeTypeApiController extends OAuth2ProtectedController
{

    use RequestProcessor;
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitBadgeTypeService
     */
    private $service;

    /**
     * OAuth2SummitBadgeFeatureTypeApiController constructor.
     * @param ISummitBadgeTypeRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitBadgeTypeService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitBadgeTypeRepository $repository,
        ISummitRepository $summit_repository,
        ISummitBadgeTypeService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    /**
     * @return array
     */
    protected function getFilterRules():array
    {
        return [
            'name'        => ['=@', '=='],
            'is_default'  => [ '=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'name'       => 'sometimes|required|string',
            'is_default' => 'sometimes|required|boolean',
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

    use GetAllBySummit {
        getAllBySummit as protected traitGetAllBySummit;
    }

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    #[OA\Get(
        path: "/api/v1/summits/{id}/badge-types",
        description: "Get all badge types for a summit",
        summary: "Get badge types",
        operationId: "getAllBySummitBadgeTypes",
        tags: ['Badge Types'],
        security: [['summit_badge_types_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1),
                description: 'Page number'
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10),
                description: 'Items per page'
            ),
            new OA\Parameter(
                name: 'filter',
                in: 'query',
                required: false,
                explode: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Filter operators: name=@/==, is_default=='
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                explode: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Order by fields: id, name'
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                explode: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Relations to expand: access_levels, badge_features, allowed_view_types'
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                explode: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Relations to include: access_levels, badge_features, allowed_view_types'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedSummitBadgeTypesResponse")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]

    #[OA\Get(
        path: "/api/v1/summits/{id}/badge-types/{badge_type_id}",
        description: "Get a specific badge type",
        summary: "Get badge type",
        operationId: "getSummitBadgeType",
        tags: ['Badge Types'],
        security: [['summit_badge_types_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'badge_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The badge type id'
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                explode: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Relations to expand: access_levels, badge_features, allowed_view_types'
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                explode: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Relations to include: access_levels, badge_features, allowed_view_types'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitBadgeType")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]

    #[OA\Post(
        path: "/api/v1/summits/{id}/badge-types",
        description: "Create a new badge type",
        summary: "Create badge type",
        operationId: "addSummitBadgeType",
        tags: ['Badge Types'],
        security: [['summit_badge_types_oauth2' => [
            SummitScopes::WriteSummitData
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SummitBadgeTypeCreateRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitBadgeType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]

    #[OA\Put(
        path: "/api/v1/summits/{id}/badge-types/{badge_type_id}",
        description: "Update an existing badge type",
        summary: "Update badge type",
        operationId: "updateSummitBadgeType",
        tags: ['Badge Types'],
        security: [['summit_badge_types_oauth2' => [
            SummitScopes::WriteSummitData
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'badge_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The badge type id'
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SummitBadgeTypeUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitBadgeType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]

    #[OA\Delete(
        path: "/api/v1/summits/{id}/badge-types/{badge_type_id}",
        description: "Delete a badge type",
        summary: "Delete badge type",
        operationId: "deleteSummitBadgeType",
        tags: ['Badge Types'],
        security: [['summit_badge_types_oauth2' => [
            SummitScopes::WriteSummitData
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'badge_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The badge type id'
            )
        ],
        responses: [
            new OA\Response(response: 204, description: 'No Content'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
        return SummitBadgeTypeValidationRulesFactory::build($payload);
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
       return $this->service->addBadgeType($summit, $payload);
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @return IResourceServerContext
     */
    protected function getResourceServerContext(): IResourceServerContext
    {
        return $this->resource_server_context;
    }

    /**
     * @return IBaseRepository
     */
    protected function getRepository(): IBaseRepository
    {
        return $this->repository;
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @return void
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->deleteBadgeType($summit, $child_id);
    }

    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
       return $summit->getBadgeTypeById($child_id);
    }

    /**
     * @param array $payload
     * @return array
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SummitBadgeTypeValidationRulesFactory::build($payload, true);
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->updateBadgeType($summit, $child_id, $payload);
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/badge-types/{badge_type_id}/access-levels/{access_level_id}",
        description: "Add an access level to a badge type",
        summary: "Add access level to badge type",
        operationId: "addAccessLevelToBadgeType",
        tags: ['Badge Types'],
        security: [['summit_badge_types_oauth2' => [
            SummitScopes::WriteSummitData
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'badge_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The badge type id'
            ),
            new OA\Parameter(
                name: 'access_level_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The access level id'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitBadgeType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function addAccessLevelToBadgeType($summit_id, $badge_type_id, $access_level_id){
       return $this->processRequest(function() use( $summit_id, $badge_type_id, $access_level_id){

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $child = $this->service->addAccessLevelToBadgeType($summit, intval($badge_type_id), intval($access_level_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($child)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
       });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/badge-types/{badge_type_id}/access-levels/{access_level_id}",
        description: "Remove an access level from a badge type",
        summary: "Remove access level from badge type",
        operationId: "removeAccessLevelFromBadgeType",
        tags: ['Badge Types'],
        security: [['summit_badge_types_oauth2' => [
            SummitScopes::WriteSummitData
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'badge_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The badge type id'
            ),
            new OA\Parameter(
                name: 'access_level_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The access level id'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitBadgeType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function removeAccessLevelFromBadgeType($summit_id, $badge_type_id, $access_level_id){
        return $this->processRequest(function() use($summit_id, $badge_type_id, $access_level_id){
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $child = $this->service->removeAccessLevelFromBadgeType($summit, intval($badge_type_id), intval($access_level_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($child)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/badge-types/{badge_type_id}/features/{feature_id}",
        description: "Add a feature to a badge type",
        summary: "Add feature to badge type",
        operationId: "addFeatureToBadgeType",
        tags: ['Badge Types'],
        security: [['summit_badge_types_oauth2' => [
            SummitScopes::WriteSummitData
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'badge_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The badge type id'
            ),
            new OA\Parameter(
                name: 'feature_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The feature id'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitBadgeType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function addFeatureToBadgeType($summit_id, $badge_type_id, $feature_id){
        return $this->processRequest(function() use($summit_id, $badge_type_id, $feature_id){

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $child = $this->service->addFeatureToBadgeType($summit, intval($badge_type_id), intval($feature_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($child)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/badge-types/{badge_type_id}/features/{feature_id}",
        description: "Remove a feature from a badge type",
        summary: "Remove feature from badge type",
        operationId: "removeFeatureFromBadgeType",
        tags: ['Badge Types'],
        security: [['summit_badge_types_oauth2' => [
            SummitScopes::WriteSummitData
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'badge_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The badge type id'
            ),
            new OA\Parameter(
                name: 'feature_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The feature id'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitBadgeType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function removeFeatureFromBadgeType($summit_id, $badge_type_id, $feature_id){
        return $this->processRequest(function() use ($summit_id, $badge_type_id, $feature_id){
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $child = $this->service->removeFeatureFromBadgeType($summit, intval($badge_type_id), intval($feature_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($child)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/badge-types/{badge_type_id}/view-types/{badge_view_type_id}",
        description: "Add a view type to a badge type",
        summary: "Add view type to badge type",
        operationId: "addViewTypeToBadgeType",
        tags: ['Badge Types'],
        security: [['summit_badge_types_oauth2' => [
            SummitScopes::WriteSummitData
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'badge_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The badge type id'
            ),
            new OA\Parameter(
                name: 'badge_view_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The badge view type id'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitBadgeType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function addViewTypeToBadgeType($summit_id, $badge_type_id, $view_type_id){
        return $this->processRequest(function() use($summit_id, $badge_type_id, $view_type_id){

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $child = $this->service->addViewTypeToBadgeType($summit, intval($badge_type_id), intval($view_type_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($child)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/badge-types/{badge_type_id}/view-types/{badge_view_type_id}",
        description: "Remove a view type from a badge type",
        summary: "Remove view type from badge type",
        operationId: "removeViewTypeFromBadgeType",
        tags: ['Badge Types'],
        security: [['summit_badge_types_oauth2' => [
            SummitScopes::WriteSummitData
        ]]],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'badge_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The badge type id'
            ),
            new OA\Parameter(
                name: 'badge_view_type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The badge view type id'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitBadgeType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function removeViewTypeFromBadgeType($summit_id, $badge_type_id, $view_type_id){
        return $this->processRequest(function() use ($summit_id, $badge_type_id, $view_type_id){
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $child = $this->service->removeViewTypeFromBadgeType($summit, intval($badge_type_id), intval($view_type_id));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($child)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

}
