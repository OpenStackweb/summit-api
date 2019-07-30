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
use models\summit\SummitTaxType;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitTicketType;
/**
 * Interface ISummitTaxTypeService
 * @package App\Services\Model
 */
interface ISummitTaxTypeService
{
    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitTaxType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addTaxType(Summit $summit, array $data):SummitTaxType;

    /**
     * @param Summit $summit
     * @param int $tax_type_id
     * @param array $data
     * @return SummitTicketType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateTaxType(Summit $summit, int $tax_type_id, array $data):SummitTaxType;

    /**
     * @param Summit $summit
     * @param int $tax_type_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteTaxType(Summit $summit, int $tax_type_id);

    /**
     * @param Summit $summit
     * @param int $tax_type_id
     * @param int $ticket_type_id
     * @return SummitTaxType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addTaxTypeToTicketType(Summit $summit, int $tax_type_id, int $ticket_type_id):SummitTaxType;

    /**
     * @param Summit $summit
     * @param int $tax_type_id
     * @param int $ticket_type_id
     * @return SummitTaxType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removeTaxTypeFromTicketType(Summit $summit, int $tax_type_id, int $ticket_type_id):SummitTaxType;
}