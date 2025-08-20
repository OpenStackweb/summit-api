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
use App\Jobs\Emails\Schedule\RSVP\ReRSVPInviteEmail;
use App\Jobs\Emails\Schedule\RSVP\RSVPInviteEmail;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Events\RSVP\Repositories\IRSVPInvitationRepository;
use App\ModelSerializers\SerializerUtils;
use App\Security\RSVPInvitationsScopes;
use App\Security\SummitScopes;
use App\Services\ISummitRSVPInvitationService;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\SummitRegistrationInvitation;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;


class OAuth2RSVPInvitationApiController extends OAuth2ProtectedController
{
    // traits
    use ParametrizedGetAll;

    use RequestProcessor;

    use Assertions;

    /**
     * @var ISummitRSVPInvitationService
     */
    private ISummitRSVPInvitationService $service;

    /**
     * @var ISummitRepository
     */
    private ISummitRepository $summit_repository;


    private ISummitEventRepository $summit_event_repository;

    /**
     * @param ISummitEventRepository $summit_event_repository
     * @param IRSVPInvitationRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitRSVPInvitationService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct(
        ISummitEventRepository       $summit_event_repository,
        IRSVPINvitationRepository    $repository,
        ISummitRepository            $summit_repository,
        ISummitRSVPInvitationService $service,
        IResourceServerContext       $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
        $this->summit_event_repository = $summit_event_repository;
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/events/{event_id}/rsvp-invitations/csv",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: 'Import RSVP Invitations',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    type: "object",
                    required:["file"],
                    properties: [
                        new OA\Property(
                            property: "file",
                            type: "string",
                            format: "binary",
                            description: "Invitation file (CSV) [Columns: email ]"
                        )
                    ]
                )
            )
        ),
        operationId: 'importRSVPInvitations',
        tags: ['RSVP Invitations'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_rsvp_invitations_oauth2' => [
            RSVPInvitationsScopes::Write,
            SummitScopes::WriteSummitData
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
                response: 200,
                description: 'RSVP Import success',
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function ingestInvitations(LaravelRequest $request, $summit_id, $event_id)
    {
        return $this->processRequest(function () use ($request, $summit_id, $event_id) {

            $summit = $this->getSummitOr404($summit_id);
            $summit_event = $this->getEventOr404($summit, $event_id);
            $this->getCurrentMemberOr403();

            $payload = $request->all();

            $rules = [
                'file' => 'required|file|mimes:csv,txt',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException;
                $ex->setMessages($validation->messages()->toArray());
                throw $ex;
            }

            $file = $request->file('file');

            // Extra diagnostics: surface the underlying PHP upload error if invalid
            if (!$file || !$file->isValid()) {
                Log::debug("OAuth2RSVPInvitationApiController::ingestInvitations file is not valid");
                $errorCode = $file?->getError();
                $errorMsg  = match ($errorCode) {
                    UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize in php.ini',
                    UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE in the form',
                    UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded',
                    UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder (upload_tmp_dir)',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk (permissions)',
                    UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload',
                    default               => 'Unknown upload error',
                };
                throw new ValidationException("Upload error ({$errorCode}): {$errorMsg}");
            }

            Log::debug("OAuth2RSVPInvitationApiController::ingestInvitations file is valid, calling service");
            $this->service->importInvitationData($summit_event, $file);
            return $this->ok();
        });
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/events/{event_id}/rsvp-invitations",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: 'Read Invitations',
        operationId: 'readInvitations',
        tags: ['RSVP Invitations'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_rsvp_invitations_oauth2' => [
            RSVPInvitationsScopes::Read,
            SummitScopes::ReadAllSummitData,
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
                    items: new OA\Items(type: 'string', example: 'attendee_email@@email@test.com')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'id,-status')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to include',
                schema: new OA\Schema(type: 'string', example: 'invitee,event')
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                description: 'Relations to load eagerly',
                schema: new OA\Schema(type: 'string', example: 'invitee,event')
            ),
            new OA\Parameter(
                name: 'fields',
                in: 'query',
                required: false,
                description: 'Comma-separated list of fields to return',
                schema: new OA\Schema(type: 'string', example: 'id,status,invitee.first_name,invitee.last_name')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'get all paginated RSVP invitations',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedRSVPInvitationsResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function getAllByEventId($summit_id, $event_id)
    {

        return $this->processRequest(function () use ($summit_id, $event_id) {
            $summit = $this->getSummitOr404($summit_id);
            $summit_event = $this->getEventOr404($summit, $event_id);
            $this->getCurrentMemberOr403();

            return $this->_getAll(
                function () {
                    return [
                        'id' => ['=='],
                        'not_id' => ['=='],
                        'attendee_email' => ['@@', '=@', '=='],
                        'attendee_first_name' => ['@@', '=@', '=='],
                        'attendee_last_name' => ['@@', '=@', '=='],
                        'attendee_full_name' => ['@@', '=@', '=='],
                        'is_accepted' => ['=='],
                        'is_sent' => ['=='],
                        'status' => ['=='],
                    ];
                },
                function () {
                    return [
                        'id' => 'sometimes|integer',
                        'not_id' => 'sometimes|integer',
                        'attendee_email' => 'sometimes|required|string',
                        'attendee_first_name' => 'sometimes|required|string',
                        'attendee_last_name' => 'sometimes|required|string',
                        'attendee_full_name' => 'sometimes|required|string',
                        'is_accepted' => 'sometimes|required|string|in:true,false',
                        'is_sent' => 'sometimes|required|string|in:true,false',
                        'status' => 'sometimes|required|string|in:' . join(",", SummitRegistrationInvitation::AllowedStatus),
                    ];
                },
                function () {
                    return [
                        'id',
                        'attendee_email',
                        'attendee_first_name',
                        'attendee_last_name',
                        'attendee_full_name',
                        'status',
                    ];
                },
                function ($filter) use ($summit_event) {
                    if ($filter instanceof Filter) {
                        $filter->addFilterCondition(FilterElement::makeEqual('summit_event_id', $summit_event->getId()));
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
        path: "/api/v1/summits/{id}/events/{event_id}/rsvp-invitations/csv",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: 'get CSV Invitations',
        operationId: 'csvInvitations',
        tags: ['RSVP Invitations'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_rsvp_invitations_oauth2' => [
            RSVPInvitationsScopes::Read,
            SummitScopes::ReadAllSummitData,
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
                    items: new OA\Items(type: 'string', example: 'attendee_email@@email@test.com')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'id,-status')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to include',
                schema: new OA\Schema(type: 'string', example: 'invitee,event')
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                description: 'Relations to load eagerly',
                schema: new OA\Schema(type: 'string', example: 'invitee,event')
            ),
            new OA\Parameter(
                name: 'fields',
                in: 'query',
                required: false,
                description: 'Comma-separated list of fields to return',
                schema: new OA\Schema(type: 'string', example: 'id,status,invitee.first_name,invitee.last_name')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'CSV RSVP invitations',
                content: new OA\MediaType(
                    mediaType: 'text/csv',
                    schema: new OA\Schema(
                        type: 'string',
                        format: 'binary' // file download
                    ),
                    // Optional: example CSV shown in UI (some UIs ignore it for binary)
                    example: "id,invitee_id,event_id,status,is_accepted,is_sent\n1,1,2,Active,1,0\n"
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
            $summit_event = $this->getEventOr404($summit, $event_id);
            $this->getCurrentMemberOr403();

            return $this->_getAllCSV(
                function () {
                    return [
                        'id' => ['=='],
                        'not_id' => ['=='],
                        'attendee_email' => ['@@', '=@', '=='],
                        'attendee_first_name' => ['@@', '=@', '=='],
                        'attendee_last_name' => ['@@', '=@', '=='],
                        'attendee_full_name' => ['@@', '=@', '=='],
                        'is_accepted' => ['=='],
                        'is_sent' => ['=='],
                        'status' => ['=='],
                    ];
                },
                function () {
                    return [
                        'id' => 'sometimes|integer',
                        'not_id' => 'sometimes|integer',
                        'attendee_email' => 'sometimes|required|string',
                        'attendee_first_name' => 'sometimes|required|string',
                        'attendee_last_name' => 'sometimes|required|string',
                        'attendee_full_name' => 'sometimes|required|string',
                        'is_accepted' => 'sometimes|required|string|in:true,false',
                        'is_sent' => 'sometimes|required|string|in:true,false',
                        'status' => 'sometimes|required|string|in:' . join(",", SummitRegistrationInvitation::AllowedStatus),
                    ];
                },
                function () {
                    return [
                        'id',
                        'attendee_email',
                        'attendee_first_name',
                        'attendee_last_name',
                        'attendee_full_name',
                        'status',
                    ];
                },
                function ($filter) use ($summit_event) {
                    if ($filter instanceof Filter) {
                        $filter->addFilterCondition(FilterElement::makeEqual('summit_event_id', $summit_event->getId()));
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
                        'is_accepted' => new BooleanCellFormatter(),
                        'is_sent' => new BooleanCellFormatter(),
                    ];
                },
                function () {
                    return [];
                },
                'rsvp-invitations-'
            );
        });
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/events/{event_id}/rsvp-invitations/send",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: 'Send Invitations',
        operationId: 'sendInvitations',
        tags: ['RSVP Invitations'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_rsvp_invitations_oauth2' => [
            RSVPInvitationsScopes::Send,
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
                    items: new OA\Items(type: 'string', example: 'attendee_email@@email@test.com')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string', example: 'id,-status')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/SendRSVPInvitationsRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'RSVP Invitation send success',
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function send($summit_id, $event_id)
    {
        return $this->processRequest(function () use ($summit_id, $event_id) {

            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $summit = $this->getSummitOr404($summit_id);
            $summit_event = $this->getEventOr404($summit, $event_id);
            $this->getCurrentMemberOr403();

            $payload = $data->all();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, [
                'email_flow_event' => 'required|string|in:' . join(',', [
                        RSVPInviteEmail::EVENT_SLUG,
                        ReRSVPInviteEmail::EVENT_SLUG,
                    ]),
                'invitations_ids' => 'sometimes|int_array',
                'excluded_invitations_ids' => 'sometimes|int_array',
                'test_email_recipient' => 'sometimes|email',
                'outcome_email_recipient' => 'sometimes|email',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'attendee_email' => ['@@', '=@', '=='],
                    'attendee_first_name' => ['@@', '=@', '=='],
                    'attendee_last_name' => ['@@', '=@', '=='],
                    'attendee_full_name' => ['@@', '=@', '=='],
                    'is_accepted' => ['=='],
                    'is_sent' => ['=='],
                    'status' => ['=='],
                ]);
            }

            if (is_null($filter))
                $filter = new Filter();

            $filter->validate([
                'id' => 'sometimes|integer',
                'not_id' => 'sometimes|integer',
                'attendee_email' => 'sometimes|required|string',
                'attendee_first_name' => 'sometimes|required|string',
                'attendee_last_name' => 'sometimes|required|string',
                'attendee_full_name' => 'sometimes|required|string',
                'is_accepted' => 'sometimes|required|string|in:true,false',
                'is_sent' => 'sometimes|required|string|in:true,false',
                'status' => 'sometimes|required|string|in:' . join(",", SummitRegistrationInvitation::AllowedStatus),
            ]);

            $this->service->triggerSend($summit_event, $payload, Request::input('filter'));

            return $this->ok();

        });
    }

    use GetAndValidateJsonPayload;

    #[OA\Post(
        path: "/api/v1/summits/{id}/events/{event_id}/rsvp-invitations",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: "Add RSVP invitations to Summit Event",
        operationId: 'AddRSVPInvitations',
        tags: ['RSVP Invitations'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_rsvp_invitations_oauth2' => [
            RSVPInvitationsScopes::Write,
            SummitScopes::WriteSummitData
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
            content: new OA\JsonContent(ref: "#/components/schemas/RSVPInvitationRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'RSVP Invitation',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVPInvitation')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function addInvitation($summit_id, $event_id)
    {
        return $this->processRequest(function () use ($summit_id, $event_id) {

            $summit = $this->getSummitOr404($summit_id);
            $summit_event = $this->getEventOr404($summit, $event_id);
            $this->getCurrentMemberOr403();

            $payload = $this->getJsonPayload([
                'invitee_id' => 'required:integer',
            ], true);

            $invitation = $this->service->add($summit_event, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($invitation)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));

        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/events/{event_id}/rsvp-invitations/{invitation_id}}",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: "Delete RSVP invitations from Summit Event",
        operationId: 'deleteRSVPInvitations',
        tags: ['RSVP Invitations'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_rsvp_invitations_oauth2' => [
            RSVPInvitationsScopes::Write,
            SummitScopes::WriteSummitData
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
                name: 'invitation_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'The RSVP invitation id'
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: '',
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function delete($summit_id, $event_id, $invitation_id)
    {
        return $this->processRequest(function () use ($summit_id, $event_id, $invitation_id) {

            $summit = $this->getSummitOr404($summit_id);
            $summit_event = $this->getEventOr404($summit, $event_id);
            $this->getCurrentMemberOr403();

            $this->service->delete($summit_event, intval($invitation_id));

            return $this->deleted();
        });
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/events/{event_id}/rsvp-invitations/all",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: "Delete all RSVP invitations from Summit Event",
        operationId: 'deleteAllRSVPInvitations',
        tags: ['RSVP Invitations'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_rsvp_invitations_oauth2' => [
            RSVPInvitationsScopes::Write,
            SummitScopes::WriteSummitData
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

        ],
        responses: [
            new OA\Response(
                response: 204,
                description: '',
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function deleteAll($summit_id, $event_id)
    {
        return $this->processRequest(function () use ($summit_id, $event_id) {

            $summit = $this->getSummitOr404($summit_id);
            $summit_event = $this->getEventOr404($summit, $event_id);
            $current_member = $this->getCurrentMemberOr403();

            $this->service->deleteAll($summit_event, $current_member);

            return $this->deleted();
        });
    }

    // public endpoints

    #[OA\Get(
        path: "/api/public/v1/summits/{id}/events/{event_id}/rsvp-invitations/{token}",
        description: "",
        summary: 'Get RSVP Invitation By Token',
        operationId: 'gettByToken',
        tags: ['RSVP Invitations (Public)'],
        security: [],
        parameters: [
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
                name: 'token',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'Invitation Token'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'RSVP Invitation',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVPInvitation')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function getInvitationByToken($summit_id, $event_id, $token)
    {
        return $this->processRequest(function () use ($summit_id, $event_id, $token) {
            $summit = $this->getSummitOr404($summit_id);
            $summit_event = $this->getScheduleEventOr404($summit, $event_id);
            if (empty($token)) return $this->error401();
            $invitation = $this->service->getInvitationBySummitEventAndToken($summit_event, $token);

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($invitation)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    #[OA\Put(
        path: "/api/public/v1/summits/{id}/events/{event_id}/rsvp-invitations/{token}/accept",
        description: "",
        summary: 'Accept RSVP Invitation',
        operationId: 'acceptByToken',
        tags: ['RSVP Invitations (Public)'],
        security: [],
        parameters: [
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
                name: 'token',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'Invitation Token'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'RSVP Invitation Acccept success',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVPInvitation')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function acceptByToken($summit_id, $event_id, $token)
    {
        return $this->processRequest(function () use ($summit_id, $event_id, $token) {
            $summit = $this->getSummitOr404($summit_id);
            $summit_event = $this->getScheduleEventOr404($summit, $event_id);
            if (empty($token)) return $this->error401();
            $invitation = $this->service->acceptInvitationBySummitEventAndToken($summit_event, $token);

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($invitation)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    #[OA\Delete(
        path: "/api/public/v1/summits/{id}/events/{event_id}/rsvp-invitations/{token}/decline",
        description: "",
        summary: 'Decline RSVP Invitation',
        operationId: 'rejectByToken',
        tags: ['RSVP Invitations (Public)'],
        security: [],
        parameters: [
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
                name: 'token',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'Invitation Token'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'RSVP Invitation Decline success',
                content: new OA\JsonContent(ref: '#/components/schemas/RSVPInvitation')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error")
        ]
    )]
    public function rejectByToken($summit_id, $event_id, $token)
    {
        return $this->processRequest(function () use ($summit_id, $event_id, $token) {
            $summit = $this->getSummitOr404($summit_id);
            $summit_event = $this->getScheduleEventOr404($summit, $event_id);

            if (empty($token)) return $this->error401();
            $invitation = $this->service->rejectInvitationBySummitEventAndToken($summit_event, $token);

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($invitation)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }
}