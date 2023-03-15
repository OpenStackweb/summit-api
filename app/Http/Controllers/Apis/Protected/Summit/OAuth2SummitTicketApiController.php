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

use App\Http\Utils\EpochCellFormatter;
use App\ModelSerializers\ISummitAttendeeTicketSerializerTypes;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\ISummitOrderService;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Request;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\IOrderConstants;
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

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitOrderService
     */
    private $service;

    /**
     * OAuth2SummitTicketApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitAttendeeTicketRepository $repository
     * @param ISummitOrderService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository               $summit_repository,
        ISummitAttendeeTicketRepository $repository,
        ISummitOrderService             $service,
        IResourceServerContext          $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
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
                    'id' => ['=='],
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
                    'ticket_type_id' => ['=='],
                    'has_owner' => ['=='],
                    'owner_status' => ['=='],
                    'has_badge' => ['=='],
                    'view_type_id' => ['=='],
                    'promo_code_id' => ['=='],
                    'promo_code' => ['==','@@', '=@'],
                    'promo_code_description' => ['@@', '=@'],
                    'promo_code_tag_id' => ['=='],
                    'promo_code_tag' => ['==','@@', '=@'],
                    'final_amount' => ['==', '<>','>=','>'],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
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
                    'id' => ['=='],
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
                    'ticket_type_id' => ['=='],
                    'has_owner' => ['=='],
                    'owner_status' => ['=='],
                    'has_badge' => ['=='],
                    'view_type_id' => ['=='],
                    'promo_code_id' => ['=='],
                    'promo_code' => ['==','@@', '=@'],
                    'promo_code_tag' => ['==','@@', '=@'],
                    'promo_code_tag_id' => ['=='],
                    'promo_code_description' => ['@@', '=@'],
                    'final_amount' => ['==', '<>','>=','>'],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
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
                    'ticket_type_id' => 'sometimes|integer',
                    'has_owner' => 'sometimes|boolean',
                    'owner_status' => 'sometimes|string|in:' . implode(',', SummitAttendee::AllowedStatus),
                    'has_badge' => 'sometimes|boolean',
                    'view_type_id' => 'sometimes|integer',
                    'promo_code_id' => 'sometimes|integer',
                    'promo_code' => 'sometimes|string',
                    'promo_code_tag' => 'sometimes|string',
                    'promo_code_description' => 'sometimes|string',
                    'promo_code_tag_id' => 'sometimes|integer',
                    'final_amount' => 'sometimes|numeric',
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
                    'purchase_date' => new EpochCellFormatter(),
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
                'ticket_questions' => $summit->getOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage)
            ]
        );
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
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
             * ticket_type_name ( mandatory if id and number are missing)
             * ticket_type_id ( mandatory if id and number are missing)
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
                'ticket_type_name' => '',
                'ticket_type_id' => '',
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

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
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

    /**
     * @return mixed
     */
    public function getAllMyTickets()
    {
        return $this->getAllMyTicketsBySummit('all');
    }

    public function getAllMyTicketsBySummit($summit_id)
    {
        $owner = $this->getResourceServerContext()->getCurrentUser();
        return $this->_getAll(
            function () {
                return [
                    'number' => ['=@', '=='],
                    'order_number' => ['=@', '=='],
                    'summit_id' => ['=='],
                    'order_id' => ['=='],
                    'status' => ['==', '<>'],
                    'order_owner_id' => ['==', '<>'],
                    'has_order_owner' => ['=='],
                    'final_amount' => ['==', '<>','>=','>'],
                ];
            },
            function () {
                return [
                    'number' => 'sometimes|string',
                    'order_number' => 'sometimes|string',
                    'summit_id' => 'sometimes|integer',
                    'order_id' => 'sometimes|integer',
                    'order_owner_id' => 'sometimes|integer',
                    'has_order_owner' => 'sometimes|boolean',
                    'status' => sprintf('sometimes|in:%s', implode(',', IOrderConstants::ValidStatus)),
                    'final_amount' => 'sometimes|numeric',
                ];
            },
            function () {
                return [
                    'id',
                    'number',
                    'status',
                ];
            },
            function ($filter) use ($owner, $summit_id) {
                if ($filter instanceof Filter) {
                    if (is_numeric($summit_id)) {
                        $filter->addFilterCondition(FilterElement::makeEqual('summit_id', intval($summit_id)));
                    }
                    $filter->addFilterCondition(FilterElement::makeEqual('member_id', $owner->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('is_active', true));
                }
                return $filter;
            },
            function () {
                return ISummitAttendeeTicketSerializerTypes::AdminType;
            }
        );
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
        return $this->service->getTicket($summit, $child_id);
    }

    /**
     * @param $summit_id
     * @param $ticket_id
     * @return mixed
     */
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

    /**
     * @param $summit_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAttendeeBadge($summit_id, $ticket_id)
    {
        return $this->processRequest(function () use ($summit_id, $ticket_id) {
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
    }

    /**
     * @param $summit_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteAttendeeBadge($summit_id, $ticket_id)
    {
        return $this->processRequest(function () use ($summit_id, $ticket_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->service->deleteBadge($summit, $ticket_id);
            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $ticket_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $ticket_id
     * @param $feature_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $ticket_id
     * @param $feature_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function printAttendeeBadgeDefault($summit_id, $ticket_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404('Summit not Found.');

        $viewType = $summit->getDefaultBadgeViewType();
        if (is_null($viewType))
            return $this->error404('Default view type not found.');

        return $this->printAttendeeBadge($summit_id, $ticket_id, $viewType->getName());
    }

    /**
     * @param $summit_id
     * @param $ticket_id
     * @param $view_type
     * @return \Illuminate\Http\JsonResponse|mixed
     */
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

    /**
     * @param $summit_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function canPrintAttendeeBadgeDefault($summit_id, $ticket_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404('Summit not Found.');

        $viewType = $summit->getDefaultBadgeViewType();
        if (is_null($viewType))
            return $this->error404('Default view type not found.');

        return $this->canPrintAttendeeBadge($summit_id, $ticket_id, $viewType->getName());
    }

    /**
     * @param $summit_id
     * @param $ticket_id
     * @param $view_type
     * @return \Illuminate\Http\JsonResponse|mixed
     */
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