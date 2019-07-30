<?php namespace services\model;
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
use models\summit\Sponsor;
use models\summit\Summit;
/**
 * Interface ISummitSponsorService
 * @package services\model
 */
interface ISummitSponsorService
{
    /**
     * @param Summit $summit
     * @param array $payload
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSponsor(Summit $summit, array $payload):Sponsor;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param array $payload
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateSponsor(Summit $summit, int $sponsor_id, array $payload):Sponsor;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSponsor(Summit $summit, int $sponsor_idd):void;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $member_id
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSponsorUser(Summit $summit, int $sponsor_id, int $member_id):Sponsor;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $member_id
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removeSponsorUser(Summit $summit, int $sponsor_id, int $member_id):Sponsor;
}