<?php namespace App\Http\Controllers;
/*
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

use App\Models\Foundation\Summit\Repositories\ISummitSponsorshipTypeRepository;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\ISummitSponsorshipTypeService;
use Illuminate\Http\Request as LaravelRequest;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;

/**
 * Class OAuth2SummitSponsorshipTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSponsorshipTypeApiController
    extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitSponsorshipTypeService
     */
    private $service;

    /**
     * @param ISummitRepository $summit_repository
     * @param ISummitSponsorshipTypeRepository $repository
     * @param ISummitSponsorshipTypeService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitSponsorshipTypeRepository $repository,
        ISummitSponsorshipTypeService $service,
        IResourceServerContext $resource_server_context
    )
    {
        $this->service = $service;
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        parent::__construct($resource_server_context);
    }

    use GetAllBySummit;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    /**
     * @return array
     */
    protected function getFilterRules(): array
    {
        return [
            'name' => ['==', '=@'],
            'label' => ['==', '=@'],
            'size' => ['==', '=@'],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules(): array
    {
        return [
            'name' => 'sometimes|required|string',
            'label' => 'sometimes|required|string',
            'size' => 'sometimes|required|string',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderRules(): array
    {
        return [
            'id',
            'name',
            'order',
            'label',
            'size',
        ];
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->add($summit, $payload);
    }

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
        return SummitSponsorshipTypeValidationRules::buildForAdd($payload);
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @return void
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->delete($summit, intval($child_id));
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @return IEntity|null
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
       return $summit->getSummitSponsorshipTypeById(intval($child_id));
    }

    /**
     * @param array $payload
     * @return array
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SummitSponsorshipTypeValidationRules::buildForUpdate($payload);
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->update($summit,$child_id, $payload);
    }

    use RequestProcessor;

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @param $type_id
     * @return mixed
     * @throws \models\exceptions\EntityNotFoundException
     * @throws \models\exceptions\ValidationException
     */
    public function addBadgeImage(LaravelRequest $request, $summit_id, $type_id){
        return $this->processRequest(function () use ($request, $summit_id, $type_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $image = $this->service->addBadgeImage($summit, $type_id, $file);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($image)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));

        });
    }

    /**
     * @param $summit_id
     * @param $type_id
     * @return mixed
     * @throws \models\exceptions\EntityNotFoundException
     * @throws \models\exceptions\ValidationException
     */
    public function removeBadgeImage($summit_id, $type_id){
        return $this->processRequest(function () use ($summit_id, $type_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->service->deleteBadgeImage($summit, $type_id);

            return $this->deleted();

        });
    }
}