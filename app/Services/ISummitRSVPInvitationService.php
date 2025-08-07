<?php namespace App\Services;
/**
 * Copyright 2025 OpenStack Foundation
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
use App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation;
use Illuminate\Http\UploadedFile;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\SummitEvent;
use utils\Filter;


interface ISummitRSVPInvitationService
{

    public function importInvitationData(SummitEvent $summit_event, UploadedFile $csv_file, array $payload=[]):void;

    /**
     * @param SummitEvent $summit_event
     * @param int $invitation_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function delete(SummitEvent $summit_event, int $invitation_id):void;

    /**
     * @param SummitEvent $summit_event
     */
    public function deleteAll(SummitEvent $summit_event):void;

    /**
     * @param SummitEvent $summit_event
     * @param array $payload
     * @return RSVPInvitation
     */
    public function add(SummitEvent $summit_event, array $payload):RSVPInvitation;

    /**
     * @param SummitEvent $summit_event
     * @param int $invitation_id
     * @param array $payload
     * @return RSVPInvitation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function update(SummitEvent $summit_event, int $invitation_id, array $payload):RSVPInvitation;

    /**
     * @param Member $current_member
     * @param string $token
     * @return RSVPInvitation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function getInvitationByToken(Member $current_member, string $token):RSVPInvitation;

    /**
     * @param Member $current_member
     * @param string $token
     * @return RSVPInvitation
     */
    public function acceptInvitationBySummitAndToken(Member $current_member, string $token): RSVPInvitation;

    /**
     * @param Member $current_member
     * @param string $token
     * @return RSVPInvitation
     */
    public function rejectInvitationBySummitAndToken(Member $current_member, string $token): RSVPInvitation;


    /**
     * @param SummitEvent $summit_event
     * @param array $payload
     * @param mixed $filter
     */
    public function triggerSend(SummitEvent $summit_event, array $payload, $filter = null):void;

    /**
     * @param int $event_id
     * @param array $payload
     * @param Filter|null $filter
     */
    public function send(int $event_id, array $payload, Filter $filter = null):void;
}