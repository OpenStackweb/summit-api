<?php namespace App\Http\Controllers;
/*
 * Copyright 2023 OpenStack Foundation
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

use App\Facades\ResourceServerContext;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\IScheduleService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitProposedScheduleEventRepository;
use models\summit\ISummitProposedScheduleLockRepository;
use models\summit\ISummitRepository;
use models\utils\IBaseRepository;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class OAuth2SummitProposedScheduleApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitProposedScheduleApiController extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;

    use RequestProcessor;

    use ParametrizedGetAll;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitProposedScheduleLockRepository
     */
    private $schedule_lock_repository;

    /**
     * @var IScheduleService
     */
    private $service;

    /**
     * @param ISummitRepository $summit_repository
     * @param ISummitProposedScheduleEventRepository $repository
     * @param ISummitProposedScheduleLockRepository $schedule_lock_repository
     * @param IScheduleService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository                      $summit_repository,
        ISummitProposedScheduleEventRepository $repository,
        ISummitProposedScheduleLockRepository  $schedule_lock_repository,
        IScheduleService                       $service,
        IResourceServerContext                 $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->service = $service;
        $this->repository = $repository;
        $this->schedule_lock_repository = $schedule_lock_repository;
        $this->summit_repository = $summit_repository;
    }

    /**
     * @return IBaseRepository
     */
    protected function getRepository(): IBaseRepository
    {
        return $this->repository;
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getProposedScheduleEvents($summit_id, $source)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'start_date' => ['==', '<', '>', '>=', '<='],
                    'end_date' => ['==', '<', '>', '>=', '<='],
                    'duration' => ['==', '<', '>', '>=', '<='],
                    'presentation_title' => ['@@', '=@'],
                    'presentation_id' => ['=='],
                    'location_id' => ['=='],
                    'track_id' => ['=='],
                ];
            },
            function () {
                return [
                    'start_date' => 'sometimes|date_format:U',
                    'end_date' => 'sometimes|required_with:start_date|date_format:U|after:start_date',
                    'duration' => 'sometimes|integer',
                    'presentation_title' => 'sometimes|string',
                    'presentation_id' => 'sometimes|integer',
                    'location_id' => 'sometimes|integer',
                    'track_id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'start_date',
                    'end_date',
                    'presentation_id',
                    'presentation_title',
                    'track_id'
                ];
            },
            function ($filter) use ($summit_id, $source) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', intval($summit_id)));
                    $filter->addFilterCondition(FilterElement::makeEqual('source', $source));
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
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function publish($summit_id, $source, $presentation_id)
    {

        return $this->processRequest(function () use ($summit_id, $source, $presentation_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(ProposedScheduleValidationRulesFactory::buildForAdd());

            $schedule_event =
                $this->service->publishProposedActivityToSource($source, intval($presentation_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($schedule_event)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $presentation_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function unpublish($summit_id, $source, $presentation_id)
    {

        return $this->processRequest(function () use ($summit_id, $source, $presentation_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit))
                return $this->error404();

            $this->service->unPublishProposedActivity($source, intval($presentation_id));

            return $this->deleted();
        });
    }

    use ParseAndGetFilter;

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function publishAll($summit_id, $source)
    {

        return $this->processRequest(function () use ($summit_id, $source) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(ProposedScheduleValidationRulesFactory::buildForUpdate());

            $filter = self::getFilter(function () {
                return [
                    'start_date' => ['==', '<', '>', '>=', '<='],
                    'end_date' => ['==', '<', '>', '>=', '<='],
                    'location_id' => ['=='],
                    'track_id' => ['=='],
                ];
            }, function () {
                return [
                    'start_date' => 'sometimes|date_format:U',
                    'end_date' => 'sometimes|required_with:start_date|date_format:U|after:start_date',
                    'location_id' => 'sometimes|integer',
                    'track_id' => 'sometimes|integer',
                ];
            });

            $schedule = $this->service->publishAll($source, $summit->getId(), $payload, $filter);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($schedule)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }


    /**
     * @param $summit_id
     * @param $source
     * @param $track_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function send2Review($summit_id, $source, $track_id)
    {
        return $this->processRequest(function () use ($summit_id, $source, $track_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(ProposedScheduleLockValidationRulesFactory::buildForAdd());

            $member = ResourceServerContext::getCurrentUser(false);

            $schedule = $this->service->send2Review($summit, $member, $source, intval($track_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($schedule)
                ->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $source
     * @param $track_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeReview($summit_id, $source, $track_id)
    {
        return $this->processRequest(function () use ($summit_id, $source, $track_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(ProposedScheduleLockValidationRulesFactory::buildForUpdate());

            $this->service->removeReview($summit, $source, intval($track_id), $payload);

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $source
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getProposedScheduleReviewSubmissions($summit_id, $source)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'track_id' => ['=='],
                ];
            },
            function () {
                return [
                    'track_id' => 'sometimes|integer',
                ];
            },
            function () {
                return [
                    'track_id'
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Public;
            },
            null,
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit_id, $source) {
                return $this->schedule_lock_repository->getBySummitAndSource
                (
                    $summit_id,
                    $source,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }
}