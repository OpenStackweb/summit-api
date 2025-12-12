<?php namespace App\Http\Controllers;
/**
 * Copyright 2016 OpenStack Foundation
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

use App\Http\Exceptions\HTTP403ForbiddenException;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\IStatsConstants;
use App\Models\Foundation\Summit\Registration\IBuildDefaultPaymentGatewayProfileStrategy;
use App\ModelSerializers\SerializerUtils;
use App\Security\SummitScopes;
use App\Utils\FilterUtils;
use Doctrine\ORM\EntityNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ConfirmationExternalOrderRequest;
use models\summit\IEventFeedbackRepository;
use models\summit\ISpeakerRepository;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\Summit;
use ModelSerializers\ISerializerTypeSelector;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SummitQREncKeySerializer;
use services\model\ISummitService;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;
use utils\Order;
use utils\OrderElement;
use utils\PagingInfo;
use OpenApi\Attributes as OA;

final class OAuth2SummitApiController extends OAuth2ProtectedController
{

    /**
     * @var IBuildDefaultPaymentGatewayProfileStrategy
     */
    private $build_default_payment_gateway_profile_strategy;

    /**
     * @var ISummitService
     */
    private $summit_service;

    /**
     * @var ISpeakerRepository
     */
    private $speaker_repository;

    /**
     * @var ISummitEventRepository
     */
    private $event_repository;

    /**
     * @var IEventFeedbackRepository
     */
    private $event_feedback_repository;

    /**
     * @var ISerializerTypeSelector
     */
    private $serializer_type_selector;

    /**
     * OAuth2SummitApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitEventRepository $event_repository
     * @param ISpeakerRepository $speaker_repository
     * @param IEventFeedbackRepository $event_feedback_repository
     * @param ISummitService $summit_service
     * @param ISerializerTypeSelector $serializer_type_selector
     * @param IBuildDefaultPaymentGatewayProfileStrategy $build_default_payment_gateway_profile_strategy
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository                          $summit_repository,
        ISummitEventRepository                     $event_repository,
        ISpeakerRepository                         $speaker_repository,
        IEventFeedbackRepository                   $event_feedback_repository,
        ISummitService                             $summit_service,
        ISerializerTypeSelector                    $serializer_type_selector,
        IBuildDefaultPaymentGatewayProfileStrategy $build_default_payment_gateway_profile_strategy,
        IResourceServerContext                     $resource_server_context
    )
    {
        parent::__construct($resource_server_context);

        $this->repository = $summit_repository;
        $this->speaker_repository = $speaker_repository;
        $this->event_repository = $event_repository;
        $this->event_feedback_repository = $event_feedback_repository;
        $this->serializer_type_selector = $serializer_type_selector;
        $this->build_default_payment_gateway_profile_strategy = $build_default_payment_gateway_profile_strategy;
        $this->summit_service = $summit_service;
    }

    use ParametrizedGetAll;

    use RequestProcessor;

    use ParseAndGetPaginationParams;

    #[OA\Get(
            path: "/api/v1/summits",
            operationId: "getSummits",
            summary: "Get summits list",
            tags: ["Summits"],
            parameters: [
                new OA\Parameter(
                    name: "page",
                    description: "Page number",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", default: 1)
                ),
                new OA\Parameter(
                    name: "per_page",
                    description: "Items per page",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", default: 10)
                ),
                new OA\Parameter(
                    name: "filter",
                    description: "Filter criteria",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(ref: "#/components/schemas/SummitCollection")
                ),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            ],
            security: [["summit_oauth2" => [
                SummitScopes::ReadSummitData,
                SummitScopes::ReadAllSummitData
            ]]]
        ),
        OA\Get(
            path: "/api/public/v1/summits",
            operationId: "getSummitsPublic",
            summary: "Get summits list (public)",
            tags: ["Summits (Public)"],
            parameters: [
                new OA\Parameter(
                    name: "page",
                    description: "Page number",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", default: 1)
                ),
                new OA\Parameter(
                    name: "per_page",
                    description: "Items per page",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", default: 10)
                ),
                new OA\Parameter(
                    name: "filter",
                    description: "Filter criteria",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(ref: "#/components/schemas/SummitCollection")
                ),
            ]
        )
    ]
    public function getSummits()
    {
        $current_member = $this->resource_server_context->getCurrentUser();

        if (!is_null($current_member) &&
            !$current_member->isAdmin() &&
            !$current_member->hasAllowedSummits()) {
            return $this->error403(['message' => sprintf("Member %s has not permission for any Summit", $current_member->getId())]);
        }

        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '==', '@@'],
                    'start_date' => ['==', '<', '>', '<=', '>=','[]'],
                    'end_date' => ['==', '<', '>', '<=', '>=','[]'],
                    'registration_begin_date' => ['==', '<', '>', '<=', '>=','[]'],
                    'registration_end_date' => ['==', '<', '>', '<=', '>=','[]'],
                    'ticket_types_count' => ['==', '<', '>', '<=', '>=', '<>'],
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|required|string',
                    'start_date' => 'sometimes|required|date_format:U|epoch_seconds',
                    'end_date' => 'sometimes|required_with:start_date|date_format:U|epoch_seconds|after:start_date',
                    'registration_begin_date' => 'sometimes|required|date_format:U|epoch_seconds',
                    'registration_end_date' => 'sometimes|required_with:start_date|date_format:U|epoch_seconds|after:registration_begin_date',
                    'ticket_types_count' => 'sometimes|required|integer'
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'start_date',
                    'registration_begin_date'
                ];
            },
            function ($filter) use ($current_member) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('mark_as_deleted', '0'));
                    if(!is_null($current_member)){
                        if($current_member->isAdmin()) return $filter;
                        $allowed_summits = $current_member->getAllAllowedSummitsIds();
                        // allowed summits are empty dummy value
                        if(!count($allowed_summits)) $allowed_summits[] = 0;
                        $filter->addFilterCondition
                        (
                            FilterElement::makeEqual
                            (
                                'summit_id',
                                $allowed_summits,
                                "OR"

                            )
                        );
                    }
                }
                return $filter;
            },
            function () {
                return $this->serializer_type_selector->getSerializerType();
            },
            function () {
                return new Order([
                    OrderElement::buildAscFor("start_date"),
                ]);
            },
            function () {
                return PHP_INT_MAX;
            },
            null,
            [
                'build_default_payment_gateway_profile_strategy' => $this->build_default_payment_gateway_profile_strategy
            ]
        );
    }

    #[OA\Get(
            path: "/api/v1/summits/all",
            operationId: "getAllSummits",
            summary: "Get all summits",
            tags: ["Summits"],
            parameters: [
                new OA\Parameter(
                    name: "page",
                    description: "Page number",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", default: 1)
                ),
                new OA\Parameter(
                    name: "per_page",
                    description: "Items per page",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", default: 10)
                ),
                new OA\Parameter(
                    name: "filter",
                    description: "Filter criteria: name, start_date, end_date, registration_begin_date, registration_end_date, ticket_types_count, submission_begin_date, submission_end_date, voting_begin_date, voting_end_date, selection_begin_date, selection_end_date, selection_plan_enabled, begin_allow_booking_date, end_allow_booking_date",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
                new OA\Parameter(
                    name: "expand",
                    description: "Relations to expand: featured_speakers, schedule, type, locations",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
                new OA\Parameter(
                    name: "relations",
                    description: "Relations to add: ticket_types, locations, wifi_connections, selection_plans, meeting_booking_room_allowed_attributes, summit_sponsors, order_extra_questions, tax_types, payment_profiles, email_flows_events, summit_documents, featured_speakers, dates_with_events, presentation_action_types, schedule_settings, badge_view_types, lead_report_settings, badge_types, badge_features_types, badge_access_level_types, dates_with_events, supported_currencies",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(ref: "#/components/schemas/SummitCollection")
                ),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            ],
            security: [["summit_oauth2" => [
                SummitScopes::ReadAllSummitData
            ]]]
        ),
        OA\Get(
            path: "/api/public/v1/summits/all",
            operationId: "getAllSummitsPublic",
            summary: "Get all summits (public)",
            tags: ["Summits (Public)"],
            parameters: [
                new OA\Parameter(
                    name: "page",
                    description: "Page number",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", default: 1)
                ),
                new OA\Parameter(
                    name: "per_page",
                    description: "Items per page",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", default: 10)
                ),
                new OA\Parameter(
                    name: "filter",
                    description: "Filter criteria",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
                new OA\Parameter(
                    name: "expand",
                    description: "Relations to expand",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(ref: "#/components/schemas/SummitCollection")
                ),
            ]
        )
    ]
    public function getAllSummits()
    {

        $current_member = $this->resource_server_context->getCurrentUser();

        if (!is_null($current_member) &&
            !$current_member->isAdmin() &&
            !$current_member->hasAllowedSummits()) {
            return $this->error403(
                [
                    'message' => sprintf("Member %s has not permission for any Summit", $current_member->getId())]);
        }

        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '==', '@@'],
                    'start_date' => ['==', '<', '>', '<=', '>=','[]'],
                    'end_date' => ['==', '<', '>', '<=', '>=','[]'],
                    'registration_begin_date' => ['==', '<', '>', '<=', '>=','[]'],
                    'registration_end_date' => ['==', '<', '>', '<=', '>=','[]'],
                    'ticket_types_count' => ['==', '<', '>', '<=', '>=', '<>'],
                    'submission_begin_date' => ['==', '<', '>', '<=', '>=','[]'],
                    'submission_end_date' => ['==', '<', '>', '<=', '>=','[]'],
                    'voting_begin_date' => ['==', '<', '>', '<=', '>=','[]'],
                    'voting_end_date' => ['==', '<', '>', '<=', '>=','[]'],
                    'selection_begin_date' => ['==', '<', '>', '<=', '>=','[]'],
                    'selection_end_date' => ['==', '<', '>', '<=', '>=','[]'],
                    'selection_plan_enabled' => ['=='],
                    'begin_allow_booking_date' => ['==', '<', '>', '<=', '>=','[]'],
                    'end_allow_booking_date' => ['==', '<', '>', '<=', '>=','[]']
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|required|string',
                    'start_date' => 'sometimes|required|date_format:U|epoch_seconds',
                    'end_date' => 'sometimes|required_with:start_date|date_format:U|epoch_seconds|after:start_date',
                    'registration_begin_date' => 'sometimes|required|date_format:U|epoch_seconds',
                    'registration_end_date' => 'sometimes|required_with:start_date|date_format:U|epoch_seconds|after:registration_begin_date',
                    'ticket_types_count' => 'sometimes|required|integer',
                    'submission_begin_date' => 'sometimes|required|date_format:U|epoch_seconds',
                    'submission_end_date' => 'sometimes|required_with:submission_begin_date|date_format:U|epoch_seconds',
                    'voting_begin_date' => 'sometimes|required|date_format:U|epoch_seconds',
                    'voting_end_date' => 'sometimes|required_with:voting_begin_date|date_format:U|epoch_seconds',
                    'selection_begin_date' => 'sometimes|required|date_format:U|epoch_seconds',
                    'selection_end_date' => 'sometimes|required_with:selection_begin_date|date_format:U|epoch_seconds',
                    'selection_plan_enabled' => 'sometimes|required|boolean',
                    'begin_allow_booking_date' =>'sometimes|required|date_format:U|epoch_seconds',
                    'end_allow_booking_date' => 'sometimes|required|date_format:U|epoch_seconds',
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'start_date',
                    'registration_begin_date'
                ];
            },
            function ($filter) use ($current_member) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('mark_as_deleted', '0'));
                    if(!is_null($current_member)){
                        if($current_member->isAdmin()) return $filter;
                        $allowed_summits = $current_member->getAllAllowedSummitsIds();
                        // is allowed summits are empty, add dummy value
                        if(!count($allowed_summits)) $allowed_summits[] = 0;
                        $filter->addFilterCondition
                        (
                            FilterElement::makeEqual
                            (
                                'summit_id',
                                $allowed_summits,
                                "OR"

                            )
                        );
                    }
                }
                return $filter;
            },
            function () {
                return $this->serializer_type_selector->getSerializerType();
            }
            ,
            function () {
                return new Order([
                    OrderElement::buildAscFor("start_date"),
                ]);
            },
            function () {
                return PHP_INT_MAX;
            },
            null,
            [
                'build_default_payment_gateway_profile_strategy' => $this->build_default_payment_gateway_profile_strategy
            ]
        );
    }

    #[OA\Get(
            path: "/api/v1/summits/{id}",
            operationId: "getSummit",
            summary: "Get summit by ID or slug",
            tags: ["Summits"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID or 'current'",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "string")
                ),
                new OA\Parameter(
                    name: "expand",
                    description: "Relations to expand: featured_speakers, schedule, type, locations",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
                new OA\Parameter(
                    name: "relations",
                    description: "Relations to add: ticket_types, locations, wifi_connections, selection_plans, meeting_booking_room_allowed_attributes, summit_sponsors, order_extra_questions, tax_types, payment_profiles, email_flows_events, summit_documents, featured_speakers, dates_with_events, presentation_action_types, schedule_settings, badge_view_types, lead_report_settings, badge_types, badge_features_types, badge_access_level_types, dates_with_events, supported_currencies",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(ref: "#/components/schemas/Summit")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            ],
            security: [["summit_oauth2" => [
                SummitScopes::ReadSummitData,
                SummitScopes::ReadAllSummitData
            ]]]
        ),
        OA\Get(
            path: "/api/public/v1/summits/{id}",
            operationId: "getSummitPublic",
            summary: "Get summit by ID or slug (public)",
            tags: ["Summits (Public)"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID or 'current'",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "string")
                ),
                new OA\Parameter(
                    name: "expand",
                    description: "Relations to expand",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(ref: "#/components/schemas/Summit")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            ]
        ),
        OA\Get(
            path: "/api/v2/summits/{id}",
            operationId: "getSummitV2",
            summary: "Get summit by ID or slug (v2)",
            tags: ["Summits"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID or 'current'",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "string")
                ),
                new OA\Parameter(
                    name: "expand",
                    description: "Relations to expand",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(ref: "#/components/schemas/Summit")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            ]
        )
    ]
    public function getSummit($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            Log::debug(sprintf("OAuth2SummitApiController::getSummit %s", $summit_id));
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)
                ->find($summit_id, ['event_types','badge_features_types','badge_features_types.image','presentation_categories','ticket_types','locations','category_groups','badge_access_level_types']);
            if (!$summit instanceof Summit || $summit->isDeleting()) return $this->error404();
            $current_member = $this->resource_server_context->getCurrentUser();

            $serializer_type = SerializerRegistry::SerializerType_Public;

            if(!is_null($current_member) && $current_member->isSummitAllowed($summit))
                $serializer_type = SerializerRegistry::SerializerType_Private;

            Log::debug(sprintf("OAuth2SummitApiController::getSummit %s serializer_type %s", $summit_id, $serializer_type));
            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($summit, $serializer_type)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        [
                            'build_default_payment_gateway_profile_strategy' => $this->build_default_payment_gateway_profile_strategy
                        ]
                    )
            );
        });
    }

    #[OA\Get(
            path: "/api/v1/summits/current",
            operationId: "getAllCurrentSummit",
            summary: "Get current summit",
            tags: ["Summits"],
            parameters: [
                new OA\Parameter(
                    name: "expand",
                    description: "Relations to expand: featured_speakers, schedule, type, locations",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
                new OA\Parameter(
                    name: "relations",
                    description: "Relations to add: ticket_types, locations, wifi_connections, selection_plans, meeting_booking_room_allowed_attributes, summit_sponsors, order_extra_questions, tax_types, payment_profiles, email_flows_events, summit_documents, featured_speakers, dates_with_events, presentation_action_types, schedule_settings, badge_view_types, lead_report_settings, badge_types, badge_features_types, badge_access_level_types, dates_with_events, supported_currencies",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(ref: "#/components/schemas/Summit")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            ],
            security: [["summit_oauth2" => [
                SummitScopes::ReadSummitData,
                SummitScopes::ReadAllSummitData
            ]]]
        ),
        OA\Get(
            path: "/api/public/v1/summits/all/current",
            operationId: "getAllCurrentSummitPublic",
            summary: "Get current summit (public)",
            tags: ["Summits (Public)"],
            parameters: [
                new OA\Parameter(
                    name: "expand",
                    description: "Relations to expand",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(ref: "#/components/schemas/Summit")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            ]
        )
    ]
    /**
     * @return JsonResponse|mixed
     */
    public function getAllCurrentSummit()
    {
        return $this->processRequest(function () {
            $summit = $this->repository->getCurrent();
            if (is_null($summit)) return $this->error404();
            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isAdmin() && !$current_member->hasPermissionFor($summit))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);
            $serializer_type = $this->serializer_type_selector->getSerializerType();
            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($summit, $serializer_type)
                    ->serialize(
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        [
                            'build_default_payment_gateway_profile_strategy' => $this->build_default_payment_gateway_profile_strategy
                        ])
            );
        });
    }

    private function getSummitOr404($id):Summit{
        $summit = $this->repository->getById(intval($id));
        if (is_null($summit))
            $summit = $this->repository->getBySlug(trim($id));

        if (!$summit instanceof Summit || $summit->isDeleting())
            throw new EntityNotFoundException("Summit not Found.");
        return $summit;
    }

    public function getAllSummitByIdOrSlugPublic($id){
        return $this->processRequest(function () use ($id) {
            $summit = $this->getSummitOr404($id);
            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($summit)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        [
                            'build_default_payment_gateway_profile_strategy' => $this->build_default_payment_gateway_profile_strategy
                        ])
            );
        });
    }

    #[OA\Get(
            path: "/api/v1/summits/all/{id}/registration-stats",
            operationId: "getAllSummitByIdOrSlugRegistrationStats",
            summary: "Get summit registration statistics",
            tags: ["Summits", "Statistics"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID or slug",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "string")
                ),
                new OA\Parameter(
                    name: "filter",
                    description: "Filter by start_date and end_date",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(type: "object")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
                new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::ReadAllSummitData
            ]]]
        )
    ]
    /**
     * @param $id
     * @return JsonResponse|mixed
     */
    public function getAllSummitByIdOrSlug($id)
    {
        return $this->processRequest(function () use ($id) {
            $summit = $this->getSummitOr404($id);

            $current_member = $this->resource_server_context->getCurrentUser();

            if (!is_null($current_member) && !$current_member->isAdmin() && !$current_member->hasPermissionFor($summit))
                throw new HTTP403ForbiddenException
                (
                    sprintf
                    (
                        "Member %s has not permission for this Summit", $current_member->getId()
                    )
                );

            $serializer_type = $this->serializer_type_selector->getSerializerType();

            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($summit, $serializer_type)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        [
                            'build_default_payment_gateway_profile_strategy' => $this->build_default_payment_gateway_profile_strategy
                        ])
            );
        });
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getAllSummitByIdOrSlugRegistrationStats($id)
    {
        return $this->processRequest(function () use ($id) {
            $summit = $this->repository->getById(intval($id));
            if (is_null($summit))
                $summit = $this->repository->getBySlug(trim($id));

            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isAdmin() && !$current_member->hasPermissionFor($summit))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::get('filter'), [
                    'start_date' => [ '>='],
                    'end_date' => ['<='],
                ]);
            }

            if (is_null($filter)) $filter = new Filter();

            $filter->validate([
                'start_date' => 'sometimes|required|date_format:U|epoch_seconds',
                'end_date' => 'sometimes|required_with:start_date|date_format:U|epoch_seconds|after:start_date',
            ]);

            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($summit, SerializerRegistry::SerializerType_Admin_Registration_Stats)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        ['filter' => $filter]
                    )
            );
        });
    }

    #[OA\Get(
            path: "/api/v1/summits/all/{id}/registration-stats/check-ins",
            operationId: "getAttendeesCheckinsOverTimeStats",
            summary: "Get attendees check-ins statistics",
            tags: ["Summits", "Statistics"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
                new OA\Parameter(
                    name: "group_by",
                    description: "Group by criteria",
                    in: "query",
                    required: true,
                    schema: new OA\Schema(type: "string")
                ),
                new OA\Parameter(
                    name: "page",
                    description: "Page number",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", default: 1)
                ),
                new OA\Parameter(
                    name: "per_page",
                    description: "Items per page",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", default: 10)
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(type: "array", items: new OA\Items(type: "object"))
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
                new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::ReadAllSummitData
            ]]]
        )
    ]
    public function getAttendeesCheckinsOverTimeStats($id)
    {
        return $this->processRequest(function () use ($id) {
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($id);
            if (is_null($summit))
                $summit = $this->repository->getBySlug(trim($id));

            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isSummitAllowed($summit))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $group_by = Request::get('group_by');
            if(!in_array($group_by, IStatsConstants::AttendeesCheckinsAllowedGroupBy))
                throw new ValidationException(
                    "Invalid group by criteria. Valid ones are ".join(',', IStatsConstants::AttendeesCheckinsAllowedGroupBy));

            list($page, $per_page) = self::getPaginationParams();

            $filter = self::getFilter(
                function () {
                    return [
                        'start_date' => ['>='],
                        'end_date' => ['<='],
                    ];
                },
                function () {
                    return [
                        'start_date' => 'sometimes|required|date_format:U|epoch_seconds',
                        'end_date' => 'sometimes|required_with:start_date|date_format:U|epoch_seconds|after:start_date',
                    ];
                });

            list($start_date, $end_date) = FilterUtils::parseDateRangeUTC($filter);

            $response = $summit->getAttendeesCheckinsGroupedBy($group_by, new PagingInfo($page, $per_page), $start_date, $end_date);

            return $this->ok($response->toArray());
        });
    }

    #[OA\Get(
            path: "/api/v1/summits/all/{id}/registration-stats/purchased-tickets",
            operationId: "getPurchasedTicketsOverTimeStats",
            summary: "Get purchased tickets statistics",
            tags: ["Summits", "Statistics"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
                new OA\Parameter(
                    name: "group_by",
                    description: "Group by criteria",
                    in: "query",
                    required: true,
                    schema: new OA\Schema(type: "string")
                ),
                new OA\Parameter(
                    name: "page",
                    description: "Page number",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", default: 1)
                ),
                new OA\Parameter(
                    name: "per_page",
                    description: "Items per page",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", default: 10)
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(type: "array", items: new OA\Items(type: "object"))
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
                new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::ReadAllSummitData
            ]]]
        )
    ]
    public function getPurchasedTicketsOverTimeStats($id)
    {
        return $this->processRequest(function () use ($id) {
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($id);
            if (is_null($summit))
                $summit = $this->repository->getBySlug(trim($id));

            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isSummitAllowed($summit))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $group_by = Request::get('group_by');
            if(!in_array($group_by, IStatsConstants::AttendeesCheckinsAllowedGroupBy))
                throw new ValidationException(
                    "Invalid group by criteria. Valid ones are ".join(',', IStatsConstants::AttendeesCheckinsAllowedGroupBy));

            list($page, $per_page) = self::getPaginationParams();

            $filter = self::getFilter(
                function () {
                    return [
                        'start_date' => ['>='],
                        'end_date' => ['<='],
                    ];
                },
                function () {
                    return [
                        'start_date' => 'sometimes|required|date_format:U|epoch_seconds',
                        'end_date' => 'sometimes|required_with:start_date|date_format:U|epoch_seconds|after:start_date',
                    ];
                });

            list($start_date, $end_date) = FilterUtils::parseDateRangeUTC($filter);

            $response = $summit->getPurchasedTicketsGroupedBy($group_by, new PagingInfo($page, $per_page), $start_date, $end_date);

            return $this->ok($response->toArray());
        });
    }

    #[OA\Post(
            path: "/api/v1/summits",
            operationId: "addSummit",
            summary: "Create a new summit",
            tags: ["Summits"],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(ref: "#/components/schemas/Summit")
            ),
            responses: [
                new OA\Response(
                    response: Response::HTTP_CREATED,
                    description: "Summit created",
                    content: new OA\JsonContent(ref: "#/components/schemas/Summit")
                ),
                new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::WriteSummitData
            ]]]
        )
    ]
    public function addSummit()
    {
        return $this->processRequest(function () {

            $payload = $this->getJsonPayload(SummitValidationRulesFactory::buildForAdd(), true, [
                'slug.required' => 'A Slug is required.',
                'schedule_start_date.before_or_equal' => 'Show on schedule page needs to be after the start of the Show And Before of the Show End.',
            ]);

            $summit = $this->summit_service->addSummit($payload);
            $serializer_type = $this->serializer_type_selector->getSerializerType();
            return $this->created(SerializerRegistry::getInstance()->getSerializer($summit, $serializer_type)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
            path: "/api/v1/summits/{id}",
            operationId: "updateSummit",
            summary: "Update summit",
            tags: ["Summits"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(ref: "#/components/schemas/Summit")
            ),
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(ref: "#/components/schemas/Summit")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
                new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::WriteSummitData
            ]]]
        )
    ]
    public function updateSummit($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $payload = $this->getJsonPayload(SummitValidationRulesFactory::buildForUpdate(), true);

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isAdmin() && !$current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $summit = $this->summit_service->updateSummit($summit_id, $payload);
            $serializer_type = $this->serializer_type_selector->getSerializerType();
            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($summit, $serializer_type)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Delete(
            path: "/api/v1/summits/{id}",
            operationId: "deleteSummit",
            summary: "Delete summit",
            tags: ["Summits"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
            ],
            responses: [
                new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Summit deleted"),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::WriteSummitData
            ]]]
        )
    ]
    public function deleteSummit($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $this->summit_service->deleteSummit($summit_id);

            return $this->deleted();
        });
    }

    #[OA\Get(
            path: "/api/v1/summits/{id}/external-orders/{external_order_id}",
            operationId: "getExternalOrder",
            summary: "Get external order",
            tags: ["Summits", "External Orders"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
                new OA\Parameter(
                    name: "external_order_id",
                    description: "External Order ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "string")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(type: "object")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Order not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            ],
            security: [["summit_oauth2" => []]]
        )
    ]
    public function getExternalOrder($summit_id, $external_order_id)
    {
        return $this->processRequest(function () use ($summit_id, $external_order_id) {
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $order = $this->summit_service->getExternalOrder($summit, $external_order_id);
            return $this->ok($order);
        });
    }

    #[OA\Post(
            path: "/api/v1/summits/{id}/external-orders/{external_order_id}/external-attendees/{external_attendee_id}/confirm",
            operationId: "confirmExternalOrderAttendee",
            summary: "Confirm external order attendee",
            tags: ["Summits", "External Orders"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
                new OA\Parameter(
                    name: "external_order_id",
                    description: "External Order ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "string")
                ),
                new OA\Parameter(
                    name: "external_attendee_id",
                    description: "External Attendee ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "string")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(type: "object")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            ],
            security: [["summit_oauth2" => []]]
        )
    ]
    public function confirmExternalOrderAttendee($summit_id, $external_order_id, $external_attendee_id)
    {
        return $this->processRequest(function () use ($summit_id, $external_order_id, $external_attendee_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();
            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member))
                throw new \HTTP401UnauthorizedException;

            $attendee = $this->summit_service->confirmExternalOrderAttendee
            (
                new ConfirmationExternalOrderRequest
                (
                    $summit,
                    $current_member->getId(),
                    trim($external_order_id),
                    trim($external_attendee_id)
                )
            );

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($attendee)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->repository;
    }

    #[OA\Post(
            path: "/api/v1/summits/{id}/logo",
            operationId: "addSummitLogo",
            summary: "Add summit logo",
            tags: ["Summits", "Media"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\MediaType(
                    mediaType: "multipart/form-data",
                    schema: new OA\Schema(
                        required: ["file"],
                        properties: [
                            new OA\Property(property: "file", type: "string", format: "binary", description: "Logo file")
                        ]
                    )
                )
            ),
            responses: [
                new OA\Response(
                    response: Response::HTTP_CREATED,
                    description: "Logo created",
                    content: new OA\JsonContent(type: "object")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
                new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::WriteSummitData
            ]]]
        )
    ]
    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @return JsonResponse|mixed
     */
    public function addSummitLogo(LaravelRequest $request, $summit_id)
    {
        return $this->processRequest(function () use ($request, $summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isAdmin() && !$current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $photo = $this->summit_service->addSummitLogo($summit_id, $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($photo)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Delete(
            path: "/api/v1/summits/{id}/logo",
            operationId: "deleteSummitLogo",
            summary: "Delete summit logo",
            tags: ["Summits", "Media"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
            ],
            responses: [
                new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Logo deleted"),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::WriteSummitData
            ]]]
        )
    ]
    public function deleteSummitLogo($summit_id)
    {
       return $this->processRequest(function() use($summit_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isAdmin() && !$current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $this->summit_service->deleteSummitLogo($summit_id);

            return $this->deleted();

        });
    }

    #[OA\Post(
            path: "/api/v1/summits/{id}/logo/secondary",
            operationId: "addSummitSecondaryLogo",
            summary: "Add summit secondary logo",
            tags: ["Summits", "Media"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\MediaType(
                    mediaType: "multipart/form-data",
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: "file", type: "string", format: "binary")
                        ]
                    )
                )
            ),
            responses: [
                new OA\Response(
                    response: Response::HTTP_CREATED,
                    description: "Secondary logo created",
                    content: new OA\JsonContent(type: "object")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::WriteSummitData
            ]]]
        )
    ]
    public function addSummitSecondaryLogo(LaravelRequest $request, $summit_id)
    {
        return $this->processRequest(function () use ($request, $summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isAdmin() && !$current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $photo = $this->summit_service->addSummitSecondaryLogo($summit_id, $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($photo)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Delete(
            path: "/api/v1/summits/{id}/logo/secondary",
            operationId: "deleteSummitSecondaryLogo",
            summary: "Delete summit secondary logo",
            tags: ["Summits", "Media"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
            ],
            responses: [
                new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Secondary logo deleted"),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::WriteSummitData
            ]]]
        )
    ]
    public function deleteSummitSecondaryLogo($summit_id)
    {
        return $this->processRequest(function() use($summit_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && !$current_member->isAdmin() && !$current_member->hasPermissionForOnGroup($summit, IGroup::SummitAdministrators))
                return $this->error403(['message' => sprintf("Member %s has not permission for this Summit", $current_member->getId())]);

            $this->summit_service->deleteSummitSecondaryLogo($summit_id);

            return $this->deleted();

        });
    }

    #[OA\Post(
            path: "/api/v1/summits/{id}/featured-speakers/{speaker_id}",
            operationId: "addFeatureSpeaker",
            summary: "Add featured speaker to summit",
            tags: ["Summits", "Featured Speakers"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
                new OA\Parameter(
                    name: "speaker_id",
                    description: "Speaker ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
            ],
            responses: [
                new OA\Response(response: Response::HTTP_OK, description: "Speaker added as featured"),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit or speaker not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::WriteSummitData
            ]]]
        )
    ]
    /**
     * @param $summit_id
     * @param $speaker_id
     * @return JsonResponse|mixed
     */
    public function addFeatureSpeaker($summit_id, $speaker_id)
    {
       return $this->processRequest(function () use($summit_id, $speaker_id){

            $this->summit_service->addFeaturedSpeaker(intval($summit_id), intval($speaker_id));

            return $this->updated();

        });
    }

    #[OA\Put(
            path: "/api/v1/summits/{id}/featured-speakers/{speaker_id}",
            operationId: "updateFeatureSpeaker",
            summary: "Update featured speaker order",
            tags: ["Summits", "Featured Speakers"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
                new OA\Parameter(
                    name: "speaker_id",
                    description: "Speaker ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["order"],
                    properties: [
                        new OA\Property(property: "order", type: "integer", minimum: 1, description: "Display order")
                    ]
                )
            ),
            responses: [
                new OA\Response(response: Response::HTTP_OK, description: "Featured speaker updated"),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit or speaker not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::WriteSummitData
            ]]]
        )
    ]
    /**
     * @param $summit_id
     * @param $speaker_id
     * @return JsonResponse|mixed
     */
    public function updateFeatureSpeaker($summit_id, $speaker_id)
    {
       return $this->processRequest(function() use($summit_id, $speaker_id){

            $payload = $this->getJsonPayload([
                'order' => 'required|integer|min:1',
            ], true);

            $this->summit_service->updateFeaturedSpeaker(intval($summit_id), intval($speaker_id), $payload);

            return $this->updated();

        });
    }

    #[OA\Delete(
            path: "/api/v1/summits/{id}/featured-speakers/{speaker_id}",
            operationId: "removeFeatureSpeaker",
            summary: "Remove featured speaker from summit",
            tags: ["Summits", "Featured Speakers"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
                new OA\Parameter(
                    name: "speaker_id",
                    description: "Speaker ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
            ],
            responses: [
                new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Featured speaker removed"),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit or speaker not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::WriteSummitData
            ]]]
        )
    ]
    /**
     * @param $summit_id
     * @param $speaker_id
     * @return JsonResponse|mixed
     */
    public function removeFeatureSpeaker($summit_id, $speaker_id)
    {
        return $this->processRequest(function() use($summit_id, $speaker_id){

            $this->summit_service->removeFeaturedSpeaker(intval($summit_id), intval($speaker_id));

            return $this->deleted();

        });
    }

    #[OA\Get(
            path: "/api/v1/summits/{id}/featured-speakers",
            operationId: "getAllFeatureSpeaker",
            summary: "Get all featured speakers for summit",
            tags: ["Summits", "Featured Speakers"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
                new OA\Parameter(
                    name: "page",
                    description: "Page number",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", default: 1)
                ),
                new OA\Parameter(
                    name: "per_page",
                    description: "Items per page",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", default: 10)
                ),
                new OA\Parameter(
                    name: "filter",
                    description: "Filter by: first_name, last_name, email, id, full_name, member_id, member_user_external_id",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
                new OA\Parameter(
                    name: "order",
                    description: "Order by: first_name, last_name, id, email, order",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "string")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(type: "object")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::ReadAllSummitData,
                SummitScopes::ReadSummitData
            ]]]
        )
    ]
    /**
     * @param $summit_id
     * @return JsonResponse|mixed
     */
    public function getAllFeatureSpeaker($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->getRepository(), $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'first_name' => ['=@', '=='],
                    'last_name' => ['=@', '=='],
                    'email' => ['=@', '=='],
                    'id' => ['=='],
                    'full_name' => ['=@', '=='],
                    'member_id' => ['=='],
                    'member_user_external_id' => ['=='],
                ];
            },
            function () {
                return [
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'email' => 'sometimes|string',
                    'id' => 'sometimes|integer',
                    'full_name' => 'sometimes|string',
                    'member_id' => 'sometimes|integer',
                    'member_user_external_id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'first_name',
                    'last_name',
                    'id',
                    'email',
                    'order',
                ];
            },
            function ($filter) use ($summit) {
                return $filter;
            },
            function () {
                return $this->serializer_type_selector->getSerializerType();
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->speaker_repository->getFeaturedSpeakers
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
            [
                'summit_id' => $summit_id,
                'published' => true,
                'summit' => $summit
            ]
        );
    }

    #[OA\Put(
            path: "/api/v1/summits/{id}/qr-codes/all/enc-key",
            operationId: "generateQREncKey",
            summary: "Generate QR encryption key",
            tags: ["Summits", "QR Code"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_CREATED,
                    description: "QR encryption key generated",
                    content: new OA\JsonContent(type: "object")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::SummitAdministrators,
                    IGroup::Administrators,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::WriteSummitData
            ]]]
        )
    ]
    /**
     * @param $summit_id
     * @return JsonResponse|mixed
     */
    public function generateQREncKey($summit_id)
    {
        return $this->processRequest(function() use($summit_id){

            $summit = SummitFinderStrategyFactory::build($this->getRepository(), $this->getResourceServerContext())->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $this->summit_service->generateQREncKey($summit);

            return $this->created(SerializerRegistry::getInstance()
                ->getSerializer($summit, SummitQREncKeySerializer::SerializerType)->serialize());
        });
    }

    #[OA\Get(
            path: "/api/v1/summits/{id}/lead-report-settings/metadata",
            operationId: "getLeadReportSettingsMetadata",
            summary: "Get lead report settings metadata",
            tags: ["Summits", "Lead Reports"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(type: "object")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::ReadSummitData,
                SummitScopes::ReadAllSummitData
            ]]]
        )
    ]
    /**
     * @param $summit_id
     * @return mixed
     */
    public function getLeadReportSettingsMetadata($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            return $this->ok($summit->getLeadReportSettingsMetadata());
        });
    }

    #[OA\Get(
            path: "/api/v1/summits/{id}/lead-report-settings",
            operationId: "getLeadReportSettings",
            summary: "Get lead report settings",
            tags: ["Summits", "Lead Reports"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
            ],
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(type: "array", items: new OA\Items(type: "object"))
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::ReadSummitData,
                SummitScopes::ReadAllSummitData
            ]]]
        )
    ]
    /**
     * @param $summit_id
     * @return mixed
     */
    public function getLeadReportSettings($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $lead_report_settings = [];
            foreach ($summit->getLeadReportSettings() as $config) {
                $lead_report_settings[] = SerializerRegistry::getInstance()->getSerializer($config)->serialize();
            }
            return $this->ok($lead_report_settings);;
        });
    }

    #[OA\Post(
            path: "/api/v1/summits/{id}/lead-report-settings",
            operationId: "addLeadReportSettings",
            summary: "Add lead report settings",
            tags: ["Summits", "Lead Reports"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(type: "object")
            ),
            responses: [
                new OA\Response(
                    response: Response::HTTP_CREATED,
                    description: "Lead report settings created",
                    content: new OA\JsonContent(type: "object")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::WriteSummitData
            ]]]
        )
    ]
    /**
     * @param $summit_id
     * @return mixed
     */
    public function addLeadReportSettings($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(LeadReportSettingsValidationRulesFactory::buildForAdd(), true);

            $settings = $this->summit_service->addLeadReportSettings($summit, $payload);

            return $this->created(SerializerRegistry::getInstance()
                ->getSerializer($settings)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Put(
            path: "/api/v1/summits/{id}/lead-report-settings",
            operationId: "updateLeadReportSettings",
            summary: "Update lead report settings",
            tags: ["Summits", "Lead Reports"],
            parameters: [
                new OA\Parameter(
                    name: "id",
                    description: "Summit ID",
                    in: "path",
                    required: true,
                    schema: new OA\Schema(type: "integer")
                ),
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(type: "object")
            ),
            responses: [
                new OA\Response(
                    response: Response::HTTP_OK,
                    description: "Success",
                    content: new OA\JsonContent(type: "object")
                ),
                new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
                new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
                new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            ],
            x: [
                'required-groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            security: [["summit_oauth2" => [
                SummitScopes::WriteSummitData
            ]]]
        )
    ]
    /**
     * @param $summit_id
     * @return mixed
     */
    public function updateLeadReportSettings($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(LeadReportSettingsValidationRulesFactory::buildForUpdate(), true);

            $settings = $this->summit_service->updateLeadReportSettings($summit, $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($settings)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }


    #[OA\Get(
        path: "/api/v1/summits/{summit_id}/badge/{badge}/validate",
        summary: 'Validate Scanned Badges',
        operationId: 'validateBadge',
        tags: ['Badges'],
        x: [
            'required-groups' => [
                IGroup::SponsorExternalUsers,
                IGroup::SuperAdmins,
                IGroup::Administrators,
            ]
        ],
        security: [['summit_oauth2' => [
            SummitScopes::ReadBadgeScanValidate
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'summit_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'badge',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'RAW Badge QR scan encoded on BASE 64'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Badge validation success',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidateBadgeResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function validateBadge($summit_id, $badge) {
        return $this->processRequest(function () use ($summit_id, $badge) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $summitAttendeeBadge = $this->summit_service->validateBadge($summit, $badge);

            return $this->ok(SerializerRegistry::getInstance()
                ->getSerializer($summitAttendeeBadge)
                ->serialize(
                    'features,ticket,ticket.owner,ticket.owner',
                    [
                        'id',
                        'features.id',
                        'features.name',
                        'features.description',
                        'ticket.id',
                        'ticket.number',
                        'ticket.owner.first_name',
                        'ticket.owner.last_name',
                        'ticket.owner.email',
                        'ticket.owner.company',
                    ]
                )
            );
        });
    }
}
