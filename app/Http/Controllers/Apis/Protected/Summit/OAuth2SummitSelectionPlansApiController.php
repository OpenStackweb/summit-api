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
use App\Models\Foundation\Summit\Repositories\IPresentationActionTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISelectionPlanRepository;
use App\Models\Foundation\Summit\Repositories\ISummitCategoryChangeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitSelectionPlanExtraQuestionTypeRepository;
use App\ModelSerializers\SerializerUtils;
use App\Rules\Boolean;
use App\Services\Model\ISelectionPlanExtraQuestionTypeService;
use App\Services\Model\ISummitSelectionPlanService;
use Illuminate\Http\Request as LaravelRequest;
use libs\utils\HTMLCleaner;
use libs\utils\PaginationValidationRules;
use models\exceptions\EntityNotFoundException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use ModelSerializers\IPresentationSerializerTypes;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;
use utils\PagingInfo;

/**
 * Class OAuth2SummitSelectionPlansApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSelectionPlansApiController extends OAuth2ProtectedController
{
    use RequestProcessor;

    use GetAndValidateJsonPayload;

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
     * ISelectionPlanOrderExtraQuestionTypeService
     */
    private $selection_plan_extra_questions_service;

    /**
     * @var ISummitSelectionPlanExtraQuestionTypeRepository
     */
    private $selection_plan_extra_questions_repository;

    /**
     * @var IPresentationActionTypeRepository
     */
    private $presentation_action_repository;

    /**
     * OAuth2SummitSelectionPlansApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitEventRepository $summit_event_repository
     * @param ISummitCategoryChangeRepository $category_change_request_repository
     * @param ISelectionPlanRepository $selection_plan_repository
     * @param ISummitSelectionPlanExtraQuestionTypeRepository $selection_plan_extra_questions_repository
     * @param IPresentationActionTypeRepository $presentation_action_repository
     * @param ISummitSelectionPlanService $selection_plan_service
     * @param ISelectionPlanExtraQuestionTypeService $selection_plan_extra_questions_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository                               $summit_repository,
        ISummitEventRepository                          $summit_event_repository,
        ISummitCategoryChangeRepository                 $category_change_request_repository,
        ISelectionPlanRepository                        $selection_plan_repository,
        ISummitSelectionPlanExtraQuestionTypeRepository $selection_plan_extra_questions_repository,
        IPresentationActionTypeRepository               $presentation_action_repository,
        ISummitSelectionPlanService                     $selection_plan_service,
        ISelectionPlanExtraQuestionTypeService          $selection_plan_extra_questions_service,
        IResourceServerContext                          $resource_server_context
    )
    {
        parent::__construct($resource_server_context);

        $this->repository = $selection_plan_repository;
        $this->summit_repository = $summit_repository;
        $this->summit_event_repository = $summit_event_repository;
        $this->category_change_request_repository = $category_change_request_repository;
        $this->presentation_action_repository = $presentation_action_repository;
        $this->selection_plan_service = $selection_plan_service;
        $this->selection_plan_extra_questions_service = $selection_plan_extra_questions_service;
        $this->selection_plan_extra_questions_repository = $selection_plan_extra_questions_repository;
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return mixed
     */
    public function getSelectionPlan($summit_id, $selection_plan_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($selection_plan)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return mixed
     */
    public function updateSelectionPlan($summit_id, $selection_plan_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SummitSelectionPlanValidationRulesFactory::buildForUpdate());

            $selection_plan = $this->selection_plan_service->updateSelectionPlan($summit, intval($selection_plan_id),
                HTMLCleaner::cleanData($payload, [
                    'submission_period_disclaimer',
                ]));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($selection_plan)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function addSelectionPlan($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SummitSelectionPlanValidationRulesFactory::buildForAdd());

            $selection_plan = $this->selection_plan_service->addSelectionPlan($summit,
                HTMLCleaner::cleanData($payload,
                    [
                        'submission_period_disclaimer',
                    ]));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($selection_plan)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return mixed
     */
    public function deleteSelectionPlan($summit_id, $selection_plan_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->deleteSelectionPlan($summit, intval($selection_plan_id));

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_group_id
     * @return mixed
     */
    public function addTrackGroupToSelectionPlan($summit_id, $selection_plan_id, $track_group_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $track_group_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->addTrackGroupToSelectionPlan($summit, intval($selection_plan_id), intval($track_group_id));

            return $this->updated();
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $track_group_id
     * @return mixed
     */
    public function deleteTrackGroupToSelectionPlan($summit_id, $selection_plan_id, $track_group_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $track_group_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->deleteTrackGroupToSelectionPlan($summit, intval($selection_plan_id), intval($track_group_id));

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $status
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getCurrentSelectionPlanByStatus($summit_id, $status)
    {
        return $this->processRequest(function () use ($summit_id, $status) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $selection_plan = $this->selection_plan_service->getCurrentSelectionPlanByStatus($summit, $status);

            if (is_null($selection_plan))
                return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($selection_plan)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    use ParametrizedGetAll;

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAll($summit_id)
    {
        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@','@@','=='],
                    'status' => ['=='],
                ];
            },
            function () {

                return [
                    'name' => 'sometimes|string',
                    'status' => 'sometimes|string|in:submission,selection,voting',
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'last_edited',
                ];
            },
            function ($filter) use ($summit_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit_id));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            null,
        );
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getSelectionPlanPresentations($summit_id, $selection_plan_id)
    {
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
                    'is_chair_visible' => ['sometimes', new Boolean],
                    'is_voting_visible' => ['sometimes', new Boolean],
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
                    if (!is_null($current_member)) {
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
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getSelectionPlanPresentationsCSV($summit_id, $selection_plan_id)
    {
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
                    'is_chair_visible' => ['sometimes', new Boolean],
                    'is_voting_visible' => ['sometimes', new Boolean],
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
                    $filter->addFilterCondition(FilterElement::makeEqual('selection_plan_id', intval($selection_plan_id)));
                    $current_member = $this->resource_server_context->getCurrentUser(false);
                    if (!is_null($current_member)) {
                        $filter->addFilterCondition(FilterElement::makeEqual('current_member_id', $current_member->getId()));
                    }
                }
                return $filter;
            },
            function () {
                return IPresentationSerializerTypes::TrackChairs_CSV;
            },
            function () {
                return [
                    'start_date' => new EpochCellFormatter(),
                    'end_date' => new EpochCellFormatter(),
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

    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getSelectionPlanPresentation($summit_id, $selection_plan_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan))
                return $this->error404();

            $presentation = $selection_plan->getPresentation(intval($presentation_id));
            if (is_null($presentation))
                throw new EntityNotFoundException();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer
            (
                $presentation,
                IPresentationSerializerTypes::TrackChairs
            )->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function markPresentationAsViewed($summit_id, $selection_plan_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $presentation = $this->selection_plan_service->markPresentationAsViewed
            (
                $summit,
                intval($selection_plan_id),
                intval($presentation_id)
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $presentation,
                IPresentationSerializerTypes::TrackChairs
            )->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addCommentToPresentation($summit_id, $selection_plan_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $payload = $this->getJsonPayload([
                'body' => 'required|string',
                'is_public' => 'required|boolean',
            ]);

            $comment = $this->selection_plan_service->addPresentationComment
            (
                $summit,
                intval($selection_plan_id),
                intval($presentation_id),
                HTMLCleaner::cleanData($payload, ['body'])
            );

            return $this->created(SerializerRegistry::getInstance()->getSerializer($comment)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllPresentationCategoryChangeRequest($summit_id, $selection_plan_id)
    {

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
                    'new_category_name' => ['=@', '=='],
                    'old_category_name' => ['=@', '=='],
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
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function createPresentationCategoryChangeRequest($summit_id, $selection_plan_id, $presentation_id)
    {
        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'new_category_id' => 'required|integer',
            ]);

            $change_request = $this->selection_plan_service->createPresentationCategoryChangeRequest
            (
                $summit,
                intval($selection_plan_id),
                intval($presentation_id),
                intval($payload['new_category_id'])
            );

            return $this->created(SerializerRegistry::getInstance()->getSerializer($change_request)
                ->serialize(
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
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
        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $presentation_id, $category_change_request_id) {


            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload([
                'approved' => 'required|bool',
                'reason' => 'sometimes|string',
            ]);

            $change_request = $this->selection_plan_service->resolvePresentationCategoryChangeRequest
            (
                $summit,
                intval($selection_plan_id),
                intval($presentation_id),
                intval($category_change_request_id),
                $payload
            );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($change_request)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * Extra questions
     */


    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getExtraQuestions($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '=='],
                    'type' => ['=@', '=='],
                    'label' => ['=@', '=='],
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|string',
                    'type' => 'sometimes|string',
                    'label' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'label',
                    'order',
                ];
            },
            function ($filter) use ($summit) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition
                    (
                        FilterElement::makeEqual('summit_id', $summit->getId())
                    );
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->selection_plan_extra_questions_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getExtraQuestion($summit_id, $question_id){
        return $this->processRequest(function() use($summit_id, $question_id){
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $question = $summit->getSelectionPlanExtraQuestionById(intval($question_id));
            if (is_null($question)) return $this->error404('Question not found.');

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($question)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }
    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getExtraQuestionsBySelectionPlan($summit_id, $selection_plan_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'name' => ['=@', '=='],
                    'type' => ['=@', '=='],
                    'label' => ['=@', '=='],
                ];
            },
            function () {
                return [
                    'name' => 'sometimes|string',
                    'type' => 'sometimes|string',
                    'label' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'name',
                    'label',
                    'order',
                ];
            },
            function ($filter) use ($summit, $selection_plan_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition
                    (
                        FilterElement::makeEqual('selection_plan_id', intval($selection_plan_id))
                    );
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->selection_plan_extra_questions_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
            ,
            ['selection_plan_id' => intval($selection_plan_id)]
        );
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getExtraQuestionsMetadata($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->ok
        (
            $this->selection_plan_extra_questions_repository->getQuestionsMetadata()
        );
    }


    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getExtraQuestionsMetadataBySelectionPlan($summit_id, $selection_plan_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->ok
        (
            $this->selection_plan_extra_questions_repository->getQuestionsMetadata()
        );
    }

    use ParametrizedAddEntity;

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addExtraQuestion($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit];

        return $this->_add(
            function ($payload) {
                return SelectionPlanExtraQuestionValidationRulesFactory::build($payload);
            },
            function ($payload, $summit) {
                return $this->selection_plan_extra_questions_service->addExtraQuestion($summit, HTMLCleaner::cleanData($payload, ['label']));
            },
            ...$args
        );
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateExtraQuestion($summit_id, $question_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit];

        return $this->_update($question_id, function ($payload) {
            return SelectionPlanExtraQuestionValidationRulesFactory::build($payload, true);
        },
            function ($question_id, $payload, $summit) {
                return $this->selection_plan_extra_questions_service->updateExtraQuestion
                (
                    $summit,
                    intval($question_id),
                    HTMLCleaner::cleanData($payload, ['label'])
                );
            }, ...$args);
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addExtraQuestionAndAssign($summit_id, $selection_plan_id){
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
        if (is_null($selection_plan)) return $this->error404();

        $args = [$selection_plan_id];

        return $this->_add(
            function ($payload) {
                return SelectionPlanExtraQuestionValidationRulesFactory::build($payload);
            },
            function ($payload, $selection_plan_id) {
                  return $this->selection_plan_extra_questions_service->addExtraQuestionAndAssignTo($selection_plan_id, HTMLCleaner::cleanData($payload, ['label']));
            },
            ...$args
        );
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $question_id
     * @return mixed
     */
    public function assignExtraQuestion($summit_id, $selection_plan_id, $question_id){

        return $this->processRequest(function() use($summit_id, $selection_plan_id, $question_id){

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $assigment = $this->selection_plan_extra_questions_service->assignExtraQuestion(intval($selection_plan_id), intval($question_id));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($assigment)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    use ParametrizedGetEntity;

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getExtraQuestionBySelectionPlan($summit_id, $selection_plan_id, $question_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
        if (is_null($selection_plan)) return $this->error404();

        return $this->_get($question_id, function ($id) use ($selection_plan) {
            $q = $selection_plan->getExtraQuestionById(intval($id));
            if(is_null($q)) return null;
            return $selection_plan->getAssignedExtraQuestion($q);
        });
    }

    use ParametrizedUpdateEntity;

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateExtraQuestionBySelectionPlan($summit_id, $selection_plan_id, $question_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
        if (is_null($selection_plan)) return $this->error404();

        $args = [$selection_plan];

        return $this->_update($question_id, function ($payload) {
            return SelectionPlanExtraQuestionValidationRulesFactory::build($payload, true);
        },
            function ($question_id, $payload, $selection_plan) {
                return $this->selection_plan_extra_questions_service->updateExtraQuestionBySelectionPlan
                (
                    $selection_plan,
                    intval($question_id),
                    HTMLCleaner::cleanData($payload, ['label'])
                );
            }, ...$args);
    }

    use ParametrizedDeleteEntity;

    /**
     * @param $summit_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteExtraQuestion($summit_id, $question_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit];

        return $this->_delete(intval($question_id), function ($question_id, $summit) {
            $this->selection_plan_extra_questions_service->deleteExtraQuestion($summit, intval($question_id));
        }, ...$args);
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeExtraQuestion($summit_id, $selection_plan_id, $question_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
        if (is_null($selection_plan)) return $this->error404();

        $args = [$selection_plan_id];

        return $this->_delete(intval($question_id), function ($question_id, $selection_plan_id) {
            $this->selection_plan_extra_questions_service->removeExtraQuestion($selection_plan_id, intval($question_id));
        }, ...$args);
    }

    // Question Values

    /**
     * @param $summit_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addExtraQuestionValue($summit_id, $question_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit, intval($question_id)];

        return $this->_add(
            function ($payload) {
                return ExtraQuestionTypeValueValidationRulesFactory::buildForAdd($payload);
            },
            function ($payload, $summit, $question_id) {
                return $this->selection_plan_extra_questions_service->addExtraQuestionValue
                (
                    $summit, intval($question_id), $payload
                );
            },
            ...$args);
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $question_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addExtraQuestionValueBySelectionPlan($summit_id, $selection_plan_id, $question_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
        if (is_null($selection_plan)) return $this->error404();

        $args = [$summit, intval($question_id)];

        return $this->_add(
            function ($payload) {
                return ExtraQuestionTypeValueValidationRulesFactory::buildForAdd($payload);
            },
            function ($payload, $summit, $question_id) {
                return $this->selection_plan_extra_questions_service->addExtraQuestionValue
                (
                    $summit, intval($question_id), $payload
                );
            },
            ...$args);
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateExtraQuestionValue($summit_id, $question_id, $value_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit, intval($question_id)];

        return $this->_update($value_id, function ($payload) {
            return ExtraQuestionTypeValueValidationRulesFactory::buildForUpdate($payload);
        },
            function ($value_id, $payload, $summit, $question_id) {
                return $this->selection_plan_extra_questions_service->updateExtraQuestionValue
                (
                    $summit,
                    intval($question_id),
                    intval($value_id),
                    $payload
                );
            }, ...$args);
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $question_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateExtraQuestionValueBySelectionPlan($summit_id, $selection_plan_id, $question_id, $value_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
        if (is_null($selection_plan)) return $this->error404();

        $args = [$summit, intval($question_id)];

        return $this->_update($value_id, function ($payload) {
            return ExtraQuestionTypeValueValidationRulesFactory::buildForUpdate($payload);
        },
            function ($value_id, $payload, $summit, $question_id) {
                return $this->selection_plan_extra_questions_service->updateExtraQuestionValue
                (
                    $summit,
                    intval($question_id),
                    intval($value_id),
                    $payload
                );
            }, ...$args);
    }

    /**
     * @param $summit_id
     * @param $question_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteExtraQuestionValue($summit_id, $question_id, $value_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $args = [$summit, intval($question_id)];

        return $this->_delete($value_id, function ($value_id, $summit, $question_id) {
            $this->selection_plan_extra_questions_service->deleteExtraQuestionValue($summit, intval($question_id), intval($value_id));
        }
            , ...$args);
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $question_id
     * @param $value_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteExtraQuestionValueBySelectionPlan($summit_id, $selection_plan_id, $question_id, $value_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
        if (is_null($selection_plan)) return $this->error404();

        $args = [$summit, intval($question_id)];

        return $this->_delete($value_id, function ($value_id, $summit, $question_id) {
            $this->selection_plan_extra_questions_service->deleteExtraQuestionValue($summit, intval($question_id), intval($value_id));
        }
            , ...$args);
    }

    /**
     * @param $id
     * @param $selection_plan_id
     * @param $event_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function attachEventType($id, $selection_plan_id, $event_type_id)
    {
        return $this->processRequest(function () use ($id, $selection_plan_id, $event_type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->attachEventTypeToSelectionPlan($summit, intval($selection_plan_id), intval($event_type_id));
            return $this->updated();
        });
    }

    /**
     * @param $id
     * @param $selection_plan_id
     * @param $event_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function detachEventType($id, $selection_plan_id, $event_type_id)
    {
        return $this->processRequest(function () use ($id, $selection_plan_id, $event_type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->detachEventTypeFromSelectionPlan($summit, intval($selection_plan_id), intval($event_type_id));
            return $this->deleted();
        });
    }

    //Allowed Presentation Action Types

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllowedPresentationActionTypes($summit_id, $selection_plan_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
        if (is_null($selection_plan)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'label' => ['=@', '=='],
                    'id' => ['=='],
                ];
            },
            function () {
                return [
                    'label' => 'sometimes|string',
                    'id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'order',
                ];
            },
            function ($filter) use ($summit, $selection_plan_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('selection_plan_id', $selection_plan_id));
                }
                return $filter;
            },
            function () {},
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->presentation_action_repository->getAllByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            },
            [
                'selection_plan_id' => $selection_plan_id,
            ]
        );
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllowedPresentationActionType($summit_id, $selection_plan_id, $type_id) {
        return $this->processRequest(function() use($summit_id, $selection_plan_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $presentation_action_type = $summit->getPresentationActionTypeById(intval($type_id));
            if (is_null($presentation_action_type)) return $this->error404();

            $allowed_presentation_action_type = $selection_plan->getPresentationActionType($presentation_action_type);
            if (is_null($allowed_presentation_action_type)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($allowed_presentation_action_type)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                [
                    'selection_plan_id' => $selection_plan_id,
                ]
            ));
        });
    }

    /**
     * @param $id
     * @param $selection_plan_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addAllowedPresentationActionType($id, $selection_plan_id, $type_id)
    {
        return $this->processRequest(function () use ($id, $selection_plan_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SummitSelectionPlanValidationRulesFactory::buildForAddPresentationActionType());

            $allowed_presentation_action_type = $this->selection_plan_service->upsertAllowedPresentationActionType(
                $summit, intval($selection_plan_id), intval($type_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($allowed_presentation_action_type)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                [
                    'selection_plan_id' => $selection_plan_id,
                ]
            ));
        });
    }

    /**
     * @param $id
     * @param $selection_plan_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateAllowedPresentationActionType($id, $selection_plan_id, $type_id)
    {
        return $this->processRequest(function () use ($id, $selection_plan_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(SummitSelectionPlanValidationRulesFactory::buildForUpdatePresentationActionType());

            $allowed_presentation_action_type = $this->selection_plan_service->upsertAllowedPresentationActionType(
                $summit, intval($selection_plan_id), intval($type_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($allowed_presentation_action_type)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
                [
                    'selection_plan_id' => $selection_plan_id,
                ]
            ));
        });
    }

    /**
     * @param $id
     * @param $selection_plan_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeAllowedPresentationActionType($id, $selection_plan_id, $type_id)
    {
        return $this->processRequest(function () use ($id, $selection_plan_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->removeAllowedPresentationActionType($summit, intval($selection_plan_id), intval($type_id));
            return $this->deleted();
        });
    }

    /**
     * Allowed Members
     */

    /**
     * @param $id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllowedMembers($id, $selection_plan_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
        if (is_null($summit)) return $this->error404();

        $selection_plan = $summit->getSelectionPlanById(intval($selection_plan_id));
        if(is_null($selection_plan)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'email' => ['@@', '=@']
                ];
            },
            function () {
                return [
                    'email' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'email',
                ];
            },
            function ($filter) use ($summit, $selection_plan_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('id', $selection_plan_id));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Admin;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) {
                return $this->repository->getAllAllowedMembersByPage
                (
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }
    /**
     * @param $id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addAllowedMember($id, $selection_plan_id){
        return $this->processRequest(function () use ($id, $selection_plan_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(['email' => 'required|email'], true);

            $allowed_member = $this->selection_plan_service->addAllowedMember($summit, intval($selection_plan_id), $payload['email']);

            return $this->created(
                SerializerRegistry::getInstance()->getSerializer($allowed_member)->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
        });
    }

    /**
     * @param $id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeAllowedMember($id, $selection_plan_id, $allowed_member_id){
        return $this->processRequest(function () use ($id, $selection_plan_id, $allowed_member_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($id);
            if (is_null($summit)) return $this->error404();

            $this->selection_plan_service->removeAllowedMember($summit, intval($selection_plan_id), $allowed_member_id);
            return $this->deleted();
        });
    }

    /**
     * @param LaravelRequest $request
     * @param $id
     * @param $selection_plan_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function importAllowedMembers(LaravelRequest $request, $id, $selection_plan_id)
    {

        return $this->processRequest(function () use ($request, $id, $selection_plan_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $this->selection_plan_service->importAllowedMembers($summit,$selection_plan_id, $file);

            return $this->ok();

        });
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getMySelectionPlans($id){
        return $this->processRequest(function() use($id){

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $filter = new Filter();
            $filter->addFilterCondition(FilterParser::buildFilter('summit_id','==', intval($id)));
            $filter->addFilterCondition(FilterParser::buildFilter('is_enabled','==',true));
            $filter->addFilterCondition(FilterParser::buildFilter('allowed_member_email','==', $current_member->getEmail()));

            $page = $this->repository->getAllByPage(new PagingInfo(1,PaginationValidationRules::PerPageMax), $filter);

            return $this->ok($page->toArray(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }
}