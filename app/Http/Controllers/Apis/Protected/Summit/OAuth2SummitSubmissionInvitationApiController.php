<?php

namespace App\Http\Controllers;

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

use App\Http\Utils\BooleanCellFormatter;
use App\Http\Utils\EpochCellFormatter;
use App\Jobs\Emails\PresentationSubmissions\Invitations\InviteSubmissionEmail;
use App\Jobs\Emails\PresentationSubmissions\Invitations\ReInviteSubmissionEmail;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Repositories\ISummitSubmissionInvitationRepository;
use App\Security\SummitScopes;
use App\Services\Model\ISummitSubmissionInvitationService;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;

/**
 * Class OAuth2SummitSubmissionInvitationApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSubmissionInvitationApiController extends OAuth2ProtectedController
{
    use RequestProcessor;

    use ParametrizedGetAll;

    use GetSummitChildElementById {
        get as protected traitGet;
    }

    use DeleteSummitChildElement {
        delete as protected traitDelete;
    }

    use AddSummitChildElement {
        add as protected traitAdd;
    }

    use UpdateSummitChildElement {
        update as protected traitUpdate;
    }

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitSubmissionInvitationService
     */
    private $service;

    /**
     * OAuth2SummitRegistrationInvitationApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitSubmissionInvitationRepository $repository
     * @param ISummitSubmissionInvitationService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository                     $summit_repository,
        ISummitSubmissionInvitationRepository $repository,
        ISummitSubmissionInvitationService    $service,
        IResourceServerContext                $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/submission-invitations/csv",
        description: "Import submission invitations from CSV file - required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitRegistrationAdmins,
        summary: "Import submission invitations from CSV",
        operationId: "ingestSummitSubmissionInvitations",
        tags: ['Summit Submission Invitations'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        security: [['summit_submission_invitations_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteSubmissionInvitations,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['file'],
                    properties: [
                        new OA\Property(
                            property: 'file',
                            type: 'string',
                            format: 'binary',
                            description: 'CSV file to import'
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function ingestInvitations(LaravelRequest $request, $summit_id)
    {
        return $this->processRequest(function () use ($request, $summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $this->service->importInvitationData($summit, $file);
            return $this->ok();
        });
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @inheritDoc
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        return $summit->getSubmissionInvitationById($child_id);
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/submission-invitations",
        description: "Get all submission invitations for a summit - required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitRegistrationAdmins,
        summary: "Get all submission invitations",
        operationId: "getAllSummitSubmissionInvitations",
        tags: ['Summit Submission Invitations'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        security: [['summit_submission_invitations_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadSubmissionInvitations,
        ]]],
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
                schema: new OA\Schema(type: 'integer', default: 1),
                description: 'Page number'
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10),
                description: 'Items per page'
            ),
            new OA\Parameter(
                name: 'filter',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Filter expression (e.g., email=@john,is_sent==true)'
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Order by field (e.g., +id, -email)'
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Expand relationships (tags)'
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Relations to include (tags)'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitSubmissionInvitationsResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getAllBySummit($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'email' => ['@@', '=@', '=='],
                    'first_name' => ['@@', '=@', '=='],
                    'last_name' => ['@@', '=@', '=='],
                    'is_sent' => ['=='],
                    'ticket_types_id' => ['=='],
                    'tags' => ['@@', '=@', '=='],
                    'tags_id' => ['=='],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'not_id' => 'sometimes|integer',
                    'email' => 'sometimes|required|string',
                    'first_name' => 'sometimes|required|string',
                    'last_name' => 'sometimes|required|string',
                    'is_sent' => 'sometimes|required|string|in:true,false',
                    'tags' => 'sometimes|required|string',
                    'tags_id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'id',
                    'email',
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
            }
        );
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/submission-invitations/csv",
        description: "Get all submission invitations for a summit in CSV format - required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitRegistrationAdmins,
        summary: "Get all submission invitations (CSV)",
        operationId: "getAllSummitSubmissionInvitationsCSV",
        tags: ['Summit Submission Invitations'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        security: [['summit_submission_invitations_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadSubmissionInvitations,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'filter',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Filter expression'
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Order by field'
            ),
            new OA\Parameter(
                name: 'columns',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Comma-separated list of columns to include'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\MediaType(
                    mediaType: 'text/csv',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getAllBySummitCSV($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function () {
                return [
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'email' => ['@@', '=@', '=='],
                    'first_name' => ['@@', '=@', '=='],
                    'last_name' => ['@@', '=@', '=='],
                    'is_sent' => ['=='],
                    'tags' => ['@@', '=@', '=='],
                    'tags_id' => ['=='],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'not_id' => 'sometimes|integer',
                    'email' => 'sometimes|required|string',
                    'first_name' => 'sometimes|required|string',
                    'last_name' => 'sometimes|required|string',
                    'is_sent' => 'sometimes|required|string|in:true,false',
                    'tags' => 'sometimes|required|string',
                    'tags_id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'id',
                    'email',
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
                    'sent_date' => new EpochCellFormatter(),
                    'is_sent' => new BooleanCellFormatter(),
                ];
            },
            function () {

                $allowed_columns = [
                    'id',
                    'email',
                    'first_name',
                    'last_name',
                    'speaker_id',
                    'summit_id',
                    'is_sent',
                    'sent_date',
                    'tags',
                ];

                $columns_param = Request::input("columns", "");
                $columns = [];
                if (!empty($columns_param))
                    $columns = explode(',', $columns_param);
                $diff = array_diff($columns, $allowed_columns);
                if (count($diff) > 0) {
                    throw new ValidationException(sprintf("columns %s are not allowed!", implode(",", $diff)));
                }
                if (empty($columns))
                    $columns = $allowed_columns;
                return $columns;
            },
            'summit-submission-invitations-'
        );
    }

    /**
     * @inheritDoc
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->delete($summit, $child_id);
    }

    /**
     * @inheritDoc
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->add($summit, $payload);
    }

    /**
     * @inheritDoc
     */
    function getAddValidationRules(array $payload): array
    {
        return SummitSubmissionInvitationValidationRulesFactory::buildForAdd($payload);
    }

    /**
     * @inheritDoc
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SummitSubmissionInvitationValidationRulesFactory::buildForUpdate($payload);
    }

    /**
     * @inheritDoc
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->update($summit, $child_id, $payload);
    }

    #[OA\Get(
        path: "/api/v1/summits/{id}/submission-invitations/{invitation_id}",
        description: "Get a specific submission invitation by id - required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitRegistrationAdmins,
        summary: "Get submission invitation",
        operationId: "getSummitSubmissionInvitation",
        tags: ['Summit Submission Invitations'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        security: [['summit_submission_invitations_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadSubmissionInvitations,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'invitation_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The invitation id'
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Expand relationships (tags)'
            ),
            new OA\Parameter(
                name: 'relations',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Relations to include (tags)'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitSubmissionInvitation')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function get($summit_id, $invitation_id)
    {
        return $this->traitGet($summit_id, $invitation_id);
    }

    #[OA\Post(
        path: "/api/v1/summits/{id}/submission-invitations",
        description: "Create a new submission invitation - required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitRegistrationAdmins,
        summary: "Create submission invitation",
        operationId: "createSummitSubmissionInvitation",
        tags: ['Summit Submission Invitations'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        security: [['summit_submission_invitations_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteSubmissionInvitations,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SummitSubmissionInvitationCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitSubmissionInvitation')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function add($summit_id)
    {
        return $this->traitAdd($summit_id);
    }

    #[OA\Put(
        path: "/api/v1/summits/{id}/submission-invitations/{invitation_id}",
        description: "Update an existing submission invitation - required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitRegistrationAdmins,
        summary: "Update submission invitation",
        operationId: "updateSummitSubmissionInvitation",
        tags: ['Summit Submission Invitations'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        security: [['summit_submission_invitations_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteSubmissionInvitations,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'invitation_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The invitation id'
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SummitSubmissionInvitationUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitSubmissionInvitation')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function update($summit_id, $invitation_id)
    {
        return $this->traitUpdate($summit_id, $invitation_id);
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/submission-invitations/{invitation_id}",
        description: "Delete a submission invitation - required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitRegistrationAdmins,
        summary: "Delete submission invitation",
        operationId: "deleteSummitSubmissionInvitation",
        tags: ['Summit Submission Invitations'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        security: [['summit_submission_invitations_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteSubmissionInvitations,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'invitation_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The invitation id'
            )
        ],
        responses: [
            new OA\Response(response: 204, description: 'No Content'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function delete($summit_id, $invitation_id)
    {
        return $this->traitDelete($summit_id, $invitation_id);
    }

    #[OA\Delete(
        path: "/api/v1/summits/{id}/submission-invitations/all",
        description: "Delete all submission invitations for a summit - required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitRegistrationAdmins,
        summary: "Delete all submission invitations",
        operationId: "deleteAllSummitSubmissionInvitations",
        tags: ['Summit Submission Invitations'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        security: [['summit_submission_invitations_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteSubmissionInvitations,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            )
        ],
        responses: [
            new OA\Response(response: 204, description: 'No Content'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function deleteAll($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->service->deleteAll($summit);
            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: "/api/v1/summits/{id}/submission-invitations/all/send",
        description: "Send submission invitations to selected recipients - required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators . ", " . IGroup::SummitRegistrationAdmins,
        summary: "Send submission invitations",
        operationId: "sendSummitSubmissionInvitations",
        tags: ['Summit Submission Invitations'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins
            ]
        ],
        security: [['summit_submission_invitations_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteSubmissionInvitations,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'The summit id'
            ),
            new OA\Parameter(
                name: 'filter',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Filter expression to select invitations'
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email_flow_event'],
                properties: [
                    new OA\Property(
                        property: 'email_flow_event',
                        type: 'string',
                        enum: ['SUMMIT_SUBMISSIONS_INVITE_SUBMISSION', 'SUMMIT_SUBMISSIONS_REINVITE_SUBMISSION'],
                        description: 'Email flow event type'
                    ),
                    new OA\Property(
                        property: 'selection_plan_id',
                        type: 'integer',
                        description: 'Selection plan ID'
                    ),
                    new OA\Property(
                        property: 'invitations_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        description: 'Array of invitation IDs to send'
                    ),
                    new OA\Property(
                        property: 'excluded_invitations_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        description: 'Array of invitation IDs to exclude'
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function send($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $data->all();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, [
                'email_flow_event' => 'required|string|in:' . join(',', [
                        InviteSubmissionEmail::EVENT_SLUG,
                        ReInviteSubmissionEmail::EVENT_SLUG,
                    ]),
                'selection_plan_id' => 'sometimes|integer',
                'invitations_ids' => 'sometimes|int_array',
                'excluded_invitations_ids' => 'sometimes|int_array',
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
                    'email' => ['@@', '=@', '=='],
                    'first_name' => ['@@', '=@', '=='],
                    'last_name' => ['@@', '=@', '=='],
                    'is_sent' => ['=='],
                    'tags' => ['@@', '=@', '=='],
                    'tags_id' => ['=='],
                ]);
            }

            if (is_null($filter))
                $filter = new Filter();

            $filter->validate([
                'id' => 'sometimes|integer',
                'not_id' => 'sometimes|integer',
                'is_sent' => 'sometimes|required|string|in:true,false',
                'email' => 'sometimes|required|string',
                'first_name' => 'sometimes|required|string',
                'last_name' => 'sometimes|required|string',
                'tags' => 'sometimes|required|string',
                'tags_id' => 'sometimes|integer',
            ]);

            $this->service->triggerSend($summit, $payload, Request::input('filter'));

            return $this->ok();

        });
    }

}
