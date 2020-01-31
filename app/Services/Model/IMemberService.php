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
     * @param $user_external_id
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @return Member
     */
    public function registerExternalUser($user_external_id, string $email, string $first_name, string $last_name):Member;
}