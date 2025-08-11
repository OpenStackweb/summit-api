<?php namespace App\Http\Controllers;
/**
 * Copyright 2025 OpenStack Foundation
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

use App\Jobs\Emails\Schedule\RSVP\ReRSVPInviteEmail;
use App\Jobs\Emails\Schedule\RSVP\RSVPInviteEmail;
use App\Models\Foundation\Summit\Events\RSVP\Repositories\IRSVPInvitationRepository;
use App\ModelSerializers\SerializerUtils;
use App\Services\ISummitRSVPInvitationService;
use Illuminate\Http\Request as LaravelRequest;
use App\Services\Model\ISummitRSVPService;
use Illuminate\Support\Facades\Request;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use Illuminate\Support\Facades\Validator;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\SummitRegistrationInvitation;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;

class OAuth2RSVPInvitationApiController extends OAuth2ProtectedController
{
    use RequestProcessor;

    /**
     * @var ISummitRSVPService
     */
    private ISummitRSVPInvitationService $service;

    /**
     * @var ISummitRepository
     */
    private ISummitRepository $summit_repository;


    private ISummitEventRepository $summit_event_repository;

    public function __construct(
        ISummitEventRepository $summit_event_repository,
        IRSVPINvitationRepository $repository,
        ISummitRepository $summit_repository,
        ISummitRSVPInvitationService $service,
        IResourceServerContext $resource_server_context
    ){
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
        $this->summit_event_repository = $summit_event_repository;
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $summit_event_id
     * @return mixed
     */
    public function ingestInvitations(LaravelRequest $request, $summit_id, $summit_event_id){
        return $this->processRequest(function () use ($request, $summit_id, $summit_event_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();
            $summit_event = $summit->getEvent(intval($summit_event_id));
            if (is_null($summit_event)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();


            $payload = $request->all();

            $rules = [
                'file' => 'required',
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException;
                $ex->setMessages($validation->messages()->toArray());
                throw $ex;
            }

            $file = $request->file('file');

            $this->service->importInvitationData($summit_event, $file);
            return $this->ok();
        });
    }

    // traits
    use ParametrizedGetAll;

    public function getAllByEventId($summit_id, $summit_event_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $summit_event = $summit->getEvent(intval($summit_event_id));
        if (is_null($summit_event)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'attendee_email' => ['@@','=@', '=='],
                    'attendee_first_name' => ['@@','=@', '=='],
                    'attendee_last_name' => ['@@','=@', '=='],
                    'attendee_full_name' => ['@@','=@', '=='],
                    'is_accepted' => ['=='],
                    'is_sent' => ['=='],
                    'status' => ['=='],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'not_id' => 'sometimes|integer',
                    'attendee_email' => 'sometimes|required|string',
                    'attendee_first_name' => 'sometimes|required|string',
                    'attendee_last_name' => 'sometimes|required|string',
                    'attendee_full_name' => 'sometimes|required|string',
                    'is_accepted' => 'sometimes|required|string|in:true,false',
                    'is_sent' => 'sometimes|required|string|in:true,false',
                    'status' => 'sometimes|required|string|in:'.join(",", SummitRegistrationInvitation::AllowedStatus),
                ];
            },
            function () {
                return [
                    'id',
                    'attendee_email',
                    'attendee_first_name',
                    'attendee_last_name',
                    'attendee_full_name',
                    'status',
                ];
            },
            function ($filter) use ($summit_event) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_event_id', $summit_event->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }

    /**
     * @param $summit_id
     * @param $summit_event_id
     * @return mixed
     */
    public function send($summit_id, $summit_event_id)
    {
        return $this->processRequest(function () use ($summit_id, $summit_event_id) {

            if (!Request::isJson()) return $this->error400();
            $data = Request::json();

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $summit_event = $summit->getEvent(intval($summit_event_id));
            if (is_null($summit_event)) return $this->error404();

            $payload = $data->all();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, [
                'email_flow_event' => 'required|string|in:' . join(',', [
                        RSVPInviteEmail::EVENT_SLUG,
                        ReRSVPInviteEmail::EVENT_SLUG,
                    ]),
                'invitations_ids' => 'sometimes|int_array',
                'excluded_invitations_ids' => 'sometimes|int_array',
                'test_email_recipient'     => 'sometimes|email',
                'outcome_email_recipient'  => 'sometimes|email',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'attendee_email' => ['@@','=@', '=='],
                    'attendee_first_name' => ['@@','=@', '=='],
                    'attendee_last_name' => ['@@','=@', '=='],
                    'attendee_full_name' => ['@@','=@', '=='],
                    'is_accepted' => ['=='],
                    'is_sent' => ['=='],
                    'status' => ['=='],
                ]);
            }

            if (is_null($filter))
                $filter = new Filter();

            $filter->validate([
                'id' => 'sometimes|integer',
                'not_id' => 'sometimes|integer',
                'attendee_email' => 'sometimes|required|string',
                'attendee_first_name' => 'sometimes|required|string',
                'attendee_last_name' => 'sometimes|required|string',
                'attendee_full_name' => 'sometimes|required|string',
                'is_accepted' => 'sometimes|required|string|in:true,false',
                'is_sent' => 'sometimes|required|string|in:true,false',
                'status' => 'sometimes|required|string|in:'.join(",", SummitRegistrationInvitation::AllowedStatus),
            ]);

            $this->service->triggerSend($summit_event, $payload, Request::input('filter'));

            return $this->ok();

        });
    }

    use GetAndValidateJsonPayload;

    public function addInvitation($summit_id, $summit_event_id){
        return $this->processRequest(function () use ($summit_id, $summit_event_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();
            $summit_event = $summit->getEvent(intval($summit_event_id));
            if (is_null($summit_event)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $payload = $this->getJsonPayload([
                'attendee_id' => 'required:integer',
            ], true);

            $invitation = $this->service->add($summit_event, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($invitation)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));

        });
    }

    public function deleteInvitation($summit_id, $summit_event_id, $invitation_id ){
        return $this->processRequest(function () use ($summit_id, $summit_event_id, $invitation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();
            $summit_event = $summit->getEvent(intval($summit_event_id));
            if (is_null($summit_event)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->service->delete($summit_event, intval($invitation_id));

            return $this->deleted();
        });
    }


    // public endpoints

    /**
     * @param $summit_id
     * @param $summit_event_id
     * @param $token
     * @return mixed
     */
    public function getInvitationByToken($summit_id, $summit_event_id, $token){
        return $this->processRequest(function () use ($summit_id, $summit_event_id, $token) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();
            $summit_event = $summit->getEvent(intval($summit_event_id));
            if (is_null($summit_event)) return $this->error404();

            $invitation = $this->service->getInvitationBySummitEventAndToken($summit_event, $token);

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($invitation)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $summit_event_id
     * @param $token
     * @return mixed
     */
    public function acceptByToken($summit_id, $summit_event_id, $token){
        return $this->processRequest(function () use ($summit_id, $summit_event_id, $token) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();
            $summit_event = $summit->getEvent(intval($summit_event_id));
            if (is_null($summit_event)) return $this->error404();

            $invitation = $this->service->acceptInvitationBySummitEventAndToken($summit_event, $token);

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($invitation)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $summit_event_id
     * @param $token
     * @return mixed
     */
    public function rejectByToken($summit_id, $summit_event_id, $token){
        return $this->processRequest(function () use ($summit_id, $summit_event_id, $token) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();
            $summit_event = $summit->getEvent(intval($summit_event_id));
            if (is_null($summit_event)) return $this->error404();

            $invitation = $this->service->rejectInvitationBySummitEventAndToken($summit_event, $token);

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($invitation)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }
}