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
use App\Http\Utils\EpochCellFormatter;
use App\ModelSerializers\SerializerUtils;
use App\Rules\Boolean;
use App\Services\Model\ISummitTicketTypeService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\ISummitTicketTypeRepository;
use models\summit\SummitTicketType;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class OAuth2SummitsTicketTypesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitsTicketTypesApiController extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;

    use RequestProcessor;

    use ParametrizedGetAll;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitTicketTypeService
     */
    private $ticket_type_service;

    /**
     * OAuth2SummitsTicketTypesApiController constructor.
     * @param ISummitTicketTypeRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitTicketTypeService $ticket_type_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitTicketTypeRepository $repository,
        ISummitRepository           $summit_repository,
        ISummitTicketTypeService    $ticket_type_service,
        IResourceServerContext      $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->ticket_type_service = $ticket_type_service;
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/ticket-types",
        summary: "Get all ticket types for a summit (public audience only)",
        security: [["Bearer" => []]],
        tags: ["summit-ticket-types"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
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
                description: "Filter operators: id==, badge_type_id==, name=@/@@/==, description=@/@@/==, external_id=@/@@/==, audience=@/@@/==, sales_start_date>/</<=/>=/ ==/[], sales_end_date>/</<=/>=/ ==/[], created>/</<=/>=/ ==/[], last_edited>/</<=/>=/ ==/[], allows_to_delegate=="
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                explode: false,
                schema: new OA\Schema(type: "string"),
                description: "Order by fields: id, created, name, external_id, audience"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedSummitTicketTypesResponse")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getAllBySummit($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'id' => [ '=='],
                    'badge_type_id' => ['=='],
                    'name' => ['=@', '@@', '=='],
                    'description' => ['=@', '@@', '=='],
                    'external_id' => ['=@', '@@', '=='],
                    'audience' => ['=@', '@@', '=='],
                    'sales_start_date'=> ['>', '<', '<=', '>=', '==','[]'],
                    'sales_end_date'=> ['>', '<', '<=', '>=', '==','[]'],
                    'created'=> ['>', '<', '<=', '>=', '==','[]'],
                    'last_edited'=> ['>', '<', '<=', '>=', '==','[]'],
                    'allows_to_delegate' => ['=='],
                ];
            },
            function () {
                return [
                    'id' =>'sometimes|integer',
                    'badge_type_id' =>'sometimes|integer',
                    'name' => 'sometimes|string',
                    'description' => 'sometimes|string',
                    'external_id' => 'sometimes|string',
                    'audience' => 'sometimes|string|in:' . implode(',', SummitTicketType::AllowedAudience),
                    'sales_start_date' => 'sometimes|required|date_format:U|epoch_seconds',
                    'sales_end_date' => 'sometimes|required|date_format:U|epoch_seconds',
                    'created' => 'sometimes|required|date_format:U|epoch_seconds',
                    'last_edited' => 'sometimes|required|date_format:U|epoch_seconds',
                    'allows_to_delegate' => ['sometimes', new Boolean()],
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'name',
                    'external_id',
                    'audience'
                ];
            },
            function ($filter) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('audience', SummitTicketType::Audience_All));
                }
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
                    $summit, new PagingInfo($page, $per_page),
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
    public function getAllBySummitCSV($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function () {
                return [
                    'id' => [ '=='],
                    'badge_type_id' => ['=='],
                    'name' => ['=@', '@@', '=='],
                    'description' => ['=@', '@@', '=='],
                    'external_id' => ['=@', '@@', '=='],
                    'audience' => ['=@', '@@', '=='],
                    'sales_start_date'=> ['>', '<', '<=', '>=', '==','[]'],
                    'sales_end_date'=> ['>', '<', '<=', '>=', '==','[]'],
                    'created'=> ['>', '<', '<=', '>=', '==','[]'],
                    'last_edited'=> ['>', '<', '<=', '>=', '==','[]'],
                    'allows_to_delegate' => ['=='],
                ];
            },
            function () {
                return [
                    'id' =>'sometimes|integer',
                    'badge_type_id' =>'sometimes|integer',
                    'name' => 'sometimes|string',
                    'description' => 'sometimes|string',
                    'external_id' => 'sometimes|string',
                    'audience' => 'sometimes|string|in:' . implode(',', SummitTicketType::AllowedAudience),
                    'sales_start_date' => 'sometimes|required|date_format:U|epoch_seconds',
                    'sales_end_date' => 'sometimes|required|date_format:U|epoch_seconds',
                    'created' => 'sometimes|required|date_format:U|epoch_seconds',
                    'last_edited' => 'sometimes|required|date_format:U|epoch_seconds',
                    'allows_to_delegate' => ['sometimes', new Boolean()],
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'name',
                    'external_id',
                    'audience'
                ];
            },
            function ($filter) use ($summit) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_CSV;
            },
            function () {
                return [
                    'created' => new EpochCellFormatter,
                    'last_edited' => new EpochCellFormatter,
                ];
            },
            function () {
                return [];
            },
            "ticket-types",
            [],
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
        path: "/api/v2/summits/{id}/ticket-types",
        summary: "Get all ticket types for a summit (all audiences)",
        security: [["Bearer" => []]],
        tags: ["summit-ticket-types"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
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
                description: "Filter operators: id==, badge_type_id==, name=@/@@/==, description=@/@@/==, external_id=@/@@/==, audience=@/@@/==, sales_start_date>/</<=/>=/ ==/[], sales_end_date>/</<=/>=/ ==/[], created>/</<=/>=/ ==/[], last_edited>/</<=/>=/ ==/[], allows_to_delegate=="
            ),
            new OA\Parameter(
                name: "order",
                in: "query",
                required: false,
                explode: false,
                schema: new OA\Schema(type: "string"),
                description: "Order by fields: id, created, name, external_id, audience"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedSummitTicketTypesResponse")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getAllBySummitV2($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();
        return $this->_getAll(
            function () {
                return [
                    'id' => [ '=='],
                    'badge_type_id' => ['=='],
                    'name' => ['=@', '@@', '=='],
                    'description' => ['=@', '@@', '=='],
                    'external_id' => ['=@', '@@', '=='],
                    'audience' => ['=@', '@@', '=='],
                    'sales_start_date'=> ['>', '<', '<=', '>=', '==','[]'],
                    'sales_end_date'=> ['>', '<', '<=', '>=', '==','[]'],
                    'created'=> ['>', '<', '<=', '>=', '==','[]'],
                    'last_edited'=> ['>', '<', '<=', '>=', '==','[]'],
                    'allows_to_delegate' => ['=='],
                ];
            },
            function () {
                return [
                    'id' =>'sometimes|integer',
                    'badge_type_id' =>'sometimes|integer',
                    'name' => 'sometimes|string',
                    'description' => 'sometimes|string',
                    'external_id' => 'sometimes|string',
                    'audience' => 'sometimes|string|in:' . implode(',', SummitTicketType::AllowedAudience),
                    'sales_start_date' => 'sometimes|required|date_format:U|epoch_seconds',
                    'sales_end_date' => 'sometimes|required|date_format:U|epoch_seconds',
                    'created' => 'sometimes|required|date_format:U|epoch_seconds',
                    'last_edited' => 'sometimes|required|date_format:U|epoch_seconds',
                    'allows_to_delegate' => ['sometimes', new Boolean()],
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'name',
                    'external_id',
                    'audience'
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
                return $this->repository->getBySummit($summit, new PagingInfo($page, $per_page), $filter, $order);
            }
        );
    }

    use ParseAndGetFilter;
    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/ticket-types/allowed",
        summary: "Get allowed ticket types for current member",
        security: [["Bearer" => []]],
        tags: ["summit-ticket-types"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "filter",
                in: "query",
                required: false,
                explode: false,
                schema: new OA\Schema(type: "string"),
                description: "Filter operators: promo_code=="
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedSummitTicketTypesResponse")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getAllowedBySummitAndCurrentMember($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $member = $this->resource_server_context->getCurrentUser();
            if (is_null($member)) return $this->error403();

            $filter = self::getFilter(function(){
                return [
                    'promo_code' => ['=='],
                ];

            }, function(){
                return [
                    'promo_code' => 'sometimes|required|string',
                ];
            });

            $promocode_val = null;
            if ($filter->hasFilter('promo_code')) {
                $promocode_val = $filter->getValue('promo_code')[0];
                Log::debug(sprintf("OAuth2SummitsTicketTypesApiController::getAllowedBySummitAndCurrentMember promo_code %s", $promocode_val));
            }

            $ticket_types = $this->ticket_type_service->getAllowedTicketTypes($summit, $member, $promocode_val);

            $resp = new PagingResponse(count($ticket_types), count($ticket_types), 1, 1, $ticket_types);

            return $this->ok
            (
                $resp->toArray
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    []
                )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $ticket_type_id
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/ticket-types/{ticket_type_id}",
        summary: "Get a specific ticket type by id",
        security: [["Bearer" => []]],
        tags: ["summit-ticket-types"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "ticket_type_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The ticket type id"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitTicketType")
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getTicketTypeBySummit($summit_id, $ticket_type_id)
    {
        return $this->processRequest(function () use ($summit_id, $ticket_type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $ticket_type = $summit->getTicketTypeById($ticket_type_id);
            if (is_null($ticket_type))
                return $this->error404();
            return $this->ok(SerializerRegistry::getInstance()->getSerializer($ticket_type)->serialize
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
        path: "/api/v1/summits/{id}/ticket-types",
        summary: "Create a new ticket type for a summit",
        security: [["Bearer" => []]],
        tags: ["summit-ticket-types"],
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
                schema: new OA\Schema(ref: "#/components/schemas/SummitTicketType")
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitTicketType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function addTicketTypeBySummit($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SummitTicketTypeValidationRulesFactory::buildForAdd());

            $ticket_type = $this->ticket_type_service->addTicketType($summit, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($ticket_type)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $ticket_type_id
     * @return mixed
     */
    #[OA\Put(
        path: "/api/v1/summits/{id}/ticket-types/{ticket_type_id}",
        summary: "Update a ticket type",
        security: [["Bearer" => []]],
        tags: ["summit-ticket-types"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "ticket_type_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The ticket type id"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(ref: "#/components/schemas/SummitTicketType")
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OK",
                content: new OA\JsonContent(ref: "#/components/schemas/SummitTicketType")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function updateTicketTypeBySummit($summit_id, $ticket_type_id)
    {
        return $this->processRequest(function () use ($summit_id, $ticket_type_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SummitTicketTypeValidationRulesFactory::buildForUpdate());

            $ticket_type = $this->ticket_type_service->updateTicketType($summit, $ticket_type_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($ticket_type)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $ticket_type_id
     * @return mixed
     */
    #[OA\Delete(
        path: "/api/v1/summits/{id}/ticket-types/{ticket_type_id}",
        summary: "Delete a ticket type",
        security: [["Bearer" => []]],
        tags: ["summit-ticket-types"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "ticket_type_id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The ticket type id"
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
    public function deleteTicketTypeBySummit($summit_id, $ticket_type_id)
    {
        return $this->processRequest(function () use ($summit_id, $ticket_type_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->ticket_type_service->deleteTicketType($summit, $ticket_type_id);

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/ticket-types/seed-defaults",
        summary: "Seed default ticket types from Eventbrite",
        security: [["Bearer" => []]],
        tags: ["summit-ticket-types"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: "Created",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedSummitTicketTypesResponse")
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function seedDefaultTicketTypesBySummit($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $ticket_types = $this->ticket_type_service->seedSummitTicketTypesFromEventBrite($summit);

            $response = new PagingResponse
            (
                count($ticket_types),
                count($ticket_types),
                1,
                1,
                $ticket_types
            );

            return $this->created($response->toArray(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $currency_symbol
     * @return mixed
     */
    #[OA\Put(
        path: "/api/v1/summits/{id}/ticket-types/all/currency/{currency_symbol}",
        summary: "Update currency symbol for all ticket types in a summit",
        security: [["Bearer" => []]],
        tags: ["summit-ticket-types"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                description: "The summit id"
            ),
            new OA\Parameter(
                name: "currency_symbol",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
                description: "The currency symbol (e.g., USD, EUR)"
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "OK"),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function updateCurrencySymbol($summit_id, $currency_symbol)
    {
        return $this->processRequest(function () use ($summit_id, $currency_symbol) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $member = $this->resource_server_context->getCurrentUser();
            if (is_null($member)) return $this->error403();

            if(!$member->isAuthzFor($summit)){
                return $this->error403();
            }

            if(!in_array($currency_symbol, SummitTicketType::AllowedCurrencies)){
                throw new ValidationException(sprintf("Currency symbol %s is not allowed.", $currency_symbol));
            }

            $this->ticket_type_service->updateCurrencySymbol($summit, strval($currency_symbol));

            return $this->updated();
        });
    }
}
