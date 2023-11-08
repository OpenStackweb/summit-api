<?php namespace models\summit\factories;
/**
 * Copyright 2023 OpenStack Foundation
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
use models\main\Member;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeNote;

/**
 * Class SummitAttendeeNoteFactory
 * @package models\summit\factories
 */
final class SummitAttendeeNoteFactory
{
    /**
     * @param SummitAttendee $attendee
     * @param array $payload
     * @return SummitAttendeeNote
     * @throws EntityNotFoundException
     */
    public static function build(SummitAttendee $attendee, Member $author, array $payload)
    {
        return self::populate($attendee, $author, new SummitAttendeeNote($payload['content'], $attendee), $payload);
    }

    /**
     * @param SummitAttendee $attendee
     * @param Member $author
     * @param SummitAttendeeNote $attendee_note
     * @param array $payload
     * @return SummitAttendeeNote
     * @throws EntityNotFoundException
     */
    public static function populate
    (
        SummitAttendee      $attendee,
        Member              $author,
        SummitAttendeeNote  $attendee_note,
        array               $payload
    )
    {
        if (isset($payload['ticket_id'])) {
            $ticket_id = intval($payload['ticket_id']);
            $ticket = $attendee->getTicketById($ticket_id);
            if (is_null($ticket))
                throw new EntityNotFoundException(sprintf("Ticket id %s does not belong to attendee id %s.",
                    $ticket_id, $attendee->getId()));

            $attendee_note->setTicket($ticket);
        }
        $attendee_note->setAuthor($author);
        return $attendee_note;
    }
}