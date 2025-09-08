<?php namespace App\Http\Controllers;
/**
 * Copyright 2025 OpenStack Foundation
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

use App\Http\Controllers\Utils\Assertions;
use App\Http\Utils\BooleanCellFormatter;
use App\Http\Utils\EpochCellFormatter;
use App\Models\Foundation\Main\IGroup;
use App\ModelSerializers\SerializerUtils;
use App\Security\SummitScopes;
use App\Services\Model\ISummitRSVPService;
use Illuminate\Http\Response;
use models\oauth2\IResourceServerContext;
use models\summit\IRSVPRepository;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\RSVP;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use utils\Filter;
use utils\FilterElement;


class OAuth2RSVPApiController extends OAuth2ProtectedController
{
    use RequestProcessor;

    use Assertions;

    private ISummitRepository $summit_repository;

    private ISummitEventRepository $summit_event_repository;

    private ISummitRSVPService $service;

    private IRSVPRepository $rsvp_repository;

    /**
     * @param ISummitEventRepository $summit_event_repository
     * @param IRSVPRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitRSVPService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct(
        ISummitEventRepository       $summit_event_repository,
        IRSVPRepository              $repository,
        ISummitRepository            $summit_repository,
        ISummitRSVPService           $service,
        IResourceServerContext       $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
        $this->summit_event_repository = $summit_event_repository;
    }

    // traits
    use ParametrizedGetAll;

    use RequestProcessor;

    use GetAndValidateJsonPayload;

    use ValidateEventUri;

    #[OA\Post(
        path: "/api/v1/summits/{id}/events/{event_id}/rsvp",
        description: "",
        summary: 'Perform RSVP',
        operationId: 'doRSVP',
        tags: ['RSVP'],
        security: [['summit_rsvp_oauth2' => [
            SummitScopes::AddMyRSVP,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'summit_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'event_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The event id'
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'RSVP Created',
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function rsvp($summit_id, $event_id)
    {
        return $this->processRequest(function () use ($summit_id, $event_id) {

            $summit = $this->getSummitOr404($summit_id);

            $event = $this->getScheduleEventOr404($summit, $event_id);

            $current_member = $this->getCurrentMemberOr403();

            $payload = $this->getJsonPayload([
                'answers' => 'sometimes|rsvp_answer_dto_array',
                'event_uri' => 'sometimes|url',
            ]);

            $rsvp = $this->service->rsvpEvent($summit, $current_member, $event->getId(), $this->validateEventUri($payload));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($rsvp)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/events/{event_id}/unrsvp",
        description: "",
        summary: 'UnRSVP',
        operationId: 'unRSVP',
        tags: ['RSVP'],
        security: [['summit_rsvp_oauth2' => [
            SummitScopes::DeleteMyRSVP,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'summit_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'event_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The event id'
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'UnRSVP success',
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function unrsvp($summit_id, $event_id)
    {
        return $this->processRequest(function () use ($summit_id, $event_id) {

            $summit = $this->getSummitOr404($summit_id);

            $event = $this->getScheduleEventOr404($summit, $event_id);

            $current_member = $this->getCurrentMemberOr403();

            $this->service->unRSVPEvent($summit, $current_member, $event->getId());

            return $this->deleted();
        });
    }

    // CRUD Operations

    #[OA\Post(
        path: "/api/v1/summits/{id}/events/{event_id}/rsvps",
        description: "",
        summary: 'Perform RSVP Creation ( ADMIN )',
        operationId: 'addRSVP',
        tags: ['RSVP'],
        security: [['summit_rsvp_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'summit_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'event_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The event id'
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/RSVPAdminAddRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'RSVP Created',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVP')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function add($summit_id, $event_id){
        return $this->processRequest(function () use ($summit_id, $event_id) {

            $summit = $this->getSummitOr404($summit_id);

            $event = $this->getScheduleEventOr404($summit, $event_id);

            $payload = $this->getJsonPayload([
                'seat_type' => 'required|string|in:' . join(',', [
                        RSVP::SeatTypeRegular,
                        RSVP::SeatTypeWaitList,
                    ]),
                'answers' => 'sometimes|rsvp_answer_dto_array',
                'attendee_id' => 'required|integer',
            ]);

            $rsvp = $this->service->createRSVPFromPayload($summit, $event->getId(), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($rsvp)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/events/{event_id}/rsvps",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: 'Read RSVPS from event',
        operationId: 'readRSVP',
        tags: ['RSVP'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_rsvp_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'summit_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'event_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The event id'
            ),
            // query string params
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions in the format field<op>value. Operators: @@, ==, =@.',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'owner_email@@email@test.com')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'id,-seat_type')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to include',
                schema: new OA\Schema(type: 'string', example: 'event,owner')
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                description: 'Relations to load eagerly',
                schema: new OA\Schema(type: 'string', example: 'event,owner')
            ),
            new OA\Parameter(
                name: 'fields',
                in: 'query',
                required: false,
                description: 'Comma-separated list of fields to return',
                schema: new OA\Schema(type: 'string', example: 'id,seat_type,owner.first_name,owner.last_name')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'get all paginated RSVP From Summit Event',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedRSVPsResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function getAllByEventId($summit_id, $event_id){

        return $this->processRequest(function() use($summit_id, $event_id) {
            $summit = $this->getSummitOr404($summit_id);

            $event = $this->getEventOr404($summit, $event_id);

            return $this->_getAll(
                function () {
                    return [
                        'id' => ['=='],
                        'not_id' => ['=='],
                        'owner_email' => ['@@', '=@', '=='],
                        'owner_first_name' => ['@@', '=@', '=='],
                        'owner_last_name' => ['@@', '=@', '=='],
                        'owner_full_name' => ['@@', '=@', '=='],
                        'seat_type' => ['=='],
                    ];
                },
                function () {
                    return [
                        'id' => 'sometimes|integer',
                        'not_id' => 'sometimes|integer',
                        'owner_email' => 'sometimes|required|string',
                        'owner_first_name' => 'sometimes|required|string',
                        'owner_last_name' => 'sometimes|required|string',
                        'owner_full_name' => 'sometimes|required|string',
                        'seat_type' => 'sometimes|required|string|in:' . join(",", RSVP::ValidSeatTypes),
                    ];
                },
                function () {
                    return [
                        'id',
                        'owner_email',
                        'owner_first_name',
                        'owner_last_name',
                        'owner_full_name',
                        'seat_type',
                    ];
                },
                function ($filter) use ($event) {
                    if ($filter instanceof Filter) {
                        $filter->addFilterCondition(FilterElement::makeEqual('summit_event_id', $event->getId()));
                    }
                    return $filter;
                },
                function () {
                    return SerializerRegistry::SerializerType_Public;
                }
            );
        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/events/{event_id}/rsvps/csv",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: 'get CSV RSVP',
        operationId: 'csvRspvs',
        tags: ['RSVP'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_rsvp_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'summit_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'event_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The event id'
            ),
            // query string params
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions in the format field<op>value. Operators: @@, ==, =@.',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'owner_email@@email@test.com')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'id,-seat_type')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to include',
                schema: new OA\Schema(type: 'string', example: 'event,owner')
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                description: 'Relations to load eagerly',
                schema: new OA\Schema(type: 'string', example: 'event,owner')
            ),
            new OA\Parameter(
                name: 'fields',
                in: 'query',
                required: false,
                description: 'Comma-separated list of fields to return',
                schema: new OA\Schema(type: 'string', example: 'id,seat_type,owner.first_name,owner.last_name')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'CSV RSVP',
                content: new OA\MediaType(
                    mediaType: 'text/csv',
                    schema: new OA\Schema(
                        type: 'string',
                        format: 'binary' // file download
                    ),
                    // Optional: example CSV shown in UI (some UIs ignore it for binary)
                    example: "id,owner_id,event_id,seat_type,created,confirmation_number,action_source,action_date,status\n1,1,2,Active,1,0\n"
                ),
                headers: [
                    new OA\Header(
                        header: 'Content-Disposition',
                        description: 'Attachment filename',
                        schema: new OA\Schema(type: 'string'),
                    )
                ]
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function getAllByEventIdCSV($summit_id, $event_id)
    {

        return $this->processRequest(function () use ($summit_id, $event_id) {
            $summit = $this->getSummitOr404($summit_id);
            $event  = $this->getEventOr404($summit, $event_id);
            $this->getCurrentMemberOr403();

            return $this->_getAllCSV(
                function () {
                    return [
                        'id' => ['=='],
                        'not_id' => ['=='],
                        'owner_email' => ['@@', '=@', '=='],
                        'owner_first_name' => ['@@', '=@', '=='],
                        'owner_last_name' => ['@@', '=@', '=='],
                        'owner_full_name' => ['@@', '=@', '=='],
                        'seat_type' => ['=='],
                    ];
                },
                function () {
                    return [
                        'id' => 'sometimes|integer',
                        'not_id' => 'sometimes|integer',
                        'owner_email' => 'sometimes|required|string',
                        'owner_first_name' => 'sometimes|required|string',
                        'owner_last_name' => 'sometimes|required|string',
                        'owner_full_name' => 'sometimes|required|string',
                        'seat_type' => 'sometimes|required|string|in:' . join(",", RSVP::ValidSeatTypes),
                    ];
                },
                function () {
                    return [
                        'id',
                        'owner_email',
                        'owner_first_name',
                        'owner_last_name',
                        'owner_full_name',
                        'seat_type',
                    ];
                },
                function ($filter) use ($event) {
                    if ($filter instanceof Filter) {
                        $filter->addFilterCondition(FilterElement::makeEqual('summit_event_id', $event->getId()));
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
                        'action_date' => new EpochCellFormatter(),
                    ];
                },
                function () {
                    return [];
                },
                'rsvp-'
            );
        });
    }


    #[OA\Get(
        path: "/api/v1/summits/{id}/events/{event_id}/rsvps/{rsvp_id}",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: 'Read RSVP by id',
        operationId: 'readRSVPById',
        tags: ['RSVP'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_rsvp_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'summit_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'event_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The event id'
            ),
            new OA\Parameter(
                name: 'rsvp_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The rsvp id'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'RSVP From Summit Event',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVP')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function getById($summit_id, $event_id, $rsvp_id){
        return $this->processRequest(function() use($summit_id, $event_id, $rsvp_id){

            $summit = $this->getSummitOr404($summit_id);
            $event = $this->getScheduleEventOr404($summit, $event_id);
            $rsvp = $this->getRSVPOr404($event, $rsvp_id);

            return $this->ok(SerializerRegistry::getInstance()
                ->getSerializer($rsvp)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/events/{event_id}/rsvps/{rsvp_id}",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: 'Delete RSVP by id',
        operationId: 'deleteRSVPById',
        tags: ['RSVP'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_rsvp_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'summit_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'event_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The event id'
            ),
            new OA\Parameter(
                name: 'rsvp_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The rsvp id'
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,description: "Deleted"
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function delete($summit_id, $event_id, $rsvp_id){
        return $this->processRequest(function() use($summit_id, $event_id, $rsvp_id){
            $summit = $this->getSummitOr404($summit_id);
            $event = $this->getScheduleEventOr404($summit, $event_id);
            $rsvp = $this->getRSVPOr404($event, $rsvp_id);
            $this->service->delete($event, $rsvp->getId());
            return $this->deleted();
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/events/{event_id}/rsvps/{rsvp_id}",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: 'Update RSVP by id',
        operationId: 'updateRVPById',
        tags: ['RSVP'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_rsvp_oauth2' => [
            SummitScopes::WriteSummitData,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token (alternative to Authorization: Bearer)',
                schema: new OA\Schema(type: 'string', example: 'eyJhbGciOi...'),
            ),
            new OA\Parameter(
                name: 'summit_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'event_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The event id'
            ),
            new OA\Parameter(
                name: 'rsvp_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The rsvp id'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/RSVPUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Updated RSVP From Summit Event',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVP')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function update($summit_id, $event_id, $rsvp_id){
        return $this->processRequest(function() use($summit_id, $event_id, $rsvp_id){

            $summit = $this->getSummitOr404($summit_id);
            $event = $this->getScheduleEventOr404($summit, $event_id);

            $payload = $this->getJsonPayload([
                'seat_type' => 'sometimes|string|in:' . join(',', [
                        RSVP::SeatTypeRegular,
                        RSVP::SeatTypeWaitList,
                    ]),
                'status' => 'sometimes|string|in:' . join(',', [
                        RSVP::Status_Active,
                        RSVP::Status_Inactive,
                        RSVP::Status_TicketReassigned,
                    ]),
                'answers' => 'sometimes|rsvp_answer_dto_array',
            ]);

            $rsvp = $this->service->update($event, $rsvp_id,  $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($rsvp)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

}