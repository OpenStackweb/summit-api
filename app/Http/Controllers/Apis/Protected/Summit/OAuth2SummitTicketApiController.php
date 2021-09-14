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
use App\Jobs\IngestSummitExternalRegistrationData;
use App\ModelSerializers\ISummitAttendeeTicketSerializerTypes;
use App\Services\Model\ISummitOrderService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\IOrderConstants;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitOrderExtraQuestionTypeConstants;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use Illuminate\Http\Request as LaravelRequest;
/**
 * Class OAuth2SummitTicketApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitTicketApiController extends OAuth2ProtectedController
{

    use GetSummitChildElementById;

    use ParametrizedGetAll;

    use GetAndValidateJsonPayload;

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
        ISummitRepository $summit_repository,
        ISummitAttendeeTicketRepository $repository,
        ISummitOrderService $service,
        IResourceServerContext $resource_server_context
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
    public function getAllBySummit($summit_id){
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function(){
                return [
                    'number'              => ['=@', '=='],
                    'order_number'        => ['=@', '=='],
                    'owner_name'          => ['=@', '=='],
                    'owner_first_name'    => ['=@', '=='],
                    'owner_last_name'     => ['=@', '=='],
                    'owner_email'         => ['=@', '=='],
                    'owner_company'       => ['=@', '=='],
                    'summit_id'           => ['=='],
                    'owner_id'            => ['=='],
                    'order_id'            => ['=='],
                    'status'              => ['==','<>'],
                ];
            },
            function(){
                return [
                    'status'                => sprintf('sometimes|in:%s',implode(',', IOrderConstants::ValidStatus)),
                    'number'                => 'sometimes|string',
                    'order_number'          => 'sometimes|string',
                    'owner_name'            => 'sometimes|string',
                    'owner_first_name'      => 'sometimes|string',
                    'owner_last_name'       => 'sometimes|string',
                    'owner_email'           => 'sometimes|string',
                    'owner_company'         => 'sometimes|string',
                    'summit_id'             => 'sometimes|integer',
                    'owner_id'              => 'sometimes|integer',
                    'order_id'              => 'sometimes|integer',
                ];
            },
            function()
            {
                return [
                    'id',
                    'number',
                    'status',
                ];
            },
            function($filter) use($summit){
                if($filter instanceof Filter){
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function(){
                return ISummitAttendeeTicketSerializerTypes::AdminType;
            }
        );
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllBySummitCSV($summit_id){
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function(){
                return [
                    'number'              => ['=@', '=='],
                    'order_number'        => ['=@', '=='],
                    'owner_name'          => ['=@', '=='],
                    'owner_first_name'    => ['=@', '=='],
                    'owner_last_name'     => ['=@', '=='],
                    'owner_email'         => ['=@', '=='],
                    'owner_company'       => ['=@', '=='],
                    'summit_id'           => ['=='],
                    'owner_id'            => ['=='],
                    'order_id'            => ['=='],
                    'status'              => ['=='],
                ];
            },
            function(){
                return [
                    'status'           => sprintf('sometimes|in:%s',implode(',', IOrderConstants::ValidStatus)),
                    'number'           => 'sometimes|string',
                    'order_number'     => 'sometimes|string',
                    'owner_name'       => 'sometimes|string',
                    'owner_first_name' => 'sometimes|string',
                    'owner_last_name'  => 'sometimes|string',
                    'owner_email'      => 'sometimes|string',
                    'owner_company'    => 'sometimes|string',
                    'summit_id'        => 'sometimes|integer',
                    'owner_id'         => 'sometimes|integer',
                    'order_id'         => 'sometimes|integer',
                ];
            },
            function()
            {
                return [
                    'id',
                    'number',
                    'status',
                ];
            },
            function($filter) use($summit){
                if($filter instanceof Filter){
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_CSV;
            },
            function(){
                return [
                    'created'     => new EpochCellFormatter(),
                    'last_edited' => new EpochCellFormatter(),
                    'bought_date' => new EpochCellFormatter(),
                ];
            },
            function() use($summit){
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
                    'bought_date',
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
                ];

                foreach ($summit->getBadgeFeaturesTypes() as $featuresType){
                    $allowed_columns[] = $featuresType->getName();
                }

                foreach ($summit->getOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage) as $question){
                    $allowed_columns[] = $question->getLabel();
                }

                $columns_param = Request::input("columns", "");
                $columns = [];
                if(!empty($columns_param))
                    $columns  = explode(',', $columns_param);
                $diff     = array_diff($columns, $allowed_columns);
                if(count($diff) > 0){
                    throw new ValidationException(sprintf("columns %s are not allowed!", implode(",", $diff)));
                }
                if(empty($columns))
                    $columns = $allowed_columns;
                return $columns;
            },
            sprintf('tickets-%s-', $summit_id),
            [
                'features_types'   => $summit->getBadgeFeaturesTypes(),
                'ticket_questions' => $summit->getOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage)
            ]
        );
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function ingestExternalTicketData($summit_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload([
                'email_to' => 'nullable|email',
            ]);

            $this->service->ingestExternalTicketData($summit, $payload);

            return $this->ok();

        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412(array($ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getImportTicketDataTemplate($summit_id){
        try {
            /**
             * id
             * number
             * attendee_email ( mandatory if id and number are missing)
             * attendee_first_name (optional)
             * attendee_last_name (optional)
             * attendee_company (optional)
             * ticket_type_name ( mandatory if id and number are missing)
             * ticket_type_id ( mandatory if id and number are missing)
             * badge_type_id (optional)
             * badge_type_name (optional)
             * badge_features (optional)
             */

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $row = [
                'id'                  => '',
                'number'              => '',
                'attendee_email'      => '',
                'attendee_first_name' => '',
                'attendee_last_name'  => '',
                'attendee_company'    => '',
                'ticket_type_name'    => '',
                'ticket_type_id'      => '',
                'badge_type_id'       => '',
                'badge_type_name'     => '',
            ];

            // badge features for summit
            foreach ($summit->getBadgeFeaturesTypes() as $featuresType){
                $row[$featuresType->getName()] = '' ;
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

        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412(array($ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function importTicketData(LaravelRequest $request, $summit_id){

        try {

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

        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412(array($ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return mixed
     */
    public function getAllMyTickets(){
        $owner = $this->getResourceServerContext()->getCurrentUser();
        return $this->_getAll(
            function(){
                return [
                    'number'              => ['=@', '=='],
                    'order_number'        => ['=@', '=='],
                    'summit_id'           => ['=='],
                    'order_id'            => ['=='],
                    'status'              => ['==','<>'],
                ];
            },
            function(){
                return [
                    'number'                => 'sometimes|string',
                    'order_number'          => 'sometimes|string',
                    'summit_id'             => 'sometimes|integer',
                    'order_id'              => 'sometimes|integer',
                    'status'                => sprintf('sometimes|in:%s',implode(',', IOrderConstants::ValidStatus)),
                ];
            },
            function()
            {
                return [
                    'id',
                    'number',
                    'status',
                ];
            },
            function($filter) use($owner){
                if($filter instanceof Filter){
                    $filter->addFilterCondition(FilterElement::makeEqual('member_id', $owner->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('is_active', true));
                }
                return $filter;
            },
            function(){
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
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'amount' => 'required|numeric|greater_than:0',
            ]);

            $ticket = $this->service->refundTicket($summit, $ticket_id, floatval($payload['amount']));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($ticket)->serialize( Request::input('expand', '')));
        }
        catch(\InvalidArgumentException $ex){
            Log::warning($ex);
            return $this->error400();
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAttendeeBadge($summit_id, $ticket_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $ticket = is_int($ticket_id) ? $this->repository->getById(intval($ticket_id)) : $this->repository->getByNumber($ticket_id);
            if(is_null($ticket) || !$ticket instanceof SummitAttendeeTicket) return $this->error404();;
            if($ticket->getOrder()->getSummitId() != $summit->getId()) return $this->error404();
            if(!$ticket->hasBadge()) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($ticket->getBadge())->serialize( Request::input('expand', '')));
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function createAttendeeBadge($summit_id, $ticket_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'badge_type_id' => 'sometimes|integer',
                'features'      => 'sometimes|int_array',
            ]);

            $badge = $this->service->createBadge($summit, $ticket_id, $payload);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($badge)->serialize( Request::input('expand', '')));
        }
        catch(\InvalidArgumentException $ex){
            Log::warning($ex);
            return $this->error400();
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteAttendeeBadge($summit_id, $ticket_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->service->deleteBadge($summit, $ticket_id);
            return $this->deleted();
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $ticket_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateAttendeeBadgeType($summit_id, $ticket_id, $type_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $badge = $this->service->updateBadgeType($summit, $ticket_id, $type_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($badge)->serialize( Request::input('expand', '')));
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $ticket_id
     * @param $feature_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addAttendeeBadgeFeature($summit_id, $ticket_id, $feature_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $badge = $this->service->addAttendeeBadgeFeature($summit, $ticket_id, $feature_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($badge)->serialize( Request::input('expand', '')));
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $ticket_id
     * @param $feature_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeAttendeeBadgeFeature($summit_id, $ticket_id, $feature_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $badge = $this->service->removeAttendeeBadgeFeature($summit, $ticket_id, $feature_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($badge)->serialize( Request::input('expand', '')));
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function printAttendeeBadge($summit_id, $ticket_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $badge = $this->service->printAttendeeBadge($summit, $ticket_id, $current_member);

            return $this->updated
            (
                SerializerRegistry::getInstance()->getSerializer($badge)->serialize( Request::input('expand', ''))
            );

        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function canPrintAttendeeBadge($summit_id, $ticket_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $badge = $this->service->canPrintAttendeeBadge($summit, $ticket_id, $current_member);

            return $this->ok
            (
                SerializerRegistry::getInstance()->getSerializer($badge)->serialize( Request::input('expand', ''))
            );

        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}