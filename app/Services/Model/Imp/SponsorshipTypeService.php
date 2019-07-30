<?php namespace App\Services\Model;
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

use App\Models\Foundation\Main\OrderableChilds;
use App\Models\Foundation\Summit\Factories\SponsorshipTypeFactory;
use App\Models\Foundation\Summit\Repositories\ISponsorshipTypeRepository;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\SponsorshipType;
/**
 * Class SponsorshipTypeService
 * @package App\Services\Model
 */
final class SponsorshipTypeService
    extends AbstractService
    implements ISponsorshipTypeService
{

    use OrderableChilds;
    /**
     * @var ISponsorshipTypeRepository
     */
    private $repository;

    /**
     * SponsorshipTypeService constructor.
     * @param ISponsorshipTypeRepository $repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISponsorshipTypeRepository $repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
    }


    /**
     * @param array $payload
     * @return SponsorshipType
     * @throws ValidationException
     */
    public function addSponsorShipType(array $payload): SponsorshipType
    {
        return $this->tx_service->transaction(function() use($payload){

            $name = trim($payload['name']);
            $former_sponsorship_type = $this->repository->getByName($name);
            if(!is_null($former_sponsorship_type))
                throw new ValidationException("sponsorship type name already exists");


            $label = trim($payload['label']);
            $former_sponsorship_type = $this->repository->getByLabel($label);
            if(!is_null($former_sponsorship_type))
                throw new ValidationException("sponsorship type label already exists");

            $sponsorship_type =  SponsorshipTypeFactory::build($payload);
            $max_order  = $this->repository->getMaxOrder();
            $sponsorship_type->setOrder($max_order + 1);
            $this->repository->add($sponsorship_type);
            return $sponsorship_type;
        });
    }

    /**
     * @param int $sponsorship_type_id
     * @param array $payload
     * @return SponsorshipType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateSponsorShipType(int $sponsorship_type_id, array $payload): SponsorshipType
    {
        return $this->tx_service->transaction(function() use($sponsorship_type_id, $payload){
            $sponsorship_type =  $this->repository->getById($sponsorship_type_id);
            if(is_null($sponsorship_type))
                throw new EntityNotFoundException("sponsorship not found");

            if(isset($payload['name'])) {
                $name = trim($payload['name']);
                $former_sponsorship_type = $this->repository->getByName($name);
                if (!is_null($former_sponsorship_type) && $former_sponsorship_type->getId() != $sponsorship_type_id)
                    throw new ValidationException("sponsorship type name already exists");
            }


            if(isset($payload['label'])) {
                $label = trim($payload['label']);
                $former_sponsorship_type = $this->repository->getByLabel($label);
                if (!is_null($former_sponsorship_type) && $former_sponsorship_type->getId() != $sponsorship_type_id)
                    throw new ValidationException("sponsorship type label already exists");
            }

            if (isset($payload['order']) && intval($payload['order']) != $sponsorship_type->getOrder()) {
                // request to update order
               self::recalculateOrderForCollection($this->repository->getAll(), $sponsorship_type, intval($payload['order']));
            }

            return SponsorshipTypeFactory::populate($sponsorship_type, $payload);
        });
    }

    /**
     * @param int $sponsorship_type_id
     * @throws EntityNotFoundException
     */
    public function deleteSponsorShipType(int $sponsorship_type_id): void
    {
        $this->tx_service->transaction(function() use($sponsorship_type_id){
            $sponsorship_type =  $this->repository->getById($sponsorship_type_id);
            if(is_null($sponsorship_type))
                throw new EntityNotFoundException("sponsorship not found");

            $this->repository->delete($sponsorship_type);

        });
    }
}