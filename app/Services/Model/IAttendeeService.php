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
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeNote;
use models\summit\SummitAttendeeTicket;
use utils\Filter;

/**
 * Interface IAttendeeService
 * @package App\Services\Model
 */
interface IAttendeeService
{
    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitAttendee
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addAttendee(Summit $summit, array $data);

    /**
     * @param Summit $summit
     * @param int $attendee_id
     * @return void
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteAttendee(Summit $summit, $attendee_id);

    /**
     * @param Summit $summit
     * @param int $attendee_id
     * @param array $data
     * @return SummitAttendee
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateAttendee(Summit $summit, $attendee_id, array $data);

    /**
     * @param SummitAttendee $attendee
     * @param int $ticket_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return SummitAttendeeTicket
     */
    public function deleteAttendeeTicket(SummitAttendee $attendee, $ticket_id);

    /**
     * @param Summit $summit
     * @param int $page_nbr
     * @return mixed
     */
    public function updateRedeemedPromoCodes(Summit $summit, $page_nbr = 1);

    /**
     * @param Summit $summit
     * @param SummitAttendee $attendee
     * @param Member $other_member
     * @param int $ticket_id
     * @return SummitAttendeeTicket
     * @throws \Exception
     */
    public function reassignAttendeeTicketByMember(Summit $summit, SummitAttendee $attendee, Member $other_member, int $ticket_id):SummitAttendeeTicket;

    /**
     * @param Summit $summit
     * @param SummitAttendee $attendee
     * @param int $ticket_id
     * @param array $payload
     * @return SummitAttendeeTicket
     * @throws \Exception
     */
    public function reassignAttendeeTicket(Summit $summit, SummitAttendee $attendee, int $ticket_id, array $payload):SummitAttendeeTicket;

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

    /**
     * @param Summit $summit_id
     * @param int $attendee_id
     * @return SummitAttendee|null
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function doVirtualCheckin(Summit $summit, int $attendee_id):?SummitAttendee;

    /**
     * @param Summit $summit_id
     * @param String $qr_code
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function doCheckIn(Summit $summit, String $qr_code): void;

    /**
     * @param int $member_id
     */
    public function updateAttendeesByMemberId(int $member_id):void;

    public function resynchAttendeesStatusBySummit(int $summit_id):void;

    /**
     * @param Summit $summit
     * @param Member $author
     * @param int $attendee_id
     * @param int|null $note_id
     * @param array $payload
     * @return SummitAttendeeNote
     */
    public function upsertAttendeeNote(
        Summit $summit, Member $author, int $attendee_id, ?int $note_id, array $payload): SummitAttendeeNote;

    /**
     * @param Summit $summit
     * @param int $attendee_id
     * @param int $note_id
     */
    public function deleteAttendeeNote(Summit $summit, int $attendee_id, int $note_id): void;

    /**
     * @param int $attendee_id
     * @return void
     * @throws \Exception
     */
    public function processAttendeeCheckStatusUpdate(int $attendee_id):void;
}