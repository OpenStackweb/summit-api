<?php namespace App\Http\Controllers;
/*
 * Copyright 2023 OpenStack Foundation
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
use App\Security\SummitScopes;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use App\Facades\ResourceServerContext;
use App\ModelSerializers\SerializerUtils;
use App\Rules\Boolean;
use App\Services\Model\IScheduleService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitProposedScheduleEventRepository;
use models\summit\ISummitProposedScheduleLockRepository;
use models\summit\ISummitRepository;
use models\utils\IBaseRepository;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class OAuth2SummitProposedScheduleApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitProposedScheduleApiController extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;

    use RequestProcessor;

    use ParametrizedGetAll;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitProposedScheduleLockRepository
     */
    private $schedule_lock_repository;

    /**
     * @var IScheduleService
     */
    private $service;

    /**
     * @param ISummitRepository $summit_repository
     * @param ISummitProposedScheduleEventRepository $repository
     * @param ISummitProposedScheduleLockRepository $schedule_lock_repository
     * @param IScheduleService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository                      $summit_repository,
        ISummitProposedScheduleEventRepository $repository,
        ISummitProposedScheduleLockRepository  $schedule_lock_repository,
        IScheduleService                       $service,
        IResourceServerContext                 $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->service = $service;
        $this->repository = $repository;
        $this->schedule_lock_repository = $schedule_lock_repository;
        $this->summit_repository = $summit_repository;
    }

    /**
     * @return IBaseRepository
     */
    protected function getRepository(): IBaseRepository
    {
        return $this->repository;
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/proposed-schedules/{source}/presentations",
        operationId: 'getProposedScheduleEvents',
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::TrackChairs . ", " . IGroup::TrackChairsAdmins,
        summary: "Get proposed schedule events for a specific source",
        tags: ["Summit Proposed Schedule"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins
            ]
        ],
        security: [['summit_proposed_schedule_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "source",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "The source identifier"
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1),
                description: "Page number"
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10),
                description: "Items per page"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                explode: false,
                schema: new OA\Schema(type: "string"),
                description: "Filter operators: start_date==/</>/<=/>=/ [], end_date==/</>/<=/>=/ [], duration==/</>/<=/>=, presentation_title@@/=@, presentation_id==, location_id==, track_id==, type_show_always_on_schedule=="
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                explode: false,
                schema: new OA\Schema(type: "string"),
                description: "Order by fields: start_date, end_date, presentation_id, presentation_title, track_id"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedSummitProposedScheduleSummitEventsResponse")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getProposedScheduleEvents($summit_id, $source)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'start_date' => ['==', '<', '>', '>=', '<=','[]'],
                    'end_date' => ['==', '<', '>', '>=', '<=','[]'],
                    'duration' => ['==', '<', '>', '>=', '<='],
                    'presentation_title' => ['@@', '=@'],
                    'presentation_id' => ['=='],
                    'location_id' => ['=='],
                    'track_id' => ['=='],
                    'type_show_always_on_schedule' => ['=='],
                ];
            },
            function () {
                return [
                    'start_date' => 'sometimes|date_format:U|epoch_seconds',
                    'end_date' => 'sometimes|required_with:start_date|date_format:U|epoch_seconds|after:start_date',
                    'duration' => 'sometimes|integer',
                    'presentation_title' => 'sometimes|string',
                    'presentation_id' => 'sometimes|integer',
                    'location_id' => 'sometimes|integer',
                    'track_id' => 'sometimes|integer',
                    'type_show_always_on_schedule' => ['sometimes', new Boolean]
                ];
            },
            function () {
                return [
                    'start_date',
                    'end_date',
                    'presentation_id',
                    'presentation_title',
                    'track_id'
                ];
            },
            function ($filter) use ($summit_id, $source) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', intval($summit_id)));
                    $filter->addFilterCondition(FilterElement::makeEqual('source', $source));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }

    /**
     * @param $summit_id
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: "/api/v1/summits/{id}/proposed-schedules/{source}/presentations/{presentation_id}/propose",
        operationId: 'publishProposedSchedulePresentation',
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::TrackChairs . ", " . IGroup::TrackChairsAdmins,
        summary: "Publish a presentation to the proposed schedule",
        tags: ["Summit Proposed Schedule"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins
            ]
        ],
        security: [['summit_proposed_schedule_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "source",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "The source identifier"
            ),
            new OA\Parameter(
                name: "presentation_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The presentation id"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(ref: "#/components/schemas/SummitProposedSchedulePublishRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitProposedScheduleSummitEvent")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function publish($summit_id, $source, $presentation_id)
    {

        return $this->processRequest(function () use ($summit_id, $source, $presentation_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(ProposedScheduleValidationRulesFactory::buildForAdd());

            $schedule_event =
                $this->service->publishProposedActivityToSource($source, intval($presentation_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($schedule_event)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Delete(
        path: "/api/v1/summits/{id}/proposed-schedules/{source}/presentations/{presentation_id}/propose",
        operationId: 'unpublishProposedSchedulePresentation',
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::TrackChairs . ", " . IGroup::TrackChairsAdmins,
        summary: "Unpublish a presentation from the proposed schedule",
        tags: ["Summit Proposed Schedule"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins
            ]
        ],
        security: [['summit_proposed_schedule_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "source",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "The source identifier"
            ),
            new OA\Parameter(
                name: "presentation_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The presentation id"
            )
        ],
        responses: [
            new OA\Response(response: 204, description: "No Content"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function unpublish($summit_id, $source, $presentation_id)
    {

        return $this->processRequest(function () use ($summit_id, $source, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit))
                return $this->error404();

            $this->service->unPublishProposedActivity($source, intval($presentation_id));

            return $this->deleted();
        });
    }

    use ParseAndGetFilter;

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: "/api/v1/summits/{id}/proposed-schedules/{source}/presentations/all/publish",
        operationId: 'publishAllProposedSchedulePresentations',
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators . ", " . IGroup::TrackChairs . ", " . IGroup::TrackChairsAdmins,
        summary: "Publish all presentations to the proposed schedule with optional filters",
        tags: ["Summit Proposed Schedule"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins
            ]
        ],
        security: [['summit_proposed_schedule_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "source",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "The source identifier"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                explode: false,
                schema: new OA\Schema(type: "string"),
                description: "Filter operators: start_date==/</>/<=/>=/ [], end_date==/</>/<=/>=/ [], location_id==, track_id=="
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(ref: "#/components/schemas/SummitProposedSchedulePublishAllRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitProposedSchedulePublishAllResponse")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function publishAll($summit_id, $source)
    {

        return $this->processRequest(function () use ($summit_id, $source) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(ProposedScheduleValidationRulesFactory::buildForUpdate());

            $filter = self::getFilter(function () {
                return [
                    'start_date' => ['==', '<', '>', '>=', '<=','[]'],
                    'end_date' => ['==', '<', '>', '>=', '<=','[]'],
                    'location_id' => ['=='],
                    'track_id' => ['=='],
                ];
            }, function () {
                return [
                    'start_date' => 'sometimes|date_format:U|epoch_seconds',
                    'end_date' => 'sometimes|required_with:start_date|date_format:U|epoch_seconds|after:start_date',
                    'location_id' => 'sometimes|integer',
                    'track_id' => 'sometimes|integer',
                ];
            });

            $schedule = $this->service->publishAll($source, $summit->getId(), $payload, $filter);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($schedule)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }


    /**
     * @param $summit_id
     * @param $source
     * @param $track_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/proposed-schedules/{source}/tracks/{track_id}/lock",
        operationId: 'sendProposedScheduleTrackToReview',
        description: "required-groups " . IGroup::TrackChairs . ", " . IGroup::TrackChairsAdmins,
        summary: "Send a track schedule for review (lock the track)",
        tags: ["Summit Proposed Schedule"],
        x: [
            'required-groups' => [
                IGroup::TrackChairs,
                IGroup::TrackChairsAdmins
            ]
        ],
        security: [['summit_proposed_schedule_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "source",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "The source identifier"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(ref: "#/components/schemas/SummitProposedScheduleLockRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitProposedScheduleLock")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function send2Review($summit_id, $source, $track_id)
    {
        return $this->processRequest(function () use ($summit_id, $source, $track_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(ProposedScheduleLockValidationRulesFactory::buildForAdd());

            $member = ResourceServerContext::getCurrentUser(false);

            $schedule = $this->service->send2Review($summit, $member, $source, intval($track_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($schedule)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $source
     * @param $track_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Delete(
        path: "/api/v1/summits/{id}/proposed-schedules/{source}/tracks/{track_id}/lock",
        operationId: 'removeProposedScheduleTrackReview',
        description: "required-groups " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitAdministrators,
        summary: "Remove review lock from a track schedule (unlock the track)",
        tags: ["Summit Proposed Schedule"],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators
            ]
        ],
        security: [['summit_proposed_schedule_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "source",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "The source identifier"
            ),
            new OA\Parameter(
                name: "track_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The track id"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(ref: "#/components/schemas/SummitProposedScheduleLockRequest")
            )
        ),
        responses: [
            new OA\Response(response: 204, description: "No Content"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function removeReview($summit_id, $source, $track_id)
    {
        return $this->processRequest(function () use ($summit_id, $source, $track_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(ProposedScheduleLockValidationRulesFactory::buildForUpdate());

            $this->service->removeReview($summit, $source, intval($track_id), $payload);

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $source
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/proposed-schedules/{source}/locks",
        operationId: 'getProposedScheduleReviewSubmissions',
        summary: "Get all proposed schedule review submissions (locks) for a source",
        tags: ["Summit Proposed Schedule"],
        security: [['summit_proposed_schedule_oauth2' => [
            SummitScopes::ReadSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "source",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "The source identifier"
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1),
                description: "Page number"
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10),
                description: "Items per page"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                explode: false,
                schema: new OA\Schema(type: "string"),
                description: "Filter operators: track_id=="
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                explode: false,
                schema: new OA\Schema(type: "string"),
                description: "Order by fields: track_id"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedSummitProposedScheduleLocksResponse")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getProposedScheduleReviewSubmissions($summit_id, $source)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'track_id' => ['=='],
                ];
            },
            function () {
                return [
                    'track_id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'track_id'
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit_id, $source) {
                return $this->schedule_lock_repository->getBySummitAndSource
                (
                    $summit_id,
                    $source,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }
}
