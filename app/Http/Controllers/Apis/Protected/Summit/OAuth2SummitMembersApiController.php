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

use App\Http\Exceptions\HTTP403ForbiddenException;
use App\Http\Utils\CurrentAffiliationsCellFormatter;
use App\Http\Utils\EpochCellFormatter;
use App\ModelSerializers\SerializerUtils;
use Exception;
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
     * OAuth2SummitMembersApiController constructor.
     * @param IMemberRepository $member_repository
     * @param ISummitRepository $summit_repository
     * @param ISummitService $summit_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IMemberRepository      $member_repository,
        ISummitRepository      $summit_repository,
        ISummitService         $summit_service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->summit_repository = $summit_repository;
        $this->repository = $member_repository;
        $this->summit_service = $summit_service;
    }

    use RequestProcessor;

    /**
     * @param $summit_id
     * @param $member_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
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
                    'created' => ['>', '<', '<=', '>=', '==','>=<'],
                    'last_edited' => ['>', '<', '<=', '>=', '==','>=<'],
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
                    'created' => 'sometimes|required|date_format:U',
                    'last_edited' => 'sometimes|required|date_format:U',
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
                    'created' => ['>', '<', '<=', '>=', '==','>=<'],
                    'last_edited' => ['>', '<', '<=', '>=', '==','>=<'],
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
                'created' => 'sometimes|required|date_format:U',
                'last_edited' => 'sometimes|required|date_format:U',
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
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addEventRSVP($summit_id, $member_id, $event_id)
    {
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'answers' => 'sometimes|rsvp_answer_dto_array',
                'event_uri' => 'sometimes|url',
            ]);

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $event = $summit->getScheduleEvent(intval($event_id));

            if (is_null($event)) {
                return $this->error404();
            }

            $rsvp = $this->summit_service->addRSVP($summit, $current_member, $event_id, $this->validateEventUri($payload));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($rsvp)->serialize
            (
                Request::input('expand', '')
            ));
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412(array($ex->getMessage()));
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        } catch (HTTP403ForbiddenException $ex) {
            Log::warning($ex);
            return $this->error403();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateEventRSVP($summit_id, $member_id, $event_id)
    {
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'answers' => 'sometimes|rsvp_answer_dto_array',
                'event_uri' => 'sometimes|url',
            ]);

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $event = $summit->getScheduleEvent(intval($event_id));

            if (is_null($event)) {
                return $this->error404();
            }

            $rsvp = $this->summit_service->updateRSVP($summit, $current_member, $event_id, $this->validateEventUri($payload));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($rsvp)->serialize
            (
                Request::input('expand', '')
            ));
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412(array($ex->getMessage()));
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404(array('message' => $ex->getMessage()));
        } catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        } catch (HTTP403ForbiddenException $ex) {
            Log::warning($ex);
            return $this->error403();
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $member_id
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteEventRSVP($summit_id, $member_id, $event_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $event = $summit->getScheduleEvent(intval($event_id));

            if (is_null($event)) {
                return $this->error404();
            }

            $this->summit_service->unRSVPEvent($summit, $current_member, $event_id);

            return $this->deleted();

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
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

    /**
     * @param $summit_id
     * @param $member_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function createScheduleShareableLink($summit_id, $member_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $link = $this->summit_service->createScheduleShareableLink($summit, $current_member);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($link)->serialize
            (
                Request::input('expand', '')
            ));
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
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

    /**
     * @param $summit_id
     * @param $member_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function revokeScheduleShareableLink($summit_id, $member_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $link = $this->summit_service->revokeScheduleShareableLink($summit, $current_member);

            return $this->deleted();

        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
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

    /**
     * @param $summit_id
     * @param $cid
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|mixed
     */
    public function getCalendarFeedICS($summit_id, $cid)
    {
        try {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $feedBody = $this->summit_service->buildICSFeed($summit, $cid);

            return $this->rawContent($feedBody, [
                'Content-type' => 'text/calendar',
            ]);
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
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

}