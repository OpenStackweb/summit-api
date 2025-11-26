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

use App\Models\Foundation\Main\IGroup;
use App\ModelSerializers\SerializerUtils;
use App\Rules\Boolean;
use App\Security\SummitScopes;
use App\Services\Model\ISummitMetricService;
use Illuminate\Http\Response;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitMetricType;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;

/**
 * Class OAuth2SummitMetricsApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitMetricsApiController extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;

    use RequestProcessor;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitMetricService
     */
    private $service;

    /**
     * OAuth2SummitMembersApiController constructor.
     * @param IMemberRepository $member_repository
     * @param ISummitRepository $summit_repository
     * @param ISummitMetricService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IMemberRepository      $member_repository,
        ISummitRepository      $summit_repository,
        ISummitMetricService   $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->summit_repository = $summit_repository;
        $this->repository = $member_repository;
        $this->service = $service;
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    #[OA\Put(
        path: "/api/v1/summits/{id}/metrics/enter",
        operationId: 'enter',
        summary: "Record a metric entry (enter)",
        security: [["summit_metrics_oauth2" => [SummitScopes::EnterEvent, SummitScopes::WriteMetrics]]],
        tags: ["Summit Metrics"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(ref: "#/components/schemas/SummitMetricEnterRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitMetric")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function enter($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload(
                [
                    'type' => 'required|string|in:' . implode(",", ISummitMetricType::ValidTypes),
                    'source_id' => 'sometimes|integer',
                    'location' => 'sometimes|string',
                ]
            );

            $metric = $this->service->enter($summit, $current_member, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($metric)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/metrics/leave",
        operationId: 'leave',
        summary: "Record a metric exit (leave)",
        security: [["summit_metrics_oauth2" => [SummitScopes::LeaveEvent, SummitScopes::WriteMetrics]]],
        tags: ["Summit Metrics"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(ref: "#/components/schemas/SummitMetricLeaveRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitMetric")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function leave($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();


            $payload = $this->getJsonPayload(
                [
                    'type' => 'required|string|in:' . implode(",", ISummitMetricType::ValidTypes),
                    'source_id' => 'sometimes|integer',
                    'location' => 'sometimes|string',
                ]
            );
            $metric = $this->service->leave($summit, $current_member, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($metric)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    #[OA\Put(
        path: "/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}/metrics/enter",
        operationId: 'enterToEvent',
        summary: "Record a metric entry to a specific event",
        security: [["summit_metrics_oauth2" => [SummitScopes::EnterEvent]]],
        tags: ["Summit Metrics"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "member_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string", enum: ["me"]),
                description: "The member id (must be 'me')"
            ),
            new OA\Parameter(
                name: "event_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The event id"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitMetric")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function enterToEvent($summit_id, $member_id, $event_id)
    {
        return $this->processRequest(function () use ($summit_id, $member_id, $event_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $metric = $this->service->enter($summit, $current_member, [
                'type' => ISummitMetricType::Event,
                'source_id' => intval($event_id)
            ]);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($metric)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}/metrics/leave",
        operationId: 'leaveFromEvent',
        summary: "Record a metric exit from a specific event",
        security: [["summit_metrics_oauth2" => [SummitScopes::LeaveEvent]]],
        tags: ["Summit Metrics"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "member_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string", enum: ["me"]),
                description: "The member id (must be 'me')"
            ),
            new OA\Parameter(
                name: "event_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The event id"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitMetric")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function leaveFromEvent($summit_id, $member_id, $event_id)
    {
        return $this->processRequest(function () use ($summit_id, $member_id, $event_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $metric = $this->service->leave($summit, $current_member, [
                'type' => ISummitMetricType::Event,
                'source_id' => intval($event_id)
            ]);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($metric)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/metrics/onsite/enter",
        operationId: 'onSiteEnter',
        summary: "Record an on-site metric entry (for attendees entering venue/room)",
        security: [["summit_metrics_oauth2" => [SummitScopes::WriteMetrics]]],
        tags: ["Summit Metrics"],
        x: [
            "authz_groups" => [IGroup::SummitAccessControl]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(ref: "#/components/schemas/SummitMetricOnSiteEnterRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitMetric")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function onSiteEnter($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload([
                'attendee_id' => 'required|integer',
                'room_id' => 'sometimes|integer',
                'event_id' => 'sometimes|integer',
                'ticket_number' => 'sometimes|string',
                'required_access_levels' => 'sometimes|int_array',
                'check_ingress' =>  ['sometimes', new Boolean],
            ]);

            $metric = $this->service->onSiteEnter($summit, $current_member, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($metric)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/metrics/onsite/enter",
        operationId: 'checkOnSiteEnter',
        summary: "Check if on-site entry is allowed for an attendee (validation only, does not record entry)",
        security: [["summit_metrics_oauth2" => [SummitScopes::ReadAllSummitData, SummitScopes::ReadSummitData, SummitScopes::ReadMetrics]]],
        tags: ["Summit Metrics"],
        x: [
            "authz_groups" => [IGroup::SummitAccessControl]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "attendee_id",
                in: "query",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The attendee id"
            ),
            new OA\Parameter(
                name: "room_id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer"),
                description: "The room id"
            ),
            new OA\Parameter(
                name: "event_id",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer"),
                description: "The event id"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitMetric")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function checkOnSiteEnter($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload([
                'attendee_id' => 'required|integer',
                'room_id' => 'sometimes|integer',
                'event_id' => 'sometimes|integer',
            ], false);

            $metric = $this->service->checkOnSiteEnter($summit, $current_member, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($metric)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/metrics/onsite/leave",
        operationId: 'onSiteLeave',
        summary: "Record an on-site metric exit (for attendees leaving venue/room)",
        security: [["summit_metrics_oauth2" => [SummitScopes::WriteMetrics]]],
        tags: ["Summit Metrics"],
        x: [
            "authz_groups" => [IGroup::SummitAccessControl]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(ref: "#/components/schemas/SummitMetricOnSiteLeaveRequest")
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitMetric")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function onSiteLeave($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload([
                'attendee_id' => 'required|integer',
                'room_id' => 'sometimes|integer',
                'event_id' => 'sometimes|integer',
                'required_access_levels' => 'sometimes|int_array',
            ]);

            $metric = $this->service->onSiteLeave($summit, $current_member, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($metric)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }
}
