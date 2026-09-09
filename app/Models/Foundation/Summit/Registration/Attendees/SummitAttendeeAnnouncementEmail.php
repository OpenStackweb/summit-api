<?php namespace models\summit;
/**
 * Copyright 2026 OpenStack Foundation
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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
use DateTime;

/**
 * Per-recipient, per-email-type, timestamped proof that an attendee bulk email was sent -
 * structural mirror of SpeakerAnnouncementSummitEmail, adapted for the one shape speakers don't
 * have: SummitAttendeeTicketEmailStrategy sends up to one email per ticket, not one per
 * attendee, so this carries an optional ticket association the speaker entity has no need for.
 * $type stores the raw requested email_flow_event string (e.g. GenericSummitAttendeeEmail::
 * EVENT_SLUG) rather than a fixed enum - unlike SpeakerAnnouncementSummitEmail::TypeAccepted
 * etc., attendee flow events are not a closed set here.
 *
 * @package models\summit
 */
#[ORM\Table(name: 'SummitAttendeeAnnouncementEmail')]
#[ORM\Entity]
class SummitAttendeeAnnouncementEmail extends SilverstripeBaseModel
{
    Use SummitOwned;

    /**
     * @var string
     */
    #[ORM\Column(name: 'AnnouncementEmailTypeSent', type: 'string')]
    private $type;

    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'AnnouncementEmailSentDate', type: 'datetime')]
    private $send_date;

    /**
     * @var SummitAttendee
     */
    #[ORM\JoinColumn(name: 'AttendeeID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitAttendee::class, inversedBy: 'announcement_emails')]
    protected $attendee;

    /**
     * @var SummitAttendeeTicket|null
     */
    #[ORM\JoinColumn(name: 'TicketID', referencedColumnName: 'ID', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitAttendeeTicket::class)]
    protected $ticket;

    /**
     * @return string
     */
    public function getType():?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return DateTime
     */
    public function getSendDate():?DateTime
    {
        return $this->send_date;
    }

    public function isSent():bool{
        return !is_null($this->send_date);
    }

    public function markAsSent():void{
        $this->send_date = new DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return SummitAttendee
     */
    public function getAttendee():?SummitAttendee
    {
        return $this->attendee;
    }

    /**
     * @param SummitAttendee $attendee
     */
    public function setAttendee($attendee)
    {
        $this->attendee = $attendee;
    }

    public function clearAttendee():void{
        $this->attendee = null;
    }

    /**
     * @return SummitAttendeeTicket|null
     */
    public function getTicket():?SummitAttendeeTicket
    {
        return $this->ticket;
    }

    /**
     * @param SummitAttendeeTicket|null $ticket
     */
    public function setTicket(?SummitAttendeeTicket $ticket):void
    {
        $this->ticket = $ticket;
    }

}
