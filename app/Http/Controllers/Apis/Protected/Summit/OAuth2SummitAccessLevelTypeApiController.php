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
use App\Models\Foundation\Summit\Repositories\ISummitAccessLevelTypeRepository;
use App\Services\Model\ISummitAccessLevelTypeService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IBaseRepository;
use models\utils\IEntity;
/**
 * Class OAuth2SummitAccessLevelTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitAccessLevelTypeApiController
    extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitAccessLevelTypeService
     */
    private $service;

    use GetAllBySummit;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

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

    public function __construct
    (
        ISummitAccessLevelTypeRepository $repository,
        ISummitRepository $summit_repository,
        ISummitAccessLevelTypeService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
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
     * @param array $payload
     * @return array
     */
    protected function getAddValidationRules(array $payload): array
    {
        return AccessLevelTypeValidationRulesFactory::build($payload);
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->addAccessLevelType($summit, $payload);
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @return IEntity|null
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        return $summit->getBadgeAccessLevelTypeById($child_id);
    }

    /**
     * @param array $payload
     * @return array
     */
    function getUpdateValidationRules(array $payload): array
    {
        return AccessLevelTypeValidationRulesFactory::build($payload, true);
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->updateAccessLevelType($summit, $child_id, $payload);
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @throws EntityNotFoundException
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
       $this->service->deleteAccessLevelType($summit, $child_id);
    }
}