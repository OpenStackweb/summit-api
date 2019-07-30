<?php namespace App\Http\Controllers;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISummitBadgeTypeRepository;
use App\Services\Model\ISummitBadgeTypeService;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IBaseRepository;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use Exception;
/**
 * Class OAuth2SummitBadgeTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitBadgeTypeApiController extends OAuth2ProtectedController
{

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitBadgeTypeService
     */
    private $service;

    /**
     * OAuth2SummitBadgeFeatureTypeApiController constructor.
     * @param ISummitBadgeTypeRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitBadgeTypeService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitBadgeTypeRepository $repository,
        ISummitRepository $summit_repository,
        ISummitBadgeTypeService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    /**
     * @return array
     */
    protected function getFilterRules():array
    {
        return [
            'name'        => ['=@', '=='],
            'is_default'  => [ '=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'name'       => 'sometimes|required|string',
            'is_default' => 'sometimes|required|boolean',
        ];
    }
    /**
     * @return array
     */
    protected function getOrderRules():array{
        return [
            'id',
            'name',
        ];
    }

    use GetAllBySummit;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
        return SummitBadgeTypeValidationRulesFactory::build($payload);
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
       return $this->service->addBadgeType($summit, $payload);
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @return IResourceServerContext
     */
    protected function getResourceServerContext(): IResourceServerContext
    {
        return $this->resource_server_context;
    }

    /**
     * @return IBaseRepository
     */
    protected function getRepository(): IBaseRepository
    {
        return $this->repository;
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @return void
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->deleteBadgeType($summit, $child_id);
    }

    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
       return $summit->getBadgeTypeById($child_id);
    }

    /**
     * @param array $payload
     * @return array
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SummitBadgeTypeValidationRulesFactory::build($payload, true);
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->updateBadgeType($summit, $child_id, $payload);
    }

    /**
     * @param $summit_id
     * @param $badge_type_id
     * @param $access_level_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addAccessLevelToBadgeType($summit_id, $badge_type_id, $access_level_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $child = $this->service->addAccessLevelToBadgeType($summit, $badge_type_id, $access_level_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($child)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
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
     * @param $badge_type_id
     * @param $access_level_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeAccessLevelFromBadgeType($summit_id, $badge_type_id, $access_level_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $child = $this->service->removeAccessLevelFromBadgeType($summit, $badge_type_id, $access_level_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($child)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
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
     * @param $badge_type_id
     * @param $feature_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addFeatureToBadgeType($summit_id, $badge_type_id, $feature_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $child = $this->service->addFeatureToBadgeType($summit, $badge_type_id, $feature_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($child)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
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
     * @param $badge_type_id
     * @param $feature_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeFeatureFromBadgeType($summit_id, $badge_type_id, $feature_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $child = $this->service->removeFeatureFromBadgeType($summit, $badge_type_id, $feature_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($child)->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
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
}