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

use App\Jobs\Emails\InviteAttendeeTicketEditionMail;
use App\Jobs\Emails\SummitAttendeeTicketRegenerateHashEmail;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\IAttendeeService;
use App\Services\Model\ISummitOrderService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\main\Member;
use models\oauth2\IResourceServerContext;
use models\summit\IEventFeedbackRepository;
use models\summit\ISpeakerRepository;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\SummitAttendee;
use ModelSerializers\SerializerRegistry;
use services\model\ISummitService;
use utils\Filter;
use utils\FilterElement;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use utils\FilterParser;

/**
 * Class OAuth2SummitAttendeesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitAttendeesApiController extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;

    /**
     * @var ISummitService
     */
    private $summit_service;

    /**
     * @var IAttendeeService
     */
    private $attendee_service;

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
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitOrderService
     */
    private $summit_order_service;


    use ParametrizedGetAll;

    /**
     * OAuth2SummitAttendeesApiController constructor.
     * @param ISummitAttendeeRepository $attendee_repository
     * @param ISummitRepository $summit_repository
     * @param ISummitEventRepository $event_repository
     * @param ISpeakerRepository $speaker_repository
     * @param IEventFeedbackRepository $event_feedback_repository
     * @param IMemberRepository $member_repository
     * @param ISummitService $summit_service
     * @param IAttendeeService $attendee_service
     * @param ISummitOrderService $summit_order_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitAttendeeRepository $attendee_repository,
        ISummitRepository $summit_repository,
        ISummitEventRepository $event_repository,
        ISpeakerRepository $speaker_repository,
        IEventFeedbackRepository $event_feedback_repository,
        IMemberRepository $member_repository,
        ISummitService $summit_service,
        IAttendeeService $attendee_service,
        ISummitOrderService $summit_order_service,
        IResourceServerContext $resource_server_context
    ) {
        parent::__construct($resource_server_context);
        $this->summit_repository         = $summit_repository;
        $this->repository                = $attendee_repository;
        $this->speaker_repository        = $speaker_repository;
        $this->event_repository          = $event_repository;
        $this->event_feedback_repository = $event_feedback_repository;
        $this->member_repository         = $member_repository;
        $this->summit_service            = $summit_service;
        $this->attendee_service          = $attendee_service;
        $this->summit_order_service      = $summit_order_service;
    }

    /**
     *  Attendees endpoints
     */

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getOwnAttendee($summit_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $type     = CheckAttendeeStrategyFactory::Me;
            $attendee = CheckAttendeeStrategyFactory::build($type, $this->resource_server_context)->check('me', $summit);
            if(is_null($attendee)) return $this->error404();
            return $this->ok(SerializerRegistry::getInstance()->getSerializer($attendee)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        }
        catch (\HTTP401UnauthorizedException $ex1) {
            Log::warning($ex1);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @return mixed
     */
    public function getAttendee($summit_id, $attendee_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $attendee = $this->repository->getById($attendee_id);
            if(is_null($attendee)) return $this->error404();

            return $this->ok
            (
                SerializerRegistry::getInstance()->getSerializer
                (
                    $attendee,
                    SerializerRegistry::SerializerType_Private
                )->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                    [ 'serializer_type' => SerializerRegistry::SerializerType_Private ]
                ));
        }
        catch (\HTTP401UnauthorizedException $ex1) {
            Log::warning($ex1);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @return mixed
     */
    public function getAttendeeSchedule($summit_id, $attendee_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $attendee =  CheckAttendeeStrategyFactory::build(CheckAttendeeStrategyFactory::Own, $this->resource_server_context)->check($attendee_id, $summit);
            if(is_null($attendee)) return $this->error404();

            $schedule = [];
            foreach ($attendee->getSchedule() as $attendee_schedule)
            {
                if(!$summit->isEventOnSchedule($attendee_schedule->getEvent()->getId())) continue;
                $schedule[] = SerializerRegistry::getInstance()->getSerializer($attendee_schedule)->serialize();
            }

            return $this->ok($schedule);
        }
        catch (\HTTP401UnauthorizedException $ex1)
        {
            Log::warning($ex1);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @param $event_id
     * @return mixed
     */
    public function addEventToAttendeeSchedule($summit_id, $attendee_id, $event_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $attendee = CheckAttendeeStrategyFactory::build(CheckAttendeeStrategyFactory::Own, $this->resource_server_context)->check($attendee_id, $summit);
            if (is_null($attendee)) return $this->error404();

            $this->summit_service->addEventToMemberSchedule($summit, $attendee->getMember(), intval($event_id));

            return $this->created();
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex)
        {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @param $event_id
     * @return mixed
     */
    public function removeEventFromAttendeeSchedule($summit_id, $attendee_id, $event_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $attendee = CheckAttendeeStrategyFactory::build(CheckAttendeeStrategyFactory::Own, $this->resource_server_context)->check($attendee_id, $summit);
            if (is_null($attendee)) return $this->error404();

            $this->summit_service->removeEventFromMemberSchedule($summit, $attendee->getMember(), intval($event_id));

            return $this->deleted();

        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex)
        {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @param $event_id
     * @return mixed
     */
    public function deleteEventRSVP($summit_id, $attendee_id, $event_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $event = $summit->getScheduleEvent(intval($event_id));

            if (is_null($event)) {
                return $this->error404();
            }

            $attendee = CheckAttendeeStrategyFactory::build(CheckAttendeeStrategyFactory::Own, $this->resource_server_context)->check($attendee_id, $summit);
            if (is_null($attendee)) return $this->error404();

            $this->summit_service->unRSVPEvent($summit, $attendee->getMember(), $event_id);

            return $this->deleted();

        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAttendeesBySummit($summit_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function(){
                return [
                    'first_name'           => ['=@', '=='],
                    'last_name'            => ['=@', '=='],
                    'full_name'            => ['=@', '=='],
                    'company'              => ['=@', '=='],
                    'email'                => ['=@', '=='],
                    'external_order_id'    => ['=@', '=='],
                    'external_attendee_id' => ['=@', '=='],
                    'member_id'            => ['==', '>'],
                    'ticket_type'          => ['=@', '=='],
                    'badge_type'           => ['=@', '=='],
                    'status'               => ['=@', '=='],
                    'has_member'           => ['=='],
                ];
            },
            function(){
                return [
                    'first_name'           => 'sometimes|string',
                    'last_name'            => 'sometimes|string',
                    'full_name'            => 'sometimes|string',
                    'company'              => 'sometimes|string',
                    'email'                => 'sometimes|string',
                    'external_order_id'    => 'sometimes|string',
                    'external_attendee_id' => 'sometimes|string',
                    'member_id'            => 'sometimes|integer',
                    'ticket_type'          => 'sometimes|string',
                    'badge_type'           => 'sometimes|string',
                    'status'               => 'sometimes|string',
                    'has_member'           => 'sometimes|required|string|in:true,false',
                ];
            },
            function()
            {
                return [
                    'first_name',
                    'last_name',
                    'email',
                    'full_name',
                    'company',
                    'id',
                    'external_order_id',
                    'member_id',
                    'status',
                    'full_name',
                ];
            },
            function($filter) use($summit){
                if($filter instanceof Filter){
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_Private;
            }
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAttendeesBySummitCSV($summit_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function(){
                return [
                    'first_name'           => ['=@', '=='],
                    'last_name'            => ['=@', '=='],
                    'full_name'            => ['=@', '=='],
                    'email'                => ['=@', '=='],
                    'external_order_id'    => ['=@', '=='],
                    'company'              => ['=@', '=='],
                    'external_attendee_id' => ['=@', '=='],
                    'member_id'            => ['==','<=','>='],
                    'ticket_type'          => ['=@', '=='],
                    'badge_type'           => ['=@', '=='],
                    'status'               => ['=@', '=='],
                    'has_member'           => ['=='],
                ];
            },
            function(){
                return [
                    'first_name'           => 'sometimes|string',
                    'last_name'            => 'sometimes|string',
                    'full_name'            => 'sometimes|string',
                    'email'                => 'sometimes|string',
                    'external_order_id'    => 'sometimes|string',
                    'external_attendee_id' => 'sometimes|string',
                    'company'              => 'sometimes|string',
                    'member_id'            => 'sometimes|integer',
                    'ticket_type'          => 'sometimes|string',
                    'badge_type'           => 'sometimes|string',
                    'status'               => 'sometimes|string',
                    'has_member'           => 'sometimes|required|string|in:true,false',
                ];
            },
            function()
            {
                return [
                    'first_name',
                    'last_name',
                    'id',
                    'external_order_id',
                    'company',
                    'member_id',
                    'status',
                    'email',
                    'full_name',
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
                return [];
            },
            function(){
                return [];
            },
            'attendees-'
        );
    }

    /**
     * @param int $summit_id
     * @return mixed
     */
    public function addAttendee($summit_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $data = Input::json();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = [
                'shared_contact_info'         => 'sometimes|boolean',
                'summit_hall_checked_in'      => 'sometimes|boolean',
                'summit_hall_checked_in_date' => 'sometimes|date_format:U',
                'first_name'                  => 'required_without:member_id|string|max:255',
                'surname'                     => 'required_without:member_id|string|max:255',
                'admin_notes'                 => 'sometimes|string|max:1024',
                'company'                     => 'sometimes|string|max:255',
                'email'                       => 'required_without:member_id|string|max:255|email',
                'member_id'                   => 'required_without_all:email|integer',
                'extra_questions'             => 'sometimes|order_extra_question_dto_array',
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

            $attendee = $this->attendee_service->addAttendee($summit, $data->all());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($attendee)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @return mixed
     */
    public function deleteAttendee($summit_id, $attendee_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $attendee = $this->repository->getById($attendee_id);
            if(is_null($attendee)) return $this->error404();

            $this->attendee_service->deleteAttendee($summit, $attendee->getIdentifier());

            return $this->deleted();

        }
        catch (\HTTP401UnauthorizedException $ex1) {
            Log::warning($ex1);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param int $summit_id
     * @param int $attendee_id
     * @return mixed
     */
    public function updateAttendee($summit_id, $attendee_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $data = Input::json();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $attendee = $this->repository->getById($attendee_id);
            if(is_null($attendee)) return $this->error404();

            $rules = [
                'shared_contact_info'         => 'sometimes|boolean',
                'summit_hall_checked_in'      => 'sometimes|boolean',
                'summit_hall_checked_in_date' => 'sometimes|date_format:U',
                'disclaimer_accepted_date'    => 'sometimes|date_format:U',
                'first_name'                  => 'required_without:member_id|string|max:255',
                'surname'                     => 'required_without:member_id|string|max:255',
                'company'                     => 'sometimes|string|max:255',
                'email'                       => 'required_without:member_id|string|max:255|email',
                'member_id'                   => 'required_without_all:first_name,surname,email|integer',
                'extra_questions'             => 'sometimes|order_extra_question_dto_array',
                'admin_notes'                 => 'sometimes|string|max:1024',
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

            $attendee = $this->attendee_service->updateAttendee($summit, $attendee_id, $data->all());

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($attendee)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @return mixed
     */
    public function addAttendeeTicket($summit_id, $attendee_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $data = Input::json();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $attendee = $this->repository->getById($attendee_id);
            if(is_null($attendee) || !$attendee instanceof SummitAttendee) return $this->error404();

            $rules = [
                'ticket_type_id'       => 'required|integer',
                'promo_code'           => 'nullable|string',
                'external_order_id'    => 'nullable|string',
                'external_attendee_id' => 'nullable|string',
            ];

            $payload = $data->all();
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $payload['owner_email']      = $attendee->getEmail();
            $payload['owner_first_name'] = $attendee->getFirstName();
            $payload['owner_last_name']  = $attendee->getSurname();
            $payload['owner_company']    = $attendee->getCompanyName();

            if($attendee->hasMember())
                $payload['owner_id'] = $attendee->getMemberId();

            $ticket = $this->summit_order_service->createOrderSingleTicket($summit, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($ticket)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @param $ticket_id
     * @return mixed
     */
    public function deleteAttendeeTicket($summit_id, $attendee_id, $ticket_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $attendee = $this->repository->getById($attendee_id);
            if(is_null($attendee)) return $this->error404();

            $ticket = $this->attendee_service->deleteAttendeeTicket($attendee, $ticket_id);

            return $this->deleted();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @param $ticket_id
     * @param $other_member_id
     * @return mixed
     */
    public function reassignAttendeeTicketByMember($summit_id, $attendee_id, $ticket_id, $other_member_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $attendee = $this->repository->getById($attendee_id);
            if(is_null($attendee) || !$attendee instanceof SummitAttendee) return $this->error404();

            $other_member = $this->member_repository->getById($other_member_id);
            if(is_null($other_member) || !$other_member instanceof Member) return $this->error404();

            $ticket = $this->attendee_service->reassignAttendeeTicketByMember($summit, $attendee, $other_member, intval($ticket_id));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($ticket)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $attendee_id
     * @param $ticket_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function reassignAttendeeTicket($summit_id, $attendee_id, $ticket_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $attendee = $this->repository->getById($attendee_id);
            if(is_null($attendee) || !$attendee instanceof SummitAttendee) return $this->error404();

            $payload = $this->getJsonPayload([
                'attendee_first_name' => 'nullable|string|max:255',
                'attendee_last_name'  => 'nullable|string|max:255',
                'attendee_email'      => 'required|string|max:255|email',
                'attendee_company'    => 'nullable|string|max:255',
                'extra_questions'     => 'sometimes|order_extra_question_dto_array'
            ]);

            $ticket = $this->attendee_service->reassignAttendeeTicket($summit, $attendee, intval($ticket_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($ticket)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function send($summit_id){
        try {

            if(!Request::isJson()) return $this->error400();
            $data = Input::json();

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $data->all();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, [
                'email_flow_event' => 'required|string|in:'.join(',', [
                        SummitAttendeeTicketRegenerateHashEmail::EVENT_SLUG,
                        InviteAttendeeTicketEditionMail::EVENT_SLUG
                    ]),
                'attendees_ids' => 'sometimes|int_array',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $filter = null;

            if (Input::has('filter')) {
                $filter = FilterParser::parse(Input::get('filter'), [
                    'first_name'           => ['=@', '=='],
                    'last_name'            => ['=@', '=='],
                    'full_name'            => ['=@', '=='],
                    'email'                => ['=@', '=='],
                    'external_order_id'    => ['=@', '=='],
                    'company'              => ['=@', '=='],
                    'external_attendee_id' => ['=@', '=='],
                    'member_id'            => ['==','<=','>='],
                    'ticket_type'          => ['=@', '=='],
                    'badge_type'           => ['=@', '=='],
                    'status'               => ['=@', '=='],
                ]);
            }

            if (is_null($filter))
                $filter = new Filter();

            $filter->validate([
                'first_name'           => 'sometimes|string',
                'last_name'            => 'sometimes|string',
                'full_name'            => 'sometimes|string',
                'company'              => 'sometimes|string',
                'email'                => 'sometimes|string',
                'external_order_id'    => 'sometimes|string',
                'external_attendee_id' => 'sometimes|string',
                'member_id'            => 'sometimes|integer',
                'ticket_type'          => 'sometimes|string',
                'badge_type'           => 'sometimes|string',
                'status'               => 'sometimes|string',
            ]);

            $this->attendee_service->triggerSend($summit, $payload, Input::get('filter'));

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
}