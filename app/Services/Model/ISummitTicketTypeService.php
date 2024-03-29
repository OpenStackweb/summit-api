<?php namespace App\Services\Model;
/**
 * Copyright 2018 OpenStack Foundation
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
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\Summit;
use models\summit\SummitTicketType;
use utils\Filter;

/**
 * Interface ISummitTicketTypeService
 * @package App\Services\Model
 */
interface ISummitTicketTypeService
{
    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitTicketType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addTicketType(Summit $summit, array $data);

    /**
     * @param Summit $summit
     * @param int $ticket_type_id
     * @param array $data
     * @return SummitTicketType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateTicketType(Summit $summit, $ticket_type_id, array $data);

    /**
     * @param Summit $summit
     * @param int $ticket_type_id
     * @return SummitTicketType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteTicketType(Summit $summit, $ticket_type_id);

    /**
     * @param Summit $summit
     * @return SummitTicketType[]
     * @throws ValidationException
     */
    public function seedSummitTicketTypesFromEventBrite(Summit $summit);

    /**
     * @param Summit $summit
     * @param Member $member
     * @param string|null $promocode_code
     * @return SummitTicketType[]
     * @throws \Exception
     */
    public function getAllowedTicketTypes(Summit $summit, Member $member, ?string $promocode_code = null): array;

    /**
     * @param Summit $summit
     * @param String $currency_symbol
     * @return void
     * @throws \Exception
     */
    public function updateCurrencySymbol(Summit $summit, string $currency_symbol): void;
}