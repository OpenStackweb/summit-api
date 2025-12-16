<?php

namespace App\Http\Controllers;

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

use App\Http\Utils\EpochCellFormatter;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgePrintRepository;
use App\Security\SummitScopes;
use App\Swagger\Security\BadgePrintsAuthSchema;
use Illuminate\Http\Response;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use services\model\ISummitAttendeeBadgePrintService;
use utils\Filter;
use utils\FilterElement;

/**
 * Class OAuth2SummitAttendeeBadgePrintApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitAttendeeBadgePrintApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitAttendeeBadgePrintService
     */
    private $service;

    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitAttendeeBadgePrintRepository $repository,
        ISummitAttendeeBadgePrintService $service,
        IResourceServerContext $resource_server_context
    ) {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    use ParametrizedGetAll;

    #[OA\Get(
        path: "/api/v1/summits/{id}/tickets/{ticket_id}/badge/current/prints",
        operationId: "getAllBadgePrintsByTicket",
        summary: "Get all badge prints for a ticket",
        description: "Returns a paginated list of badge print records for a specific ticket. Allows ordering, filtering and pagination.",
        security: [
            [
                "summit_attendee_badge_print_oauth2" => [
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
        tags: ["Summit Badge Prints"],
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
                schema: new OA\Schema(type: 'integer'),
                description: 'The page number'
            ),
            new OA\Parameter(
                name: 'page_size',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'The number of pages in each page',
            ),
            new OA\Parameter(
                name: "ticket_id",
                in: "path",
                required: true,
                description: "Ticket ID",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "filter[]",
                in: "query",
                required: false,
                description: "Filter badge prints. Available filters: id==, view_type_id==, created (>, <, <=, >=, ==, []), print_date (>, <, <=, >=, ==, []), requestor_full_name (==, @@, =@), requestor_email (==, @@, =@)",
                schema: new OA\Schema(type: "array", items: new OA\Items(type: "string")),
                explode: true
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                description: "Order by field. Valid fields: id, created, view_type_id, print_date, requestor_full_name, requestor_email",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                description: "Expand related entities. Available expansions: requestor, badge, view_type",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Success",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedSummitAttendeeBadgePrintsResponse")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit or ticket not found"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Invalid filter or order parameter"),
        ]
    )]
    public function getAllBySummitAndTicket($summit_id, $ticket_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit))
            return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'id' => ['=='],
                    'view_type_id' => ['=='],
                    'created' => ['>', '<', '<=', '>=', '==', '[]'],
                    'print_date' => ['>', '<', '<=', '>=', '==', '[]'],
                    'requestor_full_name' => ['==', '@@', '=@'],
                    'requestor_email' => ['==', '@@', '=@'],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'view_type_id' => 'sometimes|integer',
                    'created' => 'sometimes|date_format:U|epoch_seconds',
                    'print_date' => 'sometimes|date_format:U|epoch_seconds',
                    'requestor_full_name' => 'sometimes|string',
                    'requestor_email' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'view_type_id',
                    'print_date',
                    'requestor_full_name',
                    'requestor_email',
                ];
            },
            function ($filter) use ($summit, $ticket_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('ticket_id', intval($ticket_id)));
                }
                return $filter;
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/tickets/{ticket_id}/badge/current/prints/csv",
        operationId: "getAllBadgePrintsByTicketCSV",
        summary: "Export badge prints to CSV",
        description: "Exports all badge print records for a specific ticket to CSV format. Allows ordering and filtering.",
        security: [
            [
                "summit_attendee_badge_print_oauth2" => [
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
        tags: ["Summit Badge Prints"],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: "ticket_id",
                in: "path",
                required: true,
                description: "Ticket ID",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "filter[]",
                in: "query",
                required: false,
                description: "Filter badge prints. Available filters: id==, view_type_id==, created (>, <, <=, >=, ==, []), print_date (>, <, <=, >=, ==, []), requestor_full_name (==, @@, =@), requestor_email (==, @@, =@)",
                schema: new OA\Schema(type: "array", items: new OA\Items(type: "string")),
                explode: true
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                description: "Order by field. Valid fields: id, created, view_type_id, print_date, requestor_full_name, requestor_email",
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
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit or ticket not found"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Invalid filter or order parameter"),
        ]
    )]
    public function getAllBySummitAndTicketCSV($summit_id, $ticket_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit))
            return $this->error404();

        return $this->_getAllCSV(
            function () {
                return [
                    'id' => ['=='],
                    'view_type_id' => ['=='],
                    'created' => ['>', '<', '<=', '>=', '==', '[]'],
                    'print_date' => ['>', '<', '<=', '>=', '==', '[]'],
                    'requestor_full_name' => ['==', '@@', '=@'],
                    'requestor_email' => ['==', '@@', '=@'],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'view_type_id' => 'sometimes|integer',
                    'created' => 'sometimes|date_format:U|epoch_seconds',
                    'print_date' => 'sometimes|date_format:U|epoch_seconds',
                    'requestor_full_name' => 'sometimes|string',
                    'requestor_email' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'view_type_id',
                    'print_date',
                    'requestor_full_name',
                    'requestor_email',
                ];
            },
            function ($filter) use ($summit, $ticket_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('ticket_id', intval($ticket_id)));
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
                    'print_date' => new EpochCellFormatter(),
                ];
            },
            function () {
                return [];
            },
            'badge-prints-'
        );
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/tickets/{ticket_id}/badge/current/prints",
        operationId: "deleteBadgePrintsByTicket",
        summary: "Delete all badge prints for a ticket",
        description: "Deletes all badge print records for a specific ticket",
        security: [
            [
                "summit_attendee_badge_print_oauth2" => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::UpdateRegistrationOrders
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
        tags: ["Summit Badge Prints"],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: "ticket_id",
                in: "path",
                required: true,
                description: "Ticket ID",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "Badge prints deleted successfully"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit or ticket not found"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
        ]
    )]
    public function deleteBadgePrints($summit_id, $ticket_id)
    {
        return $this->processRequest(function () use ($summit_id, $ticket_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find(intval($summit_id));
            if (is_null($summit))
                return $this->error404();

            $this->service->deleteBadgePrintsByTicket($summit, intval($ticket_id));

            return $this->deleted();
        });
    }
}
