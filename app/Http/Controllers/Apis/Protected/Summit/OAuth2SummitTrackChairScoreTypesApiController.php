<?php namespace App\Http\Controllers;
/**
 * Copyright 2022 OpenStack Foundation
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

use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Repositories\IPresentationTrackChairRatingTypeRepository;
use App\Models\Foundation\Summit\Repositories\IPresentationTrackChairScoreTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISelectionPlanRepository;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\ITrackChairRankingService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\utils\IBaseRepository;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;

/**
 * Class OAuth2SummitTrackChairScoreTypesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitTrackChairScoreTypesApiController
    extends OAuth2ProtectedController
{
    use GetAndValidateJsonPayload;

    use RequestProcessor;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISelectionPlanRepository
     */
    private $selection_plan_repository;

    /**
     * @var IPresentationTrackChairRatingTypeRepository
     */
    protected $repository;

    /**
     * @var ITrackChairRankingService
     */
    private $service;

    /**
     * OAuth2SummitTrackChairsApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISelectionPlanRepository $selection_plan_repository
     * @param IPresentationTrackChairScoreTypeRepository $repository
     * @param ITrackChairRankingService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISelectionPlanRepository $selection_plan_repository,
        IPresentationTrackChairScoreTypeRepository $repository,
        ITrackChairRankingService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->service = $service;
        $this->summit_repository = $summit_repository;
        $this->selection_plan_repository = $selection_plan_repository;
        $this->repository = $repository;
    }

    use ParametrizedGetAll;

    /**
     * @return IBaseRepository
     */
    protected function getRepository(): IBaseRepository
    {
        return $this->repository;
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $type_id Track Chair Rating Type Id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getTrackChairScoreTypes($summit_id, $selection_plan_id, $type_id) {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
        if (is_null($summit)) return $this->error404();

        $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
        if (is_null($selection_plan)) return $this->error404();

        $track_chair_rating_type = $selection_plan->getTrackChairRatingTypeById(intval($type_id));
        if (is_null($track_chair_rating_type)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'type_id' => ['=='],
                    'name' => ['@@','=@','==']
                ];
            },
            function () {
                return [
                    'type_id' => 'sometimes|integer',
                    'name'=> 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'score',
                    'name',
                ];
            },
            function ($filter) use ($track_chair_rating_type) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('type_id', $track_chair_rating_type->getId()));
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
     * @param $selection_plan_id
     * @param $type_id Track Chair Rating Type Id
     * @param $score_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getTrackChairScoreType($summit_id, $selection_plan_id, $type_id, $score_type_id) {

        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $type_id, $score_type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $track_chair_rating_type = $selection_plan->getTrackChairRatingTypeById(intval($type_id));
            if (is_null($track_chair_rating_type)) return $this->error404();

            $track_chair_score_type = $track_chair_rating_type->getScoreTypeById($score_type_id);
            if (is_null($track_chair_score_type)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($track_chair_score_type)
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
     * @param $selection_plan_id
     * @param $type_id Track Chair Rating Type Id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addTrackChairScoreType($summit_id, $selection_plan_id, $type_id) {

        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $payload = $this->getJsonPayload(ScoreTypeValidationRulesFactory::buildForAdd());

            $track_chair_score_type = $this->service->addTrackChairScoreType($selection_plan, intval($type_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($track_chair_score_type)
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
     * @param $selection_plan_id
     * @param $type_id Track Chair Rating Type Id
     * @param $score_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateTrackChairScoreType($summit_id, $selection_plan_id, $type_id, $score_type_id) {

        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $type_id, $score_type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $payload = $this->getJsonPayload(ScoreTypeValidationRulesFactory::buildForUpdate());

            $track_chair_score_type = $this->service->updateTrackChairScoreType($selection_plan, intval($type_id), intval($score_type_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($track_chair_score_type)
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
     * @param $selection_plan_id
     * @param $type_id Track Chair Rating Type Id
     * @param $score_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteTrackChairScoreType($summit_id, $selection_plan_id, $type_id, $score_type_id) {

        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $type_id, $score_type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $this->service->deleteTrackChairScoreType($selection_plan, intval($type_id), intval($score_type_id));

            return $this->deleted();
        });
    }
}