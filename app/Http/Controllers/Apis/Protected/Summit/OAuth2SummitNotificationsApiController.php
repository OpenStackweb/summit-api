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
use App\Models\Foundation\Main\IGroup;
use App\Security\SummitScopes;
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
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

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
    #[OA\Get(
        path: '/api/v1/summits/{id}/notifications',
        operationId: 'getNotifications',
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: 'Get all push notifications for a summit',
        tags: ['Summit Notifications'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_notifications_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadNotifications,
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
                name: 'id',
                description: 'Summit ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Page number',
                in: 'query',
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Items per page',
                in: 'query',
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
            new OA\Parameter(
                name: 'expand',
                description: 'Expand relations (event,group,recipients)',
                in: 'query',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List of notifications',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedNotificationsResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
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
     * @return mixed
     */
    #[OA\Get(
        path: '/api/v1/summits/{id}/notifications/sent',
        operationId: 'getApprovedNotifications',
        summary: 'Get all approved push notifications sent to current user',
        tags: ['Summit Notifications'],
        security: [['summit_notifications_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadNotifications,
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
                name: 'id',
                description: 'Summit ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Page number',
                in: 'query',
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Items per page',
                in: 'query',
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
            new OA\Parameter(
                name: 'expand',
                description: 'Expand relations (event,group,recipients)',
                in: 'query',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List of approved notifications',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedNotificationsResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
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
    #[OA\Get(
        path: '/api/v1/summits/{id}/notifications/csv',
        operationId: 'getNotificationsCSV',
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: 'Export all push notifications as CSV',
        tags: ['Summit Notifications'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_notifications_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadNotifications,
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
                name: 'id',
                description: 'Summit ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'filter[]',
                in: 'query',
                required: false,
                description: 'Filter expressions',
                style: 'form',
                explode: true,
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string')
                )
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                required: false,
                description: 'Order by field(s)',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'CSV file with notifications'
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
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
    #[OA\Get(
        path: '/api/v1/summits/{id}/notifications/{notification_id}',
        operationId: 'getNotificationById',
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        summary: 'Get specific push notification',
        tags: ['Summit Notifications'],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_notifications_oauth2' => [
            SummitScopes::ReadAllSummitData,
            SummitScopes::ReadNotifications,
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
                name: 'id',
                description: 'Summit ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'notification_id',
                description: 'Notification ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'expand',
                description: 'Expand relations',
                in: 'query',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Notification details',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitPushNotificationResponse')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or notification not found'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Server Error'),
        ]
    )]
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
    #[OA\Delete(
        path: '/api/v1/summits/{id}/notifications/{notification_id}',
        operationId: 'deleteNotification',
        description: 'Delete a notification from a summit. required-groups: SuperAdmins, Administrators, SummitAdministrators',
        tags: ['Summit Notifications'],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        security: [['summit_notifications_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteNotifications,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'notification_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Notification deleted successfully'),
            new OA\Response(response: 400, description: 'Invalid input'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Summit or notification not found'),
            new OA\Response(response: 412, description: 'Validation failed'),
            new OA\Response(response: 500, description: 'Server error'),
        ],
    )]
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
    #[OA\Put(
        path: '/api/v1/summits/{id}/notifications/{notification_id}/approve',
        operationId: 'approveNotification',
        description: 'Approve a notification for sending. required-groups: SuperAdmins, Administrators, SummitAdministrators',
        tags: ['Summit Notifications'],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        security: [['summit_notifications_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteNotifications,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'notification_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notification approved successfully'),
            new OA\Response(response: 400, description: 'Invalid input'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Summit or notification not found'),
            new OA\Response(response: 412, description: 'Validation failed'),
            new OA\Response(response: 500, description: 'Server error'),
        ],
    )]
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
    #[OA\Delete(
        path: '/api/v1/summits/{id}/notifications/{notification_id}/approve',
        operationId: 'unApproveNotification',
        description: 'Revoke approval for a notification. required-groups: SuperAdmins, Administrators, SummitAdministrators',
        tags: ['Summit Notifications'],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        security: [['summit_notifications_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteNotifications,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'notification_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notification approval revoked successfully'),
            new OA\Response(response: 400, description: 'Invalid input'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Summit or notification not found'),
            new OA\Response(response: 412, description: 'Validation failed'),
            new OA\Response(response: 500, description: 'Server error'),
        ],
    )]
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
    #[OA\Post(
        path: '/api/v1/summits/{id}/notifications',
        operationId: 'addNotification',
        description: 'Create a new push notification for a summit. required-groups: SuperAdmins, Administrators, SummitAdministrators',
        tags: ['Summit Notifications'],
        x: ['required-groups' => [IGroup::SuperAdmins, IGroup::Administrators, IGroup::SummitAdministrators]],
        security: [['summit_notifications_oauth2' => [
            SummitScopes::WriteSummitData,
            SummitScopes::WriteNotifications,
        ]]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'access_token',
                in: 'query',
                required: false,
                description: 'OAuth2 access token',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Notification data',
            content: new OA\JsonContent(ref: '#/components/schemas/SummitPushNotificationRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Notification created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitPushNotificationResponse')
            ),
            new OA\Response(response: 400, description: 'Invalid input'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Summit not found'),
            new OA\Response(response: 412, description: 'Validation failed'),
            new OA\Response(response: 500, description: 'Server error'),
        ],
    )]
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