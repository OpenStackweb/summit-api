<?php namespace App\Http\Controllers;
/**
 * Copyright 2018 OpenStack Foundation
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
use App\Models\Exceptions\AuthzException;
use App\Models\Foundation\Summit\Repositories\ISummitCategoryChangeRepository;
use App\Services\Model\ISummitSelectionPlanService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Request;
use libs\utils\HTMLCleaner;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use Exception;
use ModelSerializers\IPresentationSerializerTypes;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class OAuth2SummitSelectionPlansApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSelectionPlansApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitEventRepository
     */
    private $summit_event_repository;

    /**
     * @var ISummitSelectionPlanService
     */
    private $selection_plan_service;

    /**
     * @var ISummitCategoryChangeRepository
     */
    private $category_change_request_repository;

    /**
     * OAuth2SummitSelectionPlansApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitEventRepository $summit_event_repository
     * @param ISummitCategoryChangeRepository $category_change_request_repository
     * @param ISummitSelectionPlanService $selection_plan_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitEventRepository $summit_event_repository,
        ISummitCategoryChangeRepository $category_change_request_repository,
        ISummitSelectionPlanService $selection_plan_service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);

        $this->summit_repository = $summit_repository;
        $this->summit_event_repository = $summit_event_repository;
        $this->category_change_request_repository = $category_change_request_repository;
        $this->selection_plan_service = $selection_plan_service;
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return mixed
     */
    public function getSelectionPlan($summit_id, $selection_plan_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if (is_null($selection_plan)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($selection_plan)->serialize(Request::input('expand', '')));
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return mixed
     */
    public function updateSelectionPlan($summit_id, $selection_plan_id)
    {
        try {

            if (!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = SummitSelectionPlanValidationRulesFactory::build($payload, true);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $selection_plan = $this->selection_plan_service->updateSelectionPlan($summit, $selection_plan_id, $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($selection_plan)->serialize());
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function addSelectionPlan($summit_id)
    {
        try {

            if (!Request::isJson()) return $this->error400();
            $payload = Request::json()->all();

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $rules = SummitSelectionPlanValidationRulesFactory::build($payload);
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $selection_plan = $this->selection_plan_service->addSelectionPlan($summit, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($selection_plan)->serialize());
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return mixed
     */
    public function deleteSelectionPlan($summit_id, $selection_plan_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->deleteSelectionPlan($summit, $selection_plan_id);

            return $this->deleted();
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_group_id
     * @return mixed
     */
    public function addTrackGroupToSelectionPlan($summit_id, $selection_plan_id, $track_group_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->addTrackGroupToSelectionPlan($summit, $selection_plan_id, $track_group_id);

            return $this->deleted();
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_group_id
     * @return mixed
     */
    public function deleteTrackGroupToSelectionPlan($summit_id, $selection_plan_id, $track_group_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->deleteTrackGroupToSelectionPlan($summit, $selection_plan_id, $track_group_id);

            return $this->deleted();
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $status
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getCurrentSelectionPlanByStatus($summit_id, $status)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_plan = $this->selection_plan_service->getCurrentSelectionPlanByStatus($summit, $status);

            if (is_null($selection_plan)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($selection_plan)->serialize(Request::input('expand', '')));
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    use ParametrizedGetAll;

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getSelectionPlanPresentations($summit_id, $selection_plan_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $member = $this->resource_server_context->getCurrentUser();

            if (is_null($member))
                return $this->error403();

            $authz = $summit->isTrackChair($member) || $summit->isTrackChairAdmin($member);

            if (!$authz)
                return $this->error403();

            return $this->_getAll(
                function () {
                    return [
                        'title' => ['=@', '=='],
                        'abstract' => ['=@', '=='],
                        'social_summary' => ['=@', '=='],
                        'tags' => ['=@', '=='],
                        'level' => ['=@', '=='],
                        'summit_type_id' => ['=='],
                        'event_type_id' => ['=='],
                        'track_id' => ['=='],
                        'speaker_id' => ['=='],
                        'speaker' => ['=@', '=='],
                        'speaker_email' => ['=@', '=='],
                        'selection_status' => ['=='],
                        'id' => ['=='],
                        'selection_plan_id' => ['=='],
                        'status' => ['=='],
                        'is_chair_visible' => ['=='],
                        'is_voting_visible' => ['=='],
                        'track_chairs_status' => ['=='],
                        'viewed_status' => ['=='],
                        'actions' => ['=='],
                    ];
                },
                function () {
                    return [
                        'title' => 'sometimes|string',
                        'abstract' => 'sometimes|string',
                        'social_summary' => 'sometimes|string',
                        'tags' => 'sometimes|string',
                        'level' => 'sometimes|string',
                        'summit_type_id' => 'sometimes|integer',
                        'event_type_id' => 'sometimes|integer',
                        'track_id' => 'sometimes|integer',
                        'speaker_id' => 'sometimes|integer',
                        'speaker' => 'sometimes|string',
                        'speaker_email' => 'sometimes|string',
                        'selection_status' => 'sometimes|string',
                        'id' => 'sometimes|integer',
                        'selection_plan_id' => 'sometimes|integer',
                        'status' => 'sometimes|string',
                        'is_chair_visible' => 'sometimes|boolean',
                        'is_voting_visible' => 'sometimes|boolean',
                        'track_chairs_status' => 'sometimes|string|in:voted,untouched,team_selected,selected,maybe,pass',
                        'viewed_status' => 'sometimes|string|in:seen,unseen,moved',
                        'actions' => 'sometimes|string',
                    ];
                },
                function () {
                    return [
                        'id',
                        'title',
                        'start_date',
                        'end_date',
                        'created',
                        'track',
                        'location',
                        'trackchairsel',
                        'last_edited',
                    ];
                },
                function ($filter) use ($summit, $selection_plan_id) {
                    if ($filter instanceof Filter) {
                        $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                        $filter->addFilterCondition(FilterElement::makeEqual('selection_plan_id', $selection_plan_id));
                        $current_member = $this->resource_server_context->getCurrentUser(false);
                        if(!is_null($current_member)) {
                            $filter->addFilterCondition(FilterElement::makeEqual('current_member_id', $current_member->getId()));
                        }
                    }
                    return $filter;
                },
                function () {
                    return IPresentationSerializerTypes::TrackChairs;
                },
                null,
                null,
                function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                    return $this->summit_event_repository->getAllByPage
                    (
                        new PagingInfo($page, $per_page),
                        call_user_func($applyExtraFilters, $filter),
                        $order
                    );
                }
            );
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getSelectionPlanPresentationsCSV($summit_id, $selection_plan_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $member = $this->resource_server_context->getCurrentUser();

            if (is_null($member))
                return $this->error403();

            $authz = $summit->isTrackChair($member) || $summit->isTrackChairAdmin($member);

            if (!$authz)
                return $this->error403();

            return $this->_getAllCSV(
                function () {
                    return [
                        'title' => ['=@', '=='],
                        'abstract' => ['=@', '=='],
                        'social_summary' => ['=@', '=='],
                        'tags' => ['=@', '=='],
                        'level' => ['=@', '=='],
                        'summit_type_id' => ['=='],
                        'event_type_id' => ['=='],
                        'track_id' => ['=='],
                        'speaker_id' => ['=='],
                        'speaker' => ['=@', '=='],
                        'speaker_email' => ['=@', '=='],
                        'selection_status' => ['=='],
                        'id' => ['=='],
                        'selection_plan_id' => ['=='],
                        'status' => ['=='],
                        'is_chair_visible' => ['=='],
                        'is_voting_visible' => ['=='],
                        'track_chairs_status' => ['=='],
                        'viewed_status' => ['=='],
                        'actions' => ['=='],
                    ];
                },
                function () {
                    return [
                        'title' => 'sometimes|string',
                        'abstract' => 'sometimes|string',
                        'social_summary' => 'sometimes|string',
                        'tags' => 'sometimes|string',
                        'level' => 'sometimes|string',
                        'summit_type_id' => 'sometimes|integer',
                        'event_type_id' => 'sometimes|integer',
                        'track_id' => 'sometimes|integer',
                        'speaker_id' => 'sometimes|integer',
                        'speaker' => 'sometimes|string',
                        'speaker_email' => 'sometimes|string',
                        'selection_status' => 'sometimes|string',
                        'id' => 'sometimes|integer',
                        'selection_plan_id' => 'sometimes|integer',
                        'status' => 'sometimes|string',
                        'is_chair_visible' => 'sometimes|boolean',
                        'is_voting_visible' => 'sometimes|boolean',
                        'track_chairs_status' => 'sometimes|string|in:voted,untouched,team_selected,selected,maybe,pass',
                        'viewed_status' => 'sometimes|string|in:seen,unseen,moved',
                        'actions' => 'sometimes|string',
                    ];
                },
                function () {
                    return [
                        'id',
                        'title',
                        'start_date',
                        'end_date',
                        'created',
                        'track',
                        'location',
                        'trackchairsel',
                        'last_edited',
                    ];
                },
                function ($filter) use ($summit, $selection_plan_id) {
                    if ($filter instanceof Filter) {
                        $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                        $filter->addFilterCondition(FilterElement::makeEqual('selection_plan_id', $selection_plan_id));
                        $current_member = $this->resource_server_context->getCurrentUser(false);
                        if(!is_null($current_member)) {
                            $filter->addFilterCondition(FilterElement::makeEqual('current_member_id', $current_member->getId()));
                        }
                    }
                    return $filter;
                },
                function () {
                    return IPresentationSerializerTypes::TrackChairs;
                },
                function () {
                    return [
                        'created' => new EpochCellFormatter(),
                        'last_edited' => new EpochCellFormatter(),
                    ];
                },
                function () {
                    return [];
                },
                'presentations-',
                [],
                function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                    return $this->summit_event_repository->getAllByPage
                    (
                        new PagingInfo($page, $per_page),
                        call_user_func($applyExtraFilters, $filter),
                        $order
                    );
                }
            );
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getSelectionPlanPresentation($summit_id, $selection_plan_id, $presentation_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $presentation = $selection_plan->getPresentation(intval($presentation_id));
            if(is_null($presentation)) throw new EntityNotFoundException();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer
            (
                $presentation
            )->serialize(Request::input('expand', '')));

        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        } catch (AuthzException $ex) {
            Log::warning($ex);
            return $this->error403($ex);
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function markPresentationAsViewed($summit_id, $selection_plan_id, $presentation_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $presentation = $this->selection_plan_service->markPresentationAsViewed($summit, $selection_plan_id, $presentation_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $presentation,
                IPresentationSerializerTypes::TrackChairs
            )->serialize(Request::input('expand', '')));
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        }
        catch (AuthzException $ex) {
            Log::warning($ex);
            return $this->error403($ex);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addCommentToPresentation($summit_id, $selection_plan_id, $presentation_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $data = Request::json();
            $payload = $data->all();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, [
                'body' => 'required|string',
                'is_public' => 'required|boolean',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();
                return $this->error412
                (
                    $messages
                );
            }

            $comment = $this->selection_plan_service->addPresentationComment($summit, $selection_plan_id, $presentation_id, HTMLCleaner::cleanData($payload, ['body']));
            return $this->created(SerializerRegistry::getInstance()->getSerializer($comment)->serialize(Request::input('expand', '')));
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        }
        catch (AuthzException $ex) {
            Log::warning($ex);
            return $this->error403($ex);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }


    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllPresentationCategoryChangeRequest($summit_id, $selection_plan_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $member = $this->resource_server_context->getCurrentUser();

            if (is_null($member))
                return $this->error403();

            $authz = $summit->isTrackChair($member) || $summit->isTrackChairAdmin($member);

            if (!$authz)
                return $this->error403();

            return $this->_getAll(
                function () {
                    return [
                        'selection_plan_id' => ['=='],
                        'summit_id' => ['=='],
                        'new_category_id' => ['=='],
                        'old_category_id' => ['=='],
                        'new_category_title' => ['=@', '=='],
                        'old_category_title' => ['=@', '=='],
                        'requester_fullname' => ['=@', '=='],
                        'requester_email' => ['=@', '=='],
                        'aprover_fullname' => ['=@', '=='],
                        'aprover_email' => ['=@', '=='],
                        'presentation_title' => ['=@', '=='],
                    ];
                },
                function () {
                    return [
                        'selection_plan_id' => 'sometimes|integer',
                        'summit_id' => 'sometimes|integer',
                        'new_category_id' => 'sometimes|integer',
                        'old_category_id' => 'sometimes|integer',
                        'new_category_name' => 'sometimes|string',
                        'old_category_name' => 'sometimes|string',
                        'requester_fullname' => 'sometimes|string',
                        'aprover_fullname' => 'sometimes|string',
                        'aprover_email' => 'sometimes|string',
                        'presentation_title' => 'sometimes|string',
                    ];
                },
                function () {
                    return [
                        'id',
                        'approval_date',
                        'status',
                        'presentation_title',
                        'new_category_name',
                        'old_category_name',
                        'requester_fullname',
                    ];
                },
                function ($filter) use ($summit, $selection_plan_id) {
                    if ($filter instanceof Filter) {
                        $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                        $filter->addFilterCondition(FilterElement::makeEqual('selection_plan_id', $selection_plan_id));
                    }
                    return $filter;
                },
                function () {
                    return SerializerRegistry::SerializerType_Public;
                },
                null,
                null,
                function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                    return $this->category_change_request_repository->getAllByPage
                    (
                        new PagingInfo($page, $per_page),
                        call_user_func($applyExtraFilters, $filter),
                        $order
                    );
                }
            );

        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        }
        catch (AuthzException $ex) {
            Log::warning($ex);
            return $this->error403($ex);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function createPresentationCategoryChangeRequest($summit_id, $selection_plan_id, $presentation_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $data = Request::json();
            $payload = $data->all();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, [
                'new_category_id' => 'required|integer',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();
                return $this->error412
                (
                    $messages
                );
            }

            $change_request = $this->selection_plan_service->createPresentationCategoryChangeRequest
            (
                $summit,
                intval($selection_plan_id),
                intval($presentation_id),
                intval($payload['new_category_id'])
            );

            return $this->created(SerializerRegistry::getInstance()->getSerializer($change_request)->serialize(Request::input('expand', '')));
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        }
        catch (AuthzException $ex) {
            Log::warning($ex);
            return $this->error403($ex);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @param $category_change_request_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function resolvePresentationCategoryChangeRequest($summit_id, $selection_plan_id, $presentation_id, $category_change_request_id)
    {
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $data = Request::json();
            $payload = $data->all();

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, [
                'approved' => 'required|bool',
                'reason' => 'sometimes|string',
            ]);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();
                return $this->error412
                (
                    $messages
                );
            }

            $change_request = $this->selection_plan_service->resolvePresentationCategoryChangeRequest
            (
                $summit,
                intval($selection_plan_id),
                intval($presentation_id),
                intval($category_change_request_id),
                $payload
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($change_request)->serialize(Request::input('expand', '')));
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        }
        catch (AuthzException $ex) {
            Log::warning($ex);
            return $this->error403($ex);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}