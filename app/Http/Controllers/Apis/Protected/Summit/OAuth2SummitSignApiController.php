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

use App\Models\Foundation\Summit\Repositories\ISummitSignRepository;
use App\Services\Model\ISummitSignService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;

/**
 * Class OAuth2SummitSignApiController
 * @package App\Http\Controller
 */
final class OAuth2SummitSignApiController extends OAuth2ProtectedController
{
    private $summit_repository;

    private $service;

    /**
     * @param ISummitSignRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitSignService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitSignRepository $repository,
        ISummitRepository $summit_repository,
        ISummitSignService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->summit_repository = $summit_repository;
        $this->service = $service;
        $this->repository = $repository;
    }

    use GetAllBySummit;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->add($summit, $payload);
    }

    function getAddValidationRules(array $payload): array
    {
       return [
           'location_id' => 'required|integer',
           'template' => 'required|string'
       ];
    }

    protected function getSummitRepository(): ISummitRepository
    {
       return $this->summit_repository;
    }

    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        return $summit->getSignById(intval($child_id));
    }

    function getUpdateValidationRules(array $payload): array
    {
        return [
            'template' => 'sometimes|string'
        ];
    }

    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->update($summit, $child_id, $payload);
    }

    protected function getFilterRules():array{
        return [
            'location_id' => ['=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'location_id' => 'sometimes|integer',
        ];
    }
    /**
     * @return array
     */
    protected function getOrderRules():array{
        return [
            'id'
        ];
    }
}