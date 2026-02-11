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

use App\Http\Utils\BooleanCellFormatter;
use App\Http\Utils\EpochCellFormatter;
use App\Http\Utils\MultipartFormDataCleaner;
use App\libs\Utils\Doctrine\ReplicaAwareTrait;
use App\Models\Foundation\Main\IGroup;
use App\ModelSerializers\SerializerUtils;
use App\Security\MemberScopes;
use App\Security\SummitScopes;
use OpenApi\Attributes as OA;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use libs\utils\HTMLCleaner;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\main\Member;
use models\oauth2\IResourceServerContext;
use models\summit\IEventFeedbackRepository;
use models\summit\ISpeakerRepository;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\Presentation;
use models\summit\SummitEvent;
use ModelSerializers\IPresentationSerializerTypes;
use ModelSerializers\SerializerRegistry;
use services\model\ISummitService;
use Symfony\Component\HttpFoundation\Response;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;
use utils\Order;
use utils\OrderElement;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class OAuth2SummitEventsApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitEventsApiController extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;

    use RequestProcessor;

    use ValidateEventUri;

    use ParametrizedGetAll;

    use ReplicaAwareTrait;

    /**
     * @var ISummitService
     */
    private $service;

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
     * @var IMemberRepository
     */
    private $member_repository;


    public function __construct
    (
        ISummitRepository        $summit_repository,
        ISummitEventRepository   $event_repository,
        ISpeakerRepository       $speaker_repository,
        IEventFeedbackRepository $event_feedback_repository,
        IMemberRepository        $member_repository,
        ISummitService           $service,
        IResourceServerContext   $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $summit_repository;
        $this->speaker_repository = $speaker_repository;
        $this->event_repository = $event_repository;
        $this->event_feedback_repository = $event_feedback_repository;
        $this->member_repository = $member_repository;
        $this->service = $service;
    }

    /**
     * @return string
     */
    private function getSerializerType(): string
    {
        $current_user = $this->resource_server_context->getCurrentUser(true);
        $application_type = $this->resource_server_context->getApplicationType();
        $path = Request::path();
        $method = Request::method();
        $clientId = $this->resource_server_context->getCurrentClientId();
        $scope = $this->resource_server_context->getCurrentScope();

        Log::debug(sprintf("OAuth2SummitEventsApiController::getSerializerType client id %s app_type %s scope %s has current user %b %s %s ", $clientId, $application_type, implode(" ", $scope), !is_null($current_user), $method, $path));
        if ($application_type == IResourceServerContext::ApplicationType_Service || (!is_null($current_user) && ($current_user->isAdmin() || ($current_user->isSummitAdmin())))) {
            Log::debug(sprintf("OAuth2SummitEventsApiController::getSerializerType app_type %s has current user %b PRIVATE", $application_type, !is_null($current_user)));
            return SerializerRegistry::SerializerType_Private;
        }
        Log::debug(sprintf("OAuth2SummitEventsApiController::getSerializerType app_type %s has current user %b PUBLIC", $application_type, !is_null($current_user)));
        return SerializerRegistry::SerializerType_Public;
    }

    /**
     *  Events endpoints
     */

    // OpenAPI Documentation

    #[OA\Get(
        path: '/api/v1/summits/{id}/events',
        operationId: 'getEvents',
        summary: 'Get all events for a summit',
        description: 'Retrieves a paginated list of all events (published and unpublished) for a specific summit. Requires admin privileges.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadSummitData, SummitScopes::ReadAllSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter[]', in: 'query', required: false, description: 'Filter expressions', style: 'form', explode: true, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by field(s)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Events retrieved successfully', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitEventsResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getEvents($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $current_user = $this->resource_server_context->getCurrentUser(true);
            return $this->withReplica(function() use ($summit_id, $current_user) {
                $strategy = new RetrieveAllSummitEventsBySummitStrategy($this->repository, $this->event_repository, $this->resource_server_context);
                $response = $strategy->getEvents(['summit_id' => $summit_id]);
                return $this->ok
                (
                    $response->toArray
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        [
                            'current_user' => $current_user
                    ],
                        $this->getSerializerType()
                    )
                );
            });

        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/events/csv',
        operationId: 'getEventsCSV',
        summary: 'Export all summit events to CSV',
        description: 'Exports a CSV file containing all events for a specific summit.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadSummitData, SummitScopes::ReadAllSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'filter[]', in: 'query', required: false, description: 'Filter expressions', style: 'form', explode: true, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by field(s)', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'CSV file generated', content: new OA\MediaType(mediaType: 'text/csv', schema: new OA\Schema(type: 'string', format: 'binary'))),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getEventsCSV($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $current_user = $this->resource_server_context->getCurrentUser(true);
            return $this->withReplica(function() use ($summit_id, $current_user) {
                $summit = SummitFinderStrategyFactory::build($this->getRepository(), $this->getResourceServerContext())->find($summit_id);
                if (is_null($summit)) return $this->error404();

                $strategy = new RetrieveAllSummitEventsBySummitCSVStrategy
                (
                    $this->repository,
                    $this->event_repository,
                    $this->resource_server_context
                );
                $response = $strategy->getEvents(['summit_id' => $summit_id]);

                $filename = "activities-" . date('Ymd');
                $list = $response->toArray
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    ['none'],
                    [
                        'current_user' => $current_user,
                    ],
                    SerializerRegistry::SerializerType_CSV
                );

                return $this->export
                (
                    'csv',
                    $filename,
                    $list['data'],
                    [
                        'created' => new EpochCellFormatter(),
                        'last_edited' => new EpochCellFormatter(),
                        'start_date' => new EpochCellFormatter(EpochCellFormatter::DefaultFormat, $summit->getTimeZone()),
                        'end_date' => new EpochCellFormatter(EpochCellFormatter::DefaultFormat, $summit->getTimeZone()),
                        'allow_feedback' => new BooleanCellFormatter(),
                        'is_published' => new BooleanCellFormatter(),
                        'rsvp_external' => new BooleanCellFormatter(),
                    ]
                );
            });

        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/events/published',
        operationId: 'getScheduledEvents',
        summary: 'Get all published/scheduled events for a summit',
        description: 'Retrieves a paginated list of all published events for a specific summit.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadSummitData, SummitScopes::ReadAllSummitData]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter[]', in: 'query', required: false, description: 'Filter expressions', style: 'form', explode: true, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by field(s)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Published events retrieved', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitEventsResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    #[OA\Get(
        path: '/api/public/v1/summits/{id}/events/published',
        operationId: 'getScheduledEventsPublic',
        summary: 'Get all published/scheduled events for a summit',
        description: 'Retrieves a paginated list of all published events for a specific summit.',
        tags: ['Summit Events (Public)'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter[]', in: 'query', required: false, description: 'Filter expressions', style: 'form', explode: true, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by field(s)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Published events retrieved', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitEventsResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
    /**
     * @param $summit_id
     * @return mixed
     */
    public function getScheduledEvents($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $current_user = $this->resource_server_context->getCurrentUser(true);

            return $this->withReplica(function() use($summit_id, $current_user){

                $summit = SummitFinderStrategyFactory::build($this->getRepository(), $this->getResourceServerContext())->find($summit_id);
                if (is_null($summit)) return $this->error404();

                $params = [
                    'summit_id'    => $summit_id,
                    'summit'       => $summit,
                    'published'    => true,
                    'current_user' => $current_user
                ];

                $strategy = new RetrievePublishedSummitEventsBySummitStrategy($this->repository, $this->event_repository, $this->resource_server_context);
                $response = $strategy->getEvents($params);
                return $this->ok($response->toArray
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    $params,
                    $this->getSerializerType()
                ));
            });

        });
    }

    use ParametrizedGetAll;

    #[OA\Get(
        path: '/api/public/v1/summits/{id}/events/all/published/tags',
        operationId: 'getScheduledEventsTags',
        summary: 'Get all tags from published events for a summit',
        description: 'Retrieves a paginated list of tags used in published events for a specific summit. This is a public endpoint.',
        tags: ['Summit Events (Public)'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter[]', in: 'query', required: false, description: 'Filter by tag (=@, ==)', style: 'form', explode: true, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by tag', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Tags retrieved successfully', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedTagsResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getScheduledEventsTags($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->getRepository(), $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'tag' => ['=@', '=='],
                ];
            },
            function () {
                return [
                    'tag' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'tag'
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->event_repository->getAllPublishedTagsByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllEvents()
    {
        return $this->processRequest(function(){
            $current_user = $this->resource_server_context->getCurrentUser(true);

            return $this->withReplica(function() use($current_user){
                $strategy = new RetrieveAllSummitEventsStrategy($this->event_repository);
                $response = $strategy->getEvents();
                return $this->ok
                (
                    $response->toArray
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        [
                            'current_user' => $current_user
                        ],
                        $this->getSerializerType()
                    )
                );
            });
        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/presentations',
        operationId: 'getAllPresentations',
        summary: 'Get all presentations for a summit',
        description: 'Retrieves a paginated list of all presentations for a specific summit.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadSummitData, SummitScopes::ReadAllSummitData]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter[]', in: 'query', required: false, description: 'Filter expressions', style: 'form', explode: true, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by field(s)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Presentations retrieved successfully', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitEventsResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllPresentations($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            $current_user = $this->resource_server_context->getCurrentUser(true);

            return $this->withReplica(function() use($current_user, $summit_id){
                $strategy = new RetrieveAllSummitPresentationsStrategy($this->repository, $this->event_repository, $this->resource_server_context);
                $response = $strategy->getEvents(['summit_id' => intval($summit_id)]);
                $params = [
                    'current_user' => $current_user,
                ];
                return $this->ok
                (
                    $response->toArray
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        $params,
                        $this->getSerializerType()
                    )
                );
            });

        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/presentations/voteable',
        operationId: 'getAllVoteablePresentations',
        summary: 'Get all voteable presentations for a summit',
        description: 'Retrieves a paginated list of all presentations that allow attendee voting for a specific summit.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadSummitData, SummitScopes::ReadAllSummitData]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter[]', in: 'query', required: false, description: 'Filter expressions', style: 'form', explode: true, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by field(s)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Voteable presentations retrieved successfully', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitEventsResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllVoteablePresentations($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) throw new EntityNotFoundException;

            $strategy = new RetrieveAllSummitVoteablePresentationsStrategy
            (
                $this->repository,
                $this->event_repository,
                $this->resource_server_context
            );

            $response = $strategy->getEvents(['summit_id' => intval($summit_id)]);

            $params = [
                'current_user' => $this->resource_server_context->getCurrentUser(true),
                'use_cache' => true,
            ];

            return $this->ok
            (
                $response->toArray
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    $params,
                    $this->getSerializerType()
                )
            );
        });
    }

    #[OA\Get(
        path: '/api/v2/summits/{id}/presentations/voteable',
        operationId: 'getAllVoteablePresentationsV2',
        summary: 'Get all voteable presentations for a summit (V2)',
        description: 'Retrieves a paginated list of all presentations that allow attendee voting for a specific summit with admin-level details.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadSummitData, SummitScopes::ReadAllSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter[]', in: 'query', required: false, description: 'Filter expressions', style: 'form', explode: true, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by field(s)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Voteable presentations retrieved successfully', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitEventsResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllVoteablePresentationsV2($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) throw new EntityNotFoundException;

            $strategy = new RetrieveAllSummitVoteablePresentationsStrategy
            (
                $this->repository,
                $this->event_repository,
                $this->resource_server_context
            );
            $response = $strategy->getEvents(['summit_id' => intval($summit_id)]);

            $params = [
                'current_user' => $this->resource_server_context->getCurrentUser(true),
                'use_cache' => true,
            ];

            $filter = $strategy->getFilter();

            if (!is_null($filter)) {
                $votingDateFilter = $filter->getFilter('presentation_attendee_vote_date');
                if ($votingDateFilter != null) {
                    $params['begin_attendee_voting_period_date'] = $votingDateFilter[0]->getValue();
                    if (count($votingDateFilter) > 1) {
                        $params['end_attendee_voting_period_date'] = $votingDateFilter[1]->getValue();
                    }
                }
            }

            return $this->ok
            (
                $response->toArray
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    $params,
                    SerializerRegistry::SerializerType_Admin_Voteable
                )
            );
        });
    }

    #[OA\Get(
        path: '/api/v2/summits/{id}/presentations/voteable/csv',
        operationId: 'getAllVoteablePresentationsV2CSV',
        summary: 'Export voteable presentations to CSV (V2)',
        description: 'Exports a CSV file containing all voteable presentations for a specific summit.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadSummitData, SummitScopes::ReadAllSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'filter[]', in: 'query', required: false, description: 'Filter expressions', style: 'form', explode: true, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by field(s)', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'CSV file generated', content: new OA\MediaType(mediaType: 'text/csv', schema: new OA\Schema(type: 'string', format: 'binary'))),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    public function getAllVoteablePresentationsV2CSV($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) throw new EntityNotFoundException;

            $strategy = new RetrieveAllSummitVoteablePresentationsStrategyCSV
            (
                $this->repository,
                $this->event_repository,
                $this->resource_server_context
            );

            $response = $strategy->getEvents(['summit_id' => intval($summit_id)]);

            $params = [
                'current_user' => $this->resource_server_context->getCurrentUser(true),
                'use_cache' => true,
            ];

            $filter = $strategy->getFilter();

            if (!is_null($filter)) {
                $votingDateFilter = $filter->getFilter('presentation_attendee_vote_date');
                if ($votingDateFilter != null) {
                    $params['begin_attendee_voting_period_date'] = $votingDateFilter[0]->getValue();
                    if (count($votingDateFilter) > 1) {
                        $params['end_attendee_voting_period_date'] = $votingDateFilter[1]->getValue();
                    }
                }
            }


            $filename = "voteable-presentations-" . date('Ymd');
            $list = $response->toArray
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                ['none'],
                $params,
                SerializerRegistry::SerializerType_Admin_Voteable_CSV
            );

            return $this->export
            (
                'csv',
                $filename,
                $list['data'],
                [
                    'created' => new EpochCellFormatter(),
                    'last_edited' => new EpochCellFormatter(),
                ]
            );
        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/presentations/voteable/{presentation_id}',
        operationId: 'getVoteablePresentation',
        summary: 'Get a specific voteable presentation',
        description: 'Retrieves a single voteable presentation by ID.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadSummitData, SummitScopes::ReadAllSummitData]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'presentation_id', in: 'path', required: true, description: 'Presentation ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Presentation retrieved successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEvent')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $presentation_id
     * @return array|\Illuminate\Http\JsonResponse|mixed
     */
    public function getVoteablePresentation($summit_id, $presentation_id)
    {
        return $this->processRequest(function() use($summit_id, $presentation_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) throw new EntityNotFoundException;

            $event = $summit->getScheduleEvent(intval($presentation_id));

            if (is_null($event) || !$event instanceof Presentation) throw new EntityNotFoundException;

            if (!$event->getType()->isAllowAttendeeVote())
                throw new EntityNotFoundException('Type does not allows Attendee Vote.');

            $current_user = $this->resource_server_context->getCurrentUser(true);

            if (!$event->hasAccess($current_user)) {
                Log::debug(sprintf("OAuth2SummitEventsApiController::getVoteablePresentation summit id %s presentation id %s user has no access.", $summit_id, $presentation_id));
                throw new EntityNotFoundException("User has not access to this presentation.");
            }

            return SerializerRegistry::getInstance()->getSerializer($event, SerializerRegistry::SerializerType_Private)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                [
                    'current_user' => $current_user
                ]
            );
        });
    }

    /**
     * @return mixed
     */
    public function getAllScheduledEvents()
    {
        return $this->processRequest(function(){
            $current_user = $this->resource_server_context->getCurrentUser(true);

            return $this->withReplica(function () use ($current_user) {
                $strategy = new RetrieveAllPublishedSummitEventsStrategy($this->event_repository);
                $response = $strategy->getEvents();
                return $this->ok
                (
                    $response->toArray
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        [
                            'current_user' => $current_user
                        ],
                        $this->getSerializerType()
                    )
                );
            });

        });
    }


    #[OA\Get(
        path: '/api/v1/summits/{id}/events/{event_id}',
        operationId: 'getEvent',
        summary: 'Get a specific event by ID',
        description: 'Retrieves a single event by its ID for a specific summit.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadSummitData, SummitScopes::ReadAllSummitData]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Event retrieved successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEvent')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function getEvent($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){

            $current_user = $this->resource_server_context->getCurrentUser(true);

            return $this->withReplica(function () use ($current_user, $summit_id, $event_id){
                $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
                if (is_null($summit)) throw new EntityNotFoundException;

                $event = $summit->getEvent(intval($event_id));

                if (is_null($event)) throw new EntityNotFoundException;

                return SerializerRegistry::getInstance()->getSerializer($event, $this->getSerializerType())->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    [
                        'current_user' => $current_user
                    ]
                );
            });

        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/events/{event_id}/published',
        operationId: 'getScheduledEvent',
        summary: 'Get a specific published/scheduled event by ID',
        description: 'Retrieves a single published event by its ID for a specific summit.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadSummitData, SummitScopes::ReadAllSummitData]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Published event retrieved successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEvent')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    #[OA\Get(
        path: '/api/public/v1/summits/{id}/events/{event_id}/published',
        operationId: 'getScheduledEventPublic',
        summary: 'Get a specific published/scheduled event by ID',
        description: 'Retrieves a single published event by its ID for a specific summit.',
        tags: ['Summit Events (Public)'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Published event retrieved successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEvent')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function getScheduledEvent($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            return $this->withReplica(function () use ($summit_id, $event_id){
                $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
                if (is_null($summit)) throw new EntityNotFoundException;

                $event = $summit->getScheduleEvent(intval($event_id));

                if (is_null($event))
                    throw new EntityNotFoundException;

                return SerializerRegistry::getInstance()->getSerializer($event)->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                );
            });
        });
    }

    #[OA\Post(
        path: '/api/v1/summits/{id}/events/{event_id}/published/mail',
        operationId: 'shareScheduledEventByEmail',
        summary: 'Share a scheduled event by email',
        description: 'Sends an email sharing a specific published event. Rate limited to 5 requests per day.',
        security: [['summit_events_api_oauth2' => [SummitScopes::SendMyScheduleMail]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ShareEventByEmailRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Email sent successfully'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function shareScheduledEventByEmail($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'from' => 'required|email',
                'to' => 'required|email',
                'event_uri' => 'sometimes|url',
            ]);

            $this->service->shareEventByEmail($summit, $event_id, $this->validateEventUri($payload));

            return $this->ok();
        });
    }

    #[OA\Post(
        path: '/api/v1/summits/{id}/events',
        operationId: 'addEvent',
        summary: 'Create a new event for a summit',
        description: 'Creates a new event for a specific summit.',
        security: [['summit_events_api_oauth2' => [SummitScopes::WriteEventData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AddSummitEventRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Event created successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEvent')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @return mixed
     */
    public function addEvent($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $payload = $data->all();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, SummitEventValidationRulesFactory::build($payload));

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $fields = [
                'title',
                'description',
                'social_summary',
            ];

            $event = $this->service->addEvent($summit, HTMLCleaner::cleanData($payload, $fields));

            return $this->created(SerializerRegistry::getInstance()->getSerializer
            (
                $event,
                $this->getSerializerType()
            )->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                [
                    'current_user' => $this->resource_server_context->getCurrentUser(true)
                ]
            ));
        });
    }


    #[OA\Put(
        path: '/api/v1/summits/{id}/events/{event_id}',
        operationId: 'updateEvent',
        summary: 'Update an existing event',
        description: 'Updates an existing event for a specific summit.',
        security: [['summit_events_api_oauth2' => [SummitScopes::WriteSummitData, SummitScopes::WriteEventData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins, IGroup::TrackChairs, IGroup::TrackChairsAdmins]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateSummitEventRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Event updated successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEvent')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function updateEvent($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){

            Log::debug(sprintf("OAuth2SummitEventsApiController::updateEvent summit id %s event id %s", $summit_id, $event_id));

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member))
                return $this->error403();

            $event = $summit->getEvent($event_id);
            if (is_null($event))
                return $this->error404();

            $isAdmin = $current_member->isSummitAllowed($summit);
            $isTrackChair = $summit->isTrackChairAdmin($current_member) || $summit->isTrackChair($current_member, $event->getCategory());

            $payload = $this->getJsonData();

            // Creates a Validator instance and validates the data.
            $rules = $isAdmin ? SummitEventValidationRulesFactory::build($payload, true) : null;
            if(is_null($rules)){
                $rules = $isTrackChair ? SummitEventValidationRulesFactory::buildForTrackChair($payload, true) : null;
            }

            if(is_null($rules))
                return $this->error403();


            $payload = $this->getJsonPayload($rules, true);

            $fields = [
                'title',
                'description',
                'social_summary',
            ];

            if($isAdmin) {
                Log::debug(sprintf("OAuth2SummitEventsApiController::updateEvent summit id %s event id %s updating event", $summit_id, $event_id));
                $event = $this->service->updateEvent($summit, $event_id, HTMLCleaner::cleanData($payload, $fields));
            }
            else{
                Log::debug(sprintf("OAuth2SummitEventsApiController::updateEvent summit id %s event id %s updating duration", $summit_id, $event_id));
                $event = $this->service->updateDuration($payload, $summit, $event);
            }

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($event, $this->getSerializerType())
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    [
                        'current_user' => $current_member
                    ]
                )
            );
        });
    }

    #[OA\Put(
        path: '/api/v1/summits/{id}/events/{event_id}/publish',
        operationId: 'publishEvent',
        summary: 'Publish an event to the schedule',
        description: 'Publishes an event to the summit schedule with optional location, start date, and duration.',
        security: [['summit_events_api_oauth2' => [SummitScopes::PublishEventData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/PublishSummitEventRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Event published successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEvent')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function publishEvent($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $rules = [
                'location_id' => 'sometimes|required|integer',
                'start_date' => 'sometimes|required|date_format:U|epoch_seconds',
                'duration' => 'sometimes|integer|min:0',
                'end_date' => 'sometimes|required_with:start_date|date_format:U|epoch_seconds|after:start_date',
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

            $event = $this->service->publishEvent($summit, $event_id, $data->all());

            return $this->updated(
                SerializerRegistry::getInstance()->getSerializer($event, $this->getSerializerType())->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    [
                        'current_user' => $this->resource_server_context->getCurrentUser(true)
                    ]
                )
            );
        });
    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/events/{event_id}/publish',
        operationId: 'unPublishEvent',
        summary: 'Unpublish an event from the schedule',
        description: 'Removes an event from the published summit schedule.',
        security: [['summit_events_api_oauth2' => [SummitScopes::PublishEventData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Event unpublished successfully'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function unPublishEvent($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();

            $this->service->unPublishEvent($summit, $event_id);

            return $this->deleted();
        });
    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/events/{event_id}',
        operationId: 'deleteEvent',
        summary: 'Delete an event',
        description: 'Permanently deletes an event from a summit.',
        security: [['summit_events_api_oauth2' => []]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Event deleted successfully'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function deleteEvent($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();

            $this->service->deleteEvent($summit, $event_id, $current_member);

            return $this->deleted();
        });
    }

    /** Feedback endpoints  */

    #[OA\Get(
        path: '/api/v1/summits/{id}/events/{event_id}/feedback',
        operationId: 'getEventFeedback',
        summary: 'Get feedback for an event',
        description: 'Retrieves a paginated list of feedback for a specific event.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadAllSummitData, SummitScopes::ReadSummitData]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter[]', in: 'query', required: false, description: 'Filter by owner_full_name, note, owner_id', style: 'form', explode: true, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by created, owner_id, owner_full_name, rate, id', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Feedback retrieved successfully', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitEventFeedbackResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function getEventFeedback($summit_id, $event_id)
    {

        return $this->withReplica(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $event = $summit->getScheduleEvent(intval($event_id));

            if (is_null($event)) {
                return $this->error404();
            }

            return $this->_getAll(
                function(){
                    return [
                        'owner_full_name' => ['=@', '==', '@@'],
                        'note' => ['=@', '==', '@@'],
                        'owner_id' => ['=='],
                    ];
                },
                function(){
                    return [
                        'owner_full_name' =>  'sometimes|required|string',
                        'note' =>  'sometimes|required|string',
                        'owner_id' =>  'sometimes|required|integer',
                    ];
                },
                function(){
                    return [
                        'created',
                        'owner_id',
                        'owner_full_name',
                        'rate',
                        'id',
                    ];
                },
                function($filter){
                    return $filter;
                },
                function(){
                    return SerializerRegistry::SerializerType_Public;
                },
                function(){
                    return new Order([
                        OrderElement::buildDescFor("created"),
                    ]);
                },
                null,
                function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($event) {
                    return $this->event_feedback_repository->getByEvent($event,
                        new PagingInfo($page, $per_page),
                        call_user_func($applyExtraFilters, $filter),
                        $order
                    );
                }
            );
        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/events/{event_id}/feedback/csv',
        operationId: 'getEventFeedbackCSV',
        summary: 'Export event feedback to CSV',
        description: 'Exports a CSV file containing all feedback for a specific event.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadAllSummitData, SummitScopes::ReadSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[]', in: 'query', required: false, description: 'Filter by owner_full_name, note, owner_id', style: 'form', explode: true, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by created, owner_id, owner_full_name, rate, id', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'CSV file generated', content: new OA\MediaType(mediaType: 'text/csv', schema: new OA\Schema(type: 'string', format: 'binary'))),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function getEventFeedbackCSV($summit_id, $event_id)
    {

        return $this->withReplica(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $event = $summit->getScheduleEvent(intval($event_id));

            if (is_null($event)) {
                return $this->error404();
            }

            return $this->_getAllCSV(
                function(){
                    return [
                        'owner_full_name' => ['=@', '==', '@@'],
                        'note' => ['=@', '==', '@@'],
                        'owner_id' => ['=='],
                    ];
                },
                function(){
                    return [
                        'owner_full_name' =>  'sometimes|required|string',
                        'note' =>  'sometimes|required|string',
                        'owner_id' =>  'sometimes|required|integer',
                    ];
                },
                function(){
                    return [
                        'created',
                        'owner_id',
                        'owner_full_name',
                        'rate',
                        'id',
                    ];
                },
                function($filter){
                    return $filter;
                },
                function(){
                    return SerializerRegistry::SerializerType_CSV;
                },
                function () {
                    return [
                        'created_date' => new EpochCellFormatter(),
                        'last_edited' => new EpochCellFormatter(),
                    ];
                },
                function () {
                    return [];
                },
                sprintf('event-%s-feedback', $event_id),
                [],
                function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($event) {
                    return $this->event_feedback_repository->getByEvent($event,
                        new PagingInfo($page, $per_page),
                        call_user_func($applyExtraFilters, $filter),
                        $order
                    );
                },
            );
        });

    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/events/{event_id}/feedback/{feedback_id}',
        operationId: 'deleteEventFeedback',
        summary: 'Delete event feedback',
        description: 'Deletes a specific feedback entry for an event.',
        security: [['summit_events_api_oauth2' => [SummitScopes::WriteSummitData, SummitScopes::WriteEventData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'feedback_id', in: 'path', required: true, description: 'Feedback ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Feedback deleted successfully'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @param $feedback_id
     * @return mixed
     */
    public function deleteEventFeedback($summit_id, $event_id, $feedback_id){
        return $this->processRequest(function() use($summit_id, $event_id, $feedback_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $event = $summit->getScheduleEvent(intval($event_id));

            if (is_null($event)) {
                return $this->error404();
            }

            $this->service->deleteEventFeedback($summit, $event_id, $feedback_id);

            return $this->deleted();
        });
    }
    #[OA\Post(
        path: '/api/v2/summits/{id}/events/{event_id}/feedback',
        operationId: 'addMyEventFeedbackReturnId',
        summary: 'Add feedback for an event (V2)',
        description: 'Adds feedback for a specific event and returns the feedback ID.',
        security: [['summit_events_api_oauth2' => [SummitScopes::WriteSummitData, SummitScopes::AddMyEventFeedback]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AddEventFeedbackRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Feedback added successfully', content: new OA\JsonContent(type: 'integer', description: 'Feedback ID')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addMyEventFeedbackReturnId($summit_id, $event_id)
    {
        return $this->_addMyEventFeedback($summit_id, $event_id, true);
    }

    #[OA\Post(
        path: '/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}/feedback',
        operationId: 'addMyEventFeedback',
        summary: 'Add feedback for an event (V1)',
        description: 'Adds feedback for a specific event on behalf of a member.',
        security: [['summit_events_api_oauth2' => [SummitScopes::AddMyEventFeedback]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'member_id', in: 'path', required: true, description: 'Member ID (use "me" for current user)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AddEventFeedbackRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Feedback added successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEventFeedback')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addMyEventFeedback($summit_id, $member_id, $event_id)
    {
        return $this->_addMyEventFeedback($summit_id, $event_id, false);
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @param bool $returnId
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    private function _addMyEventFeedback($summit_id, $event_id, $returnId = false)
    {
        return $this->processRequest(function() use($summit_id, $event_id, $returnId){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload([
                'rate' => 'required|integer|digits_between:0,5',
                'note' => 'max:500',
            ]);

            $feedback = $this->service->addMyEventFeedback
            (
                $current_member,
                $summit,
                intval($event_id),
                $payload
            );

            if ($returnId) {
                return $this->updated($feedback->getId());
            }

            return $this->created(SerializerRegistry::getInstance()->getSerializer($feedback)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
        path: '/api/v2/summits/{id}/events/{event_id}/feedback',
        operationId: 'updateMyEventFeedbackReturnId',
        summary: 'Update feedback for an event (V2)',
        description: 'Updates feedback for a specific event and returns the feedback ID.',
        security: [['summit_events_api_oauth2' => [SummitScopes::WriteSummitData, SummitScopes::AddMyEventFeedback]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AddEventFeedbackRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Feedback updated successfully', content: new OA\JsonContent(type: 'integer', description: 'Feedback ID')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateMyEventFeedbackReturnId($summit_id, $event_id)
    {
        return $this->_updateMyEventFeedback($summit_id, $event_id, true);
    }

    #[OA\Put(
        path: '/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}/feedback',
        operationId: 'updateMyEventFeedback',
        summary: 'Update feedback for an event (V1)',
        description: 'Updates feedback for a specific event on behalf of a member.',
        security: [['summit_events_api_oauth2' => [SummitScopes::AddMyEventFeedback]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'member_id', in: 'path', required: true, description: 'Member ID (use "me" for current user)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AddEventFeedbackRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Feedback updated successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEventFeedback')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateMyEventFeedback($summit_id, $member_id, $event_id)
    {
        return $this->_updateMyEventFeedback($summit_id, $event_id, false);
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @param bool $returnId
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    private function _updateMyEventFeedback($summit_id, $event_id, $returnId = false)
    {
        return $this->processRequest(function() use($summit_id, $event_id, $returnId){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload([
                'rate' => 'required|integer|digits_between:0,5',
                'note' => 'max:500',
            ]);

            $feedback = $this->service->updateMyEventFeedback
            (
                $current_member,
                $summit,
                intval($event_id),
                $payload
            );

            if ($returnId) {
                return $this->updated($feedback->getId());
            }

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($feedback)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}/feedback',
        operationId: 'getMyEventFeedback',
        summary: 'Get my feedback for an event',
        description: 'Retrieves the current user\'s feedback for a specific event.',
        security: [['summit_events_api_oauth2' => [SummitScopes::MeRead, MemberScopes::ReadMyMemberData]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'member_id', in: 'path', required: true, description: 'Member ID (use "me" for current user)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Feedback retrieved successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEventFeedback')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    public function getMyEventFeedback($summit_id, $member_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            return $this->withReplica(function() use($summit_id, $event_id, $current_member){
                $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
                if (is_null($summit)) return $this->error404();
                $feedback = $this->service->getMyEventFeedback
                (
                    $current_member,
                    $summit,
                    intval($event_id)
                );

                return $this->ok(SerializerRegistry::getInstance()->getSerializer($feedback)->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
            });

        });
    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}/feedback',
        operationId: 'deleteMyEventFeedback',
        summary: 'Delete my feedback for an event',
        description: 'Deletes the current user\'s feedback for a specific event.',
        security: [['summit_events_api_oauth2' => [SummitScopes::DeleteMyEventFeedback]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'member_id', in: 'path', required: true, description: 'Member ID (use "me" for current user)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Feedback deleted successfully'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    public function deleteMyEventFeedback($summit_id, $member_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->service->deleteMyEventFeedback
            (
                $current_member,
                $summit,
                intval($event_id)
            );

            return $this->deleted();
        });
    }

    #[OA\Post(
        path: '/api/v1/summits/{id}/events/{event_id}/attachment',
        operationId: 'addEventAttachment',
        summary: 'Add an attachment to an event',
        description: 'Uploads a file attachment to a specific event.',
        security: [['summit_events_api_oauth2' => [SummitScopes::WriteSummitData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'File to upload')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Attachment added successfully', content: new OA\JsonContent(type: 'integer', description: 'Attachment ID')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'File not provided'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addEventAttachment(LaravelRequest $request, $summit_id, $event_id)
    {
        return $this->processRequest(function() use($request, $summit_id, $event_id){

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $res = $this->service->addEventAttachment($summit, $event_id, $file);

            return !is_null($res) ? $this->created($res->getId()) : $this->error400();
        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/events/unpublished',
        operationId: 'getUnpublishedEvents',
        summary: 'Get all unpublished events for a summit',
        description: 'Retrieves a paginated list of all unpublished events for a specific summit.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadSummitData, SummitScopes::ReadAllSummitData]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter[]', in: 'query', required: false, description: 'Filter expressions', style: 'form', explode: true, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by field(s)', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Unpublished events retrieved successfully', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitEventsResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getUnpublishedEvents($summit_id)
    {

        return $this->processRequest(function() use($summit_id){

            $serializer_type = SerializerRegistry::SerializerType_Public;
            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && $current_member->isAdmin()) {
                $serializer_type = SerializerRegistry::SerializerType_Private;
            }

            return $this->withReplica(function() use($summit_id, $serializer_type){
                $strategy = new RetrieveAllUnPublishedSummitEventsStrategy($this->repository, $this->event_repository, $this->resource_server_context);

                $response = $strategy->getEvents(['summit_id' => $summit_id]);
                return $this->ok($response->toArray
                (

                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    [],
                    $serializer_type
                ));
            });

        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/events/published/empty-spots',
        operationId: 'getScheduleEmptySpots',
        summary: 'Get empty spots in the schedule',
        description: 'Retrieves available empty time slots in the published summit schedule.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadSummitData, SummitScopes::ReadAllSummitData]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'filter[]', in: 'query', required: true, description: 'Filter by location_id, start_date, end_date, gap', style: 'form', explode: true, schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Empty spots retrieved successfully', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitScheduleEmptySpotsResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Filter param is mandatory'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getScheduleEmptySpots($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            return $this->withReplica(function() use($summit_id){
                $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
                if (is_null($summit)) return $this->error404();
                $filter = null;
                if (Request::has('filter')) {
                    $filter = FilterParser::parse(Request::input('filter'), [
                        'location_id' => ['=='],
                        'start_date' => ['>='],
                        'end_date' => ['<='],
                        'gap' => ['>', '<', '<=', '>=', '=='],
                    ]);
                }

                if (empty($filter))
                    throw new ValidationException("filter param is mandatory!");

                $gaps = [];
                foreach ($this->service->getSummitScheduleEmptySpots($summit, $filter) as $gap) {
                    $gaps[] = SerializerRegistry::getInstance()->getSerializer($gap)->serialize();
                }

                $response = new PagingResponse
                (
                    count($gaps),
                    count($gaps),
                    1,
                    1,
                    $gaps
                );

                return $this->ok($response->toArray());
            });
        });
    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/events/publish',
        operationId: 'unPublishEvents',
        summary: 'Unpublish multiple events',
        description: 'Unpublishes multiple events from the summit schedule at once.',
        security: [['summit_events_api_oauth2' => [SummitScopes::PublishEventData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UnpublishEventsRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Events unpublished successfully'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function unPublishEvents($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();

            $data = Request::json();

            $rules = [
                'events' => 'required|int_array',
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

            $this->service->unPublishEvents($summit, $data->all());

            return $this->deleted();
        });
    }

    #[OA\Put(
        path: '/api/v1/summits/{id}/events/publish',
        operationId: 'updateAndPublishEvents',
        summary: 'Update and publish multiple events',
        description: 'Updates and publishes multiple events to the summit schedule at once.',
        security: [['summit_events_api_oauth2' => [SummitScopes::PublishEventData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateAndPublishEventsRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Events updated and published successfully'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateAndPublishEvents($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();

            $data = Request::json();

            $rules = [
                'events' => 'required|event_dto_publish_array',
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

            $this->service->updateAndPublishEvents($summit, $data->all());

            return $this->updated();
        });
    }

    #[OA\Put(
        path: '/api/v1/summits/{id}/events',
        operationId: 'updateEvents',
        summary: 'Update multiple events',
        description: 'Updates multiple events at once without publishing them.',
        security: [['summit_events_api_oauth2' => [SummitScopes::WriteEventData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateEventsRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Events updated successfully'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateEvents($summit_id)
    {
        return $this->processRequest(function() use($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();

            $data = Request::json();

            $rules = [
                'events' => 'required|event_dto_array',
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

            $this->service->updateEvents($summit, $data->all(), false);

            return $this->updated();
        });
    }

    #[OA\Post(
        path: '/api/v1/summits/{id}/events/{event_id}/clone',
        operationId: 'cloneEvent',
        summary: 'Clone an event',
        description: 'Creates a copy of an existing event.',
        security: [['summit_events_api_oauth2' => [SummitScopes::WriteEventData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID to clone', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Event cloned successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEvent')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function cloneEvent($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $event = $this->service->cloneEvent($summit, $event_id);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($event)->serialize());

        });
    }

    #[OA\Post(
        path: '/api/v1/summits/{id}/events/{event_id}/image',
        operationId: 'addEventImage',
        summary: 'Add an image to an event',
        description: 'Uploads an image for a specific event.',
        security: [['summit_events_api_oauth2' => [SummitScopes::WriteEventData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'Image file to upload')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Image added successfully'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'File not provided'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    public function addEventImage(LaravelRequest $request, $summit_id, $event_id)
    {
        return $this->processRequest(function() use($request, $summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $image = $this->service->addEventImage($summit, $event_id, $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($image)->serialize());

        });
    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/events/{event_id}/image',
        operationId: 'deleteEventImage',
        summary: 'Delete an event image',
        description: 'Removes the image from a specific event.',
        security: [['summit_events_api_oauth2' => [SummitScopes::WriteEventData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators, IGroup::SummitRegistrationAdmins]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Image deleted successfully'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    public function deleteEventImage($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->service->removeEventImage($summit, $event_id);
            return $this->deleted();
        });
    }

    #[OA\Post(
        path: '/api/v1/summits/{id}/events/csv',
        operationId: 'importEventData',
        summary: 'Import events from CSV',
        description: 'Imports event data from a CSV file.',
        security: [['summit_events_api_oauth2' => [SummitScopes::WriteSummitData, SummitScopes::WriteEventData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file', 'send_speaker_email'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'CSV file to import'),
                        new OA\Property(property: 'send_speaker_email', type: 'boolean', description: 'Whether to send email notifications to speakers')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Events imported successfully'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     */
    public function importEventData(LaravelRequest $request, $summit_id)
    {
        return $this->processRequest(function() use($request, $summit_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $request->all();

            $rules = [
                'file' => 'required',
                'send_speaker_email' => 'required|boolean',
            ];

            $payload = MultipartFormDataCleaner::cleanBool('send_speaker_email', $payload);

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException;
                $ex->setMessages($validation->messages()->toArray());
                throw $ex;
            }

            $file = $request->file('file');

            $this->service->importEventData($summit, $file, $payload);

            return $this->ok();

        });
    }

    #[OA\Put(
        path: '/api/v1/summits/{id}/events/{event_id}/live-info',
        operationId: 'updateEventLiveInfo',
        summary: 'Update event live streaming info',
        description: 'Updates the live streaming URL and type for an event.',
        security: [['summit_events_api_oauth2' => [SummitScopes::WriteEventData]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateEventLiveInfoRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Live info updated successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEvent')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function updateEventLiveInfo($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $data->all();
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, [
                'streaming_url' => 'required||url',
                'streaming_type' => 'required|string|in:VOD,LIVE',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $event = $this->service->updateEvent($summit, $event_id,
                [
                    'streaming_url' => $payload['streaming_url'],
                    'streaming_type' => $payload['streaming_type'],
                ]);

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($event, $this->getSerializerType())->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                [
                    'current_user' => $this->resource_server_context->getCurrentUser(true)
                ]
            ));

        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/events/{event_id}/published/tokens',
        operationId: 'getScheduledEventJWT',
        summary: 'Get JWT tokens for secure streaming',
        description: 'Retrieves JWT tokens for accessing secure streaming content for a published event.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadSummitData, SummitScopes::ReadAllSummitData]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'JWT tokens retrieved successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEventSecureStreamResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Event not configured for secure streaming'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function getScheduledEventJWT($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $current_user = $this->resource_server_context->getCurrentUser(true);
            return $this->withReplica(function() use($summit_id, $event_id, $current_user){
                Log::debug(sprintf("OAuth2SummitEventsApiController::getScheduledEventJWT summit id %s event id %s", $summit_id, $event_id));
                $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
                if (is_null($summit)) throw new EntityNotFoundException;

                $event = $summit->getScheduleEvent(intval($event_id));

                if (is_null($event)) throw new EntityNotFoundException;

                if(!$summit->hasMuxPrivateKey())
                    throw new ValidationException(sprintf("Summit %s has not set a private key.", $summit_id));

                if(!$event->isMuxStream())
                    throw new ValidationException(sprintf("Event %s has not set a valid MUX url", $event_id));

                if(!$event->IsSecureStream()){
                    throw new ValidationException(sprintf("Event %s is not marked as secure.", $event_id));
                }

                return SerializerRegistry::getInstance()->getSerializer($event, IPresentationSerializerTypes::SecureStream)->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    [
                        'current_user' => $current_user
                    ]
                );
            });
        });
    }

    #[OA\Put(
        path: '/api/v1/summits/{id}/events/{event_id}/type/{type_id}/upgrade',
        operationId: 'upgradeEvent',
        summary: 'Upgrade an event to a different type',
        description: 'Changes the type of an existing event (e.g., from SummitEvent to Presentation).',
        security: [['summit_events_api_oauth2' => [SummitScopes::WriteEventData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'type_id', in: 'path', required: true, description: 'New Event Type ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Event upgraded successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEvent')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @param $type_id
     * @return mixed
     */
    public function upgradeEvent($summit_id, $event_id, $type_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id, $type_id){

            Log::debug(sprintf("OAuth2SummitEventsApiController::upgradeEvent summit id %s event id %s type id %s", $summit_id, $event_id, $type_id));

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $event = $summit->getEvent($event_id);
            if (is_null($event))
                return $this->error404();

            $event = $this->service->upgradeSummitEvent($summit, intval($event_id), intval($type_id));

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($event, $this->getSerializerType())
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                )
            );
        });
    }

    #[OA\Get(
        path: '/api/public/v1/summits/{id}/events/all/published/overflow',
        operationId: 'getOverflowStreamingInfo',
        summary: 'Get overflow streaming information (Public)',
        description: 'Retrieves overflow streaming information for published events. This is a public endpoint.',
        tags: ['Summit Events (Public)'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'k', in: 'query', required: true, description: 'Overflow stream key', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Overflow info retrieved successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEventOverflowStreamResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Missing overflow query string key'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Event has no overflow set'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getOverflowStreamingInfo($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $query_string_key = config("overflow.query_string_key", "k");
            if (!Request::has($query_string_key))
                return $this->error400(sprintf("Missing overflow query string key in %s", $query_string_key));

            return $this->withReplica(function() use ($summit_id, $query_string_key) {
                $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
                if (is_null($summit))
                    return $this->error404("Not Found.");

                $overflow_stream_key = Request::get($query_string_key);

                Log::debug
                (
                    sprintf
                    (
                        "OAuth2SummitEventsApiController::getOverflowStreamingInfo summit %s %s %s",
                        $summit_id,
                        $overflow_stream_key,
                        $query_string_key
                    )
                );

                $event = $this->event_repository->getBySummitAndOverflowStreamKey($summit, $overflow_stream_key);
                if(is_null($event)){
                    Log::debug(sprintf("OAuth2SummitEventsApiController::getOverflowStreamingInfo Event %s not found.", $overflow_stream_key));
                }
                if (!$event instanceof SummitEvent)
                    return $this->error404("Summit event not found.");

                if(!$event->isOnOverflow())
                    return $this->error412("Summit event has not overflow set.");

                return $this->ok(SerializerRegistry::getInstance()
                    ->getSerializer($event, IPresentationSerializerTypes::OverflowStream)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                    )
                );
            });
        });
    }

    #[OA\Put(
        path: '/api/v1/summits/{id}/events/{event_id}/overflow',
        operationId: 'setOverflow',
        summary: 'Set overflow streaming for an event',
        description: 'Configures overflow streaming settings for a specific event.',
        security: [['summit_events_api_oauth2' => [SummitScopes::WriteEventData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SetOverflowRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Overflow set successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEvent')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation error'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function setOverflow($summit_id, $event_id) {
         return $this->processRequest(function() use($summit_id, $event_id){

            Log::debug(sprintf("OAuth2SummitEventsApiController::setOverflow summit id %s event id %s", $summit_id, $event_id));

            if (!Request::isJson()) return $this->error400();

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $event = $summit->getEvent(intval($event_id));
            if (is_null($event))
                return $this->error404();

            $payload = $this->getJsonPayload(SummitEventValidationRulesFactory::buildForOverflowInfo(), true);

            $event = $this->service->updateOverflowInfo($summit, intval($event_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($event, $this->getSerializerType())
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                )
            );
        });
    }
    #[OA\Delete(
        path: '/api/v1/summits/{id}/events/{event_id}/overflow',
        operationId: 'clearOverflow',
        summary: 'Clear overflow streaming for an event',
        description: 'Removes overflow streaming settings from a specific event.',
        security: [['summit_events_api_oauth2' => [SummitScopes::WriteEventData]]],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/ClearOverflowRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Overflow cleared successfully', content: new OA\JsonContent(ref: '#/components/schemas/SummitEvent')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad Request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]

    public function clearOverflow($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $event = $summit->getEvent(intval($event_id));
            if (is_null($event))
                return $this->error404();

            $payload = $this->getJsonPayload(SummitEventValidationRulesFactory::buildForClearOverFlowInfo(), true);

            $event = $this->service->removeOverflowState($summit, $event->getId(), $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($event, $this->getSerializerType())
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                )
            );
        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/events/{event_id}/published/streaming-info',
        operationId: 'getScheduledEventStreamingInfo',
        summary: 'Get streaming information for a published event',
        description: 'Retrieves streaming information for a specific published event.',
        security: [['summit_events_api_oauth2' => [SummitScopes::ReadSummitData, SummitScopes::ReadAllSummitData]]],
        tags: ['Summit Events'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Not Found'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function getScheduledEventStreamingInfo($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            throw new EntityNotFoundException("Summit Event Not found.");
            /*
            Log::debug(sprintf("getScheduledEventStreamingInfo::getScheduledEventStreamingInfo summit id %s event id %s", $summit_id, $event_id));


            $current_user = $this->resource_server_context->getCurrentUser(false);
            if(!$current_user instanceof Member)
                throw new \HTTP401UnauthorizedException();

            return $this->withReplica(function() use($summit_id, $event_id, $current_user){
                $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
                if (is_null($summit)) throw new EntityNotFoundException;

                $event = $this->service->getEventForStreamingInfo($summit, $current_user, intval($event_id));
                if (is_null($event)) throw new EntityNotFoundException;

                return SerializerRegistry::getInstance()->getSerializer($event, IPresentationSerializerTypes::StreamingInfo)->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                );
            });
            */

        });
    }
}
