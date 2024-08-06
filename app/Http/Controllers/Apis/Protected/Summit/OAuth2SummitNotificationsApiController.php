<?php namespace App\Http\Controllers;
/**
 * Copyright 2016 OpenStack Foundation
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
use App\Services\Model\ISummitPushNotificationService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitNotificationRepository;
use Illuminate\Support\Facades\Log;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Request;
use Exception;
/**
 * Class OAuth2SummitNotificationsApiController
 * @package App\Http\Controllers
 */
class OAuth2SummitNotificationsApiController extends OAuth2ProtectedController
{

    use ParametrizedGetAll;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitPushNotificationService
     */
    private $push_notification_service;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * OAuth2SummitNotificationsApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitNotificationRepository $notification_repository
     * @param IMemberRepository $member_repository
     * @param ISummitPushNotificationService $push_notification_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitNotificationRepository $notification_repository,
        IMemberRepository $member_repository,
        ISummitPushNotificationService $push_notification_service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);

        $this->repository                = $notification_repository;
        $this->push_notification_service = $push_notification_service;
        $this->member_repository         = $member_repository;
        $this->summit_repository         = $summit_repository;
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAll($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function(){
                return [
                    'message'   => ['=@', '=='],
                    'channel'   => ['=='],
                    'sent_date' => ['>', '<', '<=', '>=', '=='],
                    'created'   => ['>', '<', '<=', '>=', '=='],
                    'is_sent'   => ['=='],
                    'approved'  => ['=='],
                    'event_id'  => ['=='],
                ];
            },
            function(){
                return [
                    'message'   => 'sometimes|string',
                    'channel'   => 'sometimes|in:EVERYONE,SPEAKERS,ATTENDEES,MEMBERS,SUMMIT,EVENT,GROUP',
                    'sent_date' => 'sometimes|date_format:U|epoch_seconds',
                    'created'   => 'sometimes|date_format:U|epoch_seconds',
                    'is_sent'   => 'sometimes|boolean',
                    'approved'  => 'sometimes|boolean',
                    'event_id'  => 'sometimes|integer',
                ];
            },
            function()
            {
                return [

                    'sent_date',
                    'created',
                    'id',
                ];
            },
            function($filter) use($summit){
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page,  $per_page,  $filter,  $order, $applyExtraFilters) use ($summit){
                return  $this->repository->getAllByPageBySummit
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
            ['summit_id' => $summit_id]
        );
    }


    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllApprovedByUser($summit_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $current_member = $this->resource_server_context->getCurrentUser();


        return $this->_getAll(
            function(){
                return [
                    'message'   => ['=@', '=='],
                    'sent_date' => ['>', '<', '<=', '>=', '=='],
                    'created'   => ['>', '<', '<=', '>=', '=='],
                ];
            },
            function(){
                return [
                    'message'   => 'sometimes|string',
                    'sent_date' => 'sometimes|date_format:U|epoch_seconds',
                    'created'   => 'sometimes|date_format:U|epoch_seconds',
                ];
            },
            function()
            {
                return [
                    'sent_date',
                    'created',
                    'id',
                ];
            },
            function($filter) use($summit){
                if($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual("is_sent", true));
                }
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page,  $per_page,  $filter,  $order, $applyExtraFilters) use ($current_member, $summit){
                return  $this->repository->getAllByPageByUserBySummit
                (
                    $current_member,
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
            ['summit_id' => $summit_id]
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllCSV($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function(){
                return [
                    'message'   => ['=@', '=='],
                    'channel'   => ['=='],
                    'sent_date' => ['>', '<', '<=', '>=', '=='],
                    'created'   => ['>', '<', '<=', '>=', '=='],
                    'is_sent'   => ['=='],
                    'approved'  => ['=='],
                    'event_id'  => ['=='],
                ];
            },
            function(){
                return [
                    'message'   => 'sometimes|string',
                    'channel'   => 'sometimes|in:EVERYONE,SPEAKERS,ATTENDEES,MEMBERS,SUMMIT,EVENT,GROUP',
                    'sent_date' => 'sometimes|date_format:U|epoch_seconds',
                    'created'   => 'sometimes|date_format:U|epoch_seconds',
                    'is_sent'   => 'sometimes|boolean',
                    'approved'  => 'sometimes|boolean',
                    'event_id'  => 'sometimes|integer',
                ];
            },
            function()
            {
                return [

                    'sent_date',
                    'created',
                    'id',
                ];
            },
            function($filter) use($summit){
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_Public;
            },
            function(){
                return [
                    'created'     => new EpochCellFormatter,
                    'last_edited' => new EpochCellFormatter,
                    'sent_date'   => new EpochCellFormatter,
                    'is_sent'     => new BooleanCellFormatter,
                    'approved'    => new BooleanCellFormatter,
                ];
            },
            function(){
              return [];
            },
            "push-notification-" . date('Ymd'),
            ['summit_id' => $summit_id],
            function ($page,  $per_page,  $filter,  $order, $applyExtraFilters) use ($summit){
                return  $this->repository->getAllByPageBySummit
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
     * @param $notification_id
     * @return mixed
     */
    public function getById($summit_id, $notification_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $notification = $summit->getNotificationById($notification_id);
            if(is_null($notification))
                return $this->error404();
            return $this->ok(SerializerRegistry::getInstance()->getSerializer($notification)->serialize(Request::input('expand', '')));
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }


    /**
     * @param $summit_id
     * @param $notification_id
     * @return mixed
     */
    public function deleteNotification($summit_id, $notification_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->push_notification_service->deleteNotification($summit, $notification_id);
            return $this->deleted();
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $notification_id
     * @return mixed
     */
    public function approveNotification($summit_id, $notification_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $notification = $this->push_notification_service->approveNotification($summit, $this->resource_server_context->getCurrentUser(), $notification_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($notification)->serialize(Request::input('expand', '')));
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $notification_id
     * @return mixed
     */
    public function unApproveNotification($summit_id, $notification_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $notification = $this->push_notification_service->unApproveNotification($summit, $this->resource_server_context->getCurrentUser(), $notification_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($notification)->serialize(Request::input('expand', '')));
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function addPushNotification($summit_id){
        try {

            if(!Request::isJson()) return $this->error400();
            $data = Request::json();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = SummitPushNotificationValidationRulesFactory::build($data->all());
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $notification = $this->push_notification_service->addPushNotification($summit, $this->resource_server_context->getCurrentUser(), $data->all());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($notification)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}