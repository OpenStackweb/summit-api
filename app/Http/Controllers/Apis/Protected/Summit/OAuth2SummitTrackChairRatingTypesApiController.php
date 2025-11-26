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
use App\Models\Foundation\Summit\Repositories\IPresentationTrackChairRatingTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISelectionPlanRepository;
use App\ModelSerializers\SerializerUtils;
use App\Security\SummitScopes;
use App\Services\Model\ITrackChairRankingService;
use Illuminate\Http\Response;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\utils\IBaseRepository;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use utils\Filter;
use utils\FilterElement;

/**
 * Class OAuth2SummitTrackChairRatingTypesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitTrackChairRatingTypesApiController
    extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;

    use RequestProcessor;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISelectionPlanRepository
     */
    private $selection_plan_repository;

    /**
     * @var IPresentationTrackChairRatingTypeRepository
     */
    protected $repository;

    /**
     * @var ITrackChairRankingService
     */
    private $service;

    /**
     * OAuth2SummitTrackChairsApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISelectionPlanRepository $selection_plan_repository
     * @param IPresentationTrackChairRatingTypeRepository $repository
     * @param ITrackChairRankingService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISelectionPlanRepository $selection_plan_repository,
        IPresentationTrackChairRatingTypeRepository $repository,
        ITrackChairRankingService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->service = $service;
        $this->summit_repository = $summit_repository;
        $this->selection_plan_repository = $selection_plan_repository;
        $this->repository = $repository;
    }

    use ParametrizedGetAll;

    /**
     * @return IBaseRepository
     */
    protected function getRepository(): IBaseRepository
    {
        return $this->repository;
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chair-rating-types",
        description: "Get all track chair rating types for a selection plan",
        summary: "Get all track chair rating types",
        operationId: "getAllTrackChairRatingTypes",
        tags: ['Track Chair Rating Types'],
        security: [["track_chair_rating_types_oauth2" => [SummitScopes::ReadSummitData]]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The selection plan id'
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
                schema: new OA\Schema(type: 'string'),
                description: 'Filter expression (e.g., name=@Technical)'
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Order by field (e.g., +order, -name)'
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Expand relationships (score_types,selection_plan)'
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Relations to include (score_types)'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedPresentationTrackChairRatingTypesResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getTrackChairRatingTypes($summit_id, $selection_plan_id) {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
        if (is_null($summit)) return $this->error404();

        $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
        if (is_null($selection_plan)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'selection_plan_id' => ['=='],
                    'name' => ['@@','=@','==']
                ];
            },
            function () {
                return [
                    'selection_plan_id' => 'sometimes|integer',
                    'name'=> 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'order',
                    'name'
                ];
            },
            function ($filter) use ($selection_plan) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('selection_plan_id', $selection_plan->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chair-rating-types/{type_id}",
        description: "Get a specific track chair rating type by id",
        summary: "Get track chair rating type",
        operationId: "getTrackChairRatingType",
        tags: ['Track Chair Rating Types'],
        security: [["track_chair_rating_types_oauth2" => [SummitScopes::ReadSummitData]]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The selection plan id'
            ),
            new OA\Parameter(
                name: 'type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The rating type id'
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Expand relationships (score_types,selection_plan)'
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Relations to include (score_types)'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/PresentationTrackChairRatingType')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getTrackChairRatingType($summit_id, $selection_plan_id, $type_id) {

        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $track_chair_rating_type = $this->service->getTrackChairRatingType($selection_plan, intval($type_id));
            if (is_null($track_chair_rating_type)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($track_chair_rating_type)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chair-rating-types",
        description: "Create a new track chair rating type",
        summary: "Create track chair rating type",
        operationId: "createTrackChairRatingType",
        tags: ['Track Chair Rating Types'],
        security: [["track_chair_rating_types_oauth2" => [SummitScopes::WriteSummitData]]],
        x: [
            "authz_groups" => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::TrackChairs, IGroup::TrackChairsAdmins]
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
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The selection plan id'
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PresentationTrackChairRatingTypeCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(ref: '#/components/schemas/PresentationTrackChairRatingType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function addTrackChairRatingType($summit_id, $selection_plan_id) {

        return $this->processRequest(function () use ($summit_id, $selection_plan_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $payload = $this->getJsonPayload(RatingTypeValidationRulesFactory::buildForAdd());

            $track_chair_rating_type = $this->service->addTrackChairRatingType($selection_plan, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($track_chair_rating_type)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chair-rating-types/{type_id}",
        description: "Update an existing track chair rating type",
        summary: "Update track chair rating type",
        operationId: "updateTrackChairRatingType",
        tags: ['Track Chair Rating Types'],
        security: [["track_chair_rating_types_oauth2" => [SummitScopes::WriteSummitData]]],
        x: [
            "authz_groups" => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::TrackChairs, IGroup::TrackChairsAdmins]
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
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The selection plan id'
            ),
            new OA\Parameter(
                name: 'type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The rating type id'
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PresentationTrackChairRatingTypeUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/PresentationTrackChairRatingType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function updateTrackChairRatingType($summit_id, $selection_plan_id, $type_id) {

        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $payload = $this->getJsonPayload(RatingTypeValidationRulesFactory::buildForUpdate());

            $track_chair_rating_type = $this->service->updateTrackChairRatingType($selection_plan, intval($type_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($track_chair_rating_type)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chair-rating-types/{type_id}",
        description: "Delete a track chair rating type",
        summary: "Delete track chair rating type",
        operationId: "deleteTrackChairRatingType",
        tags: ['Track Chair Rating Types'],
        security: [["track_chair_rating_types_oauth2" => [SummitScopes::WriteSummitData]]],
        x: [
            "authz_groups" => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::TrackChairs, IGroup::TrackChairsAdmins]
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
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The selection plan id'
            ),
            new OA\Parameter(
                name: 'type_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The rating type id'
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
    public function deleteTrackChairRatingType($summit_id, $selection_plan_id, $type_id) {

        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $this->service->deleteTrackChairRatingType($selection_plan, intval($type_id));

            return $this->deleted();
        });
    }
}
