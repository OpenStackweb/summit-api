<?php namespace App\Http\Controllers;
/**
 * Copyright 2018 OpenStack Foundation
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
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Events\SummitEventTypeConstants;
use App\Models\Foundation\Summit\Repositories\ISummitEventTypeRepository;
use App\ModelSerializers\SerializerUtils;
use App\Security\SummitScopes;
use App\Services\Model\ISummitEventTypeService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\SummitEventType;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterParser;
use utils\OrderParser;
use utils\PagingInfo;
use utils\PagingResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class OAuth2SummitsEventTypesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitsEventTypesApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitEventTypeService
     */
    private $event_type_service;

    /**
     * OAuth2SummitsEventTypesApiController constructor.
     * @param ISummitEventTypeRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitEventTypeService $event_type_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitEventTypeRepository $repository,
        ISummitRepository          $summit_repository,
        ISummitEventTypeService    $event_type_service,
        IResourceServerContext     $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->event_type_service = $event_type_service;
    }

    use ParametrizedGetAll;

    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/event-types",
        operationId: "getAllSummitEventTypesBySummit",
        description: "Get all event types for a summit with pagination and filtering",
        tags: ["Event Types"],
        security: [
            [
                'summit_event_types_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID"
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
                schema: new OA\Schema(type: "integer", default: 20),
                description: "Items per page"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Filter by: name, class_name, is_default, black_out_times, use_sponsors, are_sponsors_mandatory, allows_attachment, use_speakers, are_speakers_mandatory, use_moderator, is_moderator_mandatory, should_be_available_on_cfp"
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Order by: id, name"
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Expand relationships: summit, summit_documents, allowed_ticket_types"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Event types retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedEventTypesResponse")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllBySummit($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '==', '@@'],
                    'class_name' => ['=='],
                    'is_default' => ['=='],
                    'black_out_times' => ['=='],
                    'use_sponsors' => ['=='],
                    'are_sponsors_mandatory' => ['=='],
                    'allows_attachment' => ['=='],
                    'use_speakers' => ['=='],
                    'are_speakers_mandatory' => ['=='],
                    'use_moderator' => ['=='],
                    'is_moderator_mandatory' => ['=='],
                    'should_be_available_on_cfp' => ['=='],
                ];
            },
            function () {
                return [
                    'class_name' => 'sometimes|string|in:'.join(",", SummitEventTypeConstants::$valid_class_names),
                    'name' => 'sometimes|string',
                    'is_default' => 'sometimes|boolean',
                    'black_out_times' => 'sometimes|string|in:'.join(",", SummitEventTypeConstants::$valid_blackout_times),
                    'use_sponsors' => 'sometimes|boolean',
                    'are_sponsors_mandatory' => 'sometimes|boolean',
                    'allows_attachment' => 'sometimes|boolean',
                    'use_speakers' => 'sometimes|boolean',
                    'are_speakers_mandatory' => 'sometimes|boolean',
                    'use_moderator' => 'sometimes|boolean',
                    'is_moderator_mandatory' => 'sometimes|boolean',
                    'should_be_available_on_cfp' => 'sometimes|boolean',
                ];
            },
            function () {
                return [
                    'id',
                    'name',
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
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->repository->getBySummit
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/event-types/csv",
        operationId: "getAllSummitEventTypesBySummitCSV",
        description: "Export event types for a summit as CSV",
        tags: ["Event Types"],
        security: [
            [
                'summit_event_types_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID"
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
                schema: new OA\Schema(type: "integer"),
                description: "Items per page"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Filter criteria"
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Order by: id, name"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "CSV file exported successfully",
                content: new OA\MediaType(mediaType: "text/csv")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllBySummitCSV($summit_id)
    {
        $values = Request::all();
        $rules = [
        ];

        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page = 1;
            $per_page = PHP_INT_MAX;

            if (Request::has('page')) {
                $page = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'name' => ['=@', '==', '@@'],
                    'class_name' => ['=='],
                    'is_default' => ['=='],
                    'black_out_times' => ['=='],
                    'use_sponsors' => ['=='],
                    'are_sponsors_mandatory' => ['=='],
                    'allows_attachment' => ['=='],
                    'use_speakers' => ['=='],
                    'are_speakers_mandatory' => ['=='],
                    'use_moderator' => ['=='],
                    'is_moderator_mandatory' => ['=='],
                    'should_be_available_on_cfp' => ['=='],
                ]);
            }

            if (is_null($filter)) $filter = new Filter();

            $filter->validate([
                'class_name' => 'sometimes|string|in:'.join(",", SummitEventTypeConstants::$valid_class_names),
                'name' => 'sometimes|string',
                'is_default' => 'sometimes|boolean',
                'black_out_times' => 'sometimes|string|in:'.join(",", SummitEventTypeConstants::$valid_blackout_times),
                'use_sponsors' => 'sometimes|boolean',
                'are_sponsors_mandatory' => 'sometimes|boolean',
                'allows_attachment' => 'sometimes|boolean',
                'use_speakers' => 'sometimes|boolean',
                'are_speakers_mandatory' => 'sometimes|boolean',
                'use_moderator' => 'sometimes|boolean',
                'is_moderator_mandatory' => 'sometimes|boolean',
                'should_be_available_on_cfp' => 'sometimes|boolean',
            ], [
                'class_name.in' => sprintf
                (
                    ":attribute has an invalid value ( valid values are %s )",
                    implode(", ", SummitEventTypeConstants::$valid_class_names)
                ),
            ]);

            $order = null;

            if (Request::has('order')) {
                $order = OrderParser::parse(Request::input('order'), [

                    'id',
                    'name',
                ]);
            }

            $data = $this->repository->getBySummit($summit, new PagingInfo($page, $per_page), $filter, $order);

            $filename = "event-types-" . date('Ymd');
            $list = $data->toArray();
            return $this->export
            (
                'csv',
                $filename,
                $list['data'],
                [
                    'created' => new EpochCellFormatter,
                    'last_edited' => new EpochCellFormatter,
                    'is_default' => new BooleanCellFormatter,
                    'use_sponsors' => new BooleanCellFormatter,
                    'are_sponsors_mandatory' => new BooleanCellFormatter,
                    'allows_attachment' => new BooleanCellFormatter,
                    'use_speakers' => new BooleanCellFormatter,
                    'are_speakers_mandatory' => new BooleanCellFormatter,
                    'use_moderator' => new BooleanCellFormatter,
                    'is_moderator_mandatory' => new BooleanCellFormatter,
                    'should_be_available_on_cfp' => new BooleanCellFormatter,
                ]
            );
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    use RequestProcessor;

    use GetAndValidateJsonPayload;

    /**
     * @param $summit_id
     * @param $event_type_id
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/event-types/{event_type_id}",
        operationId: "getEventTypeBySummit",
        description: "Get a specific event type by ID",
        tags: ["Event Types"],
        security: [
            [
                'summit_event_types_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID"
            ),
            new OA\Parameter(
                name: "event_type_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Event type ID"
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Expand relationships"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Event type retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/EventType")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Event type or summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    #[OA\Get(
        path: "/api/public/v1/summits/{id}/event-types/{event_type_id}",
        operationId: "getEventTypeBySummitPublic",
        description: "Get a specific event type by ID",
        tags: ["Event Types (Public)"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID"
            ),
            new OA\Parameter(
                name: "event_type_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Event type ID"
            ),
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string"),
                description: "Expand relationships"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Event type retrieved successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/EventType")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Event type or summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getEventTypeBySummit($summit_id, $event_type_id)
    {
        return $this->processRequest(function () use ($summit_id, $event_type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $event_type = $summit->getEventType(intval($event_type_id));
            if (is_null($event_type))
                return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($event_type)
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
     * @return mixed
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/event-types",
        operationId: "addEventTypeBySummit",
        description: "Create a new event type",
        tags: ["Event Types"],
        security: [
            [
                'summit_event_types_oauth2' => [
                    SummitScopes::WriteEventTypeData,
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID"
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/EventTypeAddRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Event type created successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/EventType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addEventTypeBySummit($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(EventTypeValidationRulesFactory::build(Request::all()));

            $event_type = $this->event_type_service->addEventType($summit, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($event_type)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $event_type_id
     * @return mixed
     */
    #[OA\Put(
        path: "/api/v1/summits/{id}/event-types/{event_type_id}",
        operationId: "updateEventTypeBySummit",
        description: "Update an existing event type",
        tags: ["Event Types"],
        security: [
            [
                'summit_event_types_oauth2' => [
                    SummitScopes::WriteEventTypeData,
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID"
            ),
            new OA\Parameter(
                name: "event_type_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Event type ID"
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/EventTypeUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Event type updated successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/EventType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Event type or summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function updateEventTypeBySummit($summit_id, $event_type_id)
    {
        return $this->processRequest(function () use ($summit_id, $event_type_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(EventTypeValidationRulesFactory::build(Request::all(), true));

            $event_type = $this->event_type_service->updateEventType($summit, intval($event_type_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($event_type)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $event_type_id
     * @return mixed
     */
    #[OA\Delete(
        path: "/api/v1/summits/{id}/event-types/{event_type_id}",
        operationId: "deleteEventTypeBySummit",
        description: "Delete an event type",
        tags: ["Event Types"],
        security: [
            [
                'summit_event_types_oauth2' => [
                    SummitScopes::WriteEventTypeData,
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID"
            ),
            new OA\Parameter(
                name: "event_type_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Event type ID"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: "Event type deleted successfully"
            ),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Event type or summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function deleteEventTypeBySummit($summit_id, $event_type_id)
    {
        return $this->processRequest(function () use ($summit_id, $event_type_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->event_type_service->deleteEventType($summit, intval($event_type_id));

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/event-types/seed-defaults",
        operationId: "seedDefaultEventTypesBySummit",
        description: "Seed default event types for a summit",
        tags: ["Event Types"],
        security: [
            [
                'summit_event_types_oauth2' => [
                    SummitScopes::WriteEventTypeData,
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Default event types seeded successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedEventTypesResponse")
            ),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Summit not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function seedDefaultEventTypesBySummit($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $event_types = $this->event_type_service->seedDefaultEventTypes($summit);

            $response = new PagingResponse
            (
                count($event_types),
                count($event_types),
                1,
                1,
                $event_types
            );

            return $this->created($response->toArray());
        });
    }

    /**
     * @param $summit_id
     * @param $event_type_id
     * @param $document_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Put(
        path: "/api/v1/summits/{id}/event-types/{event_type_id}/summit-documents/{document_id}",
        operationId: "addSummitDocumentToEventType",
        description: "Add a document to an event type",
        tags: ["Event Types"],
        security: [
            [
                'summit_event_types_oauth2' => [
                    SummitScopes::WriteEventTypeData,
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID"
            ),
            new OA\Parameter(
                name: "event_type_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Event type ID"
            ),
            new OA\Parameter(
                name: "document_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Document ID"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Document added to event type successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/EventType")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Event type, summit or document not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addSummitDocument($summit_id, $event_type_id, $document_id)
    {
        return $this->processRequest(function () use ($summit_id, $event_type_id, $document_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();


            $document = $this->event_type_service->addSummitDocumentToEventType
            (
                $summit,
                intval($event_type_id),
                intval($document_id)
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($document)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $event_type_id
     * @param $document_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Delete(
        path: "/api/v1/summits/{id}/event-types/{event_type_id}/summit-documents/{document_id}",
        operationId: "removeSummitDocument",
        description: "Remove a document from an event type",
        tags: ["Event Types"],
        security: [
            [
                'summit_event_types_oauth2' => [
                    SummitScopes::WriteEventTypeData,
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "Summit ID"
            ),
            new OA\Parameter(
                name: "event_type_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Event type ID"
            ),
            new OA\Parameter(
                name: "document_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64"),
                description: "Document ID"
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Document removed from event type successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/EventType")
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Event type, summit or document not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function removeSummitDocument($summit_id, $event_type_id, $document_id)
    {
        return $this->processRequest(function () use ($summit_id, $event_type_id, $document_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $document = $this->event_type_service->removeSummitDocumentFromEventType
            (
                $summit,
                intval($event_type_id),
                intval($document_id)
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($document)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }
}