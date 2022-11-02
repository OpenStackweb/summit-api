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
use App\Models\Foundation\Summit\Repositories\ISponsorshipTypeRepository;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\ISponsorshipTypeService;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
/**
 * Class OAuth2SponsorshipTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SponsorshipTypeApiController extends OAuth2ProtectedController
{

    use RequestProcessor;
    /**
     * @var ISponsorshipTypeService
     */
    private $service;

    /**
     * OAuth2SponsorshipTypeApiController constructor.
     * @param ISponsorshipTypeRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISponsorshipTypeService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISponsorshipTypeRepository $repository,
        ISummitRepository $summit_repository,
        ISponsorshipTypeService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
        $this->service = $service;
    }

    use GetAll;

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
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    use GetAndValidateJsonPayload;
    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function add()
    {
        return $this->processRequest(function(){

            $payload = $this->getJsonPayload(
                SponsorshipTypeValidationRulesFactory::buildForAdd(),
                true
            );

            $sponsorship_type = $this->service->addSponsorShipType($payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($sponsorship_type)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function get($id)
    {
        return $this->processRequest(function() use($id){
            $sponsorship_type = $this->repository->getById($id);
            if(is_null($sponsorship_type))
                return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($sponsorship_type)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function update($id)
    {
        return $this->processRequest(function() use($id){
            $payload = $this->getJsonPayload(
                SponsorshipTypeValidationRulesFactory::buildForUpdate(),
                true
            );

            $sponsorship_type = $this->service->updateSponsorShipType($id, $payload);

            return $this->update(SerializerRegistry::getInstance()->getSerializer($sponsorship_type)->serialize
            (
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function delete($id)
    {
        return $this->processRequest(function() use($id){
            $this->service->deleteSponsorShipType($id);
            return $this->deleted();
        });
    }
}