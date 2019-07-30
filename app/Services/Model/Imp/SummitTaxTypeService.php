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
use App\Models\Foundation\Summit\Factories\SummitTaxTypeFactory;
use models\summit\SummitTaxType;
use App\Models\Foundation\Summit\Repositories\ISummitTaxTypeRepository;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitTicketType;
/**
 * Class SummitTaxTypeService
 * @package App\Services\Model
 */
final class SummitTaxTypeService
    extends AbstractService
    implements ISummitTaxTypeService
{

    /**
     * @var ISummitTaxTypeRepository
     */
    private $repository;

    /**
     * SummitTaxTypeService constructor.
     * @param ISummitTaxTypeRepository $repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitTaxTypeRepository $repository,
        ITransactionService $tx_service
    )
    {
        $this->repository = $repository;
        parent::__construct($tx_service);
    }


    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitTaxType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addTaxType(Summit $summit, array $data): SummitTaxType
    {
        return $this->tx_service->transaction(function() use($summit, $data){
            $name = trim($data['name']);

            $former_tax = $summit->getTaxTypeByName($name);

            if(!is_null($former_tax)) throw new ValidationException("there is another tax type with same name!");

            $tax_type = SummitTaxTypeFactory::build($data);

            $summit->addTaxType($tax_type);

            return $tax_type;
        });
    }

    /**
     * @param Summit $summit
     * @param int $tax_type_id
     * @param array $data
     * @return SummitTicketType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateTaxType(Summit $summit, int $tax_type_id, array $data): SummitTaxType
    {
        return $this->tx_service->transaction(function() use($summit, $tax_type_id, $data){

            $tax_type = $summit->getTaxTypeById($tax_type_id);
            if(is_null($tax_type))
                throw new EntityNotFoundException();

            if(isset($data['name'])) {
                $name = trim($data['name']);

                $former_tax = $summit->getTaxTypeByName($name);

                if (!is_null($former_tax)) throw new ValidationException("there is another tax type with same name!");
            }

            return SummitTaxTypeFactory::populate($tax_type, $data);

        });
    }

    /**
     * @param Summit $summit
     * @param int $tax_type
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteTaxType(Summit $summit, int $tax_type_id)
    {
        $this->tx_service->transaction(function() use($summit, $tax_type_id){
            $tax_type = $summit->getTaxTypeById($tax_type_id);
            if(is_null($tax_type))
                throw new EntityNotFoundException();

            $summit->removeTaxType($tax_type);
        });
    }

    /**
     * @param Summit $summit
     * @param int $tax_type_id
     * @param int $ticket_type_id
     * @return SummitTaxType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addTaxTypeToTicketType(Summit $summit, int $tax_type_id, int $ticket_type_id): SummitTaxType
    {
        return $this->tx_service->transaction(function() use($summit, $tax_type_id, $ticket_type_id){
            $tax_type = $summit->getTaxTypeById($tax_type_id);
            if(is_null($tax_type))
                throw new EntityNotFoundException();

            $ticket_type = $summit->getTicketTypeById($ticket_type_id);
            if(is_null($ticket_type))
                throw new EntityNotFoundException();

            $tax_type->addTicketType($ticket_type);

            return $tax_type;
        });
    }

    /**
     * @param Summit $summit
     * @param int $tax_type_id
     * @param int $ticket_type_id
     * @return SummitTaxType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removeTaxTypeFromTicketType(Summit $summit, int $tax_type_id, int $ticket_type_id): SummitTaxType
    {
        return $this->tx_service->transaction(function() use($summit, $tax_type_id, $ticket_type_id){
            $tax_type = $summit->getTaxTypeById($tax_type_id);
            if(is_null($tax_type))
                throw new EntityNotFoundException();

            $ticket_type = $summit->getTicketTypeById($ticket_type_id);
            if(is_null($ticket_type))
                throw new EntityNotFoundException();

            $tax_type->removeTicketType($ticket_type);

            return $tax_type;
        });
    }
}