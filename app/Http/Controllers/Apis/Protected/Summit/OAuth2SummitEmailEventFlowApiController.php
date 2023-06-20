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
use App\Models\Foundation\Summit\Repositories\ISummitEmailEventFlowRepository;
use App\Services\Model\ISummitEmailEventFlowService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
/**
 * Class OAuth2SummitEmailEventFlowApiController
 * @package App\Http\Controllers
 */
class OAuth2SummitEmailEventFlowApiController extends OAuth2ProtectedController
{
    // traits
    use ParametrizedGetAll;

    use UpdateSummitChildElement;

    use GetAllBySummit;

    use GetSummitChildElementById;

    use DeleteSummitChildElement;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitEmailEventFlowService
     */
    private $service;

    /**
     * OAuth2SummitEmailEventFlowApiController constructor.
     * @param ISummitEmailEventFlowRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitEmailEventFlowService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitEmailEventFlowRepository $repository,
        ISummitRepository $summit_repository,
        ISummitEmailEventFlowService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    /**
     * @inheritDoc
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @inheritDoc
     */
    function getUpdateValidationRules(array $payload): array
    {
        return [
            'email_template_identifier' => 'sometimes|required|string',
            'recipient'                 => 'sometimes|string',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->updateEmailEventFlow($summit, $child_id, $payload);
    }

    /**
     * @inheritDoc
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
       return $summit->getEmailEventById($child_id);
    }

    /**
     * @return array
     */
    protected function getFilterRules():array
    {
        return [
            'email_template_identifier' => ['=@', '=='],
            'event_type_name' => ['=@', '=='],
            'flow_name' => ['=@', '=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'email_template_identifier' => 'sometimes|required|string',
            'event_type_name' => 'sometimes|required|string',
            'flow_name' => 'sometimes|required|string',
        ];
    }
    /**
     * @return array
     */
    protected function getOrderRules():array{
        return [
            'id',
            'email_template_identifier',
        ];
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @throws \models\exceptions\EntityNotFoundException
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->deleteEmailEventFlow($summit, $child_id);
    }
}