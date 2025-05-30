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

use App\Http\Renderers\IRenderersFormats;
use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use App\Models\Foundation\Summit\Repositories\ISummitRefundRequestRepository;
use App\ModelSerializers\ISummitAttendeeTicketSerializerTypes;
use App\ModelSerializers\ISummitOrderSerializerTypes;
use App\ModelSerializers\SerializerUtils;
use App\Rules\Boolean;
use App\Services\Model\ISummitOrderService;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\oauth2\IResourceServerContext;
use models\summit\IOrderConstants;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\ISummitRefundRequestConstants;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitAttendeeTicketRefundRequest;
use models\summit\SummitOrder;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class OAuth2SummitOrdersApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitOrdersApiController
    extends OAuth2ProtectedController
{

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use GetAndValidateJsonPayload;

    use ParametrizedGetAll;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    use RequestProcessor;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitOrderService
     */
    private $service;

    /**
     * @var ISummitAttendeeTicketRepository
     */
    private $ticket_repository;

    /**
     * @var ISummitRefundRequestRepository
     */
    private $refund_request_repository;

    /**
     * @param ISummitOrderRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitAttendeeTicketRepository $ticket_repository
     * @param ISummitRefundRequestRepository $refund_request_repository
     * @param ISummitOrderService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitOrderRepository          $repository,
        ISummitRepository               $summit_repository,
        ISummitAttendeeTicketRepository $ticket_repository,
        ISummitRefundRequestRepository  $refund_request_repository,
        ISummitOrderService             $service,
        IResourceServerContext          $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
        $this->ticket_repository = $ticket_repository;
        $this->refund_request_repository = $refund_request_repository;
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function reserve($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $owner = $this->getResourceServerContext()->getCurrentUser();

            $validation_rules = [
                'tickets' => 'required|ticket_dto_array',
                'extra_questions' => 'sometimes|extra_question_dto_array',
                'owner_company' => 'nullable|string|max:255',
                'owner_company_id' => 'nullable|integer',
            ];

            if (is_null($owner)) {
                // if there is no current user ( public api )
                // request owner data
                $validation_rules = array_merge([
                    'owner_first_name' => 'required|string|max:255',
                    'owner_last_name' => 'required|string|max:255',
                    'owner_email' => 'required|string|max:255|email',
                ], $validation_rules);
            } else {
                // if current user exists but data is empty
                if (empty($owner->getFirstName())) {
                    $validation_rules = array_merge([
                        'owner_first_name' => 'required|string|max:255',
                    ], $validation_rules);
                }

                if (empty($owner->getLastName())) {
                    $validation_rules = array_merge([
                        'owner_last_name' => 'required|string|max:255',
                    ], $validation_rules);
                }
            }

            $payload = $this->getJsonPayload($validation_rules, true);

            if (!is_null($owner)) {
                // if we have owner then set up the email
                $payload = array_merge($payload, [
                    'owner_email' => $owner->getEmail(),
                ]);
            }

            $order = $this->service->reserve($owner, $summit, $payload);

            return $this->created
            (
                SerializerRegistry::getInstance()->getSerializer
                (
                    $order, ISummitOrderSerializerTypes::ReservationType
                )->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $hash
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function checkout($summit_id, $hash)
    {

        return $this->processRequest(function () use ($summit_id, $hash) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'billing_address_1' => 'nullable|sometimes|string|max:255',
                'billing_address_2' => 'nullable|sometimes|string|max:255',
                'billing_address_zip_code' => 'nullable|sometimes|string|max:255',
                'billing_address_city' => 'nullable|sometimes|string|max:255',
                'billing_address_state' => 'nullable|sometimes|string|max:255',
                'billing_address_country' => 'nullable|sometimes|string|country_iso_alpha2_code',
                'payment_method_id' => 'nullable|sometimes|string',
            ]);

            $order = $this->service->checkout($summit, $hash, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer
            (
                $order,
                ISummitOrderSerializerTypes::CheckOutType
            )->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $hash
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getMyTicketByOrderHash($summit_id, $hash)
    {
        return $this->processRequest(function () use ($summit_id, $hash) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $ticket = $this->service->getMyTicketByOrderHash($summit, $hash);

            return $this->created(SerializerRegistry::getInstance()
                ->getSerializer($ticket, ISummitAttendeeTicketSerializerTypes::GuestEdition)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $hash
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function cancel($summit_id, $hash)
    {
        return $this->processRequest(function () use ($summit_id, $hash) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->service->cancel($summit, $hash);
            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllBySummit($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'number' => ['=@', '==','@@'],
                    'owner_name' => ['=@', '==','@@'],
                    'owner_email' => ['=@', '==','@@'],
                    'owner_company' => ['=@', '==','@@'],
                    'ticket_owner_name' => ['=@', '==','@@'],
                    'ticket_owner_email' => ['=@', '==','@@'],
                    'ticket_number' => ['=@', '==','@@'],
                    'summit_id' => ['=='],
                    'owner_id' => ['=='],
                    'status' => ['==', '<>'],
                    'created' => ['>', '<', '<=', '>=', '==','[]'],
                    'amount' => ['==', '<>', '>=', '>'],
                    'payment_method' => ['==']
                ];
            },
            function () {
                return [
                    'status' => sprintf('sometimes|in:%s', implode(',', IOrderConstants::ValidStatus)),
                    'number' => 'sometimes|string',
                    'owner_name' => 'sometimes|string',
                    'owner_email' => 'sometimes|string',
                    'owner_company' => 'sometimes|string',
                    'ticket_owner_name' => 'sometimes|string',
                    'ticket_owner_email' => 'sometimes|string',
                    'ticket_number' => 'sometimes|string',
                    'summit_id' => 'sometimes|integer',
                    'owner_id' => 'sometimes|integer',
                    'created' => 'sometimes|required|date_format:U|epoch_seconds',
                    'amount' => 'sometimes|numeric',
                    'payment_method' => sprintf('sometimes|in:%s', implode(',', IOrderConstants::ValidPaymentMethods)),
                ];
            },
            function () {
                return [
                    'id',
                    'number',
                    'status',
                    'owner_name',
                    'owner_email',
                    'owner_company',
                    'owner_id',
                    'created',
                    'amount',
                    'payment_method',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function () {
                return ISummitOrderSerializerTypes::AdminType;
            }
        );
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllBySummitCSV($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function () {
                return [
                    'number' => ['=@', '==','@@'],
                    'owner_name' => ['=@', '==','@@'],
                    'owner_email' => ['=@', '==','@@'],
                    'owner_company' => ['=@', '==','@@'],
                    'ticket_owner_name' => ['=@', '==','@@'],
                    'ticket_owner_email' => ['=@', '==','@@'],
                    'ticket_number' => ['=@', '==','@@'],
                    'summit_id' => ['=='],
                    'owner_id' => ['=='],
                    'status' => ['==', '<>'],
                    'created' => ['>', '<', '<=', '>=', '==','[]'],
                    'amount' => ['==', '<>', '>=', '>'],
                    'payment_method' => ['==']
                ];
            },
            function () {
                return [
                    'status' => sprintf('sometimes|in:%s', implode(',', IOrderConstants::ValidStatus)),
                    'number' => 'sometimes|string',
                    'owner_name' => 'sometimes|string',
                    'owner_email' => 'sometimes|string',
                    'owner_company' => 'sometimes|string',
                    'ticket_owner_name' => 'sometimes|string',
                    'ticket_owner_email' => 'sometimes|string',
                    'ticket_number' => 'sometimes|string',
                    'summit_id' => 'sometimes|integer',
                    'owner_id' => 'sometimes|integer',
                    'created' => 'sometimes|required|date_format:U|epoch_seconds',
                    'amount' => 'sometimes|numeric',
                    'payment_method' => sprintf('sometimes|in:%s', implode(',', IOrderConstants::ValidPaymentMethods)),
                ];
            },
            function () {
                return [
                    'id',
                    'number',
                    'status',
                    'owner_name',
                    'owner_email',
                    'owner_company',
                    'owner_id',
                    'created',
                    'amount',
                    'payment_method',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function () {
                return ISummitOrderSerializerTypes::AdminType;
            },
            function () {
                return [];
            },
            function () {
                return [];
            },
            'orders-'
        );
    }

    /**
     * @return mixed
     */

    public function getAllMyOrders()
    {
        return $this->getAllMyOrdersBySummit('all');
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllMyOrdersBySummit($summit_id)
    {
        $owner = $this->getResourceServerContext()->getCurrentUser();
        return $this->_getAll(
            function () {
                return [
                    'number' => ['=@', '=='],
                    'summit_id' => ['=='],
                    'status' => ['==', '<>'],
                    'owner_id' => ['=='],
                    'created' => ['>', '<', '<=', '>=', '==','[]'],
                    'tickets_assigned_to' =>  ['=='],
                    'tickets_owner_status' =>  ['=='],
                    'tickets_badge_features_id' =>  ['=='],
                    'tickets_type_id' =>  ['=='],
                    'amount' => ['==', '<>', '>=', '>'],
                    'tickets_promo_code' => ['=@', '=='],
                ];
            },
            function () {
                return [
                    'status' => sprintf('sometimes|in:%s', implode(',', IOrderConstants::ValidStatus)),
                    'number' => 'sometimes|string',
                    'summit_id' => 'sometimes|integer',
                    'owner_id' => 'sometimes|integer',
                    'created' => 'sometimes|required|date_format:U|epoch_seconds',
                    'tickets_assigned_to' => sprintf('sometimes|in:%s', implode(',', ['Me', 'SomeoneElse', 'Nobody'])),
                    'tickets_owner_status' => sprintf('sometimes|in:%s', implode(',', SummitAttendee::AllowedStatus)),
                    'tickets_badge_features_id' => 'sometimes|integer',
                    'tickets_type_id' => 'sometimes|integer',
                    'amount' => 'sometimes|numeric',
                    'tickets_promo_code' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'number',
                    'status',
                    'owner_name',
                    'owner_email',
                    'owner_company',
                    'owner_id',
                    'created',
                ];
            },
            function ($filter) use ($owner, $summit_id) {
                if ($filter instanceof Filter) {
                    if (is_numeric($summit_id)) {
                        $filter->addFilterCondition(FilterElement::makeEqual('summit_id', intval($summit_id)));
                    }
                    $filter->addFilterCondition(FilterElement::makeEqual('owner_id', $owner->getId()));
                    if($filter->hasFilter("tickets_assigned_to")){
                        $assigned_to = $filter->getValue("tickets_assigned_to")[0];
                        if(in_array($assigned_to, ['Me','SomeoneElse'])){
                            $filter->addFilterCondition(FilterElement::makeEqual('tickets_owner_member_id', $owner->getId()));
                            $filter->addFilterCondition(FilterElement::makeEqual('tickets_owner_member_email', $owner->getEmail()));
                        }
                    }
                }
                return $filter;
            },
            function () {
                return ISummitOrderSerializerTypes::OwnType;
            }
        );
    }

    /**
     * @param $order_id
     * @param $ticket_id
     * @return mixed
     */
    public function getMyTicketById($order_id, $ticket_id)
    {

        return $this->processRequest(function () use ($order_id, $ticket_id) {

            $order = $this->repository->getById(intval($order_id));
            $current_user = $this->getResourceServerContext()->getCurrentUser();

            if (is_null($current_user))
                return $this->error401();

            if (!$order instanceof SummitOrder)
                throw new EntityNotFoundException("Order not found.");

            // check ownership
            $isOrderOwner = true;
            if ($order->getOwnerEmail() != $current_user->getEmail())
                $isOrderOwner = false;

            $ticket = $order->getTicketById(intval($ticket_id));
            if (!$ticket instanceof SummitAttendeeTicket)
                throw new EntityNotFoundException("Ticket not found.");

            if(!$ticket->hasOwner() && !$isOrderOwner)
                throw new EntityNotFoundException("Order not found.");

            $isTicketOwner = true;
            $ticketOwnerEmail = $ticket->getOwnerEmail();
            Log::debug
            (
                sprintf
                (
                    "OAuth2SummitOrdersApiController::getMyTicketById ticketOwnerEmail %s current email %s",
                    $ticketOwnerEmail,
                    $current_user->getEmail()
                )
            );

            if(!empty($ticketOwnerEmail) && $ticketOwnerEmail != $current_user->getEmail())
                $isTicketOwner = false;

            if(!$isTicketOwner){
                // check if we are the manager
                $isTicketOwner = $ticket->hasOwner() && $ticket->getOwner()->isManagedBy($current_user);
                Log::debug(sprintf("OAuth2SummitOrdersApiController::getMyTicketById isTicketOwner %b (manager)" , $isTicketOwner));
            }

            if(!$isOrderOwner && !$isTicketOwner)
                throw new EntityNotFoundException("Ticket not found.");

            return $this->ok(SerializerRegistry::getInstance()
                ->getSerializer($ticket, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
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
     * @param $order_id
     */
    public function updateMyOrder($order_id)
    {
        return $this->processRequest(function () use ($order_id) {

            $current_user = $this->getResourceServerContext()->getCurrentUser();
            $payload = $this->getJsonPayload([
                'extra_questions' => 'sometimes|extra_question_dto_array',
                'owner_company' => 'sometimes|string|max:255',
                'billing_address_1' => 'sometimes|string|max:255',
                'billing_address_2' => 'sometimes|string|max:255',
                'billing_address_zip_code' => 'sometimes|string|max:255',
                'billing_address_city' => 'sometimes|string|max:255',
                'billing_address_state' => 'sometimes|string|max:255',
                'billing_address_country' => 'sometimes|string|country_iso_alpha2_code',
            ]);

            $order = $this->service->updateMyOrder($current_user, intval($order_id), $payload);

            return $this->created(SerializerRegistry::getInstance()
                ->getSerializer($order, ISummitOrderSerializerTypes::CheckOutType)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }


    /**
     * @param $order_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function cancelRefundRequestTicket($order_id, $ticket_id)
    {
        return $this->processRequest(function () use ($order_id, $ticket_id) {
            $current_user = $this->getResourceServerContext()->getCurrentUser();
            if (is_null($current_user))
                return $this->error403();

            $payload = $this->getJsonPayload([
                'notes' => 'sometimes|string|max:255',
            ]);

            $ticket = $this->service->cancelRequestRefundTicket(intval($order_id), intval($ticket_id), $current_user, trim($payload['notes'] ?? ''));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($ticket)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    /**
     * @param $order_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function requestRefundMyTicket($order_id, $ticket_id)
    {
        return $this->processRequest(function () use ($order_id, $ticket_id) {
            $current_user = $this->getResourceServerContext()->getCurrentUser();
            if (is_null($current_user))
                return $this->error403();

            $ticket = $this->service->requestRefundTicket($current_user, intval($order_id), intval($ticket_id));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($ticket)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $order_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function requestRefundMyOrder($order_id)
    {
        return $this->processRequest(function () use ($order_id) {
            $current_user = $this->getResourceServerContext()->getCurrentUser();

            $order = $this->service->requestRefundOrder($current_user, intval($order_id));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($order)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $order_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function assignAttendee($order_id, $ticket_id)
    {
        return $this->processRequest(function () use ($order_id, $ticket_id) {
            $current_user = $this->getResourceServerContext()->getCurrentUser();

            $payload = $this->getJsonPayload([
                'attendee_first_name' => 'nullable|string|max:255',
                'attendee_last_name' => 'nullable|string|max:255',
                'attendee_email' => 'required|string|max:255|email',
                'attendee_company' => 'nullable|string|max:255',
                'disclaimer_accepted' => 'nullable|boolean',
                'extra_questions' => 'sometimes|extra_question_dto_array',
                'message' => 'sometimes|string|max:1024',
            ]);

            $ticket = $this->service->ownerAssignTicket($current_user, intval($order_id), intval($ticket_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($ticket)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $order_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function reSendOrderEmail($order_id)
    {
        return $this->processRequest(function () use ($order_id) {

            $order = $this->service->reSendOrderEmail(intval($order_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($order)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $order_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function reInviteAttendee($order_id, $ticket_id)
    {
        return $this->processRequest(function () use ($order_id, $ticket_id) {

            $current_user = $this->resource_server_context->getCurrentUser();
            if (is_null($current_user))
                return $this->error403();

            $ticket = $this->ticket_repository->getById(intval($ticket_id));

            if (!$ticket instanceof SummitAttendeeTicket)
                throw new EntityNotFoundException('Ticket not found.');

            if (!$ticket->canEditTicket($current_user)) {
                return $this->error403("User can edit ticket.");
            }

            $payload = $this->getJsonPayload(['message' => 'sometimes|string|max:1024'], true);

            $ticket = $this->service->reInviteAttendee(intval($order_id), intval($ticket_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($ticket)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $order_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateTicket($summit_id, $order_id, $ticket_id)
    {
        return $this->processRequest(function () use ($summit_id, $order_id, $ticket_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'ticket_type_id' => 'nullable|integer',
                'badge_type_id' => 'nullable|integer',
                'attendee_first_name' => 'nullable|string|max:255',
                'attendee_last_name' => 'nullable|string|max:255',
                'attendee_email' => 'nullable|string|max:255|email',
                'attendee_company' => 'nullable|string|max:255',
                'attendee_company_id' => 'nullable|sometimes|integer',
                'disclaimer_accepted' => 'nullable|boolean',
                'extra_questions' => 'sometimes|extra_question_dto_array'
            ]);

            $ticket = $this->service->updateTicket($summit, intval($order_id), intval($ticket_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($ticket, ISummitAttendeeTicketSerializerTypes::AdminType)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $order_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addTicket($summit_id, $order_id)
    {
        return $this->processRequest(function () use ($summit_id, $order_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'ticket_type_id' => 'required|integer',
                'ticket_qty' => 'required|integer|min:1',
                'promo_code' => 'sometimes|string',
                'badge_type_id' => 'nullable|integer',
                'attendee_first_name' => 'nullable|string|max:255',
                'attendee_last_name' => 'nullable|string|max:255',
                'attendee_email' => 'sometimes|string|max:255|email',
                'attendee_company' => 'nullable|string|max:255',
                'disclaimer_accepted' => 'nullable|boolean',
                'extra_questions' => 'sometimes|extra_question_dto_array'
            ]);

            $order = $this->service->addTickets($summit, intval($order_id), $payload);

            return $this->created(SerializerRegistry::getInstance()
                ->getSerializer($order)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $order_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeAttendee($order_id, $ticket_id)
    {

        return $this->processRequest(function () use ($order_id, $ticket_id) {

            $current_user = $this->getResourceServerContext()->getCurrentUser();
            $ticket = $this->service->revokeTicket($current_user, intval($order_id), intval($ticket_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($ticket)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );

        });
    }

    /**
     * @param $summit_id
     * @param $order_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|mixed
     */
    public function getTicketPDFBySummit($summit_id, $order_id, $ticket_id)
    {
        return $this->processRequest(function () use ($summit_id, $order_id, $ticket_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $content = $this->service->renderTicketByFormat(intval($ticket_id), IRenderersFormats::PDFFormat, null, intval($order_id), $summit);
            return $this->pdf('ticket_' . $ticket_id . '.pdf', $content);
        });
    }

    /**
     * @param $order_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|mixed
     */
    public function getTicketPDFByOrderId($order_id, $ticket_id)
    {
        return $this->processRequest(function () use ($order_id, $ticket_id) {
            $current_user = $this->getResourceServerContext()->getCurrentUser();
            $content = $this->service->renderTicketByFormat(intval($ticket_id), IRenderersFormats::PDFFormat, $current_user, intval($order_id));
            return $this->pdf('ticket_' . $ticket_id . '.pdf', $content);
        });
    }

    /**
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|mixed
     */
    public function getMyTicketPDFById($ticket_id)
    {
        return $this->processRequest(function () use ($ticket_id) {
            $current_user = $this->getResourceServerContext()->getCurrentUser();
            $content = $this->service->renderTicketByFormat(intval($ticket_id), IRenderersFormats::PDFFormat, $current_user);
            return $this->pdf('ticket_' . $ticket_id . '.pdf', $content);
        });
    }

    /// public endpoints

    /**
     * @param $hash
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getTicketByHash($hash)
    {
        return $this->processRequest(function () use ($hash) {
            $ticket = $this->service->getTicketByHash($hash);
            if (is_null($ticket) || !$ticket->isActive())
                throw new EntityNotFoundException();
            return $this->ok(SerializerRegistry::getInstance()
                ->getSerializer($ticket, ISummitAttendeeTicketSerializerTypes::PublicEdition)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $hash
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateTicketByHash($hash)
    {
        return $this->processRequest(function () use ($hash) {

            $payload = $this->getJsonPayload([
                'attendee_first_name' => 'nullable|string|max:255',
                'attendee_last_name' => 'nullable|string|max:255',
                'attendee_company' => 'nullable|string|max:255',
                'attendee_company_id' => 'nullable|sometimes|integer',
                'disclaimer_accepted' => 'nullable|boolean',
                'share_contact_info' => 'nullable|boolean',
                'extra_questions' => 'sometimes|extra_question_dto_array'
            ]);

            $ticket = $this->service->updateTicketByHash($hash, $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($ticket, ISummitAttendeeTicketSerializerTypes::PublicEdition)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $order_hash
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateTicketsByOrderHash($order_hash)
    {
        return $this->processRequest(function () use ($order_hash) {

            $payload = $this->getJsonPayload([
                'tickets' => 'required|ticket_dto_array',
            ]);

            $order = $this->service->updateTicketsByOrderHash($order_hash, $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($order, ISummitOrderSerializerTypes::CheckOutType)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateMyTicketById($ticket_id)
    {
        return $this->processRequest(function () use ($ticket_id) {

            $current_user = $this->getResourceServerContext()->getCurrentUser();
            if (is_null($current_user))
                return $this->error403();

            $payload = $this->getJsonPayload([
                'attendee_first_name' => 'nullable|string|max:255',
                'attendee_last_name' => 'nullable|string|max:255',
                'attendee_company' => 'nullable|string|max:255',
                'attendee_company_id' => 'nullable|sometimes|integer',
                'disclaimer_accepted' => 'nullable|boolean',
                'share_contact_info' => 'nullable|boolean',
                'extra_questions' => 'sometimes|extra_question_dto_array'
            ]);

            $ticket = $this->service->updateTicketById($current_user, $ticket_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($ticket, ISummitAttendeeTicketSerializerTypes::PublicEdition)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $hash
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function regenerateTicketHash($hash)
    {
        return $this->processRequest(function () use ($hash) {

            $this->service->regenerateTicketHash($hash);

            return $this->ok();
        });
    }

    /**
     * @param $hash
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|mixed
     */
    public function getTicketPDFByHash($hash)
    {
        return $this->processRequest(function () use ($hash) {
            $content = $this->service->renderTicketByFormat($hash, IRenderersFormats::PDFFormat);
            return $this->pdf('ticket_' . $hash . '.pdf', $content);
        });
    }

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
        return [
            'owner_first_name' => 'required_without:owner_id|string|max:255',
            'owner_last_name' => 'required_without:owner_id|string|max:255',
            'owner_email' => 'required_without:owner_id|string|max:255|email',
            'owner_id' => 'required_without:owner_first_name,owner_last_name,owner_email|int',
            'ticket_type_id' => 'required|integer',
            'promo_code' => 'sometimes|string',
            'ticket_qty' => 'required|integer|min:1',
            'extra_questions' => 'sometimes|extra_question_dto_array',
            'owner_company' => 'sometimes|string|max:255',
            'billing_address_1' => 'sometimes|string|max:255',
            'billing_address_2' => 'sometimes|string|max:255',
            'billing_address_zip_code' => 'sometimes|string|max:255',
            'billing_address_city' => 'sometimes|string|max:255',
            'billing_address_state' => 'sometimes|string|max:255',
            'billing_address_country' => 'sometimes|string|country_iso_alpha2_code',
        ];
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->createOfflineOrder($summit, $payload);
    }

    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        return $summit->getOrderById($child_id);
    }

    /**
     * @param $summit_id
     * @param $order_id
     * @return mixed
     */
    public function getMyOrderById($order_id)
    {

        return $this->processRequest(function () use ($order_id) {

            $order = $this->repository->getById(intval($order_id));
            $owner = $this->getResourceServerContext()->getCurrentUser();

            if (is_null($owner))
                return $this->error403();

            if (!$order instanceof SummitOrder)
                throw new EntityNotFoundException("Order not found.");

            if ($order->getOwnerEmail() != $owner->getEmail())
                throw new EntityNotFoundException("Order not found.");

            return $this->ok(SerializerRegistry::getInstance()
                ->getSerializer($order, SerializerRegistry::SerializerType_Private)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));

        });
    }

    /**
     * @param $order_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getMyTicketsByOrderId($order_id)
    {

        $owner = $this->getResourceServerContext()->getCurrentUser();
        if (is_null($owner))
            return $this->error403();

        $order = $this->repository->getById(intval($order_id));

        if (!$order instanceof SummitOrder)
            return $this->error404("Order not found.");

        if ($order->getOwnerEmail() != $owner->getEmail())
            return $this->error404("Order not found.");

        return $this->_getAll(
            function () {
                return [
                    'order_id' => ['=='],
                    'order_owner_id' => ['=='],
                    'is_active' => ['=='],
                    'assigned_to' =>  ['=='],
                    'owner_status' =>  ['=='],
                    'badge_features_id' =>  ['=='],
                    'final_amount' => ['==', '<>', '>=', '>'],
                    'ticket_type_id' =>  ['=='],
                    'promo_code' => ['=@', '=='],
                ];
            },
            function () {
                return [
                    'order_id' => 'sometimes|integer',
                    'order_owner_id' => 'sometimes|integer',
                    'is_active' => ['sometimes', new Boolean()],
                    'assigned_to' => sprintf('sometimes|in:%s', implode(',', ['Me', 'SomeoneElse', 'Nobody'])),
                    'owner_status' => sprintf('sometimes|in:%s', implode(',', SummitAttendee::AllowedStatus)),
                    'badge_features_id' => 'sometimes|integer',
                    'final_amount' => 'sometimes|numeric',
                    'ticket_type_id' => 'sometimes|integer',
                    'promo_code' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'number',
                    'status',
                    'owner_first_name',
                    'owner_last_name',
                    'owner_name',
                    'created',
                ];
            },
            function ($filter) use ($owner, $order_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('order_id', intval($order_id)));
                    $filter->addFilterCondition(FilterElement::makeEqual('order_owner_id', $owner->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('status', IOrderConstants::PaidStatus));
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
                return ISummitOrderSerializerTypes::AdminType;
            }
            , null, null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->ticket_repository->getAllByPage(
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @return string
     */
    public function getChildSerializer()
    {
        return ISummitOrderSerializerTypes::AdminType;
    }

    /**
     * @param array $payload
     * @return array
     */
    function getUpdateValidationRules(array $payload): array
    {
        return [
            'owner_first_name' => 'required_without:owner_id|string|max:255',
            'owner_last_name' => 'required_without:owner_id|string|max:255',
            'owner_email' => 'required_without:owner_id|string|max:255|email',
            'owner_id' => 'required_without:owner_first_name,owner_last_name,owner_email|int',
            'extra_questions' => 'sometimes|extra_question_dto_array',
            'owner_company' => 'required|string|max:255',
            'billing_address_1' => 'sometimes|string|max:255',
            'billing_address_2' => 'sometimes|string|max:255',
            'billing_address_zip_code' => 'sometimes|string|max:255',
            'billing_address_city' => 'sometimes|string|max:255',
            'billing_address_state' => 'sometimes|string|max:255',
            'billing_address_country' => 'sometimes|string|country_iso_alpha2_code',
        ];
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->updateOrder($summit, $child_id, $payload);
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @return void
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->deleteOrder($summit, intval($child_id));
    }

    /**
     * @param $summit_id
     * @param $order_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function activateTicket($summit_id, $order_id, $ticket_id)
    {
        return $this->processRequest(function () use ($summit_id, $order_id, $ticket_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $ticket = $this->service->activateTicket($summit, intval($order_id), intval($ticket_id));
            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($ticket, ISummitAttendeeTicketSerializerTypes::AdminType)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    public function deActivateTicket($summit_id, $order_id, $ticket_id)
    {
        return $this->processRequest(function () use ($summit_id, $order_id, $ticket_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $ticket = $this->service->deActivateTicket($summit, intval($order_id), intval($ticket_id));
            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($ticket, ISummitAttendeeTicketSerializerTypes::AdminType)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $order_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllRefundApprovedRequests($summit_id, $order_id){
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                ];
            },
            function () {
                return [
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'action_date',
                    'ticket_id',
                ];
            },
            function ($filter) use ($summit, $order_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('class_name', SummitAttendeeTicketRefundRequest::ClassName));
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('order_id', intval($order_id)));
                    $filter->addFilterCondition(FilterElement::makeEqual('status', ISummitRefundRequestConstants::ApprovedStatus));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Admin;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->refund_request_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
        );
    }

    /**
     * @param $summit_id
     * @param $order_id
     * @param $ticket_id
     * @return mixed
     */
    public function delegateTicket($summit_id, $order_id, $ticket_id)
    {
        return $this->processRequest(function () use ($summit_id, $order_id, $ticket_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_user = $this->getResourceServerContext()->getCurrentUser();
            if (is_null($current_user))
                return $this->error403();

            $payload = $this->getJsonPayload([
                'attendee_first_name' => 'required|string|max:255',
                'attendee_last_name' => 'required|string|max:255',
                'attendee_email' => 'sometimes|string|max:255|email',
                'attendee_company' => 'nullable|string|max:255',
                'attendee_company_id' => 'nullable|sometimes|integer',
                'extra_questions' => 'sometimes|extra_question_dto_array',
                'disclaimer_accepted' => 'nullable|boolean',
            ]);

            $ticket = $this->service->delegateTicket
            (
                $summit,
                intval($order_id),
                intval($ticket_id),
                $current_user,
                $payload
            );

            return $this->updated(SerializerRegistry::getInstance()
                ->getSerializer($ticket, ISummitAttendeeTicketSerializerTypes::AdminType)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }
}