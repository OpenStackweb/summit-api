<?php namespace App\Http\Controllers;
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

use App\Http\Utils\EpochCellFormatter;
use App\Models\Foundation\Main\IGroup;
use App\Security\SummitScopes;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Foundation\Summit\Repositories\IPresentationActionTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISelectionPlanRepository;
use App\Models\Foundation\Summit\Repositories\ISummitCategoryChangeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitSelectionPlanExtraQuestionTypeRepository;
use App\ModelSerializers\SerializerUtils;
use App\Rules\Boolean;
use App\Services\Model\ISelectionPlanExtraQuestionTypeService;
use App\Services\Model\ISummitSelectionPlanService;
use Illuminate\Http\Request as LaravelRequest;
use libs\utils\HTMLCleaner;
use libs\utils\PaginationValidationRules;
use models\exceptions\EntityNotFoundException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use ModelSerializers\IPresentationSerializerTypes;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;
use utils\PagingInfo;

/**
 * Class OAuth2SummitSelectionPlansApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSelectionPlansApiController extends OAuth2ProtectedController
{
    use RequestProcessor;

    use GetAndValidateJsonPayload;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitEventRepository
     */
    private $summit_event_repository;

    /**
     * @var ISummitSelectionPlanService
     */
    private $selection_plan_service;

    /**
     * @var ISummitCategoryChangeRepository
     */
    private $category_change_request_repository;

    /**
     * ISelectionPlanOrderExtraQuestionTypeService
     */
    private $selection_plan_extra_questions_service;

    /**
     * @var ISummitSelectionPlanExtraQuestionTypeRepository
     */
    private $selection_plan_extra_questions_repository;

    /**
     * @var IPresentationActionTypeRepository
     */
    private $presentation_action_repository;

    /**
     * OAuth2SummitSelectionPlansApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitEventRepository $summit_event_repository
     * @param ISummitCategoryChangeRepository $category_change_request_repository
     * @param ISelectionPlanRepository $selection_plan_repository
     * @param ISummitSelectionPlanExtraQuestionTypeRepository $selection_plan_extra_questions_repository
     * @param IPresentationActionTypeRepository $presentation_action_repository
     * @param ISummitSelectionPlanService $selection_plan_service
     * @param ISelectionPlanExtraQuestionTypeService $selection_plan_extra_questions_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository                               $summit_repository,
        ISummitEventRepository                          $summit_event_repository,
        ISummitCategoryChangeRepository                 $category_change_request_repository,
        ISelectionPlanRepository                        $selection_plan_repository,
        ISummitSelectionPlanExtraQuestionTypeRepository $selection_plan_extra_questions_repository,
        IPresentationActionTypeRepository               $presentation_action_repository,
        ISummitSelectionPlanService                     $selection_plan_service,
        ISelectionPlanExtraQuestionTypeService          $selection_plan_extra_questions_service,
        IResourceServerContext                          $resource_server_context
    )
    {
        parent::__construct($resource_server_context);

        $this->repository = $selection_plan_repository;
        $this->summit_repository = $summit_repository;
        $this->summit_event_repository = $summit_event_repository;
        $this->category_change_request_repository = $category_change_request_repository;
        $this->presentation_action_repository = $presentation_action_repository;
        $this->selection_plan_service = $selection_plan_service;
        $this->selection_plan_extra_questions_service = $selection_plan_extra_questions_service;
        $this->selection_plan_extra_questions_repository = $selection_plan_extra_questions_repository;
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}',
        operationId: 'getSelectionPlan',
        summary: 'Get a selection plan by ID',
        description: 'Retrieves a specific selection plan for a summit by its ID.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadAllSummitData,
                SummitScopes::ReadSummitData,
            ]]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Expand relationships: track_groups, extra_questions, event_types, track_chair_rating_types, allowed_presentation_action_types, summit',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'fields',
                in: 'query',
                required: false,
                description: 'Fields to return (comma-separated)',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                description: 'Relations to include',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Selection plan retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SelectionPlan')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Selection Plan not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getSelectionPlan($summit_id, $selection_plan_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($selection_plan)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return mixed
     */
    #[OA\Put(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}',
        operationId: 'updateSelectionPlan',
        summary: 'Update a selection plan',
        description: 'Updates an existing selection plan for a summit.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Expand relationships',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Selection plan updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SelectionPlan')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Selection Plan not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function updateSelectionPlan($summit_id, $selection_plan_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SummitSelectionPlanValidationRulesFactory::buildForUpdate());

            $selection_plan = $this->selection_plan_service->updateSelectionPlan($summit, intval($selection_plan_id),
                HTMLCleaner::cleanData($payload, [
                    'submission_period_disclaimer',
                ]));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($selection_plan)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/selection-plans',
        operationId: 'addSelectionPlan',
        summary: 'Create a new selection plan',
        description: 'Creates a new selection plan for a summit.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Expand relationships',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Selection plan created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SelectionPlan')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function addSelectionPlan($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SummitSelectionPlanValidationRulesFactory::buildForAdd());

            $selection_plan = $this->selection_plan_service->addSelectionPlan($summit,
                HTMLCleaner::cleanData($payload,
                    [
                        'submission_period_disclaimer',
                    ]));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($selection_plan)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return mixed
     */
    #[OA\Delete(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}',
        operationId: 'deleteSelectionPlan',
        summary: 'Delete a selection plan',
        description: 'Deletes an existing selection plan from a summit.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Selection plan deleted successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Selection Plan not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function deleteSelectionPlan($summit_id, $selection_plan_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->deleteSelectionPlan($summit, intval($selection_plan_id));

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_group_id
     * @return mixed
     */
    #[OA\Put(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-groups/{track_group_id}',
        operationId: 'addTrackGroupToSelectionPlan',
        summary: 'Add a track group to a selection plan',
        description: 'Associates a track group with a selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'track_group_id',
                in: 'path',
                required: true,
                description: 'Track Group ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Track group added successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Track Group not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function addTrackGroupToSelectionPlan($summit_id, $selection_plan_id, $track_group_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $track_group_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->addTrackGroupToSelectionPlan($summit, intval($selection_plan_id), intval($track_group_id));

            return $this->updated();
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_group_id
     * @return mixed
     */
    #[OA\Delete(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-groups/{track_group_id}',
        operationId: 'deleteTrackGroupToSelectionPlan',
        summary: 'Remove a track group from a selection plan',
        description: 'Removes the association between a track group and a selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'track_group_id',
                in: 'path',
                required: true,
                description: 'Track Group ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Track group removed successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Track Group not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function deleteTrackGroupToSelectionPlan($summit_id, $selection_plan_id, $track_group_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $track_group_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->deleteTrackGroupToSelectionPlan($summit, intval($selection_plan_id), intval($track_group_id));

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $status
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/public/v1/summits/{id}/selection-plans/current/{status}',
        operationId: 'getCurrentSelectionPlanByStatus',
        summary: 'Get current selection plan by status (Public)',
        description: 'Retrieves the current active selection plan for a summit filtered by status. This is a public endpoint.',
        tags: ['Selection Plans (Public)'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'status',
                in: 'path',
                required: true,
                description: 'Selection plan status: submission, selection, or voting',
                schema: new OA\Schema(type: 'string', enum: ['submission', 'selection', 'voting'])
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Expand relationships',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Current selection plan retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SelectionPlan')
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Selection Plan not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getCurrentSelectionPlanByStatus($summit_id, $status)
    {
        return $this->processRequest(function () use ($summit_id, $status) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $selection_plan = $this->selection_plan_service->getCurrentSelectionPlanByStatus($summit, $status);

            if (is_null($selection_plan))
                return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($selection_plan)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    use ParametrizedGetAll;

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plans',
        operationId: 'getAllSelectionPlans',
        summary: 'Get all selection plans for a summit',
        description: 'Retrieves a paginated list of selection plans for a specific summit.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadAllSummitData,
                SummitScopes::ReadSummitData,
            ]]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
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
                description: 'Filter expressions. Available fields: name (=@, @@, ==), status (==, values: submission, selection, voting)',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'status==submission')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s). Available fields: id, created, last_edited. Use + for asc, - for desc.',
                schema: new OA\Schema(type: 'string', example: '+id')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Expand relationships',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Selection plans retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSelectionPlansResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getAll($summit_id)
    {
        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@','@@','=='],
                    'status' => ['=='],
                ];
            },
            function () {

                return [
                    'name' => 'sometimes|string',
                    'status' => 'sometimes|string|in:submission,selection,voting',
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'last_edited',
                ];
            },
            function ($filter) use ($summit_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit_id));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            null,
        );
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations',
        operationId: 'getSelectionPlanPresentations',
        summary: 'Get presentations for a selection plan',
        description: 'Retrieves a paginated list of presentations for a specific selection plan. Only available to track chairs.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
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
                description: 'Filter expressions. Available fields: title, abstract, social_summary, tags, level (=@, ==), summit_type_id, event_type_id, track_id, speaker_id, id, selection_plan_id (==), speaker, speaker_email (=@, ==), status, selection_status, is_chair_visible, is_voting_visible, track_chairs_status (voted, untouched, team_selected, selected, maybe, pass), viewed_status (seen, unseen, moved), actions',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'track_id==10')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s). Available fields: id, title, start_date, end_date, created, track, location, trackchairsel, last_edited',
                schema: new OA\Schema(type: 'string', example: '+title')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Expand relationships',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Presentations retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginateDataSchemaResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden - Not a track chair'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Selection Plan not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getSelectionPlanPresentations($summit_id, $selection_plan_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $member = $this->resource_server_context->getCurrentUser();

        if (is_null($member))
            return $this->error403();

        $authz = $summit->isTrackChair($member) || $summit->isTrackChairAdmin($member);

        if (!$authz)
            return $this->error403();

        return $this->_getAll(
            function () {
                return [
                    'title' => ['=@', '=='],
                    'abstract' => ['=@', '=='],
                    'social_summary' => ['=@', '=='],
                    'tags' => ['=@', '=='],
                    'level' => ['=@', '=='],
                    'summit_type_id' => ['=='],
                    'event_type_id' => ['=='],
                    'track_id' => ['=='],
                    'speaker_id' => ['=='],
                    'speaker' => ['=@', '=='],
                    'speaker_email' => ['=@', '=='],
                    'selection_status' => ['=='],
                    'id' => ['=='],
                    'selection_plan_id' => ['=='],
                    'status' => ['=='],
                    'is_chair_visible' => ['=='],
                    'is_voting_visible' => ['=='],
                    'track_chairs_status' => ['=='],
                    'viewed_status' => ['=='],
                    'actions' => ['=='],
                ];
            },
            function () {
                return [
                    'title' => 'sometimes|string',
                    'abstract' => 'sometimes|string',
                    'social_summary' => 'sometimes|string',
                    'tags' => 'sometimes|string',
                    'level' => 'sometimes|string',
                    'summit_type_id' => 'sometimes|integer',
                    'event_type_id' => 'sometimes|integer',
                    'track_id' => 'sometimes|integer',
                    'speaker_id' => 'sometimes|integer',
                    'speaker' => 'sometimes|string',
                    'speaker_email' => 'sometimes|string',
                    'selection_status' => 'sometimes|string',
                    'id' => 'sometimes|integer',
                    'selection_plan_id' => 'sometimes|integer',
                    'status' => 'sometimes|string',
                    'is_chair_visible' => ['sometimes', new Boolean],
                    'is_voting_visible' => ['sometimes', new Boolean],
                    'track_chairs_status' => 'sometimes|string|in:voted,untouched,team_selected,selected,maybe,pass',
                    'viewed_status' => 'sometimes|string|in:seen,unseen,moved',
                    'actions' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'title',
                    'start_date',
                    'end_date',
                    'created',
                    'track',
                    'location',
                    'trackchairsel',
                    'last_edited',
                ];
            },
            function ($filter) use ($summit, $selection_plan_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('selection_plan_id', $selection_plan_id));
                    $current_member = $this->resource_server_context->getCurrentUser(false);
                    if (!is_null($current_member)) {
                        $filter->addFilterCondition(FilterElement::makeEqual('current_member_id', $current_member->getId()));
                    }
                }
                return $filter;
            },
            function () {
                return IPresentationSerializerTypes::TrackChairs;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->summit_event_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/csv',
        operationId: 'getSelectionPlanPresentationsCSV',
        summary: 'Export presentations for a selection plan to CSV',
        description: 'Exports presentations for a specific selection plan as a CSV file. Only available to track chairs.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions (same as getSelectionPlanPresentations)',
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
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'CSV file generated successfully',
                content: new OA\MediaType(
                    mediaType: 'text/csv',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden - Not a track chair'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Selection Plan not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getSelectionPlanPresentationsCSV($summit_id, $selection_plan_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $member = $this->resource_server_context->getCurrentUser();

        if (is_null($member))
            return $this->error403();

        $authz = $summit->isTrackChair($member) || $summit->isTrackChairAdmin($member);

        if (!$authz)
            return $this->error403();

        return $this->_getAllCSV(
            function () {
                return [
                    'title' => ['=@', '=='],
                    'abstract' => ['=@', '=='],
                    'social_summary' => ['=@', '=='],
                    'tags' => ['=@', '=='],
                    'level' => ['=@', '=='],
                    'summit_type_id' => ['=='],
                    'event_type_id' => ['=='],
                    'track_id' => ['=='],
                    'speaker_id' => ['=='],
                    'speaker' => ['=@', '=='],
                    'speaker_email' => ['=@', '=='],
                    'selection_status' => ['=='],
                    'id' => ['=='],
                    'selection_plan_id' => ['=='],
                    'status' => ['=='],
                    'is_chair_visible' => ['=='],
                    'is_voting_visible' => ['=='],
                    'track_chairs_status' => ['=='],
                    'viewed_status' => ['=='],
                    'actions' => ['=='],
                ];
            },
            function () {
                return [
                    'title' => 'sometimes|string',
                    'abstract' => 'sometimes|string',
                    'social_summary' => 'sometimes|string',
                    'tags' => 'sometimes|string',
                    'level' => 'sometimes|string',
                    'summit_type_id' => 'sometimes|integer',
                    'event_type_id' => 'sometimes|integer',
                    'track_id' => 'sometimes|integer',
                    'speaker_id' => 'sometimes|integer',
                    'speaker' => 'sometimes|string',
                    'speaker_email' => 'sometimes|string',
                    'selection_status' => 'sometimes|string',
                    'id' => 'sometimes|integer',
                    'selection_plan_id' => 'sometimes|integer',
                    'status' => 'sometimes|string',
                    'is_chair_visible' => ['sometimes', new Boolean],
                    'is_voting_visible' => ['sometimes', new Boolean],
                    'track_chairs_status' => 'sometimes|string|in:voted,untouched,team_selected,selected,maybe,pass',
                    'viewed_status' => 'sometimes|string|in:seen,unseen,moved',
                    'actions' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'title',
                    'start_date',
                    'end_date',
                    'created',
                    'track',
                    'location',
                    'trackchairsel',
                    'last_edited',
                ];
            },
            function ($filter) use ($summit, $selection_plan_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('selection_plan_id', intval($selection_plan_id)));
                    $current_member = $this->resource_server_context->getCurrentUser(false);
                    if (!is_null($current_member)) {
                        $filter->addFilterCondition(FilterElement::makeEqual('current_member_id', $current_member->getId()));
                    }
                }
                return $filter;
            },
            function () {
                return IPresentationSerializerTypes::TrackChairs_CSV;
            },
            function () {
                return [
                    'start_date' => new EpochCellFormatter(),
                    'end_date' => new EpochCellFormatter(),
                    'created' => new EpochCellFormatter(),
                    'last_edited' => new EpochCellFormatter(),
                ];
            },
            function () {
                return [];
            },
            'presentations-',
            [],
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->summit_event_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );

    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}',
        operationId: 'getSelectionPlanPresentation',
        summary: 'Get a specific presentation from a selection plan',
        description: 'Retrieves a specific presentation by ID from a selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'presentation_id',
                in: 'path',
                required: true,
                description: 'Presentation ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Expand relationships',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Presentation retrieved successfully'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Presentation not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getSelectionPlanPresentation($summit_id, $selection_plan_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan))
                return $this->error404();

            $presentation = $selection_plan->getPresentation(intval($presentation_id));
            if (is_null($presentation))
                throw new EntityNotFoundException();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer
            (
                $presentation,
                IPresentationSerializerTypes::TrackChairs
            )->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/view',
        operationId: 'markPresentationAsViewed',
        summary: 'Mark a presentation as viewed',
        description: 'Marks a presentation as viewed by the current track chair.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'presentation_id',
                in: 'path',
                required: true,
                description: 'Presentation ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Presentation marked as viewed successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Presentation not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function markPresentationAsViewed($summit_id, $selection_plan_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $presentation = $this->selection_plan_service->markPresentationAsViewed
            (
                $summit,
                intval($selection_plan_id),
                intval($presentation_id)
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $presentation,
                IPresentationSerializerTypes::TrackChairs
            )->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/comments',
        operationId: 'addCommentToPresentation',
        summary: 'Add a comment to a presentation',
        description: 'Adds a new comment to a presentation within a selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins,
            ]
        ],
        requestBody: new OA\RequestBody(
            description: 'Comment data',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PresentationCommentPayload')
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'presentation_id',
                in: 'path',
                required: true,
                description: 'Presentation ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Comment added successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitPresentationComment')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Presentation not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function addCommentToPresentation($summit_id, $selection_plan_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $payload = $this->getJsonPayload([
                'body' => 'required|string',
                'is_public' => 'required|boolean',
            ]);

            $comment = $this->selection_plan_service->addPresentationComment
            (
                $summit,
                intval($selection_plan_id),
                intval($presentation_id),
                HTMLCleaner::cleanData($payload, ['body'])
            );

            return $this->created(SerializerRegistry::getInstance()->getSerializer($comment)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/all/category-change-requests',
        operationId: 'getAllPresentationCategoryChangeRequest',
        summary: 'Get all category change requests for a selection plan',
        description: 'Retrieves a paginated list of category change requests for presentations in a selection plan. Only available to track chairs.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadAllSummitData,
                SummitScopes::ReadSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
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
                schema: new OA\Schema(type: 'integer', example: 10)
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions. Available fields: selection_plan_id, summit_id, new_category_id, old_category_id (==), new_category_name, old_category_name, requester_fullname, requester_email, aprover_fullname, aprover_email, presentation_title (=@, ==)',
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
                description: 'Order by field(s). Available: id, approval_date, status, presentation_title, new_category_name, old_category_name, requester_fullname',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Category change requests retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitCategoryChangesResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden - Not a track chair'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Selection Plan not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getAllPresentationCategoryChangeRequest($summit_id, $selection_plan_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $member = $this->resource_server_context->getCurrentUser();

        if (is_null($member))
            return $this->error403();

        $authz = $summit->isTrackChair($member) || $summit->isTrackChairAdmin($member);

        if (!$authz)
            return $this->error403();

        return $this->_getAll(
            function () {
                return [
                    'selection_plan_id' => ['=='],
                    'summit_id' => ['=='],
                    'new_category_id' => ['=='],
                    'old_category_id' => ['=='],
                    'new_category_name' => ['=@', '=='],
                    'old_category_name' => ['=@', '=='],
                    'requester_fullname' => ['=@', '=='],
                    'requester_email' => ['=@', '=='],
                    'aprover_fullname' => ['=@', '=='],
                    'aprover_email' => ['=@', '=='],
                    'presentation_title' => ['=@', '=='],
                ];
            },
            function () {
                return [
                    'selection_plan_id' => 'sometimes|integer',
                    'summit_id' => 'sometimes|integer',
                    'new_category_id' => 'sometimes|integer',
                    'old_category_id' => 'sometimes|integer',
                    'new_category_name' => 'sometimes|string',
                    'old_category_name' => 'sometimes|string',
                    'requester_fullname' => 'sometimes|string',
                    'aprover_fullname' => 'sometimes|string',
                    'aprover_email' => 'sometimes|string',
                    'presentation_title' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'approval_date',
                    'status',
                    'presentation_title',
                    'new_category_name',
                    'old_category_name',
                    'requester_fullname',
                ];
            },
            function ($filter) use ($summit, $selection_plan_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('selection_plan_id', $selection_plan_id));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->category_change_request_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/category-change-requests',
        operationId: 'createPresentationCategoryChangeRequest',
        summary: 'Create a category change request for a presentation',
        description: 'Creates a new request to change the category/track of a presentation.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins,
            ]
        ],
        requestBody: new OA\RequestBody(
            description: 'Category change request data',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CategoryChangeRequestPayload')
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'presentation_id',
                in: 'path',
                required: true,
                description: 'Presentation ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Category change request created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitCategoryChange')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Presentation not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function createPresentationCategoryChangeRequest($summit_id, $selection_plan_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'new_category_id' => 'required|integer',
            ]);

            $change_request = $this->selection_plan_service->createPresentationCategoryChangeRequest
            (
                $summit,
                intval($selection_plan_id),
                intval($presentation_id),
                intval($payload['new_category_id'])
            );

            return $this->created(SerializerRegistry::getInstance()->getSerializer($change_request)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @param $category_change_request_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/category-change-requests/{category_change_request_id}',
        operationId: 'resolvePresentationCategoryChangeRequest',
        summary: 'Resolve a category change request',
        description: 'Approves or rejects a category change request for a presentation.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins,
            ]
        ],
        requestBody: new OA\RequestBody(
            description: 'Resolution data',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ResolveCategoryChangeRequestPayload')
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'presentation_id',
                in: 'path',
                required: true,
                description: 'Presentation ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'category_change_request_id',
                in: 'path',
                required: true,
                description: 'Category Change Request ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Category change request resolved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitCategoryChange')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Resource not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function resolvePresentationCategoryChangeRequest($summit_id, $selection_plan_id, $presentation_id, $category_change_request_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $presentation_id, $category_change_request_id) {


            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'approved' => 'required|bool',
                'reason' => 'sometimes|string',
            ]);

            $change_request = $this->selection_plan_service->resolvePresentationCategoryChangeRequest
            (
                $summit,
                intval($selection_plan_id),
                intval($presentation_id),
                intval($category_change_request_id),
                $payload
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($change_request)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * Extra questions
     */


    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plan-extra-questions',
        operationId: 'getExtraQuestions',
        summary: 'Get all extra questions for a summit',
        description: 'Retrieves a paginated list of extra questions available for selection plans in a summit.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadAllSummitData,
                SummitScopes::ReadSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Items per page',
                schema: new OA\Schema(type: 'integer', example: 10)
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions. Available fields: name, type, label (=@, ==)',
                style: 'form',
                explode: true,
                schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s). Available: id, name, label, order',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Extra questions retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitSelectionPlanExtraQuestionTypesResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getExtraQuestions($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '=='],
                    'type' => ['=@', '=='],
                    'label' => ['=@', '=='],
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|string',
                    'type' => 'sometimes|string',
                    'label' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'label',
                    'order',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition
                    (
                        FilterElement::makeEqual('summit_id', $summit->getId())
                    );
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->selection_plan_extra_questions_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plan-extra-questions/{question_id}',
        operationId: 'getExtraQuestion',
        summary: 'Get a specific extra question',
        description: 'Retrieves a specific extra question by ID.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadAllSummitData,
                SummitScopes::ReadSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                description: 'Question ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Expand relationships: values, sub_question_rules, parent_rules',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Extra question retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitSelectionPlanExtraQuestionType')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Question not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getExtraQuestion($summit_id, $question_id){
        return $this->processRequest(function() use($summit_id, $question_id){
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $question = $summit->getSelectionPlanExtraQuestionById(intval($question_id));
            if (is_null($question)) return $this->error404('Question not found.');

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($question)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }
    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions',
        operationId: 'getExtraQuestionsBySelectionPlan',
        summary: 'Get extra questions for a selection plan',
        description: 'Retrieves a paginated list of extra questions assigned to a specific selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadAllSummitData,
                SummitScopes::ReadSummitData,
            ]]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Items per page',
                schema: new OA\Schema(type: 'integer', example: 10)
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions. Available fields: name, type, label (=@, ==)',
                style: 'form',
                explode: true,
                schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s). Available: id, name, label, order',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Extra questions retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedAssignedSelectionPlanExtraQuestionTypesResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Selection Plan not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getExtraQuestionsBySelectionPlan($summit_id, $selection_plan_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '=='],
                    'type' => ['=@', '=='],
                    'label' => ['=@', '=='],
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|string',
                    'type' => 'sometimes|string',
                    'label' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'label',
                    'order',
                ];
            },
            function ($filter) use ($summit, $selection_plan_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition
                    (
                        FilterElement::makeEqual('selection_plan_id', intval($selection_plan_id))
                    );
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->selection_plan_extra_questions_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
            ,
            ['selection_plan_id' => intval($selection_plan_id)]
        );
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plan-extra-questions/metadata',
        operationId: 'getExtraQuestionsMetadata',
        summary: 'Get extra questions metadata',
        description: 'Retrieves metadata about available extra question types.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadAllSummitData,
                SummitScopes::ReadSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Metadata retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/ExtraQuestionTypeMetadata')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getExtraQuestionsMetadata($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->ok
        (
            $this->selection_plan_extra_questions_repository->getQuestionsMetadata()
        );
    }


    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/metadata',
        operationId: 'getExtraQuestionsMetadataBySelectionPlan',
        summary: 'Get extra questions metadata for a selection plan',
        description: 'Retrieves metadata about available extra question types for a specific selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadAllSummitData,
                SummitScopes::ReadSummitData,
            ]]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Metadata retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/ExtraQuestionTypeMetadata')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Selection Plan not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getExtraQuestionsMetadataBySelectionPlan($summit_id, $selection_plan_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->ok
        (
            $this->selection_plan_extra_questions_repository->getQuestionsMetadata()
        );
    }

    use ParametrizedAddEntity;

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/selection-plan-extra-questions',
        operationId: 'addExtraQuestion',
        summary: 'Create a new extra question',
        description: 'Creates a new extra question for selection plans in a summit.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Extra question created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitSelectionPlanExtraQuestionType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function addExtraQuestion($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit];

        return $this->_add(
            function ($payload) {
                return SelectionPlanExtraQuestionValidationRulesFactory::build($payload);
            },
            function ($payload, $summit) {
                return $this->selection_plan_extra_questions_service->addExtraQuestion($summit, HTMLCleaner::cleanData($payload, ['label']));
            },
            ...$args
        );
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: '/api/v1/summits/{id}/selection-plan-extra-questions/{question_id}',
        operationId: 'updateExtraQuestion',
        summary: 'Update an extra question',
        description: 'Updates an existing extra question.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                description: 'Question ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Extra question updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitSelectionPlanExtraQuestionType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Question not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function updateExtraQuestion($summit_id, $question_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit];

        return $this->_update($question_id, function ($payload) {
            return SelectionPlanExtraQuestionValidationRulesFactory::build($payload, true);
        },
            function ($question_id, $payload, $summit) {
                return $this->selection_plan_extra_questions_service->updateExtraQuestion
                (
                    $summit,
                    intval($question_id),
                    HTMLCleaner::cleanData($payload, ['label'])
                );
            }, ...$args);
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions',
        operationId: 'addExtraQuestionAndAssign',
        summary: 'Create and assign an extra question to a selection plan',
        description: 'Creates a new extra question and assigns it to a specific selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Extra question created and assigned successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/AssignedSelectionPlanExtraQuestionType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Selection Plan not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function addExtraQuestionAndAssign($summit_id, $selection_plan_id){
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
        if (is_null($selection_plan)) return $this->error404();

        $args = [$selection_plan_id];

        return $this->_add(
            function ($payload) {
                return SelectionPlanExtraQuestionValidationRulesFactory::build($payload);
            },
            function ($payload, $selection_plan_id) {
                  return $this->selection_plan_extra_questions_service->addExtraQuestionAndAssignTo($selection_plan_id, HTMLCleaner::cleanData($payload, ['label']));
            },
            ...$args
        );
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $question_id
     * @return mixed
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/{question_id}',
        operationId: 'assignExtraQuestion',
        summary: 'Assign an existing extra question to a selection plan',
        description: 'Assigns an existing extra question to a specific selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                description: 'Question ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Extra question assigned successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/AssignedSelectionPlanExtraQuestionType')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Question not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function assignExtraQuestion($summit_id, $selection_plan_id, $question_id){

        return $this->processRequest(function() use($summit_id, $selection_plan_id, $question_id){

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $assigment = $this->selection_plan_extra_questions_service->assignExtraQuestion(intval($selection_plan_id), intval($question_id));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($assigment)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    use ParametrizedGetEntity;

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/{question_id}',
        operationId: 'getExtraQuestionBySelectionPlan',
        summary: 'Get an extra question by selection plan',
        description: 'Retrieves a specific extra question assigned to a selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadAllSummitData,
                SummitScopes::ReadSummitData,
            ]]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                description: 'Question ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Extra question retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/AssignedSelectionPlanExtraQuestionType')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Question not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getExtraQuestionBySelectionPlan($summit_id, $selection_plan_id, $question_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
        if (is_null($selection_plan)) return $this->error404();

        return $this->_get($question_id, function ($id) use ($selection_plan) {
            $q = $selection_plan->getExtraQuestionById(intval($id));
            if(is_null($q)) return null;
            return $selection_plan->getAssignedExtraQuestion($q);
        });
    }

    use ParametrizedUpdateEntity;

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/{question_id}',
        operationId: 'updateExtraQuestionBySelectionPlan',
        summary: 'Update an extra question by selection plan',
        description: 'Updates a specific extra question assigned to a selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                description: 'Question ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Extra question updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/AssignedSelectionPlanExtraQuestionType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Question not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function updateExtraQuestionBySelectionPlan($summit_id, $selection_plan_id, $question_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
        if (is_null($selection_plan)) return $this->error404();

        $args = [$selection_plan];

        return $this->_update($question_id, function ($payload) {
            return SelectionPlanExtraQuestionValidationRulesFactory::build($payload, true);
        },
            function ($question_id, $payload, $selection_plan) {
                return $this->selection_plan_extra_questions_service->updateExtraQuestionBySelectionPlan
                (
                    $selection_plan,
                    intval($question_id),
                    HTMLCleaner::cleanData($payload, ['label'])
                );
            }, ...$args);
    }

    use ParametrizedDeleteEntity;

    /**
     * @param $summit_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Delete(
        path: '/api/v1/summits/{id}/selection-plan-extra-questions/{question_id}',
        operationId: 'deleteExtraQuestion',
        summary: 'Delete an extra question',
        description: 'Deletes a specific extra question from a summit.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                description: 'Question ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Extra question deleted successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Question not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function deleteExtraQuestion($summit_id, $question_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit];

        return $this->_delete(intval($question_id), function ($question_id, $summit) {
            $this->selection_plan_extra_questions_service->deleteExtraQuestion($summit, intval($question_id));
        }, ...$args);
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Delete(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/{question_id}',
        operationId: 'removeExtraQuestion',
        summary: 'Remove an extra question from a selection plan',
        description: 'Removes an extra question assignment from a specific selection plan (does not delete the question).',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                description: 'Question ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Extra question removed successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Question not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function removeExtraQuestion($summit_id, $selection_plan_id, $question_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
        if (is_null($selection_plan)) return $this->error404();

        $args = [$selection_plan_id];

        return $this->_delete(intval($question_id), function ($question_id, $selection_plan_id) {
            $this->selection_plan_extra_questions_service->removeExtraQuestion($selection_plan_id, intval($question_id));
        }, ...$args);
    }

    // Question Values

    /**
     * @param $summit_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/selection-plan-extra-questions/{question_id}/values',
        operationId: 'addExtraQuestionValue',
        summary: 'Add a value to an extra question',
        description: 'Adds a new value option to an extra question.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                description: 'Question ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Extra question value created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/ExtraQuestionTypeValue')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Question not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function addExtraQuestionValue($summit_id, $question_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit, intval($question_id)];

        return $this->_add(
            function ($payload) {
                return ExtraQuestionTypeValueValidationRulesFactory::buildForAdd($payload);
            },
            function ($payload, $summit, $question_id) {
                return $this->selection_plan_extra_questions_service->addExtraQuestionValue
                (
                    $summit, intval($question_id), $payload
                );
            },
            ...$args);
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/{question_id}/values',
        operationId: 'addExtraQuestionValueBySelectionPlan',
        summary: 'Add a value to an extra question by selection plan',
        description: 'Adds a new value option to an extra question via selection plan context.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                description: 'Question ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Extra question value created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/ExtraQuestionTypeValue')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Question not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function addExtraQuestionValueBySelectionPlan($summit_id, $selection_plan_id, $question_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
        if (is_null($selection_plan)) return $this->error404();

        $args = [$summit, intval($question_id)];

        return $this->_add(
            function ($payload) {
                return ExtraQuestionTypeValueValidationRulesFactory::buildForAdd($payload);
            },
            function ($payload, $summit, $question_id) {
                return $this->selection_plan_extra_questions_service->addExtraQuestionValue
                (
                    $summit, intval($question_id), $payload
                );
            },
            ...$args);
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: '/api/v1/summits/{id}/selection-plan-extra-questions/{question_id}/values/{value_id}',
        operationId: 'updateExtraQuestionValue',
        summary: 'Update an extra question value',
        description: 'Updates a value option for an extra question.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                description: 'Question ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'value_id',
                in: 'path',
                required: true,
                description: 'Value ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Extra question value updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/ExtraQuestionTypeValue')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Question, or Value not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function updateExtraQuestionValue($summit_id, $question_id, $value_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit, intval($question_id)];

        return $this->_update($value_id, function ($payload) {
            return ExtraQuestionTypeValueValidationRulesFactory::buildForUpdate($payload);
        },
            function ($value_id, $payload, $summit, $question_id) {
                return $this->selection_plan_extra_questions_service->updateExtraQuestionValue
                (
                    $summit,
                    intval($question_id),
                    intval($value_id),
                    $payload
                );
            }, ...$args);
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $question_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/{question_id}/values/{value_id}',
        operationId: 'updateExtraQuestionValueBySelectionPlan',
        summary: 'Update an extra question value by selection plan',
        description: 'Updates a value option for an extra question via selection plan context.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                description: 'Question ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'value_id',
                in: 'path',
                required: true,
                description: 'Value ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Extra question value updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/ExtraQuestionTypeValue')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, Question, or Value not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function updateExtraQuestionValueBySelectionPlan($summit_id, $selection_plan_id, $question_id, $value_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
        if (is_null($selection_plan)) return $this->error404();

        $args = [$summit, intval($question_id)];

        return $this->_update($value_id, function ($payload) {
            return ExtraQuestionTypeValueValidationRulesFactory::buildForUpdate($payload);
        },
            function ($value_id, $payload, $summit, $question_id) {
                return $this->selection_plan_extra_questions_service->updateExtraQuestionValue
                (
                    $summit,
                    intval($question_id),
                    intval($value_id),
                    $payload
                );
            }, ...$args);
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Delete(
        path: '/api/v1/summits/{id}/selection-plan-extra-questions/{question_id}/values/{value_id}',
        operationId: 'deleteExtraQuestionValue',
        summary: 'Delete an extra question value',
        description: 'Deletes a value option from an extra question.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                description: 'Question ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'value_id',
                in: 'path',
                required: true,
                description: 'Value ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Extra question value deleted successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Question, or Value not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function deleteExtraQuestionValue($summit_id, $question_id, $value_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit, intval($question_id)];

        return $this->_delete($value_id, function ($value_id, $summit, $question_id) {
            $this->selection_plan_extra_questions_service->deleteExtraQuestionValue($summit, intval($question_id), intval($value_id));
        }
            , ...$args);
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $question_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Delete(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/{question_id}/values/{value_id}',
        operationId: 'deleteExtraQuestionValueBySelectionPlan',
        summary: 'Delete an extra question value by selection plan',
        description: 'Deletes a value option from an extra question via selection plan context.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'question_id',
                in: 'path',
                required: true,
                description: 'Question ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'value_id',
                in: 'path',
                required: true,
                description: 'Value ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Extra question value deleted successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, Question, or Value not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function deleteExtraQuestionValueBySelectionPlan($summit_id, $selection_plan_id, $question_id, $value_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
        if (is_null($selection_plan)) return $this->error404();

        $args = [$summit, intval($question_id)];

        return $this->_delete($value_id, function ($value_id, $summit, $question_id) {
            $this->selection_plan_extra_questions_service->deleteExtraQuestionValue($summit, intval($question_id), intval($value_id));
        }
            , ...$args);
    }

    /**
     * @param $id
     * @param $selection_plan_id
     * @param $event_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/event-types/{event_type_id}',
        operationId: 'attachEventType',
        summary: 'Attach an event type to a selection plan',
        description: 'Attaches an event type to a specific selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'event_type_id',
                in: 'path',
                required: true,
                description: 'Event Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Event type attached successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Event Type not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function attachEventType($id, $selection_plan_id, $event_type_id)
    {
        return $this->processRequest(function () use ($id, $selection_plan_id, $event_type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->attachEventTypeToSelectionPlan($summit, intval($selection_plan_id), intval($event_type_id));
            return $this->updated();
        });
    }

    /**
     * @param $id
     * @param $selection_plan_id
     * @param $event_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Delete(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/event-types/{event_type_id}',
        operationId: 'detachEventType',
        summary: 'Detach an event type from a selection plan',
        description: 'Detaches an event type from a specific selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'event_type_id',
                in: 'path',
                required: true,
                description: 'Event Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Event type detached successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Event Type not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function detachEventType($id, $selection_plan_id, $event_type_id)
    {
        return $this->processRequest(function () use ($id, $selection_plan_id, $event_type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->detachEventTypeFromSelectionPlan($summit, intval($selection_plan_id), intval($event_type_id));
            return $this->deleted();
        });
    }

    //Allowed Presentation Action Types

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-presentation-action-types',
        operationId: 'getAllowedPresentationActionTypes',
        summary: 'Get allowed presentation action types',
        description: 'Retrieves all allowed presentation action types for a selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadAllSummitData,
                SummitScopes::ReadSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'filter',
                in: 'query',
                required: false,
                description: 'Filter criteria. Supported filters: label (=@, ==), id (==)',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Sort order. Supported: order',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number',
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Items per page',
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Allowed presentation action types retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedPresentationActionTypesResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Selection Plan not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getAllowedPresentationActionTypes($summit_id, $selection_plan_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
        if (is_null($selection_plan)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'label' => ['=@', '=='],
                    'id' => ['=='],
                ];
            },
            function () {
                return [
                    'label' => 'sometimes|string',
                    'id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'order',
                ];
            },
            function ($filter) use ($summit, $selection_plan_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('selection_plan_id', $selection_plan_id));
                }
                return $filter;
            },
            function () {},
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->presentation_action_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
            [
                'selection_plan_id' => $selection_plan_id,
            ]
        );
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-presentation-action-types/{type_id}',
        operationId: 'getAllowedPresentationActionType',
        summary: 'Get a specific allowed presentation action type',
        description: 'Retrieves a specific allowed presentation action type for a selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'type_id',
                in: 'path',
                required: true,
                description: 'Presentation Action Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Presentation action type retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PresentationActionType')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Action Type not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getAllowedPresentationActionType($summit_id, $selection_plan_id, $type_id) {
        return $this->processRequest(function() use($summit_id, $selection_plan_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $presentation_action_type = $summit->getPresentationActionTypeById(intval($type_id));
            if (is_null($presentation_action_type)) return $this->error404();

            $allowed_presentation_action_type = $selection_plan->getPresentationActionType($presentation_action_type);
            if (is_null($allowed_presentation_action_type)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($allowed_presentation_action_type)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                [
                    'selection_plan_id' => $selection_plan_id,
                ]
            ));
        });
    }

    /**
     * @param $id
     * @param $selection_plan_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-presentation-action-types/{type_id}',
        operationId: 'addAllowedPresentationActionType',
        summary: 'Add an allowed presentation action type',
        description: 'Adds an allowed presentation action type to a selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'type_id',
                in: 'path',
                required: true,
                description: 'Presentation Action Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Presentation action type added successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PresentationActionType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Action Type not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function addAllowedPresentationActionType($id, $selection_plan_id, $type_id)
    {
        return $this->processRequest(function () use ($id, $selection_plan_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SummitSelectionPlanValidationRulesFactory::buildForAddPresentationActionType());

            $allowed_presentation_action_type = $this->selection_plan_service->upsertAllowedPresentationActionType(
                $summit, intval($selection_plan_id), intval($type_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($allowed_presentation_action_type)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                [
                    'selection_plan_id' => $selection_plan_id,
                ]
            ));
        });
    }

    /**
     * @param $id
     * @param $selection_plan_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-presentation-action-types/{type_id}',
        operationId: 'updateAllowedPresentationActionType',
        summary: 'Update an allowed presentation action type',
        description: 'Updates an allowed presentation action type for a selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'type_id',
                in: 'path',
                required: true,
                description: 'Presentation Action Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Presentation action type updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PresentationActionType')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Action Type not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function updateAllowedPresentationActionType($id, $selection_plan_id, $type_id)
    {
        return $this->processRequest(function () use ($id, $selection_plan_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SummitSelectionPlanValidationRulesFactory::buildForUpdatePresentationActionType());

            $allowed_presentation_action_type = $this->selection_plan_service->upsertAllowedPresentationActionType(
                $summit, intval($selection_plan_id), intval($type_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($allowed_presentation_action_type)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                [
                    'selection_plan_id' => $selection_plan_id,
                ]
            ));
        });
    }

    /**
     * @param $id
     * @param $selection_plan_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Delete(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-presentation-action-types/{type_id}',
        operationId: 'removeAllowedPresentationActionType',
        summary: 'Remove an allowed presentation action type',
        description: 'Removes an allowed presentation action type from a selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'type_id',
                in: 'path',
                required: true,
                description: 'Presentation Action Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Presentation action type removed successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Action Type not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function removeAllowedPresentationActionType($id, $selection_plan_id, $type_id)
    {
        return $this->processRequest(function () use ($id, $selection_plan_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->removeAllowedPresentationActionType($summit, intval($selection_plan_id), intval($type_id));
            return $this->deleted();
        });
    }

    /**
     * Allowed Members
     */

    /**
     * @param $id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-members',
        operationId: 'getAllowedMembers',
        summary: 'Get allowed members for a selection plan',
        description: 'Retrieves all members allowed for a selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadAllSummitData,
                SummitScopes::ReadSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'filter',
                in: 'query',
                required: false,
                description: 'Filter criteria. Supported filters: email (@@, =@)',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Sort order. Supported: email',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number',
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Items per page',
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Allowed members retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSelectionPlanAllowedMembersResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Selection Plan not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getAllowedMembers($id, $selection_plan_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
        if(is_null($selection_plan)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'email' => ['@@', '=@']
                ];
            },
            function () {
                return [
                    'email' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'email',
                ];
            },
            function ($filter) use ($summit, $selection_plan_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('id', $selection_plan_id));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Admin;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->repository->getAllAllowedMembersByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }
    /**
     * @param $id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-members',
        operationId: 'addAllowedMember',
        summary: 'Add an allowed member to a selection plan',
        description: 'Adds a member to the allowed members list of a selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        requestBody: new OA\RequestBody(
            description: 'Allowed member data',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AllowedMemberPayload')
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Allowed member added successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SelectionPlanAllowedMember')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Selection Plan not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation Error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function addAllowedMember($id, $selection_plan_id){
        return $this->processRequest(function () use ($id, $selection_plan_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(['email' => 'required|email'], true);

            $allowed_member = $this->selection_plan_service->addAllowedMember($summit, intval($selection_plan_id), $payload['email']);

            return $this->created(
                SerializerRegistry::getInstance()->getSerializer($allowed_member)->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    /**
     * @param $id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Delete(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-members/{allowed_member_id}',
        operationId: 'removeAllowedMember',
        summary: 'Remove an allowed member from a selection plan',
        description: 'Removes a member from the allowed members list of a selection plan.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'allowed_member_id',
                in: 'path',
                required: true,
                description: 'Allowed Member ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Allowed member removed successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, Selection Plan, or Allowed Member not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function removeAllowedMember($id, $selection_plan_id, $allowed_member_id){
        return $this->processRequest(function () use ($id, $selection_plan_id, $allowed_member_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->removeAllowedMember($summit, intval($selection_plan_id), $allowed_member_id);
            return $this->deleted();
        });
    }

    /**
     * @param LaravelRequest $request
     * @param $id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-members/csv',
        operationId: 'importAllowedMembers',
        summary: 'Import allowed members from CSV',
        description: 'Imports allowed members from a CSV file.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::WriteSummitData,
            ]]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Allowed members imported successfully'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or Selection Plan not found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'File param not set'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function importAllowedMembers(LaravelRequest $request, $id, $selection_plan_id)
    {

        return $this->processRequest(function () use ($request, $id, $selection_plan_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $this->selection_plan_service->importAllowedMembers($summit,$selection_plan_id, $file);

            return $this->ok();

        });
    }

    /**
     * @param $id
     * @return mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/selection-plans/me',
        operationId: 'getMySelectionPlans',
        summary: 'Get my selection plans',
        description: 'Retrieves selection plans for the current authenticated user.',
        tags: ['Selection Plans'],
        security: [
            ['selection_plans_oauth2' => [
                SummitScopes::ReadAllSummitData,
                SummitScopes::ReadSummitData,
            ]]
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID or slug',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Selection plans retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSelectionPlansResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    public function getMySelectionPlans($id){
        return $this->processRequest(function() use($id){

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $filter = new Filter();
            $filter->addFilterCondition(FilterParser::buildFilter('summit_id','==', intval($id)));
            $filter->addFilterCondition(FilterParser::buildFilter('is_enabled','==',true));
            $filter->addFilterCondition(FilterParser::buildFilter('allowed_member_email','==', $current_member->getEmail()));

            $page = $this->repository->getAllByPage(new PagingInfo(1,PaginationValidationRules::PerPageMax), $filter);

            return $this->ok($page->toArray(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }
}
