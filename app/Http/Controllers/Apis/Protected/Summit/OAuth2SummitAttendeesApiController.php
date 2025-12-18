<?php
namespace App\Http\Controllers;
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
use App\Http\Utils\Filters\FiltersParams;
use App\Jobs\Emails\InviteAttendeeTicketEditionMail;
use App\Jobs\Emails\Registration\Attendees\GenericSummitAttendeeEmail;
use App\Jobs\Emails\SummitAttendeeAllTicketsEditionEmail;
use App\Jobs\Emails\SummitAttendeeRegistrationIncompleteReminderEmail;
use App\Jobs\Emails\SummitAttendeeTicketRegenerateHashEmail;
use App\Jobs\SynchAllAttendeesStatus;
use App\Models\Foundation\Main\IGroup;
use App\ModelSerializers\SerializerUtils;
use App\Rules\Boolean;
use App\Security\SummitScopes;
use App\Services\Model\IAttendeeService;
use App\Services\Model\ISummitOrderService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use OpenApi\Attributes as OA;
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
use Illuminate\Support\Facades\Validator;
use utils\FilterParser;

/**
 * Class OAuth2SummitAttendeesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitAttendeesApiController extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;

    use RequestProcessor;

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
        $this->summit_repository = $summit_repository;
        $this->repository = $attendee_repository;
        $this->speaker_repository = $speaker_repository;
        $this->event_repository = $event_repository;
        $this->event_feedback_repository = $event_feedback_repository;
        $this->member_repository = $member_repository;
        $this->summit_service = $summit_service;
        $this->attendee_service = $attendee_service;
        $this->summit_order_service = $summit_order_service;
    }

    /**
     *  Attendees endpoints
     */

    #[OA\Get(
        path: '/api/v1/summits/{id}/attendees/me',
        operationId: 'getCurrentAttendee',
        summary: 'Get current user attendee profile',
        description: 'Returns the attendee profile for the currently authenticated user in the specified summit',
        tags: ['Summit Attendees'],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships (extra_questions, tickets, presentation_votes, ticket_types, allowed_access_levels, allowed_features, tags)', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitAttendee')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or attendee not found'),
        ]
    )]
    public function getOwnAttendee($summit_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $type = CheckAttendeeStrategyFactory::Me;
            $attendee = CheckAttendeeStrategyFactory::build($type, $this->resource_server_context)->check('me', $summit);
            if (is_null($attendee))
                return $this->error404();
            return $this->ok(SerializerRegistry::getInstance()->getSerializer($attendee)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        } catch (\HTTP401UnauthorizedException $ex1) {
            Log::warning($ex1);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/attendees/{attendee_id}',
        operationId: 'getAttendee',
        summary: 'Get attendee by ID',
        description: 'Returns a specific attendee profile from the summit',
        tags: ['Summit Attendees'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, description: 'Attendee ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitAttendee')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or attendee not found'),
        ]
    )]
    public function getAttendee($summit_id, $attendee_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $attendee = $this->repository->getById($attendee_id);
            if (is_null($attendee))
                return $this->error404();

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
                        ['serializer_type' => SerializerRegistry::SerializerType_Private]
                    )
            );
        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/attendees/{attendee_id}/schedule',
        operationId: 'getAttendeeSchedule',
        summary: 'Get attendee schedule',
        description: 'Returns the personal schedule for a specific attendee',
        tags: ['Summit Attendees'],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, description: 'Attendee ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'object')
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or attendee not found'),
        ]
    )]
    public function getAttendeeSchedule($summit_id, $attendee_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $attendee = CheckAttendeeStrategyFactory::build(CheckAttendeeStrategyFactory::Own, $this->resource_server_context)->check($attendee_id, $summit);
            if (is_null($attendee))
                return $this->error404();

            $schedule = [];
            foreach ($attendee->getSchedule() as $attendee_schedule) {
                if (!$summit->isEventOnSchedule($attendee_schedule->getEvent()->getId()))
                    continue;
                $schedule[] = SerializerRegistry::getInstance()->getSerializer($attendee_schedule)->serialize();
            }

            return $this->ok($schedule);
        } catch (\HTTP401UnauthorizedException $ex1) {
            Log::warning($ex1);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Post(
        path: '/api/v1/summits/{id}/attendees/{attendee_id}/schedule/{event_id}',
        operationId: 'addEventToAttendeeSchedule',
        summary: 'Add event to attendee schedule',
        description: 'Adds an event to the attendee\'s personal schedule',
        tags: ['Summit Attendees'],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, description: 'Attendee ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Event added to schedule successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, attendee or event not found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation Error'),
        ]
    )]
    public function addEventToAttendeeSchedule($summit_id, $attendee_id, $event_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $attendee = CheckAttendeeStrategyFactory::build(CheckAttendeeStrategyFactory::Own, $this->resource_server_context)->check($attendee_id, $summit);
            if (is_null($attendee))
                return $this->error404();

            $this->summit_service->addEventToMemberSchedule($summit, $attendee->getMember(), intval($event_id));

            return $this->created();
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/attendees/{attendee_id}/schedule/{event_id}',
        operationId: 'removeEventFromAttendeeSchedule',
        summary: 'Remove event from attendee schedule',
        description: 'Removes an event from the attendee\'s personal schedule',
        tags: ['Summit Attendees'],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, description: 'Attendee ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Event removed from schedule successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, attendee or event not found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation Error'),
        ]
    )]
    public function removeEventFromAttendeeSchedule($summit_id, $attendee_id, $event_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $attendee = CheckAttendeeStrategyFactory::build(CheckAttendeeStrategyFactory::Own, $this->resource_server_context)->check($attendee_id, $summit);
            if (is_null($attendee))
                return $this->error404();

            $this->summit_service->removeEventFromMemberSchedule($summit, $attendee->getMember(), intval($event_id));

            return $this->deleted();

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/attendees/{attendee_id}/schedule/{event_id}/rsvp',
        operationId: 'deleteAttendeeEventRsvp',
        summary: 'Delete RSVP for event',
        description: 'Deletes the attendee\'s RSVP for a specific event',
        tags: ['Summit Attendees'],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::DeleteMyRSVP,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, description: 'Attendee ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'RSVP deleted successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, attendee or event not found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation Error'),
        ]
    )]
    public function deleteEventRSVP($summit_id, $attendee_id, $event_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $event = $summit->getScheduleEvent(intval($event_id));

            if (is_null($event)) {
                return $this->error404();
            }

            $attendee = CheckAttendeeStrategyFactory::build(CheckAttendeeStrategyFactory::Own, $this->resource_server_context)->check($attendee_id, $summit);
            if (is_null($attendee))
                return $this->error404();

            $this->summit_service->unRSVPEvent($summit, $attendee->getMember(), $event_id);

            return $this->deleted();

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex3) {
            Log::warning($ex3);
            return $this->error401();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Put(
        path: '/api/v1/summits/{id}/attendees/{attendee_id}/schedule/{event_id}/check-in',
        operationId: 'checkInAttendeeToEvent',
        summary: 'Check-in attendee to event',
        description: 'Performs check-in for an attendee at a specific event on their schedule',
        tags: ['Summit Attendees'],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::WriteSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, description: 'Attendee ID or "me"', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'event_id', in: 'path', required: true, description: 'Event ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NOT_IMPLEMENTED, description: 'Not Implemented'),
        ]
    )]
    public function checkingAttendeeOnEvent($id, $attendee_id, $event_id)
    {
        return $this->error501();
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/attendees',
        operationId: 'getAllAttendees',
        summary: 'Get all attendees for a summit',
        description: 'Returns a paginated list of attendees for the specified summit with filtering, sorting and search capabilities',
        tags: ['Summit Attendees'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filter by id, first_name, last_name, full_name, company, email, member_id, ticket_type, badge_type, status, has_member, has_tickets, etc.', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by first_name, last_name, email, company, id, status, etc.', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedSummitAttendeesResponse')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
        ]
    )]
    public function getAttendeesBySummit($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit))
            return $this->error404();

        $filter = null;

        $filterRules = [
            'id' => ['=='],
            'not_id' => ['=='],
            'first_name' => ['=@', '=='],
            'last_name' => ['=@', '=='],
            'full_name' => ['=@', '=='],
            'company' => ['=@', '=='],
            'has_company' => ['=='],
            'email' => ['=@', '=='],
            'external_order_id' => ['=@', '=='],
            'external_attendee_id' => ['=@', '=='],
            'member_id' => ['==', '>'],
            'ticket_type' => ['=@', '==', '@@'],
            'ticket_type_id' => ['=='],
            'badge_type' => ['=@', '==', '@@'],
            'badge_type_id' => ['=='],
            'features' => ['=@', '==', '@@'],
            'features_id' => ['=='],
            'access_levels' => ['=@', '==', '@@'],
            'access_levels_id' => ['=='],
            'status' => ['=@', '=='],
            'has_member' => ['=='],
            'has_tickets' => ['=='],
            'has_virtual_checkin' => ['=='],
            'has_checkin' => ['=='],
            'tickets_count' => ['==', '>=', '<=', '>', '<'],
            'presentation_votes_date' => ['==', '>=', '<=', '>', '<'],
            'presentation_votes_count' => ['==', '>=', '<=', '>', '<'],
            'presentation_votes_track_group_id' => ['=='],
            'summit_hall_checked_in_date' => ['==', '>=', '<=', '>', '<', '[]'],
            'tags' => ['=@', '==', '@@'],
            'tags_id' => ['=='],
            'notes' => ['=@', '@@'],
            'has_notes' => ['=='],
            'has_manager' => ['=='],
        ];

        if (Request::has('filter')) {
            $filter = FilterParser::parse(Request::get('filter'), $filterRules);
        }

        if (is_null($filter))
            $filter = new Filter();

        $params = [];

        if (!is_null($filter)) {
            $votingDateFilter = $filter->getFilter('presentation_votes_date');
            if ($votingDateFilter != null) {
                $params['begin_attendee_voting_period_date'] = $votingDateFilter[0]->getValue();
                if (count($votingDateFilter) > 1) {
                    $params['end_attendee_voting_period_date'] = $votingDateFilter[1]->getValue();
                }
            }
            $trackGroupFilter = $filter->getFilter('presentation_votes_track_group_id');
            if ($trackGroupFilter != null) {
                $params['presentation_votes_track_group_id'] = $trackGroupFilter[0]->getValue();
            }
        }

        return $this->_getAll(
            function () use ($filterRules) {
                return $filterRules;
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'not_id' => 'sometimes|integer',
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'full_name' => 'sometimes|string',
                    'company' => 'sometimes|string',
                    'has_company' => ['sometimes', new Boolean()],
                    'email' => 'sometimes|string',
                    'external_order_id' => 'sometimes|string',
                    'external_attendee_id' => 'sometimes|string',
                    'member_id' => 'sometimes|integer',
                    'ticket_type' => 'sometimes|string',
                    'badge_type' => 'sometimes|string',
                    'features' => 'sometimes|string',
                    'access_levels' => 'sometimes|string',
                    'status' => 'sometimes|string',
                    'has_member' => 'sometimes|required|string|in:true,false',
                    'has_tickets' => 'sometimes|required|string|in:true,false',
                    'has_virtual_checkin' => 'sometimes|required|string|in:true,false',
                    'has_checkin' => 'sometimes|required|string|in:true,false',
                    'tickets_count' => 'sometimes|integer',
                    'presentation_votes_date' => 'sometimes|date_format:U|epoch_seconds',
                    'presentation_votes_count' => 'sometimes|integer',
                    'presentation_votes_track_group_id' => 'sometimes|integer',
                    'ticket_type_id' => 'sometimes|integer',
                    'badge_type_id' => 'sometimes|integer',
                    'features_id' => 'sometimes|integer',
                    'access_levels_id' => 'sometimes|integer',
                    'summit_hall_checked_in_date' => 'sometimes|date_format:U|epoch_seconds',
                    'tags' => 'sometimes|string',
                    'tags_id' => 'sometimes|integer',
                    'notes' => 'sometimes|string',
                    'has_notes' => ['sometimes', new Boolean()],
                    'has_manager' => ['sometimes', new Boolean()],
                ];
            },
            function () {
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
                    'presentation_votes_count',
                    'summit_hall_checked_in_date',
                    'tickets_count',
                    'has_notes',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            },
            null,
            null,
            null,
            $params
        );
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/attendees/csv',
        operationId: 'getAllAttendeesCSV',
        summary: 'Export attendees to CSV',
        description: 'Returns a CSV file with all attendees for the specified summit',
        tags: ['Summit Attendees'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filter by id, first_name, last_name, full_name, company, email, member_id, ticket_type, badge_type, status, has_member, has_tickets, etc.', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'order', in: 'query', required: false, description: 'Order by first_name, last_name, email, company, id, status, etc.', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'CSV file with attendees data',
                content: new OA\MediaType(
                    mediaType: 'text/csv',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
        ]
    )]
    public function getAttendeesBySummitCSV($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit))
            return $this->error404();

        return $this->_getAllCSV(
            function () {
                return [
                    'id' => ['=='],
                    'not_id' => ['=='],
                    'first_name' => ['=@', '=='],
                    'last_name' => ['=@', '=='],
                    'full_name' => ['=@', '=='],
                    'email' => ['=@', '=='],
                    'external_order_id' => ['=@', '=='],
                    'company' => ['=@', '=='],
                    'has_company' => ['=='],
                    'external_attendee_id' => ['=@', '=='],
                    'member_id' => ['==', '<=', '>='],
                    'status' => ['=@', '=='],
                    'has_member' => ['=='],
                    'has_tickets' => ['=='],
                    'tickets_count' => ['==', '>=', '<=', '>', '<'],
                    'has_virtual_checkin' => ['=='],
                    'has_checkin' => ['=='],
                    'ticket_type' => ['=@', '==', '@@'],
                    'ticket_type_id' => ['=='],
                    'badge_type' => ['=@', '==', '@@'],
                    'badge_type_id' => ['=='],
                    'features' => ['=@', '==', '@@'],
                    'features_id' => ['=='],
                    'access_levels' => ['=@', '==', '@@'],
                    'access_levels_id' => ['=='],
                    'summit_hall_checked_in_date' => ['==', '>=', '<=', '>', '<', '[]'],
                    'tags' => ['=@', '==', '@@'],
                    'tags_id' => ['=='],
                    'notes' => ['=@', '@@'],
                    'has_notes' => ['=='],
                    'has_manager' => ['=='],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'not_id' => 'sometimes|integer',
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                    'full_name' => 'sometimes|string',
                    'email' => 'sometimes|string',
                    'external_order_id' => 'sometimes|string',
                    'external_attendee_id' => 'sometimes|string',
                    'company' => 'sometimes|string',
                    'has_company' => ['sometimes', new Boolean()],
                    'member_id' => 'sometimes|integer',
                    'status' => 'sometimes|string',
                    'has_member' => 'sometimes|required|string|in:true,false',
                    'has_tickets' => 'sometimes|required|string|in:true,false',
                    'has_virtual_checkin' => 'sometimes|required|string|in:true,false',
                    'tickets_count' => 'sometimes|integer',
                    'has_checkin' => 'sometimes|required|string|in:true,false',
                    'ticket_type' => 'sometimes|string',
                    'badge_type' => 'sometimes|string',
                    'features' => 'sometimes|string',
                    'access_levels' => 'sometimes|string',
                    'ticket_type_id' => 'sometimes|integer',
                    'badge_type_id' => 'sometimes|integer',
                    'features_id' => 'sometimes|integer',
                    'access_levels_id' => 'sometimes|integer',
                    'summit_hall_checked_in_date' => 'sometimes|date_format:U|epoch_seconds',
                    'tags' => 'sometimes|string',
                    'tags_id' => 'sometimes|integer',
                    'notes' => 'sometimes|string',
                    'has_notes' => ['sometimes', new Boolean()],
                    'has_manager' => ['sometimes', new Boolean()],
                ];
            },
            function () {
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
                    'summit_hall_checked_in_date',
                    'tickets_count',
                    'has_notes',
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
                    'disclaimer_accepted_date' => new EpochCellFormatter(),
                    'has_virtual_check_in' => new BooleanCellFormatter(),
                ];
            },
            function () {
                return [];
            },
            'attendees-'
        );
    }

    #[OA\Post(
        path: '/api/v1/summits/{id}/attendees',
        operationId: 'createAttendee',
        summary: 'Create a new attendee',
        description: 'Creates a new attendee for the specified summit',
        tags: ['Summit Attendees'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteAttendeesData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AttendeeRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Attendee created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitAttendee')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation Error'),
        ]
    )]
    public function addAttendee($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {
            if (!Request::isJson())
                return $this->error400();
            $data = Request::json();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $rules = [
                'shared_contact_info' => 'sometimes|boolean',
                'summit_hall_checked_in' => 'sometimes|boolean',
                'disclaimer_accepted' => 'sometimes|boolean',
                'first_name' => 'required_without:member_id|string|max:255',
                'surname' => 'required_without:member_id|string|max:255',
                'admin_notes' => 'nullable|sometimes|string|max:1024',
                'company' => 'nullable|sometimes|string|max:255',
                'email' => 'sometimes|string|max:255|email',
                'member_id' => 'sometimes|integer',
                'extra_questions' => 'sometimes|extra_question_dto_array',
                'tags' => 'sometimes|string_array',
                'manager_id' => 'sometimes|integer',
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

            return $this->created(
                SerializerRegistry::getInstance()->getSerializer
                (
                    $attendee,
                    SerializerRegistry::SerializerType_Private
                )->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        ['serializer_type' => SerializerRegistry::SerializerType_Private]
                    )
            );
        });
    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/attendees/{attendee_id}',
        operationId: 'deleteAttendee',
        summary: 'Delete an attendee',
        description: 'Deletes a specific attendee from the summit',
        tags: ['Summit Attendees'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteAttendeesData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, description: 'Attendee ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Attendee deleted successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or attendee not found'),
        ]
    )]
    public function deleteAttendee($summit_id, $attendee_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $attendee = $this->repository->getById($attendee_id);
            if (is_null($attendee))
                return $this->error404();

            $this->attendee_service->deleteAttendee($summit, $attendee->getIdentifier());

            return $this->deleted();

        });
    }

    #[OA\Put(
        path: '/api/v1/summits/{id}/attendees/{attendee_id}',
        operationId: 'updateAttendee',
        summary: 'Update an attendee',
        description: 'Updates a specific attendee in the summit',
        tags: ['Summit Attendees'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteAttendeesData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, description: 'Attendee ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AttendeeRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Attendee updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitAttendee')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or attendee not found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation Error'),
        ]
    )]
    public function updateAttendee($summit_id, $attendee_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id) {
            if (!Request::isJson())
                return $this->error400();
            $data = Request::json();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $attendee = $this->repository->getById($attendee_id);
            if (is_null($attendee))
                return $this->error404();

            $rules = [
                'shared_contact_info' => 'sometimes|boolean',
                'summit_hall_checked_in' => 'sometimes|boolean',
                'disclaimer_accepted' => 'sometimes|boolean',
                'first_name' => 'required_without:member_id|string|max:255',
                'surname' => 'required_without:member_id|string|max:255',
                'company' => 'nullable|sometimes|string|max:255',
                'email' => 'required_without:member_id|string|max:255|email',
                'member_id' => 'required_without:email|integer',
                'extra_questions' => 'sometimes|extra_question_dto_array',
                'admin_notes' => 'nullable|sometimes|string|max:1024',
                'tags' => 'sometimes|string_array',
                'manager_id' => 'sometimes|integer',
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

            return $this->updated(
                SerializerRegistry::getInstance()->getSerializer
                (
                    $attendee,
                    SerializerRegistry::SerializerType_Private
                )->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        ['serializer_type' => SerializerRegistry::SerializerType_Private]
                    )
            );
        });
    }

    #[OA\Post(
        path: '/api/v1/summits/{id}/attendees/{attendee_id}/tickets',
        operationId: 'addAttendeeTicket',
        summary: 'Add ticket to attendee',
        description: 'Creates a new ticket for a specific attendee',
        tags: ['Summit Attendees'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteAttendeesData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, description: 'Attendee ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AddAttendeeTicketRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Ticket created successfully',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or attendee not found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation Error'),
        ]
    )]
    public function addAttendeeTicket($summit_id, $attendee_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id) {
            if (!Request::isJson())
                return $this->error400();
            $data = Request::json();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $attendee = $this->repository->getById($attendee_id);
            if (is_null($attendee) || !$attendee instanceof SummitAttendee)
                return $this->error404();

            $rules = [
                'ticket_type_id' => 'required|integer',
                'promo_code' => 'nullable|string',
                'external_order_id' => 'nullable|string',
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

            $payload['owner_email'] = $attendee->getEmail();
            $payload['owner_first_name'] = $attendee->getFirstName();
            $payload['owner_last_name'] = $attendee->getSurname();
            $payload['owner_company'] = $attendee->getCompanyName();

            if ($attendee->hasMember())
                $payload['owner_id'] = $attendee->getMemberId();

            $ticket = $this->summit_order_service->createOfflineOrder($summit, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($ticket)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/attendees/{attendee_id}/tickets/{ticket_id}',
        operationId: 'deleteAttendeeTicket',
        summary: 'Delete attendee ticket',
        description: 'Deletes a specific ticket from an attendee',
        tags: ['Summit Attendees'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteAttendeesData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, description: 'Attendee ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'ticket_id', in: 'path', required: true, description: 'Ticket ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Ticket deleted successfully'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, attendee or ticket not found'),
        ]
    )]
    public function deleteAttendeeTicket($summit_id, $attendee_id, $ticket_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id, $ticket_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $attendee = $this->repository->getById($attendee_id);
            if (is_null($attendee))
                return $this->error404();

            $this->attendee_service->deleteAttendeeTicket($attendee, $ticket_id);

            return $this->deleted();
        });
    }

    #[OA\Put(
        path: '/api/v1/summits/{id}/attendees/{attendee_id}/tickets/{ticket_id}/reassign/{other_member_id}',
        operationId: 'reassignAttendeeTicketByMember',
        summary: 'Reassign ticket to another member',
        description: 'Reassigns a ticket from one attendee to another member',
        tags: ['Summit Attendees'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteAttendeesData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, description: 'Attendee ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'ticket_id', in: 'path', required: true, description: 'Ticket ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'other_member_id', in: 'path', required: true, description: 'Target Member ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Ticket reassigned successfully',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, attendee, ticket or member not found'),
        ]
    )]
    public function reassignAttendeeTicketByMember($summit_id, $attendee_id, $ticket_id, $other_member_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id, $ticket_id, $other_member_id) {


            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $attendee = $this->repository->getById($attendee_id);
            if (is_null($attendee) || !$attendee instanceof SummitAttendee)
                return $this->error404();

            $other_member = $this->member_repository->getById($other_member_id);
            if (is_null($other_member) || !$other_member instanceof Member)
                return $this->error404();

            $ticket = $this->attendee_service->reassignAttendeeTicketByMember($summit, $attendee, $other_member, intval($ticket_id));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($ticket)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Put(
        path: '/api/v1/summits/{id}/attendees/{attendee_id}/tickets/{ticket_id}/reassign',
        operationId: 'reassignAttendeeTicket',
        summary: 'Reassign ticket to another attendee',
        description: 'Reassigns a ticket to a different attendee by email',
        tags: ['Summit Attendees'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteAttendeesData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, description: 'Attendee ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'ticket_id', in: 'path', required: true, description: 'Ticket ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ReassignAttendeeTicketRequest')
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Ticket reassigned successfully',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit, attendee or ticket not found'),
        ]
    )]
    public function reassignAttendeeTicket($summit_id, $attendee_id, $ticket_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id, $ticket_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $attendee = $this->repository->getById($attendee_id);
            if (is_null($attendee) || !$attendee instanceof SummitAttendee)
                return $this->error404();

            $payload = $this->getJsonPayload([
                'attendee_first_name' => 'nullable|string|max:255',
                'attendee_last_name' => 'nullable|string|max:255',
                'attendee_email' => 'required|string|max:255|email',
                'attendee_company' => 'nullable|string|max:255',
                'extra_questions' => 'sometimes|extra_question_dto_array'
            ]);

            $ticket = $this->attendee_service->reassignAttendeeTicket($summit, $attendee, intval($ticket_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($ticket)->serialize(
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

    #[OA\Put(
        path: '/api/v1/summits/{id}/attendees/all/send',
        operationId: 'sendAttendeesEmail',
        summary: 'Send email to attendees',
        description: 'Sends email notifications to filtered attendees',
        tags: ['Summit Attendees'],
        x: [
            'required-groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
                IGroup::SummitRegistrationAdmins,
            ]
        ],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::WriteAttendeesData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'filter', in: 'query', required: false, description: 'Filter attendees', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SendAttendeesEmailRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Email send process initiated successfully'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Bad request'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit not found'),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: 'Validation Error'),
        ]
    )]
    public function send($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            if (!Request::isJson())
                return $this->error400();
            $data = Request::json();

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $payload = $data->all();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, [
                'email_flow_event' => 'required|string|in:' . join(',', [
                    SummitAttendeeTicketRegenerateHashEmail::EVENT_SLUG,
                    InviteAttendeeTicketEditionMail::EVENT_SLUG,
                    SummitAttendeeAllTicketsEditionEmail::EVENT_SLUG,
                    SummitAttendeeRegistrationIncompleteReminderEmail::EVENT_SLUG,
                    GenericSummitAttendeeEmail::EVENT_SLUG,
                ]),
                'attendees_ids' => 'sometimes|int_array',
                'excluded_attendees_ids' => 'sometimes|int_array',
                'test_email_recipient' => 'sometimes|email',
                'outcome_email_recipient' => 'sometimes|email',
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
                    'first_name' => ['=@', '=='],
                    'last_name' => ['=@', '=='],
                    'full_name' => ['=@', '=='],
                    'company' => ['=@', '=='],
                    'has_company' => ['=='],
                    'email' => ['=@', '=='],
                    'external_order_id' => ['=@', '=='],
                    'external_attendee_id' => ['=@', '=='],
                    'member_id' => ['==', '>'],
                    'ticket_type' => ['=@', '==', '@@'],
                    'ticket_type_id' => ['=='],
                    'badge_type' => ['=@', '==', '@@'],
                    'badge_type_id' => ['=='],
                    'features' => ['=@', '==', '@@'],
                    'features_id' => ['=='],
                    'access_levels' => ['=@', '==', '@@'],
                    'access_levels_id' => ['=='],
                    'status' => ['=@', '=='],
                    'has_member' => ['=='],
                    'has_tickets' => ['=='],
                    'has_virtual_checkin' => ['=='],
                    'has_checkin' => ['=='],
                    'tickets_count' => ['==', '>=', '<=', '>', '<'],
                    'presentation_votes_date' => ['==', '>=', '<=', '>', '<'],
                    'presentation_votes_count' => ['==', '>=', '<=', '>', '<'],
                    'presentation_votes_track_group_id' => ['=='],
                    'summit_hall_checked_in_date' => ['==', '>=', '<=', '>', '<', '[]'],
                    'tags' => ['=@', '==', '@@'],
                    'tags_id' => ['=='],
                    'notes' => ['=@', '@@'],
                    'has_notes' => ['=='],
                    'has_manager' => ['=='],
                ]);
            }

            if (is_null($filter))
                $filter = new Filter();

            $filter->validate([
                'id' => 'sometimes|integer',
                'not_id' => 'sometimes|integer',
                'first_name' => 'sometimes|string',
                'last_name' => 'sometimes|string',
                'full_name' => 'sometimes|string',
                'company' => 'sometimes|string',
                'has_company' => ['sometimes', new Boolean()],
                'email' => 'sometimes|string',
                'external_order_id' => 'sometimes|string',
                'external_attendee_id' => 'sometimes|string',
                'member_id' => 'sometimes|integer',
                'ticket_type' => 'sometimes|string',
                'badge_type' => 'sometimes|string',
                'features' => 'sometimes|string',
                'access_levels' => 'sometimes|string',
                'status' => 'sometimes|string',
                'has_member' => 'sometimes|required|string|in:true,false',
                'has_tickets' => 'sometimes|required|string|in:true,false',
                'has_virtual_checkin' => 'sometimes|required|string|in:true,false',
                'has_checkin' => 'sometimes|required|string|in:true,false',
                'tickets_count' => 'sometimes|integer',
                'presentation_votes_date' => 'sometimes|date_format:U|epoch_seconds',
                'presentation_votes_count' => 'sometimes|integer',
                'presentation_votes_track_group_id' => 'sometimes|integer',
                'ticket_type_id' => 'sometimes|integer',
                'badge_type_id' => 'sometimes|integer',
                'features_id' => 'sometimes|integer',
                'access_levels_id' => 'sometimes|integer',
                'summit_hall_checked_in_date' => 'sometimes|date_format:U|epoch_seconds',
                'tags' => 'sometimes|string',
                'tags_id' => 'sometimes|integer',
                'notes' => 'sometimes|string',
                'has_notes' => ['sometimes', new Boolean()],
                'has_manager' => ['sometimes', new Boolean()],
            ]);

            $this->attendee_service->triggerSend($summit, $payload, FiltersParams::getFilterParam());

            return $this->ok();

        });
    }

    #[OA\Put(
        path: '/api/v1/summits/{id}/attendees/{attendee_id}/virtual-check-in',
        operationId: 'doAttendeeVirtualCheckIn',
        summary: 'Perform virtual check-in',
        description: 'Performs virtual check-in for a specific attendee',
        tags: ['Summit Attendees'],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::WriteSummitData,
                    SummitScopes::DoVirtualCheckIn,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, description: 'Attendee ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Virtual check-in completed successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitAttendee')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or attendee not found'),
        ]
    )]
    public function doVirtualCheckin($summit_id, $attendee_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $attendee = $this->repository->getById($attendee_id);
            if (is_null($attendee))
                return $this->error404();
            $attendee = $this->attendee_service->doVirtualCheckin($summit, $attendee_id);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($attendee)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    #[OA\Get(
        path: '/api/v1/summits/{id}/attendees/{attendee_id}/me',
        operationId: 'getMyRelatedAttendee',
        summary: 'Get related attendee for current user',
        description: 'Returns attendee information if current user owns the tickets or is the attendee',
        tags: ['Summit Attendees'],
        security: [
            [
                'summit_attendees_oauth2' => [
                    SummitScopes::ReadSummitData,
                    SummitScopes::ReadAllSummitData,
                ]
            ]
        ],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Summit ID', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'attendee_id', in: 'path', required: true, description: 'Attendee ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'expand', in: 'query', required: false, description: 'Expand relationships', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successful operation',
                content: new OA\JsonContent(ref: '#/components/schemas/SummitAttendee')
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Summit or attendee not found'),
        ]
    )]
    public function getMyRelatedAttendee($summit_id, $attendee_id)
    {
        return $this->processRequest(function () use ($summit_id, $attendee_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404("Summit not found.");

            $attendee = $summit->getAttendeeById(intval($attendee_id));
            if (!$attendee instanceof SummitAttendee)
                return $this->error404("Attendee not found.");

            // check permissions

            $current_user = $this->getResourceServerContext()->getCurrentUser();

            if (is_null($current_user))
                return $this->error403();

            // check ownership
            $isOrderOwner = false;
            foreach ($attendee->getTickets() as $ticket) {
                if (!$ticket->isPaid() || !$ticket->isActive())
                    continue;
                $order = $ticket->getOrder();
                if ($order->getOwnerEmail() === $current_user->getEmail())
                    $isOrderOwner = true;
            }

            $isAttendeeOwner = true;
            if ($attendee->getEmail() != $current_user->getEmail())
                $isAttendeeOwner = false;

            if (!$isOrderOwner && !$isAttendeeOwner)
                throw new EntityNotFoundException("Attendee not found.");

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
                        ['serializer_type' => SerializerRegistry::SerializerType_Private]
                    )
            );
        });
    }

}