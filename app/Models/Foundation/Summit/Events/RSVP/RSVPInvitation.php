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
use App\Repositories\Summit\DoctrineRSVPInvitationRepository;
use Doctrine\ORM\Mapping AS ORM;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\RSVP;
use models\summit\SummitAttendee;
use models\summit\SummitEvent;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;

#[ORM\Entity(repositoryClass: DoctrineRSVPInvitationRepository::class)]
#[ORM\Table(name: 'RSVPInvitation')]
class RSVPInvitation extends SilverstripeBaseModel
{

    use InvitationTrait;

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getInviteeId' => 'invitee',
        'getEventId' => 'event',
    ];

    protected $hasPropertyMappings = [
        'hasInvitee' => 'invitee',
        'hasEvent' => 'event',
    ];

    #[ORM\Column(name: 'Status', type: 'string')]
    protected string $status;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Hash', type: 'string')]
    private string $hash;

    public function getSentDate(): ?\DateTime
    {
        return $this->sent_date;
    }

    public function getActionDate(): ?\DateTime
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

    /**
     * @var RSVP
     */
    #[ORM\JoinColumn(name: 'RSVPID', referencedColumnName: 'ID', nullable: true, onDelete: 'CASCADE' )]
    #[ORM\OneToOne(targetEntity: RSVP::class)]
    private ?RSVP $rsvp;

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

    /**
     * @param Member $member
     * @return void
     * @throws ValidationException
     */
    public function checkOwnership(Member $member):void{
        if (strtolower($this->invitee->getEmail()) !== strtolower($member->getEmail()))
            throw new ValidationException(sprintf(
                "This invitation was sent to %s but you logged in as %s."
                . " To be able to register for this event, sign out and then RSVP from the email invite and then log in with your primary email address."
                . " Email <a href='mailto:%s'>%s</a> for additional troubleshooting."
                ,
                $member->getEmail(),
                $member->getEmail(),
                $this->getEvent()->getSummit()->getSupportEmail(),
                $this->getEvent()->getSummit()->getSupportEmail()));
    }

    public function getRSVP():RSVP{
        return $this->rsvp;
    }

    /**
     * @param RSVP $rsvp
     * @return void
     * @throws ValidationException
     */
    public function markAsAcceptedWithRSVP(RSVP $rsvp):void{
        $this->markAsAccepted();
        $this->rsvp = $rsvp;
    }

    public function getEmail():string{
        return $this->getInvitee()->getEmail();
    }

    public function markAsSent():void{
        $this->sent_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
