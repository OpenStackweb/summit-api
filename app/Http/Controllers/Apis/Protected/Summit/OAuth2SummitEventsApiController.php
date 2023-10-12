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
use App\Http\Utils\MultipartFormDataCleaner;
use App\Models\Foundation\Summit\Repositories\ISummitEventTypeRepository;
use App\ModelSerializers\SerializerUtils;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use libs\utils\HTMLCleaner;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\IEventFeedbackRepository;
use models\summit\ISpeakerRepository;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\Presentation;
use ModelSerializers\IPresentationSerializerTypes;
use ModelSerializers\SerializerRegistry;
use services\model\ISummitService;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;
use utils\Order;
use utils\OrderElement;
use utils\OrderParser;
use utils\PagingInfo;
use utils\PagingResponse;

/**
 * Class OAuth2SummitEventsApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitEventsApiController extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;

    use RequestProcessor;

    use ValidateEventUri;

    use ParametrizedGetAll;

    /**
     * @var ISummitService
     */
    private $service;

    /**
     * @var ISpeakerRepository
     */
    private $speaker_repository;

    /**
     * @var ISummitEventRepository
     */
    private $event_repository;

    /**
     * @var IEventFeedbackRepository
     */
    private $event_feedback_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;


    public function __construct
    (
        ISummitRepository        $summit_repository,
        ISummitEventRepository   $event_repository,
        ISpeakerRepository       $speaker_repository,
        IEventFeedbackRepository $event_feedback_repository,
        IMemberRepository        $member_repository,
        ISummitService           $service,
        IResourceServerContext   $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $summit_repository;
        $this->speaker_repository = $speaker_repository;
        $this->event_repository = $event_repository;
        $this->event_feedback_repository = $event_feedback_repository;
        $this->member_repository = $member_repository;
        $this->service = $service;
    }

    /**
     * @return string
     */
    private function getSerializerType(): string
    {

        $current_user = $this->resource_server_context->getCurrentUser(true);
        $application_type = $this->resource_server_context->getApplicationType();
        $path = Request::path();
        $method = Request::method();
        $clientId = $this->resource_server_context->getCurrentClientId();
        $scope = $this->resource_server_context->getCurrentScope();

        Log::debug(sprintf("OAuth2SummitEventsApiController::getSerializerType client id %s app_type %s scope %s has current user %b %s %s ", $clientId, $application_type, implode(" ", $scope), !is_null($current_user), $method, $path));
        if ($application_type == "SERVICE" || (!is_null($current_user) && ($current_user->isAdmin() || ($current_user->isSummitAdmin())))) {
            Log::debug(sprintf("OAuth2SummitEventsApiController::getSerializerType app_type %s has current user %b PRIVATE", $application_type, !is_null($current_user)));
            return SerializerRegistry::SerializerType_Private;
        }
        Log::debug(sprintf("OAuth2SummitEventsApiController::getSerializerType app_type %s has current user %b PUBLIC", $application_type, !is_null($current_user)));
        return SerializerRegistry::SerializerType_Public;
    }

    /**
     *  Events endpoints
     */

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getEvents($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $strategy = new RetrieveAllSummitEventsBySummitStrategy($this->repository, $this->event_repository, $this->resource_server_context);
            $response = $strategy->getEvents(['summit_id' => $summit_id]);
            return $this->ok
            (
                $response->toArray
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    [
                        'current_user' => $this->resource_server_context->getCurrentUser(true)
                    ],
                    $this->getSerializerType()
                )
            );
        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getEventsCSV($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->getRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $strategy = new RetrieveAllSummitEventsBySummitCSVStrategy
            (
                $this->repository,
                $this->event_repository,
                $this->resource_server_context
            );
            $response = $strategy->getEvents(['summit_id' => $summit_id]);

            $filename = "activities-" . date('Ymd');
            $list = $response->toArray
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                ['none'],
                [
                    'current_user' => $this->resource_server_context->getCurrentUser(true)
                ],
                SerializerRegistry::SerializerType_CSV
            );

            return $this->export
            (
                'csv',
                $filename,
                $list['data'],
                [
                    'created' => new EpochCellFormatter(),
                    'last_edited' => new EpochCellFormatter(),
                    'start_date' => new EpochCellFormatter(EpochCellFormatter::DefaultFormat, $summit->getTimeZone()),
                    'end_date' => new EpochCellFormatter(EpochCellFormatter::DefaultFormat, $summit->getTimeZone()),
                    'allow_feedback' => new BooleanCellFormatter(),
                    'is_published' => new BooleanCellFormatter(),
                    'rsvp_external' => new BooleanCellFormatter(),
                ]
            );
        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getScheduledEvents($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            $params = [
                'summit_id' => $summit_id,
                'current_user' => $this->resource_server_context->getCurrentUser(true)
            ];

            $strategy = new RetrievePublishedSummitEventsBySummitStrategy($this->repository, $this->event_repository, $this->resource_server_context);
            $response = $strategy->getEvents($params);
            return $this->ok($response->toArray
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                $params,
                $this->getSerializerType()
            ));
        });
    }

    use ParametrizedGetAll;

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getScheduledEventsTags($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->getRepository(), $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'tag' => ['=@', '=='],
                ];
            },
            function () {
                return [
                    'tag' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'tag'
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
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->event_repository->getAllPublishedTagsByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllEvents()
    {
        return $this->processRequest(function(){
            $strategy = new RetrieveAllSummitEventsStrategy($this->event_repository);
            $response = $strategy->getEvents();
            return $this->ok
            (
                $response->toArray
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    [
                        'current_user' => $this->resource_server_context->getCurrentUser(true)
                    ],
                    $this->getSerializerType()
                )
            );
        });
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllPresentations($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            $strategy = new RetrieveAllSummitPresentationsStrategy($this->repository, $this->event_repository, $this->resource_server_context);
            $response = $strategy->getEvents(['summit_id' => intval($summit_id)]);
            $params = [
                'current_user' => $this->resource_server_context->getCurrentUser(true),
            ];
            return $this->ok
            (
                $response->toArray
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    $params,
                    $this->getSerializerType()
                )
            );
        });
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllVoteablePresentations($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) throw new EntityNotFoundException;

            $strategy = new RetrieveAllSummitVoteablePresentationsStrategy
            (
                $this->repository,
                $this->event_repository,
                $this->resource_server_context
            );

            $response = $strategy->getEvents(['summit_id' => intval($summit_id)]);

            $params = [
                'current_user' => $this->resource_server_context->getCurrentUser(true),
                'use_cache' => true,
            ];

            return $this->ok
            (
                $response->toArray
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    $params,
                    $this->getSerializerType()
                )
            );
        });
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllVoteablePresentationsV2($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) throw new EntityNotFoundException;

            $strategy = new RetrieveAllSummitVoteablePresentationsStrategy
            (
                $this->repository,
                $this->event_repository,
                $this->resource_server_context
            );
            $response = $strategy->getEvents(['summit_id' => intval($summit_id)]);

            $params = [
                'current_user' => $this->resource_server_context->getCurrentUser(true),
                'use_cache' => true,
            ];

            $filter = $strategy->getFilter();

            if (!is_null($filter)) {
                $votingDateFilter = $filter->getFilter('presentation_attendee_vote_date');
                if ($votingDateFilter != null) {
                    $params['begin_attendee_voting_period_date'] = $votingDateFilter[0]->getValue();
                    if (count($votingDateFilter) > 1) {
                        $params['end_attendee_voting_period_date'] = $votingDateFilter[1]->getValue();
                    }
                }
            }

            return $this->ok
            (
                $response->toArray
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    $params,
                    SerializerRegistry::SerializerType_Admin_Voteable
                )
            );
        });
    }

    public function getAllVoteablePresentationsV2CSV($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) throw new EntityNotFoundException;

            $strategy = new RetrieveAllSummitVoteablePresentationsStrategyCSV
            (
                $this->repository,
                $this->event_repository,
                $this->resource_server_context
            );

            $response = $strategy->getEvents(['summit_id' => intval($summit_id)]);

            $params = [
                'current_user' => $this->resource_server_context->getCurrentUser(true),
                'use_cache' => true,
            ];

            $filter = $strategy->getFilter();

            if (!is_null($filter)) {
                $votingDateFilter = $filter->getFilter('presentation_attendee_vote_date');
                if ($votingDateFilter != null) {
                    $params['begin_attendee_voting_period_date'] = $votingDateFilter[0]->getValue();
                    if (count($votingDateFilter) > 1) {
                        $params['end_attendee_voting_period_date'] = $votingDateFilter[1]->getValue();
                    }
                }
            }


            $filename = "voteable-presentations-" . date('Ymd');
            $list = $response->toArray
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                ['none'],
                $params,
                SerializerRegistry::SerializerType_Admin_Voteable_CSV
            );

            return $this->export
            (
                'csv',
                $filename,
                $list['data'],
                [
                    'created' => new EpochCellFormatter(),
                    'last_edited' => new EpochCellFormatter(),
                ]
            );
        });
    }

    /**
     * @param $summit_id
     * @param $presentation_id
     * @return array|\Illuminate\Http\JsonResponse|mixed
     */
    public function getVoteablePresentation($summit_id, $presentation_id)
    {
        return $this->processRequest(function() use($summit_id, $presentation_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) throw new EntityNotFoundException;

            $event = $summit->getScheduleEvent(intval($presentation_id));

            if (is_null($event) || !$event instanceof Presentation) throw new EntityNotFoundException;

            if (!$event->getType()->isAllowAttendeeVote())
                throw new EntityNotFoundException('Type does not allows Attendee Vote.');

            $current_user = $this->resource_server_context->getCurrentUser(true);

            if (!$event->hasAccess($current_user)) {
                Log::debug(sprintf("OAuth2SummitEventsApiController::getVoteablePresentation summit id %s presentation id %s user has no access.", $summit_id, $presentation_id));
                throw new EntityNotFoundException("User has not access to this presentation.");
            }

            return SerializerRegistry::getInstance()->getSerializer($event, SerializerRegistry::SerializerType_Private)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                [
                    'current_user' => $current_user
                ]
            );
        });
    }

    /**
     * @return mixed
     */
    public function getAllScheduledEvents()
    {
        return $this->processRequest(function(){
            $strategy = new RetrieveAllPublishedSummitEventsStrategy($this->event_repository);
            $response = $strategy->getEvents();
            return $this->ok
            (
                $response->toArray
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    [
                        'current_user' => $this->resource_server_context->getCurrentUser(true)
                    ],
                    $this->getSerializerType()
                )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @param bool $published
     * @return array
     * @throws EntityNotFoundException
     */
    private function _getSummitEvent($summit_id, $event_id, $published = true)
    {
        $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) throw new EntityNotFoundException;

        $event = $published ? $summit->getScheduleEvent(intval($event_id)) : $summit->getEvent(intval($event_id));

        if (is_null($event)) throw new EntityNotFoundException;

        return SerializerRegistry::getInstance()->getSerializer($event, $this->getSerializerType())->serialize
        (
            SerializerUtils::getExpand(),
            SerializerUtils::getFields(),
            SerializerUtils::getRelations(),
            [
                'current_user' => $this->resource_server_context->getCurrentUser(true)
            ]
        );
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function getEvent($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            return $this->ok($this->_getSummitEvent($summit_id, $event_id, false));
        });
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function getScheduledEvent($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            return $this->ok($this->_getSummitEvent($summit_id, $event_id));
        });
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function shareScheduledEventByEmail($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'from' => 'required|email',
                'to' => 'required|email',
                'event_uri' => 'sometimes|url',
            ]);

            $this->service->shareEventByEmail($summit, $event_id, $this->validateEventUri($payload));

            return $this->ok();
        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function addEvent($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $payload = $data->all();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, SummitEventValidationRulesFactory::build($payload));

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $fields = [
                'title',
                'description',
                'social_summary',
            ];

            $event = $this->service->addEvent($summit, HTMLCleaner::cleanData($payload, $fields));

            return $this->created(SerializerRegistry::getInstance()->getSerializer
            (
                $event,
                $this->getSerializerType()
            )->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                [
                    'current_user' => $this->resource_server_context->getCurrentUser(true)
                ]
            ));
        });
    }


    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function updateEvent($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){

            Log::debug(sprintf("OAuth2SummitEventsApiController::updateEvent summit id %s event id %s", $summit_id, $event_id));

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member))
                return $this->error403();

            $event = $summit->getEvent($event_id);
            if (is_null($event))
                return $this->error404();

            $isAdmin = $current_member->isSummitAllowed($summit);
            $isTrackChair = $summit->isTrackChairAdmin($current_member) || $summit->isTrackChair($current_member, $event->getCategory());

            $payload = $this->getJsonData();

            // Creates a Validator instance and validates the data.
            $rules = $isAdmin ? SummitEventValidationRulesFactory::build($payload, true) : null;
            if(is_null($rules)){
                $rules = $isTrackChair ? SummitEventValidationRulesFactory::buildForTrackChair($payload, true) : null;
            }

            if(is_null($rules))
                return $this->error403();


            $payload = $this->getJsonPayload($rules, true);

            $fields = [
                'title',
                'description',
                'social_summary',
            ];

            if($isAdmin) {
                Log::debug(sprintf("OAuth2SummitEventsApiController::updateEvent summit id %s event id %s updating event", $summit_id, $event_id));
                $event = $this->service->updateEvent($summit, $event_id, HTMLCleaner::cleanData($payload, $fields));
            }
            else{
                Log::debug(sprintf("OAuth2SummitEventsApiController::updateEvent summit id %s event id %s updating duration", $summit_id, $event_id));
                $event = $this->service->updateDuration($payload, $summit, $event);
            }

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($event, $this->getSerializerType())
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    [
                        'current_user' => $current_member
                    ]
                )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function publishEvent($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $rules = [
                'location_id' => 'sometimes|required|integer',
                'start_date' => 'sometimes|required|date_format:U',
                'duration' => 'sometimes|integer|min:0',
                'end_date' => 'sometimes|required_with:start_date|date_format:U|after:start_date',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $event = $this->service->publishEvent($summit, $event_id, $data->all());

            return $this->updated(
                SerializerRegistry::getInstance()->getSerializer($event, $this->getSerializerType())->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    [
                        'current_user' => $this->resource_server_context->getCurrentUser(true)
                    ]
                )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function unPublishEvent($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();

            $this->service->unPublishEvent($summit, $event_id);

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function deleteEvent($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->deleteEvent($summit, $event_id);

            return $this->deleted();
        });
    }

    /** Feedback endpoints  */

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function getEventFeedback($summit_id, $event_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $event = $summit->getScheduleEvent(intval($event_id));

        if (is_null($event)) {
            return $this->error404();
        }

        return $this->_getAll(
            function(){
                return [
                    'owner_full_name' => ['=@', '==', '@@'],
                    'note' => ['=@', '==', '@@'],
                    'owner_id' => ['=='],
                ];
            },
            function(){
                return [
                    'owner_full_name' =>  'sometimes|required|string',
                    'note' =>  'sometimes|required|string',
                    'owner_id' =>  'sometimes|required|integer',
                ];
            },
            function(){
                return [
                    'created',
                    'owner_id',
                    'owner_full_name',
                    'rate',
                    'id',
                ];
            },
            function($filter){
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_Public;
            },
            function(){
                return new Order([
                    OrderElement::buildDescFor("created"),
                ]);
            },
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($event) {
                return $this->event_feedback_repository->getByEvent($event,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function getEventFeedbackCSV($summit_id, $event_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $event = $summit->getScheduleEvent(intval($event_id));

        if (is_null($event)) {
            return $this->error404();
        }

        return $this->_getAllCSV(
            function(){
                return [
                    'owner_full_name' => ['=@', '==', '@@'],
                    'note' => ['=@', '==', '@@'],
                    'owner_id' => ['=='],
                ];
            },
            function(){
                return [
                    'owner_full_name' =>  'sometimes|required|string',
                    'note' =>  'sometimes|required|string',
                    'owner_id' =>  'sometimes|required|integer',
                ];
            },
            function(){
                return [
                    'created',
                    'owner_id',
                    'owner_full_name',
                    'rate',
                    'id',
                ];
            },
            function($filter){
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_CSV;
            },
            function () {
                return [
                    'created_date' => new EpochCellFormatter(),
                    'last_edited' => new EpochCellFormatter(),
                ];
            },
            function () {
                return [];
            },
            sprintf('event-%s-feedback', $event_id),
            [],
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($event) {
                return $this->event_feedback_repository->getByEvent($event,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
        );
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @param $feedback_id
     * @return mixed
     */
    public function deleteEventFeedback($summit_id, $event_id, $feedback_id){
        return $this->processRequest(function() use($summit_id, $event_id, $feedback_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $event = $summit->getScheduleEvent(intval($event_id));

            if (is_null($event)) {
                return $this->error404();
            }

            $this->service->deleteEventFeedback($summit, $event_id, $feedback_id);

            return $this->deleted();
        });
    }
    /**
     * @param $summit_id
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addMyEventFeedbackReturnId($summit_id, $event_id)
    {
        return $this->_addMyEventFeedback($summit_id, $event_id, true);
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addMyEventFeedback($summit_id, $member_id, $event_id)
    {
        return $this->_addMyEventFeedback($summit_id, $event_id, false);
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @param bool $returnId
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    private function _addMyEventFeedback($summit_id, $event_id, $returnId = false)
    {
        return $this->processRequest(function() use($summit_id, $event_id, $returnId){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload([
                'rate' => 'required|integer|digits_between:0,5',
                'note' => 'max:500',
            ]);

            $feedback = $this->service->addMyEventFeedback
            (
                $current_member,
                $summit,
                intval($event_id),
                $payload
            );

            if ($returnId) {
                return $this->updated($feedback->getId());
            }

            return $this->created(SerializerRegistry::getInstance()->getSerializer($feedback)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateMyEventFeedbackReturnId($summit_id, $event_id)
    {
        return $this->_updateMyEventFeedback($summit_id, $event_id, true);
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateMyEventFeedback($summit_id, $member_id, $event_id)
    {
        return $this->_updateMyEventFeedback($summit_id, $event_id, false);
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @param bool $returnId
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    private function _updateMyEventFeedback($summit_id, $event_id, $returnId = false)
    {
        return $this->processRequest(function() use($summit_id, $event_id, $returnId){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload([
                'rate' => 'required|integer|digits_between:0,5',
                'note' => 'max:500',
            ]);

            $feedback = $this->service->updateMyEventFeedback
            (
                $current_member,
                $summit,
                intval($event_id),
                $payload
            );

            if ($returnId) {
                return $this->updated($feedback->getId());
            }

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($feedback)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    public function getMyEventFeedback($summit_id, $member_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $feedback = $this->service->getMyEventFeedback
            (
                $current_member,
                $summit,
                intval($event_id)
            );

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($feedback)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    public function deleteMyEventFeedback($summit_id, $member_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->service->deleteMyEventFeedback
            (
                $current_member,
                $summit,
                intval($event_id)
            );

            return $this->deleted();
        });
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addEventAttachment(LaravelRequest $request, $summit_id, $event_id)
    {
        return $this->processRequest(function() use($request, $summit_id, $event_id){

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $res = $this->service->addEventAttachment($summit, $event_id, $file);

            return !is_null($res) ? $this->created($res->getId()) : $this->error400();
        });
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getUnpublishedEvents($summit_id)
    {

        return $this->processRequest(function() use($summit_id){
            $strategy = new RetrieveAllUnPublishedSummitEventsStrategy($this->repository, $this->event_repository, $this->resource_server_context);

            $serializer_type = SerializerRegistry::SerializerType_Public;
            $current_member = $this->resource_server_context->getCurrentUser();
            if (!is_null($current_member) && $current_member->isAdmin()) {
                $serializer_type = SerializerRegistry::SerializerType_Private;
            }

            $response = $strategy->getEvents(['summit_id' => $summit_id]);
            return $this->ok($response->toArray
            (

                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                [],
                $serializer_type
            ));
        });
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getScheduleEmptySpots($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $filter = null;
            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'location_id' => ['=='],
                    'start_date' => ['>='],
                    'end_date' => ['<='],
                    'gap' => ['>', '<', '<=', '>=', '=='],
                ]);
            }

            if (empty($filter))
                throw new ValidationException("filter param is mandatory!");

            $gaps = [];
            foreach ($this->service->getSummitScheduleEmptySpots($summit, $filter) as $gap) {
                $gaps[] = SerializerRegistry::getInstance()->getSerializer($gap)->serialize();
            }

            $response = new PagingResponse
            (
                count($gaps),
                count($gaps),
                1,
                1,
                $gaps
            );

            return $this->ok($response->toArray());
        });
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function unPublishEvents($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();

            $data = Request::json();

            $rules = [
                'events' => 'required|int_array',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $this->service->unPublishEvents($summit, $data->all());

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateAndPublishEvents($summit_id)
    {
        return $this->processRequest(function() use($summit_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();

            $data = Request::json();

            $rules = [
                'events' => 'required|event_dto_publish_array',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $this->service->updateAndPublishEvents($summit, $data->all());

            return $this->updated();
        });
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateEvents($summit_id)
    {
        return $this->processRequest(function() use($summit_id) {
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();

            $data = Request::json();

            $rules = [
                'events' => 'required|event_dto_array',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $this->service->updateEvents($summit, $data->all());

            return $this->updated();
        });
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function cloneEvent($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $event = $this->service->cloneEvent($summit, $event_id);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($event)->serialize());

        });
    }

    public function addEventImage(LaravelRequest $request, $summit_id, $event_id)
    {
        return $this->processRequest(function() use($request, $summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $image = $this->service->addEventImage($summit, $event_id, $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($image)->serialize());

        });
    }

    public function deleteEventImage($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->service->removeEventImage($summit, $event_id);
            return $this->deleted();
        });
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     */
    public function importEventData(LaravelRequest $request, $summit_id)
    {
        return $this->processRequest(function() use($request, $summit_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $request->all();

            $rules = [
                'file' => 'required',
                'send_speaker_email' => 'required|boolean',
            ];

            $payload = MultipartFormDataCleaner::cleanBool('send_speaker_email', $payload);

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException;
                $ex->setMessages($validation->messages()->toArray());
                throw $ex;
            }

            $file = $request->file('file');

            $this->service->importEventData($summit, $file, $payload);

            return $this->ok();

        });
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function updateEventLiveInfo($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){

            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $data->all();
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, [
                'streaming_url' => 'required||url',
                'streaming_type' => 'required|string|in:VOD,LIVE',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $event = $this->service->updateEvent($summit, $event_id,
                [
                    'streaming_url' => $payload['streaming_url'],
                    'streaming_type' => $payload['streaming_type'],
                ]);

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($event, $this->getSerializerType())->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                [
                    'current_user' => $this->resource_server_context->getCurrentUser(true)
                ]
            ));

        });
    }

    /**
     * @param $summit_id
     * @param $event_id
     * @return mixed
     */
    public function getScheduledEventJWT($summit_id, $event_id)
    {
        return $this->processRequest(function() use($summit_id, $event_id){
            Log::debug(sprintf("OAuth2SummitEventsApiController::getScheduledEventJWT summit id %s event id %s", $summit_id, $event_id));
            $summit = SummitFinderStrategyFactory::build($this->repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) throw new EntityNotFoundException;

            $event = $summit->getScheduleEvent(intval($event_id));

            if (is_null($event)) throw new EntityNotFoundException;

            if(!$summit->hasMuxPrivateKey())
                throw new ValidationException(sprintf("Summit %s has not set a private key.", $summit_id));

            if(!$event->isMuxStream())
                throw new ValidationException(sprintf("Event %s has not set a valid MUX url", $event_id));

            if(!$event->IsSecureStream()){
                throw new ValidationException(sprintf("Event %s is not marked as secure.", $event_id));
            }

            return SerializerRegistry::getInstance()->getSerializer($event, IPresentationSerializerTypes::SecureStream)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                [
                    'current_user' => $this->resource_server_context->getCurrentUser(true)
                ]
            );
        });
    }
}