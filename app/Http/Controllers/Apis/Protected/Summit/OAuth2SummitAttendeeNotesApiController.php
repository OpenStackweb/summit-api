<?php namespace App\Http\Controllers;
/**
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
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeNoteRepository;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\IAttendeeService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use utils\Filter;
use utils\FilterElement;

/**
 * Class OAuth2SummitAttendeeNotesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitAttendeeNotesApiController extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;

    use RequestProcessor;

    use ParametrizedGetAll;

    /**
     * @var IAttendeeService
     */
    private $attendee_service;

    /**
     * @var ISummitAttendeeRepository
     */
    private $attendee_repository;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * OAuth2SummitAttendeeNotesApiController constructor.
     * @param ISummitAttendeeNoteRepository $attendee_notes_repository
     * @param ISummitAttendeeRepository $attendee_repository
     * @param ISummitRepository $summit_repository
     * @param IAttendeeService $attendee_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitAttendeeNoteRepository $attendee_notes_repository,
        ISummitAttendeeRepository     $attendee_repository,
        ISummitRepository             $summit_repository,
        IAttendeeService              $attendee_service,
        IResourceServerContext        $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->attendee_repository = $attendee_repository;
        $this->summit_repository = $summit_repository;
        $this->repository = $attendee_notes_repository;
        $this->attendee_service = $attendee_service;
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/attendees/notes",
        summary: "Get all attendee notes for a summit",
        description: "Returns all notes for all attendees in the summit. Admin access required.",
        security: [["bearer_token" => []]],
        tags: ["AttendeeNotes"],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID or slug", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "page", description: "Page number", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", description: "Items per page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(
                name: "filter",
                description: "Filter query (owner_id==value, owner_fullname=@value, owner_email=@value, ticket_id==value, content=@value, author_fullname=@value, author_email=@value, created>=timestamp, edited<=timestamp)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "order",
                description: "Order by (+id, -created, +author_fullname, +author_email, +owner_fullname, +owner_email)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(name: "expand", description: "Expand relations (author, owner, ticket)", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
                        new OA\Schema(
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "data",
                                    type: "array",
                                    items: new OA\Items(ref: "#/components/schemas/SummitAttendeeNote")
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllAttendeeNotes($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'owner_id' => ['=='],
                    'owner_fullname' => ['=@', '==', '@@'],
                    'owner_email' => ['=@', '==', '@@'],
                    'ticket_id' => ['=='],
                    'content' => ['=@', '@@'],
                    'author_fullname' => ['=@', '==', '@@'],
                    'author_email' => ['=@', '==', '@@'],
                    'created' => ['==', '>=', '<=', '>', '<'],
                    'edited' => ['==', '>=', '<=', '>', '<'],
                ];
            },
            function () {
                return [
                    'owner_id' => 'sometimes|integer',
                    'owner_fullname' => 'sometimes|string',
                    'owner_email' => 'sometimes|string',
                    'ticket_id' => 'sometimes|integer',
                    'content' => 'sometimes|string',
                    'author_fullname' => 'sometimes|string',
                    'author_email' => 'sometimes|string',
                    'created' => 'sometimes|required|date_format:U|epoch_seconds',
                    'edited' => 'sometimes|required|date_format:U|epoch_seconds',
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'author_fullname',
                    'author_email',
                    'owner_id',
                    'owner_fullname',
                    'owner_email',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/attendees/notes/csv",
        summary: "Export all attendee notes for a summit in CSV format",
        security: [["bearer_token" => []]],
        tags: ["AttendeeNotes"],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID or slug", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter", description: "Filter query", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", description: "Order by", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK - CSV file download",
                content: new OA\MediaType(
                    mediaType: "text/csv",
                    schema: new OA\Schema(type: "string", format: "binary")
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllAttendeeNotesCSV($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'owner_id' => ['=='],
                    'owner_fullname' => ['=@', '==', '@@'],
                    'owner_email' => ['=@', '==', '@@'],
                    'ticket_id' => ['=='],
                    'content' => ['=@', '@@'],
                    'author_fullname' => ['=@', '==', '@@'],
                    'author_email' => ['=@', '==', '@@'],
                    'created' => ['==', '>=', '<=', '>', '<'],
                    'edited' => ['==', '>=', '<=', '>', '<'],
                ];
            },
            function () {
                return [
                    'owner_id' => 'sometimes|integer',
                    'owner_fullname' => 'sometimes|string',
                    'owner_email' => 'sometimes|string',
                    'ticket_id' => 'sometimes|integer',
                    'content' => 'sometimes|string',
                    'author_fullname' => 'sometimes|string',
                    'author_email' => 'sometimes|string',
                    'created' => 'sometimes|required|date_format:U|epoch_seconds',
                    'edited' => 'sometimes|required|date_format:U|epoch_seconds',
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'author_fullname',
                    'author_email',
                    'owner_id',
                    'owner_fullname',
                    'owner_email',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
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
            'attendees-notes-'
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/attendees/{attendee_id}/notes",
        summary: "Get all notes for a specific attendee",
        security: [["bearer_token" => []]],
        tags: ["AttendeeNotes"],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID or slug", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "attendee_id", description: "Attendee ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "page", description: "Page number", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", description: "Items per page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(
                name: "filter",
                description: "Filter query (ticket_id==value, content=@value, author_fullname=@value, author_email=@value, created>=timestamp)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(name: "order", description: "Order by (+id, -created, +author_fullname, +author_email)", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "expand", description: "Expand relations (author, owner, ticket)", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
                        new OA\Schema(
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "data",
                                    type: "array",
                                    items: new OA\Items(ref: "#/components/schemas/SummitAttendeeNote")
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Attendee does not belong to summit"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAttendeeNotes($summit_id, $attendee_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $attendee = $this->attendee_repository->getById(intval($attendee_id));
        if (is_null($attendee)) return $this->error404();

        if ($attendee->getSummit()->getId() != intval($summit_id))
            return $this->error412("Attendee id {$attendee_id} does not belong to summit {$summit_id}.");

        return $this->_getAll(
            function () {
                return [
                    'ticket_id' => ['=='],
                    'content' => ['=@', '@@'],
                    'author_fullname' => ['=@', '==', '@@'],
                    'author_email' => ['=@', '==', '@@'],
                    'created' => ['==', '>=', '<=', '>', '<'],
                    'edited' => ['==', '>=', '<=', '>', '<'],
                ];
            },
            function () {
                return [
                    'ticket_id' => 'sometimes|integer',
                    'content' => 'sometimes|string',
                    'author_fullname' => 'sometimes|string',
                    'author_email' => 'sometimes|string',
                    'created' => 'sometimes|required|date_format:U|epoch_seconds',
                    'edited' => 'sometimes|required|date_format:U|epoch_seconds',
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'author_fullname',
                    'author_email',
                ];
            },
            function ($filter) use ($summit, $attendee) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('owner_id', $attendee->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/attendees/{attendee_id}/notes/csv",
        summary: "Export notes for a specific attendee in CSV format",
        security: [["bearer_token" => []]],
        tags: ["AttendeeNotes"],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID or slug", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "attendee_id", description: "Attendee ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "filter", description: "Filter query", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", description: "Order by", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK - CSV file download",
                content: new OA\MediaType(
                    mediaType: "text/csv",
                    schema: new OA\Schema(type: "string", format: "binary")
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Attendee does not belong to summit"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAttendeeNotesCSV($summit_id, $attendee_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $attendee = $this->attendee_repository->getById(intval($attendee_id));
        if (is_null($attendee)) return $this->error404();

        if ($attendee->getSummit()->getId() != intval($summit_id))
            return $this->error412("Attendee id {$attendee_id} does not belong to summit {$summit_id}.");

        return $this->_getAllCSV(
            function () {
                return [
                    'ticket_id' => ['=='],
                    'content' => ['=@', '@@'],
                    'author_fullname' => ['=@', '==', '@@'],
                    'author_email' => ['=@', '==', '@@'],
                    'created' => ['==', '>=', '<=', '>', '<'],
                    'edited' => ['==', '>=', '<=', '>', '<'],
                ];
            },
            function () {
                return [
                    'ticket_id' => 'sometimes|integer',
                    'content' => 'sometimes|string',
                    'author_fullname' => 'sometimes|string',
                    'author_email' => 'sometimes|string',
                    'created' => 'sometimes|required|date_format:U|epoch_seconds',
                    'edited' => 'sometimes|required|date_format:U|epoch_seconds',
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'author_fullname',
                    'author_email',
                ];
            },
            function ($filter) use ($summit, $attendee) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('owner_id', $attendee->getId()));
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
            sprintf('attendee-%s-notes-', $attendee_id)
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/attendees/{attendee_id}/notes/{note_id}",
        summary: "Get a specific attendee note",
        security: [["bearer_token" => []]],
        tags: ["AttendeeNotes"],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID or slug", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "attendee_id", description: "Attendee ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "note_id", description: "Note ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "expand", description: "Expand relations (author, owner, ticket)", in: "query", required: false, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitAttendeeNote")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAttendeeNote($summit_id, $attendee_id, $note_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id, $note_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $attendee = $summit->getAttendeeById(intval($attendee_id));
            if (is_null($attendee)) return $this->error404("Attendee id {$attendee_id} not found in summit {$summit_id}.");

            $attendee_note = $attendee->getNoteById(intval($note_id));
            if (is_null($attendee_note)) return $this->error404("Attendee note id {$note_id} not found for attendee {$attendee_id}.");

            return $this->ok
            (
                SerializerRegistry::getInstance()->getSerializer
                (
                    $attendee_note,
                    SerializerRegistry::SerializerType_Private
                )->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    ['serializer_type' => SerializerRegistry::SerializerType_Private]
                )
            );
        });
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/attendees/{attendee_id}/notes",
        summary: "Add a note to an attendee",
        security: [["bearer_token" => []]],
        tags: ["AttendeeNotes"],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID or slug", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "attendee_id", description: "Attendee ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["content"],
                properties: [
                    new OA\Property(property: "content", type: "string", description: "Note content"),
                    new OA\Property(property: "ticket_id", type: "integer", description: "Optional ticket ID to associate with the note"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitAttendeeNote")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addAttendeeNote($summit_id, $attendee_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $member = $this->resource_server_context->getCurrentUser();
            if (is_null($member)) return $this->error403();

            $payload = $this->getJsonPayload(SummitAttendeeNoteValidationRulesFactory::buildForAdd(), true);

            $note = $this->attendee_service->upsertAttendeeNote($summit, $member, intval($attendee_id), null, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($note, SerializerRegistry::SerializerType_Private)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/attendees/{attendee_id}/notes/{note_id}",
        summary: "Update an attendee note",
        security: [["bearer_token" => []]],
        tags: ["AttendeeNotes"],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID or slug", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "attendee_id", description: "Attendee ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "note_id", description: "Note ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "content", type: "string", description: "Note content"),
                    new OA\Property(property: "ticket_id", type: "integer", description: "Optional ticket ID to associate with the note"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitAttendeeNote")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function updateAttendeeNote($summit_id, $attendee_id, $note_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id, $note_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $member = $this->resource_server_context->getCurrentUser();
            if (is_null($member)) return $this->error403();

            $payload = $this->getJsonPayload(SummitAttendeeNoteValidationRulesFactory::buildForUpdate(), true);

            $note = $this->attendee_service->upsertAttendeeNote($summit, $member, intval($attendee_id), intval($note_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($note, SerializerRegistry::SerializerType_Private)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/attendees/{attendee_id}/notes/{note_id}",
        summary: "Delete an attendee note",
        security: [["bearer_token" => []]],
        tags: ["AttendeeNotes"],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID or slug", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "attendee_id", description: "Attendee ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "note_id", description: "Note ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: "No Content"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function deleteAttendeeNote($summit_id, $attendee_id, $note_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id, $note_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->attendee_service->deleteAttendeeNote($summit, intval($attendee_id), intval($note_id));

            return $this->deleted();
        });
    }
}