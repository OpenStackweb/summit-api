<?php namespace App\Models\Foundation\Summit\Repositories;
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
use models\summit\Summit;
use models\summit\SummitRegistrationInvitation;
use models\utils\IBaseRepository;
/**
 * Interface ISummitRegistrationInvitationRepository
 * @package App\Models\Foundation\Summit\Repositories
 */
interface ISummitRegistrationInvitationRepository extends IBaseRepository
{
    /**
     * @param string $hash
     * @return SummitRegistrationInvitation|null
     */
    public function getByHashExclusiveLock(string $hash):?SummitRegistrationInvitation;

    /**
     * @param Summit $summit
     * @return array|int[]
     */
    public function getAllIdsNonAcceptedPerSummit(Summit $summit):array;

    /**
     * @param string $hash
     * @param Summit $summit
     * @return SummitRegistrationInvitation|null
     */
    public function getByHashAndSummit(string $hash, Summit $summit):?SummitRegistrationInvitation;

    /**
     * @param int $summit_id
     * @return bool
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function deleteAllBySummit(int $summit_id):bool;
}