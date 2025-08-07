<?php namespace App\Models\Foundation\Summit\Events\RSVP;
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

use App\Models\Utils\Traits\InvitationTrait;
use Doctrine\ORM\Mapping AS ORM;
use models\summit\SummitAttendee;
use models\summit\SummitEvent;
use models\utils\SilverstripeBaseModel;

#[ORM\Table(name: 'RSVPInvitation')]
class RSVPInvitation extends SilverstripeBaseModel
{

    use InvitationTrait;

    #[ORM\Column(name: 'Status', type: 'string')]
    protected string $status;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Hash', type: 'string')]
    private string $hash;

    public function getSentDate(): \DateTime
    {
        return $this->sent_date;
    }

    public function getActionDate(): \DateTime
    {
        return $this->action_date;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @var ?\DateTime
     */
    #[ORM\Column(name: 'ActionDate', type: 'datetime')]
    private ?\DateTime $action_date;

    /**
     * @var ?\DateTime
     */
    #[ORM\Column(name: 'SentDate', type: 'datetime')]
    private ?\DateTime $sent_date;


    /**
     * @var SummitAttendee
     */
    #[ORM\JoinColumn(name: 'AttendeeID', referencedColumnName: 'ID', nullable: false, onDelete: 'CASCADE' )]
    #[ORM\ManyToOne(targetEntity: SummitAttendee::class, inversedBy: 'rsvp_invitations')]
    private SummitAttendee $invitee;

    /**
     * @var SummitEvent
     */
    #[ORM\JoinColumn(name: 'SummitEventID', referencedColumnName: 'ID', nullable: false, onDelete: 'CASCADE' )]
    #[ORM\ManyToOne(targetEntity: SummitEvent::class, inversedBy: 'rsvp_invitations')]
    private SummitEvent $event;

    public function getEvent(): SummitEvent
    {
        return $this->event;
    }

    public function getInvitee(): SummitAttendee
    {
        return $this->invitee;
    }

    public function __construct(SummitEvent $event, SummitAttendee $invitee)
    {
        parent::__construct();
        $this->event = $event;
        $this->invitee = $invitee;
        $this->action_date = null;
        $this->sent_date = null;
        $this->status = self::Status_Pending;
    }

    /**
     * @return string
     */
    protected  function generateTokenSeed():string{
        // build seed
        $seed = '';
        if (!is_null($this->invitee->getFirstName()))
            $seed .= $this->invitee->getFirstName();
        if (!is_null($this->invitee->getSurname()))
            $seed .= $this->invitee->getSurname();
        if (!is_null($this->invitee->getEmail()))
            $seed .= $this->invitee->getEmail();
        return $seed;
    }


}