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

use App\Services\Model\dto\ExternalUserDTO;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Affiliation;
use models\main\Member;

/**
 * Interface IMemberService
 * @package App\Services\Model
 */
interface IMemberService
{

    /**
     * @param Member $member
     * @param array $data
     * @return Affiliation
     */
    public function addAffiliation(Member $member, array $data);

    /**
     * @param Member $member
     * @param int $affiliation_id
     * @param array $data
     * @return Affiliation
     */
    public function updateAffiliation(Member $member, $affiliation_id, array $data);

    /**
     * @param Member $member
     * @param int $affiliation_id
     * @return void
     */
    public function deleteAffiliation(Member $member, $affiliation_id);

    /**
     * @param Member $member
     * @param int $rsvp_id
     * @return void
     */
    public function deleteRSVP(Member $member, $rsvp_id);

    /**
     * @param ExternalUserDTO $userDTO
     * @return Member
     */
    public function registerExternalUser(ExternalUserDTO $userDTO):Member;

    /**
     * @param $user_external_id
     * @return Member
     */
    public function registerExternalUserById($user_external_id):Member;

    /**
     * @param mixed $user_external_id
     * @throws EntityNotFoundException
     */
    public function deleteExternalUserById($user_external_id):void;

    /**
     * @param Member $member
     * @param array $groups
     * @return Member
     */
    public function synchronizeGroups(Member $member, array $groups):Member;

    /**
     * @param string $email
     * @return array|null
     * @throws \Exception
     */
    public function checkExternalUser(string $email);

    /**
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @return array
     * @throws \Exception
     */
    public function emitRegistrationRequest(string $email, string $first_name, string $last_name):array;

    /**
     * @param Member $member
     * @return Member
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function signFoundationMembership(Member $member):Member;

    /**
     * @param Member $member
     * @return Member
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function signCommunityMembership(Member $member):Member;

    /**
     * @param Member $member
     * @return void
     */
    public function resignMembership(Member $member);

    /**
     * @param int $member_id
     */
    public function assocSummitOrders(int $member_id):void;
}