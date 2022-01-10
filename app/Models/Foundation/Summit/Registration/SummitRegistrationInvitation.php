<?php namespace models\summit;
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

use Doctrine\Common\Collections\Criteria;
use Illuminate\Support\Facades\Log;
use models\main\Member;
use models\utils\RandomGenerator;
use models\utils\SilverstripeBaseModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitRegistrationInvitationRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="registration_invitations"
 *     )
 * })
 * @ORM\Table(name="SummitRegistrationInvitation")
 * Class SummitRegistrationInvitation
 * @package models\summit
 */
class SummitRegistrationInvitation extends SilverstripeBaseModel
{
    use SummitOwned;

    /**
     * @ORM\Column(name="FirstName", type="string")
     * @var string
     */
    private $first_name;

    /**
     * @ORM\Column(name="LastName", type="string")
     * @var string
     */
    private $last_name;

    /**
     * @ORM\Column(name="Email", type="string")
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(name="Hash", type="string")
     * @var string
     */
    private $hash;

    /**
     * @ORM\Column(name="SetPasswordLink", type="string")
     * @var string
     */
    private $set_password_link;

    /**
     * @var \DateTime
     * @ORM\Column(name="AcceptedDate", type="datetime")
     */
    private $accepted_date;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member")
     * @ORM\JoinColumn(name="MemberID", referencedColumnName="ID", nullable=true)
     * @var Member
     */
    private $member;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitOrder")
     * @ORM\JoinColumn(name="SummitOrderID", referencedColumnName="ID", nullable=true)
     * @var SummitOrder
     */
    private $order;

    /**
     * @ORM\ManyToMany(targetEntity="SummitTicketType", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="SummitRegistrationInvitation_SummitTicketTypes",
     *      joinColumns={@ORM\JoinColumn(name="SummitRegistrationInvitationID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="SummitTicketTypeID", referencedColumnName="ID")}
     *      )
     * @var SummitTicketType[]
     */
    private $ticket_types;

    public function __construct()
    {
        parent::__construct();
        $this->ticket_types = new ArrayCollection();
        $this->member = null;
        $this->order = null;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    /**
     * @param string $first_name
     */
    public function setFirstName(string $first_name): void
    {
        $this->first_name = $first_name;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    /**
     * @param string $last_name
     */
    public function setLastName(string $last_name): void
    {
        $this->last_name = $last_name;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    /**
     * @return \DateTime
     */
    public function getAcceptedDate(): ?\DateTime
    {
        return $this->accepted_date;
    }

    public function isAccepted(): bool
    {
        return !is_null($this->accepted_date);
    }

    public function isSent(): bool
    {
        return !empty($this->hash);
    }

    /**
     * @return Member
     */
    public function getMember(): ?Member
    {
        return $this->member;
    }

    /**
     * @param Member $member
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
    }

    /**
     * @return int
     */
    public function getMemberId()
    {
        try {
            return is_null($this->member) ? 0 : $this->member->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasMember(): bool
    {
        return $this->getMemberId() > 0;
    }

    public function clearMember()
    {
        $this->member = null;
    }

    /**
     * transient variable
     * @var string
     */
    private $token;

    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function generateConfirmationToken(): string
    {
        $generator = new RandomGenerator();
        $this->accepted_date = null;
        // build seed
        $seed = '';
        if(!is_null($this->first_name))
            $seed .= $this->first_name;
        if(!is_null($this->last_name))
            $seed .= $this->last_name;
        if(!is_null($this->email))
            $seed .= $this->email;
        $seed .= $generator->randomToken();
        $this->token = md5($seed);
        $this->hash = self::HashConfirmationToken($this->token);
        Log::debug(sprintf("SummitRegistrationInvitation::generateConfirmationToken id %s token %s hash %s", $this->id, $this->token, $this->hash));
        return $this->token;
    }

    public static function HashConfirmationToken(string $token): string
    {
        return md5($token);
    }

    /**
     * @return string
     */
    public function getSetPasswordLink(): ?string
    {
        return $this->set_password_link;
    }

    /**
     * @param string $set_password_link
     */
    public function setSetPasswordLink(string $set_password_link): void
    {
        $this->set_password_link = $set_password_link;
    }

    /**
     * @return SummitOrder
     */
    public function getOrder(): ?SummitOrder
    {
        return $this->order;
    }

    /**
     * @param SummitOrder $order
     */
    public function setOrder(SummitOrder $order): void
    {
        $this->order = $order;
    }

    public function markAsAccepted(): void
    {
        $this->accepted_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return int
     */
    public function getOrderId()
    {
        try {
            return is_null($this->order) ? 0 : $this->order->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    public function clearOrder()
    {
        $this->order = null;
    }

    /**
     * @param SummitTicketType $ticketType
     */
    public function addTicketType(SummitTicketType $ticketType){
        if($this->ticket_types->contains($ticketType)) return;
        $this->ticket_types->add($ticketType);
    }

    /**
     * @param SummitTicketType $ticketType
     */
    public function removeTicketType(SummitTicketType $ticketType){
        if(!$this->ticket_types->contains($ticketType)) return;
        $this->ticket_types->removeElement($ticketType);
    }

    /**
     * @return SummitTicketType[]
     */
    public function getTicketTypes()
    {
        return $this->ticket_types;
    }

    public function clearTicketTypes():void{
        $this->ticket_types->clear();
    }

    /**
     * @param int $ticket_type_id
     * @return bool
     */
    public function isTicketTypeAllowed(int $ticket_type_id):bool{
        if(!$this->ticket_types->count()) return true;
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $ticket_type_id));
        $ticket_type = $this->ticket_types->matching($criteria)->first();
        return !($ticket_type === false);
    }
}