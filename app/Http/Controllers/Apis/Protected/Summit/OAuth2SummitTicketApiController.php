<?php namespace App\Http\Controllers;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\libs\Utils\Doctrine\ReplicaAwareTrait;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Registration\ISummitExternalRegistrationFeedType;
use App\ModelSerializers\ISummitAttendeeTicketSerializerTypes;
use App\ModelSerializers\SerializerUtils;
use App\Rules\Boolean;
use App\Security\SummitScopes;
use App\Services\Model\ISummitOrderService;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use OpenApi\Attributes as OA;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\IOrderConstants;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitOrderExtraQuestionTypeConstants;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\PagingResponse;

/**
 * Class OAuth2SummitTicketApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitTicketApiController extends OAuth2ProtectedController
{

    use GetSummitChildElementById;

    use ParametrizedGetAll;

    use GetAndValidateJsonPayload;

    use RequestProcessor;

    use ParseAndGetFilter;

    use ReplicaAwareTrait;

    /**
     * @return string
     */
    public function getChildSerializer()
    {
        return ISummitAttendeeTicketSerializerTypes::AdminType;
    }

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitOrderService
     */
    private $service;

    private $attendee_repository;

    /**
     * OAuth2SummitTicketApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitAttendeeRepository $attendee_repository,
     * @param ISummitAttendeeTicketRepository $repository
     * @param ISummitOrderService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository               $summit_repository,
        ISummitAttendeeRepository $attendee_repository,
        ISummitAttendeeTicketRepository $repository,
        ISummitOrderService             $service,
        IResourceServerContext          $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->attendee_repository = $attendee_repository;
        $this->service = $service;
    }


    #[OA\Get(
        path: '/api/v1/summits/{summit_id}/tickets',
        operationId: 'getAllTickets',
        summary: 'Get all tickets for a summit',
        description: 'Returns a paginated list of tickets for the specified summit with filtering and sorting capabilities',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadRegistrationOrders,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
            IGroup::BadgePrinters,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filter by number, owner_name, owner_email, status, ticket_type_id, etc.', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by id, number, status, owner_name, etc.', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'not_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filter by ticket status, allowed values: Reserved, Cancelled, Confirmed, Paid, Error'),
            new OA\Parameter(name: 'number', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order_number', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'owner_name', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'owner_first_name', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'owner_last_name', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'owner_email', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'owner_company', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'summit_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'owner_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'order_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'has_requested_refund_requests', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'access_level_type_name', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'access_level_type_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'ticket_type_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'view_type_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'has_owner', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'owner_status', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filter by owner status, allowed values: Complete, Incomplete'),
            new OA\Parameter(name: 'has_badge', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'promo_code_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'promo_code', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'promo_code_description', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'promo_code_tag_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'promo_code_tag', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'final_amount', in: 'query', required: false, schema: new OA\Schema(type: 'numeric')),
            new OA\Parameter(name: 'is_printable', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'badge_type_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'has_badge_prints', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'badge_prints_count', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'has_owner_company', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'exclude_is_printable_free_unassigned', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitAttendeeTicketsResponse')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
        ]
    )]
    public function getAllBySummit($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->withReplica(function() use($summit) {
            return $this->_getAll(
                function () {
                    return [
                        'id' => ['=='],
                        'not_id' => ['=='],
                        'number' => ['@@', '=@', '=='],
                        'order_number' => ['@@', '=@', '=='],
                        'owner_name' => ['@@', '=@', '=='],
                        'owner_first_name' => ['@@', '=@', '=='],
                        'owner_last_name' => ['@@', '=@', '=='],
                        'owner_email' => ['@@', '=@', '=='],
                        'owner_company' => ['@@', '=@', '=='],
                        'summit_id' => ['=='],
                        'owner_id' => ['=='],
                        'order_id' => ['=='],
                        'status' => ['==', '<>'],
                        'is_active' => ['=='],
                        'has_requested_refund_requests' => ['=='],
                        'access_level_type_name' => ['=='],
                        'access_level_type_id' => ['=='],
                        'ticket_type_id' => ['=='],
                        'has_owner' => ['=='],
                        'owner_status' => ['=='],
                        'has_badge' => ['=='],
                        'view_type_id' => ['=='],
                        'promo_code_id' => ['=='],
                        'promo_code' => ['==', '@@', '=@'],
                        'promo_code_description' => ['@@', '=@'],
                        'promo_code_tag_id' => ['=='],
                        'promo_code_tag' => ['==', '@@', '=@'],
                        'final_amount' => ['==', '<>', '>=', '>'],
                        'is_printable' => ['=='],
                        'badge_type_id' => ['=='],
                        'has_badge_prints' => ['=='],
                        'badge_prints_count' =>  ['==', '>=', '<=', '>', '<'],
                        'has_owner_company' => ['=='],
                        'exclude_is_printable_free_unassigned' => ['=='],
                        'has_promo_code' => ['=='],
                    ];
                },
                function () {
                    return [
                        'id' => 'sometimes|integer',
                        'not_id' => 'sometimes|integer',
                        'status' => sprintf('sometimes|in:%s', implode(',', IOrderConstants::ValidStatus)),
                        'number' => 'sometimes|string',
                        'order_number' => 'sometimes|string',
                        'owner_name' => 'sometimes|string',
                        'owner_first_name' => 'sometimes|string',
                        'owner_last_name' => 'sometimes|string',
                        'owner_email' => 'sometimes|string',
                        'owner_company' => 'sometimes|string',
                        'summit_id' => 'sometimes|integer',
                        'owner_id' => 'sometimes|integer',
                        'order_id' => 'sometimes|integer',
                        'is_active' => 'sometimes|boolean',
                        'has_requested_refund_requests' => 'sometimes|boolean',
                        'access_level_type_name' => 'sometimes|string',
                        'access_level_type_id' => 'sometimes|integer',
                        'ticket_type_id' => 'sometimes|integer',
                        'view_type_id' => 'sometimes|integer',
                        'has_owner' => 'sometimes|boolean',
                        'owner_status' => 'sometimes|string|in:' . implode(',', SummitAttendee::AllowedStatus),
                        'has_badge' => 'sometimes|boolean',
                        'promo_code_id' => 'sometimes|integer',
                        'promo_code' => 'sometimes|string',
                        'promo_code_description' => 'sometimes|string',
                        'promo_code_tag_id' => 'sometimes|integer',
                        'promo_code_tag' => 'sometimes|string',
                        'final_amount' => 'sometimes|numeric',
                        'is_printable' => ['sometimes', new Boolean()],
                        'badge_type_id' => 'sometimes|integer',
                        'has_badge_prints' => ['sometimes', new Boolean()],
                        'badge_prints_count' => 'sometimes|integer',
                        'has_owner_company' => ['sometimes', new Boolean()],
                        'exclude_is_printable_free_unassigned' => ['sometimes', new Boolean()],
                        'has_promo_code' => ['sometimes', new Boolean()],
                    ];
                },
                function () {
                    return [
                        'id',
                        'number',
                        'status',
                        'owner_name',
                        'owner_first_name',
                        'owner_last_name',
                        'ticket_type',
                        'final_amount',
                        'owner_email',
                        'owner_company',
                        'promo_code',
                        'bought_date',
                        'refunded_amount',
                        'final_amount_adjusted',
                        'badge_type_id',
                        'badge_type',
                        'badge_prints_count',
                        'created',
                    ];
                },
                function ($filter) use ($summit) {
                    if ($filter instanceof Filter) {
                        $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    }
                    return $filter;
                },
                function () {
                    return ISummitAttendeeTicketSerializerTypes::AdminType;
                }
            );
        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{summit_id}/tickets/external',
        operationId: 'getExternalTickets',
        summary: 'Get external ticket data',
        description: 'Returns ticket data from external registration feed by owner email',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadRegistrationOrders,
        ]]],
        x: ['required-groups' => [
            IGroup::BadgePrinters,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'filter', in: 'query', required: true, description: 'Filter by owner_email', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitAttendeeTicketsResponse')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Summit has no external feed'),
        ]
    )]
    public function getAllBySummitExternal($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            return $this->withReplica(function() use ($summit_id) {
                $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
                if (is_null($summit))
                    return $this->error404("Summit not found.");

                $external_feed_type = $summit->getExternalRegistrationFeedType();
                if (is_null($external_feed_type) ||
                    $external_feed_type == ISummitExternalRegistrationFeedType::NoneType)
                    return $this->error412("Summit has no external feed.");

                $filter = self::getFilter(
                    function () {
                        return ['owner_email' => ['==']];
                    },
                    function () {
                        return ['owner_email' => 'required|string'];
                    }
                );

                $filterVal = $filter->getUniqueFilter('owner_email');
                if (is_null($filterVal)) return $this->error400("missing owner_email parameter.");

                try {
                    $ticket = $this->service->getTicket($summit, $filterVal->getRawValue());
                } catch (EntityNotFoundException $ex) {
                    Log::error($ex);
                    $ticket = null;
                }

                $data = is_null($ticket) ? [] : [$ticket];

                $response = new PagingResponse
                (
                    1,
                    1,
                    1,
                    1,
                    $data
                );

                return $this->ok($response->toArray(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
            });
        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{summit_id}/tickets/csv',
        operationId: 'getAllTicketsCSV',
        summary: 'Get all tickets for a summit',
        description: 'Returns a paginated list of tickets for the specified summit with filtering and sorting capabilities',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadRegistrationOrders,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filter by number, owner_name, owner_email, status, ticket_type_id, etc.', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by id, number, status, owner_name, etc.', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'not_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filter by ticket status, allowed values: Reserved, Cancelled, Confirmed, Paid, Error'),
            new OA\Parameter(name: 'number', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order_number', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'owner_name', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'owner_first_name', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'owner_last_name', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'owner_email', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'owner_company', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'summit_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'owner_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'order_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'has_requested_refund_requests', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'access_level_type_name', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'access_level_type_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'ticket_type_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'view_type_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'has_owner', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'owner_status', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filter by owner status, allowed values: Complete, Incomplete'),
            new OA\Parameter(name: 'has_badge', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'promo_code_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'promo_code', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'promo_code_description', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'promo_code_tag_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'promo_code_tag', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'final_amount', in: 'query', required: false, schema: new OA\Schema(type: 'numeric')),
            new OA\Parameter(name: 'is_printable', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'badge_type_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'has_badge_prints', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'badge_prints_count', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'has_owner_company', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'exclude_is_printable_free_unassigned', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\MediaType(
                    mediaType: 'text/csv',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
        ]
    )]
    public function getAllBySummitCSV($summit_id)
    {
        return $this->withReplica(function() use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $questions = $summit->getOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
            return $this->_getAllCSV(
                function () {
                    return [
                        'id' => ['=='],
                        'not_id' => ['=='],
                        'number' => ['@@', '=@', '=='],
                        'order_number' => ['@@', '=@', '=='],
                        'owner_name' => ['@@', '=@', '=='],
                        'owner_first_name' => ['@@', '=@', '=='],
                        'owner_last_name' => ['@@', '=@', '=='],
                        'owner_email' => ['@@', '=@', '=='],
                        'owner_company' => ['@@', '=@', '=='],
                        'summit_id' => ['=='],
                        'owner_id' => ['=='],
                        'order_id' => ['=='],
                        'status' => ['==', '<>'],
                        'is_active' => ['=='],
                        'has_requested_refund_requests' => ['=='],
                        'access_level_type_name' => ['=='],
                        'access_level_type_id' => ['=='],
                        'ticket_type_id' => ['=='],
                        'has_owner' => ['=='],
                        'owner_status' => ['=='],
                        'has_badge' => ['=='],
                        'view_type_id' => ['=='],
                        'promo_code_id' => ['=='],
                        'promo_code' => ['==', '@@', '=@'],
                        'promo_code_tag' => ['==', '@@', '=@'],
                        'promo_code_tag_id' => ['=='],
                        'promo_code_description' => ['@@', '=@'],
                        'final_amount' => ['==', '<>', '>=', '>'],
                        'is_printable' => ['=='],
                        'badge_type_id' => ['=='],
                        'has_badge_prints' => ['=='],
                        'badge_prints_count' =>  ['==', '>=', '<=', '>', '<'],
                        'has_owner_company' => ['=='],
                        'exclude_is_printable_free_unassigned' => ['=='],
                        'has_promo_code' => ['=='],
                    ];
                },
                function () {
                    return [
                        'id' => 'sometimes|integer',
                        'not_id' =>  'sometimes|integer',
                        'status' => sprintf('sometimes|in:%s', implode(',', IOrderConstants::ValidStatus)),
                        'number' => 'sometimes|string',
                        'order_number' => 'sometimes|string',
                        'owner_name' => 'sometimes|string',
                        'owner_first_name' => 'sometimes|string',
                        'owner_last_name' => 'sometimes|string',
                        'owner_email' => 'sometimes|string',
                        'owner_company' => 'sometimes|string',
                        'summit_id' => 'sometimes|integer',
                        'owner_id' => 'sometimes|integer',
                        'order_id' => 'sometimes|integer',
                        'is_active' => 'sometimes|boolean',
                        'has_requested_refund_requests' => 'sometimes|boolean',
                        'access_level_type_name' => 'sometimes|string',
                        'access_level_type_id' => 'sometimes|integer',
                        'ticket_type_id' => 'sometimes|integer',
                        'view_type_id' => 'sometimes|integer',
                        'has_owner' => 'sometimes|boolean',
                        'owner_status' => 'sometimes|string|in:' . implode(',', SummitAttendee::AllowedStatus),
                        'has_badge' => 'sometimes|boolean',
                        'promo_code_id' => 'sometimes|integer',
                        'promo_code' => 'sometimes|string',
                        'promo_code_description' => 'sometimes|string',
                        'promo_code_tag_id' => 'sometimes|integer',
                        'promo_code_tag' => 'sometimes|string',
                        'final_amount' => 'sometimes|numeric',
                        'is_printable' => ['sometimes', new Boolean()],
                        'badge_type_id' => 'sometimes|integer',
                        'has_badge_prints' => ['sometimes', new Boolean()],
                        'badge_prints_count' => 'sometimes|integer',
                        'has_owner_company' => ['sometimes', new Boolean()],
                        'exclude_is_printable_free_unassigned' => ['sometimes', new Boolean()],
                        'has_promo_code' => ['sometimes', new Boolean()],
                    ];
                },
                function () {
                    return [
                        'id',
                        'number',
                        'status',
                        'owner_name',
                        'owner_first_name',
                        'owner_last_name',
                        'ticket_type',
                        'final_amount',
                        'owner_email',
                        'owner_company',
                        'promo_code',
                        'bought_date',
                        'refunded_amount',
                        'final_amount_adjusted',
                        'badge_type_id',
                        'badge_type',
                        'badge_prints_count',
                        'created',
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
                function () use($summit) {
                    return [
                        'created' => new EpochCellFormatter(EpochCellFormatter::DefaultFormat, $summit->getTimeZone()),
                        'last_edited' => new EpochCellFormatter(EpochCellFormatter::DefaultFormat, $summit->getTimeZone()),
                        'purchase_date' => new EpochCellFormatter(EpochCellFormatter::DefaultFormat, $summit->getTimeZone()),
                        'attendee_checked_in' => new BooleanCellFormatter(),
                        'is_active' => new BooleanCellFormatter(),
                    ];
                },
                function () use ($summit) {
                    $allowed_columns = [
                        'id',
                        'created',
                        'last_edited',
                        'number',
                        'status',
                        'attendee_id',
                        'attendee_first_name',
                        'attendee_last_name',
                        'attendee_email',
                        'attendee_company',
                        'external_order_id',
                        'external_attendee_id',
                        'purchase_date',
                        'ticket_type_id',
                        'ticket_type_name',
                        'order_id',
                        'badge_id',
                        'promo_code_id',
                        'promo_code',
                        'raw_cost',
                        'final_amount',
                        'discount',
                        'refunded_amount',
                        'currency',
                        'badge_type_id',
                        'badge_type_name',
                        'promo_code_tags',
                    ];

                    foreach ($summit->getBadgeFeaturesTypes() as $featuresType) {
                        $allowed_columns[] = $featuresType->getName();
                    }

                    foreach ($summit->getOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage) as $question) {
                        $allowed_columns[] = $question->getLabel();
                    }

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
                sprintf('tickets-%s-', $summit_id),
                [
                    'features_types' => $summit->getBadgeFeaturesTypes(),
                    'ticket_questions' => $questions
                ],
                null,
                function($data, $serializerParams) use($questions){

                    $owners = [];
                    foreach ($data->getItems() as $t){
                        if ($t->hasOwner()) $owners[] = $t->getOwner()->getId();
                    }
                    $questionIds = [];
                    foreach ($questions as $q) {
                        $questionIds[] = $q->getId();
                    }
                    $questionIds = array_values(array_unique($questionIds));
                    $owners = array_values(array_unique($owners));

                    $serializerParams['answers_by_owner'] = $this->attendee_repository->getExtraQuestionAnswersByOwners($owners, $questionIds);
                    return $serializerParams;
                }
            );
        });
    }

    #[OA\Put(
        path: '/api/v1/summits/{summit_id}/tickets/ingest',
        operationId: 'ingestExternalTicketData',
        summary: 'Ingest external ticket data',
        description: 'Triggers ingestion of ticket data from external registration feed',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteRegistrationData,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/IngestExternalTicketDataRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Ingestion process started successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
        ]
    )]
    public function ingestExternalTicketData($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload([
                'email_to' => 'nullable|email',
            ]);

            $this->service->ingestExternalTicketData($summit, $payload);

            return $this->ok();

        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{summit_id}/tickets/import-template',
        operationId: 'getTicketImportTemplate',
        summary: 'Get ticket import template',
        description: 'Returns a CSV template for importing ticket data',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteRegistrationData,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'CSV template',
                content: new OA\MediaType(mediaType: 'text/csv', schema: new OA\Schema(type: 'string'))
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
        ]
    )]
    public function getImportTicketDataTemplate($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            /**
             * id
             * number
             * attendee_email ( mandatory if id and number are missing)
             * attendee_first_name (optional)
             * attendee_last_name (optional)
             * attendee_company (optional)
             * attendee_tags (optional)
             * ticket_type_name ( mandatory if id and number are missing)
             * ticket_type_id ( mandatory if id and number are missing)
             * promo_code_id (optional)
             * promo_code (optional)
             * ticket_promo_code (optional)
             * badge_type_id (optional)
             * badge_type_name (optional)
             * badge_features (optional)
             */

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $row = [
                'id' => '',
                'number' => '',
                'attendee_email' => '',
                'attendee_first_name' => '',
                'attendee_last_name' => '',
                'attendee_company' => '',
                'attendee_tags' => '',
                'ticket_type_name' => '',
                'ticket_type_id' => '',
                'promo_code_id' => '',
                'promo_code' => '',
                'ticket_promo_code' => '',
                'badge_type_id' => '',
                'badge_type_name' => '',
            ];

            // badge features for summit
            foreach ($summit->getBadgeFeaturesTypes() as $featuresType) {
                $row[$featuresType->getName()] = '';
            }

            $template = [
                $row
            ];

            return $this->export
            (
                'csv',
                'ticket-data-import-template',
                $template,
                [],
                []
            );
        });
    }

    #[OA\Post(
        path: '/api/v1/summits/{summit_id}/tickets/import',
        operationId: 'importTicketData',
        summary: 'Import ticket data from CSV',
        description: 'Imports ticket data from a CSV file',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteRegistrationData,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'CSV file to import')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Import process started successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'File parameter not set'),
        ]
    )]
    public function importTicketData(LaravelRequest $request, $summit_id)
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

            $this->service->importTicketData($summit, $file);

            return $this->ok();

        });
    }

    #[OA\Get(
        path: '/api/v1/summits/all/tickets/me',
        operationId: 'getAllMyTickets',
        summary: 'Get all my tickets across all summits',
        description: 'Returns all tickets owned by the current user across all summits',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::ReadMyRegistrationOrders,
        ]]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filter by number, order_number, status, etc.', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by id, number, status, created', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitAttendeeTicketsResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
        ]
    )]
    public function getAllMyTickets()
    {
        return $this->getAllMyTicketsBySummit('all');
    }

    #[OA\Get(
        path: '/api/v1/summits/{summit_id}/tickets/me',
        operationId: 'getMyTicketsBySummit',
        summary: 'Get my tickets for a summit',
        description: 'Returns all tickets owned by the current user for a specific summit',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::ReadMyRegistrationOrders,
        ]]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filter by number, order_number, status, etc.', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by id, number, status, created', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'number', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order_number', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order_owner_email', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'summit_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'order_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'order_owner_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'has_order_owner', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Valid values: Reserved, Cancelled, Confirmed, Paid, Error'),
            new OA\Parameter(name: 'final_amount', in: 'query', required: false, schema: new OA\Schema(type: 'numeric')),
            new OA\Parameter(name: 'assigned_to', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Valid values: Me, SomeoneElse, Nobody'),
            new OA\Parameter(name: 'owner_status', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Valid values: Complete, Incomplete'),
            new OA\Parameter(name: 'badge_features_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'ticket_type_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'promo_code', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitAttendeeTicketsResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
        ]
    )]
    public function getAllMyTicketsBySummit($summit_id)
    {
        $owner = $this->getResourceServerContext()->getCurrentUser();

        return $this->withReplica(function() use($owner, $summit_id) {
            return $this->_getAll(
                function () {
                    return [
                        'number' => ['=@', '==', '@@'],
                        'order_number' => ['=@', '==', '@@'],
                        'order_owner_email' => ['=@', '==', '@@'],
                        'summit_id' => ['=='],
                        'order_id' => ['=='],
                        'status' => ['==', '<>'],
                        'order_owner_id' => ['==', '<>'],
                        'has_order_owner' => ['=='],
                        'final_amount' => ['==', '<>', '>=', '>'],
                        'assigned_to' =>  ['=='],
                        'owner_status' =>  ['=='],
                        'badge_features_id' =>  ['=='],
                        'ticket_type_id' =>  ['=='],
                        'promo_code' => ['=@', '=='],
                    ];
                },
                function () {
                    return [
                        'number' => 'sometimes|string',
                        'order_number' => 'sometimes|string',
                        'order_owner_email' => 'sometimes|string',
                        'summit_id' => 'sometimes|integer',
                        'order_id' => 'sometimes|integer',
                        'order_owner_id' => 'sometimes|integer',
                        'has_order_owner' => 'sometimes|boolean',
                        'status' => sprintf('sometimes|in:%s', implode(',', IOrderConstants::ValidStatus)),
                        'final_amount' => 'sometimes|numeric',
                        'assigned_to' => sprintf('sometimes|in:%s', implode(',', ['Me', 'SomeoneElse', 'Nobody'])),
                        'owner_status' => sprintf('sometimes|in:%s', implode(',', SummitAttendee::AllowedStatus)),
                        'badge_features_id' => 'sometimes|integer',
                        'ticket_type_id' => 'sometimes|integer',
                        'promo_code' => 'sometimes|string',
                    ];
                },
                function () {
                    return [
                        'id',
                        'number',
                        'status',
                        'created',
                    ];
                },
                function ($filter) use ($owner, $summit_id) {
                    if ($filter instanceof Filter) {
                        if (is_numeric($summit_id)) {
                            $filter->addFilterCondition(FilterElement::makeEqual('summit_id', intval($summit_id)));
                        }
                        $filter->addFilterCondition(FilterElement::makeEqual('member_id', $owner->getId()));
                        $filter->addFilterCondition(FilterElement::makeEqual('is_active', true));

                        if($filter->hasFilter("assigned_to")){
                            $assigned_to = $filter->getValue("assigned_to")[0];
                            if(in_array($assigned_to, ['Me','SomeoneElse'])){
                                $filter->addFilterCondition(FilterElement::makeEqual('owner_member_id', $owner->getId()));
                                $filter->addFilterCondition(FilterElement::makeEqual('owner_member_email', $owner->getEmail()));
                            }
                        }
                    }
                    return $filter;
                },
                function () {
                    return ISummitAttendeeTicketSerializerTypes::AdminType;
                }
            );
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
     * @param Summit $summit
     * @param $child_id
     * @return IEntity|null
     * @throws \Exception
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        return $this->withReplica(function() use($summit, $child_id) {
            return $this->service->getTicket($summit, $child_id);
        });

    }

    #[OA\Delete(
        path: '/api/v1/summits/{summit_id}/tickets/{ticket_id}/refund',
        operationId: 'refundTicket',
        summary: 'Refund a ticket',
        description: 'Processes a refund for a specific ticket',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::UpdateRegistrationOrders,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'ticket_id', in: 'path', required: true, description: 'Ticket ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RefundTicketRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Ticket refunded successfully',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or ticket not found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation Error'),
        ]
    )]
    public function refundTicket($summit_id, $ticket_id)
    {
        return $this->processRequest(function () use ($summit_id, $ticket_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $current_user = $this->getResourceServerContext()->getCurrentUser();
            if (is_null($current_user))
                return $this->error403();

            $payload = $this->getJsonPayload([
                'amount' => 'required|numeric|greater_than:0',
                'notes' => 'sometimes|string|max:255',
            ]);

            $ticket = $this->service->refundTicket
            (
                $summit,
                $current_user,
                $ticket_id,
                floatval($payload['amount']),
                trim($payload['notes'] ?? '')
            );

            return $this->updated
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($ticket, ISummitAttendeeTicketSerializerTypes::AdminType)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    )
            );
        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{summit_id}/tickets/{ticket_id}/badge',
        operationId: 'getTicketBadge',
        summary: 'Get ticket badge',
        description: 'Returns the badge associated with a ticket',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadRegistrationOrders,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'ticket_id', in: 'path', required: true, description: 'Ticket ID or number', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(schema: 'SummitAttendeeBadge')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, ticket or badge not found'),
        ]
    )]
    public function getAttendeeBadge($summit_id, $ticket_id)
    {
        return $this->processRequest(function () use ($summit_id, $ticket_id) {

            return $this->withReplica(function() use($summit_id, $ticket_id) {
                $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
                if (is_null($summit)) return $this->error404();

                $ticket = is_int($ticket_id) ? $this->repository->getById(intval($ticket_id)) : $this->repository->getByNumber($ticket_id);
                if (is_null($ticket) || !$ticket instanceof SummitAttendeeTicket) return $this->error404();;
                if ($ticket->getOrder()->getSummitId() != $summit->getId()) return $this->error404();
                if (!$ticket->hasBadge()) return $this->error404();

                return $this->ok(SerializerRegistry::getInstance()->getSerializer($ticket->getBadge())->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
            });
        });
    }

    #[OA\Post(
        path: '/api/v1/summits/{summit_id}/tickets/{ticket_id}/badge',
        operationId: 'createTicketBadge',
        summary: 'Create ticket badge',
        description: 'Creates a badge for a specific ticket',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::UpdateRegistrationOrdersBadges,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'ticket_id', in: 'path', required: true, description: 'Ticket ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/CreateBadgeRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Badge created successfully',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or ticket not found'),
        ]
    )]
    public function createAttendeeBadge($summit_id, $ticket_id)
    {
        return $this->processRequest(function () use ($summit_id, $ticket_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'badge_type_id' => 'sometimes|integer',
                'features' => 'sometimes|int_array',
            ]);

            $badge = $this->service->createBadge($summit, $ticket_id, $payload);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($badge)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: '/api/v1/summits/{summit_id}/tickets/{ticket_id}/badge',
        operationId: 'deleteTicketBadge',
        summary: 'Delete ticket badge',
        description: 'Deletes the badge associated with a ticket',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::UpdateRegistrationOrders,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'ticket_id', in: 'path', required: true, description: 'Ticket ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Badge deleted successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, ticket or badge not found'),
        ]
    )]
    public function deleteAttendeeBadge($summit_id, $ticket_id)
    {
        return $this->processRequest(function () use ($summit_id, $ticket_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->service->deleteBadge($summit, $ticket_id);
            return $this->deleted();
        });
    }

    #[OA\Put(
        path: '/api/v1/summits/{summit_id}/tickets/{ticket_id}/badge/type/{type_id}',
        operationId: 'updateTicketBadgeType',
        summary: 'Update badge type',
        description: 'Updates the badge type for a ticket',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::UpdateRegistrationOrdersBadges,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'ticket_id', in: 'path', required: true, description: 'Ticket ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'type_id', in: 'path', required: true, description: 'Badge Type ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Badge type updated successfully',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, ticket or badge type not found'),
        ]
    )]
    public function updateAttendeeBadgeType($summit_id, $ticket_id, $type_id)
    {
        return $this->processRequest(function () use ($summit_id, $ticket_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $badge = $this->service->updateBadgeType($summit, $ticket_id, $type_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($badge)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
        path: '/api/v1/summits/{summit_id}/tickets/{ticket_id}/badge/features/{feature_id}',
        operationId: 'addTicketBadgeFeature',
        summary: 'Add badge feature',
        description: 'Adds a feature to a ticket badge',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::UpdateRegistrationOrdersBadges,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'ticket_id', in: 'path', required: true, description: 'Ticket ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'feature_id', in: 'path', required: true, description: 'Feature ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Feature added successfully',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, ticket or feature not found'),
        ]
    )]
    public function addAttendeeBadgeFeature($summit_id, $ticket_id, $feature_id)
    {
        return $this->processRequest(function () use ($summit_id, $ticket_id, $feature_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $badge = $this->service->addAttendeeBadgeFeature($summit, $ticket_id, $feature_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($badge)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: '/api/v1/summits/{summit_id}/tickets/{ticket_id}/badge/features/{feature_id}',
        operationId: 'removeTicketBadgeFeature',
        summary: 'Remove badge feature',
        description: 'Removes a feature from a ticket badge',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::UpdateRegistrationOrdersBadges,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'ticket_id', in: 'path', required: true, description: 'Ticket ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'feature_id', in: 'path', required: true, description: 'Feature ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Feature removed successfully',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, ticket or feature not found'),
        ]
    )]
    public function removeAttendeeBadgeFeature($summit_id, $ticket_id, $feature_id)
    {
        return $this->processRequest(function () use ($summit_id, $ticket_id, $feature_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $badge = $this->service->removeAttendeeBadgeFeature($summit, $ticket_id, $feature_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($badge)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Put(
        path: '/api/v1/summits/{summit_id}/tickets/{ticket_id}/badge/print',
        operationId: 'printTicketBadge',
        summary: 'Print badge with default view',
        description: 'Prints a badge using the summit\'s default view type',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::PrintRegistrationOrdersBadges,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
            IGroup::BadgePrinters,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'ticket_id', in: 'path', required: true, description: 'Ticket ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/PrintBadgeRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Badge printed successfully',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, ticket or default view type not found'),
        ]
    )]
    public function printAttendeeBadgeDefault($summit_id, $ticket_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404('Summit not Found.');

        $viewType = $summit->getDefaultBadgeViewType();
        if (is_null($viewType))
            return $this->error404('Default view type not found.');

        return $this->printAttendeeBadge($summit_id, $ticket_id, $viewType->getName());
    }

    #[OA\Put(
        path: '/api/v1/summits/{summit_id}/tickets/{ticket_id}/badge/{view_type}/print',
        operationId: 'printTicketBadgeByViewType',
        summary: 'Print badge with specific view type',
        description: 'Prints a badge using a specific view type',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::PrintRegistrationOrdersBadges,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
            IGroup::BadgePrinters,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'ticket_id', in: 'path', required: true, description: 'Ticket ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'view_type', in: 'path', required: true, description: 'View type name', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/PrintBadgeRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Badge printed successfully',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, ticket or view type not found'),
        ]
    )]
    public function printAttendeeBadge($summit_id, $ticket_id, $view_type)
    {
        return $this->processRequest(function () use ($summit_id, $ticket_id, $view_type) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'check_in' => 'sometimes|boolean',
            ]);

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $badge = $this->service->printAttendeeBadge($summit, $ticket_id, $view_type, $current_member, $payload);

            return $this->updated
            (
                SerializerRegistry::getInstance()->getSerializer($badge)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations()
                    )
            );
        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{summit_id}/tickets/{ticket_id}/badge/can-print',
        operationId: 'canPrintTicketBadge',
        summary: 'Check if badge can be printed (default view)',
        description: 'Checks if a badge can be printed using the default view type',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::PrintRegistrationOrdersBadges,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
            IGroup::BadgePrinters,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'ticket_id', in: 'path', required: true, description: 'Ticket ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Badge printability status',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or ticket not found'),
        ]
    )]
    public function canPrintAttendeeBadgeDefault($summit_id, $ticket_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404('Summit not Found.');

        $viewType = $summit->getDefaultBadgeViewType();
        if (is_null($viewType))
            return $this->error404('Default view type not found.');

        return $this->canPrintAttendeeBadge($summit_id, $ticket_id, $viewType->getName());
    }

    #[OA\Get(
        path: '/api/v1/summits/{summit_id}/tickets/{ticket_id}/badge/{view_type}/can-print',
        operationId: 'canPrintTicketBadgeByViewType',
        summary: 'Check if badge can be printed (specific view)',
        description: 'Checks if a badge can be printed using a specific view type',
        security: [['summit_tickets_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::PrintRegistrationOrdersBadges,
        ]]],
        x: ['required-groups' => [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
            IGroup::SummitRegistrationAdmins,
            IGroup::BadgePrinters,
        ]],
        tags: ['tickets'],
        parameters: [
            new OA\Parameter(name: 'summit_id', in: 'path', required: true, description: 'Summit ID or slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'ticket_id', in: 'path', required: true, description: 'Ticket ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'view_type', in: 'path', required: true, description: 'View type name', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Badge printability status',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, ticket or view type not found'),
        ]
    )]
    public function canPrintAttendeeBadge($summit_id, $ticket_id, $view_type)
    {
        return $this->processRequest(function () use ($summit_id, $ticket_id, $view_type) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $badge = $this->service->canPrintAttendeeBadge($summit, $ticket_id, $view_type, $current_member);

            return $this->ok
            (
                SerializerRegistry::getInstance()->getSerializer($badge)->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }
}
