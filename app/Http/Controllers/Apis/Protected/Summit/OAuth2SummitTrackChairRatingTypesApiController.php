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

use App\Models\Foundation\Summit\Repositories\IPresentationTrackChairRatingTypeRepository;
use App\Models\Foundation\Summit\Repositories\ISelectionPlanRepository;
use App\Services\Model\ITrackChairRankingService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\utils\IBaseRepository;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;

/**
 * Class OAuth2SummitTrackChairRatingTypesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitTrackChairRatingTypesApiController
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
     * @param IPresentationTrackChairRatingTypeRepository $repository
     * @param ITrackChairRankingService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISelectionPlanRepository $selection_plan_repository,
        IPresentationTrackChairRatingTypeRepository $repository,
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
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getTrackChairRatingTypes($summit_id, $selection_plan_id) {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
        if (is_null($summit)) return $this->error404();

        $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
        if (is_null($selection_plan)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'selection_plan_id' => ['=='],
                    'name' => ['@@','=@','==']
                ];
            },
            function () {
                return [
                    'selection_plan_id' => 'sometimes|integer',
                    'name'=> 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'order',
                    'name'
                ];
            },
            function ($filter) use ($selection_plan) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('selection_plan_id', $selection_plan->getId()));
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
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getTrackChairRatingType($summit_id, $selection_plan_id, $type_id) {

        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $track_chair_rating_type = $this->service->getTrackChairRatingType($selection_plan, intval($type_id));
            if (is_null($track_chair_rating_type)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($track_chair_rating_type)
                ->serialize
                (
                    self::getExpands(),
                    self::getFields(),
                    self::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @return IEntity
     */
    public function addTrackChairRatingType($summit_id, $selection_plan_id) {

        return $this->processRequest(function () use ($summit_id, $selection_plan_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $payload = $this->getJsonPayload(RatingTypeValidationRulesFactory::buildForAdd());

            $track_chair_rating_type = $this->service->addTrackChairRatingType($selection_plan, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($track_chair_rating_type)
                ->serialize
                (
                    self::getExpands(),
                    self::getFields(),
                    self::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function updateTrackChairRatingType($summit_id, $selection_plan_id, $type_id) {

        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $payload = $this->getJsonPayload(RatingTypeValidationRulesFactory::buildForUpdate());

            $track_chair_rating_type = $this->service->updateTrackChairRatingType($selection_plan, intval($type_id), $payload);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($track_chair_rating_type)
                ->serialize
                (
                    self::getExpands(),
                    self::getFields(),
                    self::getRelations()
                ));
        });
    }

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteTrackChairRatingType($summit_id, $selection_plan_id, $type_id) {

        return $this->processRequest(function () use ($summit_id, $selection_plan_id, $type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find(intval($summit_id));
            if (is_null($summit)) return $this->error404();

            $selection_plan = $this->selection_plan_repository->getById(intval($selection_plan_id));
            if (is_null($selection_plan)) return $this->error404();

            $this->service->deleteTrackChairRatingType($selection_plan, $type_id);

            return $this->deleted();
        });
    }
}