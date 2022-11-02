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
use App\Models\Foundation\Summit\Repositories\ISummitTaxTypeRepository;
use App\Services\Model\ISummitTaxTypeService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IBaseRepository;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use Illuminate\Support\Facades\Log;
use Exception;
/**
 * Class OAuth2SummitTaxTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitTaxTypeApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitTaxTypeService
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

    public function __construct
    (
        ISummitTaxTypeRepository $repository,
        ISummitRepository $summit_repository,
        ISummitTaxTypeService $service,
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
     * @param array $payload
     * @return array
     */
    protected function getAddValidationRules(array $payload): array
    {
        return TaxTypeValidationRulesFactory::build($payload);
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
        return $this->service->addTaxType($summit, $payload);
    }

    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
        return $summit->getTaxTypeById($child_id);
    }

    /**
     * @param array $payload
     * @return array
     */
    function getUpdateValidationRules(array $payload): array
    {
        return TaxTypeValidationRulesFactory::build($payload, true);
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
        return $this->service->updateTaxType($summit, $child_id, $payload);
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @throws EntityNotFoundException
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->deleteTaxType($summit, $child_id);
    }

    /**
     * @param $summit_id
     * @param $tax_id
     * @param $ticket_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addTaxToTicketType($summit_id, $tax_id, $ticket_type_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $child = $this->service->addTaxTypeToTicketType($summit, $tax_id, $ticket_type_id);
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
     * @param $tax_id
     * @param $ticket_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeTaxFromTicketType($summit_id, $tax_id, $ticket_type_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $child = $this->service->removeTaxTypeFromTicketType($summit, $tax_id, $ticket_type_id);
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