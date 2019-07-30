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
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\SponsorshipType;
/**
 * Interface ISponsorshipTypeService
 * @package App\Services\Model
 */
interface ISponsorshipTypeService
{
    /**
     * @param array $payload
     * @return SponsorshipType
     * @throws ValidationException
     */
    public function addSponsorShipType(array $payload):SponsorshipType;

    /**
     * @param int $sponsorship_type_id
     * @param array $payload
     * @return SponsorshipType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateSponsorShipType(int $sponsorship_type_id, array $payload):SponsorshipType;

    /**
     * @param int $sponsorship_type_id
     * @throws EntityNotFoundException
     */
    public function deleteSponsorShipType(int $sponsorship_type_id):void;
}