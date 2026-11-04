<?php

namespace App\Http\Controllers;

/**
 * Copyright 2026 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeCheckInLogRepository;
use App\Security\SummitScopes;
use Illuminate\Http\Response;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use utils\Filter;
use utils\FilterElement;

/**
 * Class OAuth2SummitAttendeeCheckInLogApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitAttendeeCheckInLogApiController extends OAuth2ProtectedController
{
    public function __construct
    (
        ISummitRepository                   $summit_repository,
        ISummitAttendeeCheckInLogRepository $repository,
        IResourceServerContext              $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
    }

    use ParametrizedGetAll;

    private function getAllowedFilters(): array
    {
        return [
            'id' => ['=='],
            'action' => ['=='],
            'source' => ['=='],
            'actor_id' => ['=='],
            'client_id' => ['==', '@@', '=@'],
            'created' => ['>', '<', '<=', '>=', '==', '[]'],
            'actor_email' => ['==', '@@', '=@'],
        ];
    }

    private function getFilterValidatorRules(): array
    {
        return [
            'id' => 'sometimes|integer',
            'action' => 'sometimes|string|in:CHECKED_IN,CHECKED_OUT',
            'source' => 'sometimes|string|in:ADMIN_UI,BADGE_SCAN,BADGE_PRINT',
            'actor_id' => 'sometimes|integer',
            'client_id' => 'sometimes|string',
            'created' => 'sometimes|date_format:U|epoch_seconds',
            'actor_email' => 'sometimes|string',
        ];
    }

    private function getAllowedOrderFields(): array
    {
        return ['id', 'created', 'action', 'source', 'actor_email'];
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/attendees/{attendee_id}/check-in-logs",
        operationId: "getAllAttendeeCheckInLogs",
        summary: "Get the check in / check out history of an attendee",
        description: "Returns a paginated list of check in / check out records for a specific attendee. Allows ordering, filtering and pagination.",
        security: [
            [
                "summit_attendee_check_in_log_oauth2" => [
                    SummitScopes::ReadAllSummitData
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        tags: ["Summit Attendee Check In Logs"],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'The summit id'),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'The attendee id'),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'The page number'),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'The number of items per page'),
            new OA\Parameter(
                name: "filter[]",
                in: "query",
                required: false,
                description: "Filter check in logs. Available filters: id==, action==, source==, actor_id==, client_id (==, @@, =@), created (>, <, <=, >=, ==, []), actor_email (==, @@, =@)",
                schema: new OA\Schema(type: "array", items: new OA\Items(type: "string")),
                explode: true
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                description: "Order by field. Valid fields: id, created, action, source, actor_email",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                description: "Expand related entities. Available expansions: actor",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Success",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedSummitAttendeeCheckInLogsResponse")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit or attendee not found"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Invalid filter or order parameter"),
        ]
    )]
    public function getAllByAttendee($summit_id, $attendee_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit))
            return $this->error404();

        $attendee = $summit->getAttendeeById(intval($attendee_id));
        if (is_null($attendee))
            return $this->error404();

        return $this->_getAll(
            function () {
                return $this->getAllowedFilters();
            },
            function () {
                return $this->getFilterValidatorRules();
            },
            function () {
                return $this->getAllowedOrderFields();
            },
            function ($filter) use ($summit, $attendee) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('attendee_id', $attendee->getId()));
                }
                return $filter;
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/attendees/{attendee_id}/check-in-logs/csv",
        operationId: "getAllAttendeeCheckInLogsCSV",
        summary: "Export the check in / check out history of an attendee to CSV",
        description: "Exports all check in / check out records for a specific attendee to CSV format. Allows ordering and filtering.",
        security: [
            [
                "summit_attendee_check_in_log_oauth2" => [
                    SummitScopes::ReadAllSummitData
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        tags: ["Summit Attendee Check In Logs"],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'The summit id'),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'The attendee id'),
            new OA\Parameter(
                name: "filter[]",
                in: "query",
                required: false,
                description: "Filter check in logs. Available filters: id==, action==, source==, actor_id==, client_id (==, @@, =@), created (>, <, <=, >=, ==, []), actor_email (==, @@, =@)",
                schema: new OA\Schema(type: "array", items: new OA\Items(type: "string")),
                explode: true
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                description: "Order by field. Valid fields: id, created, action, source, actor_email",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "CSV file",
                content: new OA\MediaType(
                    mediaType: "text/csv",
                    schema: new OA\Schema(type: "string", format: "binary")
                )
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit or attendee not found"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Invalid filter or order parameter"),
        ]
    )]
    public function getAllByAttendeeCSV($summit_id, $attendee_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit))
            return $this->error404();

        $attendee = $summit->getAttendeeById(intval($attendee_id));
        if (is_null($attendee))
            return $this->error404();

        return $this->_getAllCSV(
            function () {
                return $this->getAllowedFilters();
            },
            function () {
                return $this->getFilterValidatorRules();
            },
            function () {
                return $this->getAllowedOrderFields();
            },
            function ($filter) use ($summit, $attendee) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('attendee_id', $attendee->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_CSV;
            },
            function () {
                return [
                    'created' => new EpochCellFormatter(),
                    'last_edited' => new EpochCellFormatter(),
                ];
            },
            function () {
                return [];
            },
            'attendee-check-in-logs-'
        );
    }
}
