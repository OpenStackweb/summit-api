<?php namespace App\Services\Model;
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
use Illuminate\Http\UploadedFile;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\Summit;
use models\summit\SummitRegistrationInvitation;
use utils\Filter;
/**
 * Interface ISummitRegistrationInvitationService
 * @package App\Services\Model
 */
interface ISummitRegistrationInvitationService
{
    /**
     * @param Summit $summit
     * @param UploadedFile $csv_file
     */
    public function importInvitationData(Summit $summit, UploadedFile $csv_file):void;

    /**
     * @param Summit $summit
     * @param int $invitation_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function delete(Summit $summit, int $invitation_id):void;

    /**
     * @param Summit $summit
     */
    public function deleteAll(Summit $summit):void;

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitRegistrationInvitation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function add(Summit $summit, array $payload):SummitRegistrationInvitation;

    /**
     * @param Summit $summit
     * @param int $invitation_id
     * @param array $payload
     * @return SummitRegistrationInvitation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function update(Summit $summit, int $invitation_id, array $payload):SummitRegistrationInvitation;

    /**
     * @param Member $current_member
     * @param string $token
     * @return SummitRegistrationInvitation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function getInvitationByToken(Member $current_member, string $token):SummitRegistrationInvitation;

    /**
     * @param Summit $summit
     * @param string $email
     * @return SummitRegistrationInvitation|null
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function getInvitationByEmail(Summit $summit, string $email):?SummitRegistrationInvitation;

    /**
     * @param Summit $summit
     * @param array $payload
     * @param mixed $filter
     */
    public function triggerSend(Summit $summit, array $payload, $filter = null):void;

    /**
     * @param int $summit_id
     * @param array $payload
     * @param Filter|null $filter
     */
    public function send(int $summit_id, array $payload, Filter $filter = null):void;
}