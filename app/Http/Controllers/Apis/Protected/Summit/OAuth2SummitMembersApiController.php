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

use App\Http\Utils\CurrentAffiliationsCellFormatter;
use App\Http\Utils\EpochCellFormatter;
use App\Models\Foundation\Main\IGroup;
use App\ModelSerializers\SerializerUtils;
use App\Security\RSVPInvitationsScopes;
use App\Security\SummitScopes;
use App\Services\Model\ISummitRSVPService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use services\model\ISummitService;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;
use utils\FilterParserException;
use utils\OrderParser;
use utils\PagingInfo;
use utils\PagingResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Class OAuth2SummitMembersApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitMembersApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitService
     */
    private $summit_service;

    /**
     * @var ISummitRSVPService
     */
    private $rsvp_service;

    /**
     * OAuth2SummitMembersApiController constructor.
     * @param IMemberRepository $member_repository
     * @param ISummitRepository $summit_repository
     * @param ISummitService $summit_service
     * @param ISummitRSVPService $rsvp_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IMemberRepository      $member_repository,
        ISummitRepository      $summit_repository,
        ISummitService         $summit_service,
        ISummitRSVPService $rsvp_service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->summit_repository = $summit_repository;
        $this->repository = $member_repository;
        $this->summit_service = $summit_service;
        $this->rsvp_service = $rsvp_service;
    }

    use RequestProcessor;

    /**
     * @param $summit_id
     * @param $member_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: "/summits/{id}/members/me",
        operationId: "getMyMember",
        description: "Get current user member details for a summit",
        tags: ["Summit Members"],
        security: [['summit_members_oauth2' => [
            SummitScopes::MeRead,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "expand", description: "Expand relations", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "fields", description: "Fields to return", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "relations", description: "Relations to include", in: "query", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: "Member details", content: new OA\JsonContent(ref: "#/components/schemas/Member")),
            new OA\Response(response: HttpResponse::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: HttpResponse::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getMyMember($summit_id, $member_id)
    {
        return $this->processRequest(function () use ($summit_id, $member_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            return $this->ok
            (
                SerializerRegistry::getInstance()->getSerializer($current_member, SerializerRegistry::SerializerType_Private)
                    ->serialize
                    (
                        SerializerUtils::getExpand(),
                        SerializerUtils::getFields(),
                        SerializerUtils::getRelations(),
                        ['summit' => $summit]
                    )
            );
        });
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: "/summits/{id}/members/me/favorites",
        operationId: "getMemberFavoritesSummitEvents",
        description: "Get current user favorite summit events",
        tags: ["Summit Members"],
        security: [['summit_members_oauth2' => [
            SummitScopes::MeRead,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "expand", description: "Expand relations", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "fields", description: "Fields to return", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "relations", description: "Relations to include", in: "query", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: "List of favorite events", content: new OA\JsonContent(ref: "#/components/schemas/MemberFavoriteEventsList")),
            new OA\Response(response: HttpResponse::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: HttpResponse::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getMemberFavoritesSummitEvents($summit_id, $member_id)
    {
        return $this->processRequest(function () use ($summit_id, $member_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $favorites = array();
            foreach ($current_member->getFavoritesSummitEventsBySummit($summit) as $favorite_event) {
                if (!$summit->isEventOnSchedule($favorite_event->getEvent()->getId())) continue;
                $favorites[] = SerializerRegistry::getInstance()->getSerializer($favorite_event)->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations(),
                );
            }

            $response = new PagingResponse
            (
                count($favorites),
                count($favorites),
                1,
                1,
                $favorites
            );

            return $this->ok($response->toArray(
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
    #[OA\Post(
        path: "/summits/{id}/members/me/favorites/{event_id}",
        operationId: "addEventToMemberFavorites",
        description: "Add an event to current user favorites",
        tags: ["Summit Members"],
        security: [['summit_members_oauth2' => [
            SummitScopes::AddMyFavorites,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "event_id", description: "Event ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_CREATED, description: "Event added to favorites"),
            new OA\Response(response: HttpResponse::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: HttpResponse::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addEventToMemberFavorites($summit_id, $member_id, $event_id)
    {
        return $this->processRequest(function () use ($summit_id, $member_id, $event_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->summit_service->addEventToMemberFavorites($summit, $current_member, intval($event_id));

            return $this->created();
        });
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    #[OA\Delete(
        path: "/summits/{id}/members/me/favorites/{event_id}",
        operationId: "removeEventFromMemberFavorites",
        description: "Remove an event from current user favorites",
        tags: ["Summit Members"],
        security: [['summit_members_oauth2' => [
            SummitScopes::DeleteMyFavorites,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "event_id", description: "Event ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_NO_CONTENT, description: "Event removed from favorites"),
            new OA\Response(response: HttpResponse::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: HttpResponse::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function removeEventFromMemberFavorites($summit_id, $member_id, $event_id)
    {
        return $this->processRequest(function () use ($summit_id, $member_id, $event_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->summit_service->removeEventFromMemberFavorites($summit, $current_member, intval($event_id));

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @return mixed
     */
    #[OA\Get(
        path: "/summits/{id}/members/me/schedule",
        operationId: "getMemberScheduleSummitEvents",
        description: "Get current user schedule events",
        tags: ["Summit Members"],
        security: [['summit_members_oauth2' => [
            SummitScopes::MeRead,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "expand", description: "Expand relations", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "fields", description: "Fields to return", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "relations", description: "Relations to include", in: "query", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: "List of schedule events", content: new OA\JsonContent(ref: "#/components/schemas/MemberScheduleEventsList")),
            new OA\Response(response: HttpResponse::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: HttpResponse::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getMemberScheduleSummitEvents($summit_id, $member_id)
    {
        return $this->processRequest(function () use ($summit_id, $member_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $schedule = [];
            foreach ($current_member->getScheduleBySummit($summit) as $schedule_event) {
                if (!$summit->isEventOnSchedule($schedule_event->getEvent()->getId())) continue;
                $schedule[] = SerializerRegistry::getInstance()->getSerializer($schedule_event)->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                );
            }

            $response = new PagingResponse
            (
                count($schedule),
                count($schedule),
                1,
                1,
                $schedule
            );

            return $this->ok($response->toArray(
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
    #[OA\Post(
        path: "/summits/{id}/members/me/schedule/{event_id}",
        operationId: "addEventToMemberSchedule",
        description: "Add an event to current user schedule",
        tags: ["Summit Members"],
        security: [['summit_members_oauth2' => [
            SummitScopes::AddMySchedule,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "event_id", description: "Event ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_CREATED, description: "Event added to schedule"),
            new OA\Response(response: HttpResponse::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: HttpResponse::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function addEventToMemberSchedule($summit_id, $member_id, $event_id)
    {
        return $this->processRequest(function () use ($summit_id, $member_id, $event_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->summit_service->addEventToMemberSchedule($summit, $current_member, intval($event_id));

            return $this->created();
        });
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return mixed
     */
    #[OA\Delete(
        path: "/summits/{id}/members/me/schedule/{event_id}",
        operationId: "removeEventFromMemberSchedule",
        description: "Remove an event from current user schedule",
        tags: ["Summit Members"],
        security: [['summit_members_oauth2' => [
            SummitScopes::DeleteMySchedule,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "event_id", description: "Event ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_NO_CONTENT, description: "Event removed from schedule"),
            new OA\Response(response: HttpResponse::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: HttpResponse::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function removeEventFromMemberSchedule($summit_id, $member_id, $event_id)
    {
        return $this->processRequest(function () use ($summit_id, $member_id, $event_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $this->summit_service->removeEventFromMemberSchedule($summit, $current_member, intval($event_id));

            return $this->deleted();
        });
    }

    use ParametrizedGetAll;

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: "/summits/{id}/members",
        operationId: "getAllMembersBySummit",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        tags: ["Summit Members"],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_members_oauth2' => [
            SummitScopes::ReadAllSummitData,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "page", description: "Page number", in: "query", schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", description: "Items per page", in: "query", schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "expand", description: "Expand relations", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "fields", description: "Fields to return", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "filter", description: "Filter conditions", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", description: "Order by", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "relations", description: "Relations to include", in: "query", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: "List of members", content: new OA\JsonContent(ref: "#/components/schemas/MembersList")),
            new OA\Response(response: HttpResponse::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllBySummit($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'irc' => ['=@', '==', '@@'],
                    'twitter' => ['=@', '==', '@@'],
                    'first_name' => ['=@', '==', '@@'],
                    'last_name' => ['=@', '==', '@@'],
                    'email' => ['=@', '==', '@@'],
                    'group_slug' => ['=@', '==', '@@'],
                    'group_id' => ['=='],
                    'schedule_event_id' => ['=='],
                    'email_verified' => ['=='],
                    'active' => ['=='],
                    'github_user' => ['=@', '==', '@@'],
                    'full_name' => ['=@', '==', '@@'],
                    'created' => ['>', '<', '<=', '>=', '==','[]'],
                    'last_edited' => ['>', '<', '<=', '>=', '==','[]'],
                ];
            },
            function () {
                return [
                    'irc' => 'sometimes|required|string',
                    'twitter' => 'sometimes|required|string',
                    'first_name' => 'sometimes|required|string',
                    'last_name' => 'sometimes|required|string',
                    'email' => 'sometimes|required|string',
                    'group_slug' => 'sometimes|required|string',
                    'group_id' => 'sometimes|required|integer',
                    'schedule_event_id' => 'sometimes|required|integer',
                    'email_verified' => 'sometimes|required|boolean',
                    'active' => 'sometimes|required|boolean',
                    'github_user' => 'sometimes|required|string',
                    'full_name' => 'sometimes|required|string',
                    'created' => 'sometimes|required|date_format:U|epoch_seconds',
                    'last_edited' => 'sometimes|required|date_format:U|epoch_seconds',
                ];
            },
            function () {
                return [
                    'first_name',
                    'last_name',
                    'id',
                    'created',
                    'last_edited',
                ];
            },
            function ($filter) use ($summit_id) {
                $filter->addFilterCondition(FilterElement::makeEqual("summit_id", intval($summit_id)));
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            }
        );
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Get(
        path: "/summits/{id}/members/csv",
        operationId: "getAllMembersBySummitCSV",
        description: "required-groups " . IGroup::SummitAdministrators . ", " . IGroup::SuperAdmins . ", " . IGroup::Administrators,
        tags: ["Summit Members"],
        x: [
            'required-groups' => [
                IGroup::SummitAdministrators,
                IGroup::SuperAdmins,
                IGroup::Administrators
            ]
        ],
        security: [['summit_members_oauth2' => [
            SummitScopes::ReadAllSummitData,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "page", description: "Page number", in: "query", schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "per_page", description: "Items per page", in: "query", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "filter", description: "Filter conditions", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "order", description: "Order by", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "expand", description: "Expand relations", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "fields", description: "Fields to return", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "relations", description: "Relations to include", in: "query", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "columns", description: "CSV columns", in: "query", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: "CSV export", content: new OA\MediaType(mediaType: "text/csv")),
            new OA\Response(response: HttpResponse::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: HttpResponse::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getAllBySummitCSV($summit_id)
    {
        $values = Request::all();

        $allowed_columns = [
            "id",
            "created",
            "last_edited",
            "first_name",
            "last_name",
            "email",
            "country",
            "gender",
            "github_user",
            "bio",
            "linked_in",
            "irc",
            "twitter",
            "state",
            "country",
            "active",
            "email_verified",
            "pic",
            "affiliations",
            "groups"
        ];

        $rules = [
        ];

        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page = 1;
            $per_page = PHP_INT_MAX;

            if (Request::has('page')) {
                $page = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'irc' => ['=@', '==', '@@'],
                    'twitter' => ['=@', '==', '@@'],
                    'first_name' => ['=@', '==', '@@'],
                    'last_name' => ['=@', '==', '@@'],
                    'email' => ['=@', '==', '@@'],
                    'group_slug' => ['=@', '==', '@@'],
                    'group_id' => ['=='],
                    'email_verified' => ['=='],
                    'active' => ['=='],
                    'github_user' => ['=@', '==', '@@'],
                    'full_name' => ['=@', '==', '@@'],
                    'created' => ['>', '<', '<=', '>=', '==','[]'],
                    'last_edited' => ['>', '<', '<=', '>=', '==','[]'],
                    'schedule_event_id' => ['=='],
                ]);
            }

            if (is_null($filter)) $filter = new Filter();

            $filter->validate([
                'irc' => 'sometimes|required|string',
                'twitter' => 'sometimes|required|string',
                'first_name' => 'sometimes|required|string',
                'last_name' => 'sometimes|required|string',
                'email' => 'sometimes|required|string',
                'group_slug' => 'sometimes|required|string',
                'group_id' => 'sometimes|required|integer',
                'email_verified' => 'sometimes|required|boolean',
                'active' => 'sometimes|required|boolean',
                'github_user' => 'sometimes|required|string',
                'full_name' => 'sometimes|required|string',
                'created' => 'sometimes|required|date_format:U|epoch_seconds',
                'last_edited' => 'sometimes|required|date_format:U|epoch_seconds',
                'schedule_event_id' => 'sometimes|required|integer',
            ]);

            $order = null;

            if (Request::has('order')) {
                $order = OrderParser::parse(Request::input('order'), [
                    'first_name',
                    'last_name',
                    'id',
                    'created',
                    'last_edited',
                ]);
            }

            $filter->addFilterCondition(FilterElement::makeEqual("summit_id", $summit_id));
            $data = $this->repository->getAllByPage(new PagingInfo($page, $per_page), $filter, $order);

            $filename = "members-" . date('Ymd');

            $fields = Request::input('fields', '');
            $fields = !empty($fields) ? explode(',', $fields) : [];
            $relations = Request::input('relations', '');
            $relations = !empty($relations) ? explode(',', $relations) : [];

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

            $list = $data->toArray
            (
                Request::input('expand', ''),
                $fields,
                $relations,
                [],
                SerializerRegistry::SerializerType_Private
            );

            return $this->export
            (
                'csv',
                $filename,
                $list['data'],
                [
                    'created' => new EpochCellFormatter(),
                    'last_edited' => new EpochCellFormatter(),
                    'affiliations' => new CurrentAffiliationsCellFormatter(),
                ],
                $columns
            );
        } catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        } catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412($ex2->getMessages());
        } catch (FilterParserException $ex3) {
            Log::warning($ex3);
            return $this->error412($ex3->getMessages());
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    use ValidateEventUri;

    use GetAndValidateJsonPayload;

    /**
     * @param $summit_id
     * @param $member_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Post(
        path: "/summits/{id}/members/me/schedule/shareable-link",
        operationId: "createScheduleShareableLink",
        description: "Create a shareable link for member schedule",
        tags: ["Summit Members"],
        security: [['summit_members_oauth2' => [
            SummitScopes::AddMyScheduleShareable,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_CREATED, description: "Shareable link created", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: HttpResponse::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: HttpResponse::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function createScheduleShareableLink($summit_id, $member_id)
    {
        return $this->processRequest(function () use ($summit_id, $member_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $link = $this->summit_service->createScheduleShareableLink($summit, $current_member);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($link)->serialize
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
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    #[OA\Delete(
        path: "/summits/{id}/members/me/schedule/shareable-link",
        operationId: "revokeScheduleShareableLink",
        description: "Revoke shareable link for member schedule",
        tags: ["Summit Members"],
        security: [['summit_members_oauth2' => [
            SummitScopes::DeleteMyScheduleShareable,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_NO_CONTENT, description: "Shareable link revoked"),
            new OA\Response(response: HttpResponse::HTTP_FORBIDDEN, description: "Forbidden"),
            new OA\Response(response: HttpResponse::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function revokeScheduleShareableLink($summit_id, $member_id)
    {
        return $this->processRequest(function () use ($summit_id, $member_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $link = $this->summit_service->revokeScheduleShareableLink($summit, $current_member);

            return $this->deleted();

        });
    }

    /**
     * @param $summit_id
     * @param $cid
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|mixed
     */
    #[OA\Get(
        path: "/summits/{id}/members/me/schedule/{cid}/ics",
        operationId: "getCalendarFeedICS",
        description: "Get calendar feed in ICS format for member schedule",
        tags: ["Summit Members"],
        security: [['summit_members_oauth2' => [
            SummitScopes::MeRead,
        ]]],
        parameters: [
            new OA\Parameter(name: "id", description: "Summit ID", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "cid", description: "Calendar ID", in: "path", required: true, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: HttpResponse::HTTP_OK, description: "ICS calendar feed", content: new OA\MediaType(mediaType: "text/calendar")),
            new OA\Response(response: HttpResponse::HTTP_NOT_FOUND, description: "Not found"),
            new OA\Response(response: HttpResponse::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    public function getCalendarFeedICS($summit_id, $cid)
    {
        return $this->processRequest(function () use ($summit_id, $cid) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $feedBody = $this->summit_service->buildICSFeed($summit, $cid);

            return $this->rawContent($feedBody, [
                'Content-type' => 'text/calendar',
            ]);
        });
    }
}