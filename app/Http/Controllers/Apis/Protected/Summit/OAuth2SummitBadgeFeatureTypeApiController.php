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
use App\Models\Foundation\Summit\Repositories\ISummitBadgeFeatureTypeRepository;
use App\Services\Model\ISummitBadgeFeatureTypeService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IBaseRepository;
use models\utils\IEntity;
/**
 * Class OAuth2SummitBadgeFeatureTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitBadgeFeatureTypeApiController
    extends OAuth2ProtectedController

{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitBadgeFeatureTypeService
     */
    private $service;

    /**
     * OAuth2SummitBadgeFeatureTypeApiController constructor.
     * @param ISummitBadgeFeatureTypeRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitBadgeFeatureTypeService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitBadgeFeatureTypeRepository $repository,
        ISummitRepository $summit_repository,
        ISummitBadgeFeatureTypeService $service,
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
            'name' => ['=@', '=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'name' => 'sometimes|required|string',
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
       return SummitBadgeFeatureTypeValidationRulesFactory::build($payload);
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->addBadgeFeatureType($summit, $payload);
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
       $this->service->deleteBadgeFeatureType($summit, $child_id);
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @return IEntity|null
     */
    protected function getChildFromSummit(Summit $summit,$child_id): ?IEntity
    {
       return $summit->getFeatureTypeById($child_id);
    }

    /**
     * @param array $payload
     * @return array
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SummitBadgeFeatureTypeValidationRulesFactory::build($payload, true);
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->updateBadgeFeatureType($summit, $child_id, $payload);
    }
}