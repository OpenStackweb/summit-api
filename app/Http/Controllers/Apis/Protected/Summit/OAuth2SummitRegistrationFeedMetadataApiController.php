<?php namespace App\Http\Controllers;
/**
 * Copyright 2020 OpenStack Foundation
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

use App\Models\Foundation\Summit\Repositories\ISummitRegistrationFeedMetadataRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IBaseRepository;
use models\utils\IEntity;
use services\model\ISummitService;

/**
 * Class OAuth2SummitRegistrationFeedMetadataApiController
 * @package App\Http\Controllers
 */
class OAuth2SummitRegistrationFeedMetadataApiController
    extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitService
     */
    private $service;

    /**
     * @param ISummitRegistrationFeedMetadataRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRegistrationFeedMetadataRepository $repository,
        ISummitRepository $summit_repository,
        ISummitService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    use GetAllBySummit;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use DeleteSummitChildElement;

    /**
     * @return array
     */
    protected function getFilterRules():array
    {
        return [
            'key' => ['=@', '@@'],
            'value' => ['=@', '@@'],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'key' => 'sometimes|required|string',
            'value' => 'sometimes|required|string',
        ];
    }
    /**
     * @return array
     */
    protected function getOrderRules():array{
        return [
            'id',
            'key',
        ];
    }

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
        return [
            'key' => 'required|string',
            'value' => 'required|string',
        ];
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->addRegistrationFeedMetadata($summit, $payload);
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
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
        $this->service->removeRegistrationFeedMetadata($summit, intval($child_id));
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @return IEntity|null
     */
    protected function getChildFromSummit(Summit $summit,$child_id): ?IEntity
    {
        return $summit->getRegistrationFeedMetadataById(intval($child_id));
    }
}