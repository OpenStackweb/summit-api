<?php namespace models\main;
/**
 * Copyright 2016 OpenStack Foundation
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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use models\utils\SilverstripeBaseModel;

/**
 * @package models\main
 */
#[ORM\Table(name: 'ChatTeam')]
#[ORM\Entity(repositoryClass: \repositories\main\DoctrineChatTeamRepository::class)] // Class ChatTeam
class ChatTeam extends SilverstripeBaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->members     = new ArrayCollection();
        $this->messages    = new ArrayCollection();
        $this->invitations = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return ChatTeamMember[]
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * @param ChatTeamMember[] $members
     */
    public function setMembers($members)
    {
        $this->members = $members;
    }

    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Description', type: 'string')]
    private $description;


    /**
     * @var ChatTeamMember[]
     */
    #[ORM\OneToMany(targetEntity: \ChatTeamMember::class, mappedBy: 'team', cascade: ['persist'], orphanRemoval: true)]
    private $members;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'OwnerID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class)]
    private $owner;

    /**
     * @var ChatTeamPushNotificationMessage[]
     */
    #[ORM\OneToMany(targetEntity: \ChatTeamPushNotificationMessage::class, mappedBy: 'team', cascade: ['persist'], orphanRemoval: true)]
    private $messages;

    /**
     * @var ChatTeamInvitation[]
     */
    #[ORM\OneToMany(targetEntity: \ChatTeamInvitation::class, mappedBy: 'team', cascade: ['persist'], orphanRemoval: true)]
    private $invitations;

    /**
     * @return Member
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return int
     */
    public function getOwnerId(){
        try{
            return $this->owner->getId();
        }
        catch (\Exception $ex){
            return 0;
        }
    }

    /**
     * @param mixed $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return ChatTeamPushNotificationMessage[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @return ChatTeamInvitation[]
     */
    public function getInvitations()
    {
        return $this->invitations;
    }

    /**
     * @param ChatTeamInvitation $invitation
     */
    public function addInvitation(ChatTeamInvitation $invitation){
        $this->invitations->add($invitation);
        $invitation->setTeam($this);
    }

    /**
     * @param ChatTeamPushNotificationMessage $message
     */
    public function addMessage(ChatTeamPushNotificationMessage $message){
        $this->messages->add($message);
        $message->setTeam($this);
    }

    /**
     * @param ChatTeamMember $team_member
     */
    public function addMember(ChatTeamMember $team_member){
        $this->members->add($team_member);
        $team_member->setTeam($this);
    }

    /**
     * @param Member $inviter
     * @param Member $invitee
     * @param string $permission
     * @return ChatTeamInvitation
     */
    public function createInvitation(Member $inviter, Member $invitee, $permission = ChatTeamPermission::Read){
        $invitation = new ChatTeamInvitation();
        $invitation->setTeam($this);
        $invitation->setInviter($inviter);
        $invitation->setInvitee($invitee);
        $invitation->setPermission($permission);
        return $invitation;
    }

    public function createMember(Member $member, $permission = ChatTeamPermission::Read){
        $team_member = new ChatTeamMember();
        $team_member->setTeam($this);
        $team_member->setPermission($permission);
        $team_member->setMember($member);
        return $team_member;
    }

    /**
     * @param Member $owner
     * @param string $body
     * @param string $priority
     * @return ChatTeamPushNotificationMessage
     */
    public function createMessage(Member $owner, $body, $priority = PushNotificationMessagePriority::Normal){
        $message = new ChatTeamPushNotificationMessage();
        $message->setTeam($this);
        $message->setOwner($owner);
        $message->setMessage($body);
        $message->setPriority($priority);
        return $message;
    }

    /**
     * @param Member $member
     * @return bool
     */
    public function isMember(Member $member){
        $res = $this->members->filter(function ($e) use($member){
            return $e->getMember()->getId() == $member->getId();
        });
        return $res->count() > 0;
    }

    /**
     * @param Member $member
     * @return bool
     */
    public function canPostMessages(Member $member){
        $res = $this->members->filter(function ($e) use($member){
            return $e->getMember()->getId() == $member->getId();
        });
        if($res->count() == 0) return false;

        $team_member = $res->first();

        return $team_member->canPostMessages();
    }

    /**
     * @param Member $member
     * @return bool
     */
    public function isOwner(Member $member){
        return $this->getOwnerId() == $member->getId();
    }

    /**
     * @param Member $member
     * @return bool
     */
    public function isAlreadyInvited(Member $member){
        $res = $this->invitations->filter(function ($i) use($member){
            return $i->getInvitee()->getId() == $member->getId() && $i->isPending();
        });

        return $res->count() > 0;
    }

    /**
     * @param Member $member
     * @return bool
     */
    public function isAdmin(Member $member){
        $res = $this->members->filter(function ($e) use($member){
            return $e->getMember()->getId() == $member->getId() && $e->isAdmin();
        });
        return $res->count() > 0;
    }

    /**
     * @param Member $member
     */
    public function removeMember(Member $member){
        $res = $this->members->filter(function ($e) use($member){
            return $e->getMember()->getId() == $member->getId();
        });

        if($res->count() == 0) return;
        // remove member
        $team_member = $res->first();
        $this->members->removeElement($team_member);
        $res2 = $this->invitations->filter(function ($i) use($member){
            return $i->getInvitee()->getId() == $member->getId();
        });
        // remove invitation
        if($res2->count() > 0) {
            $this->invitations->removeElement($res2->first());
        }

        $team_member->setTeam(null);
    }
}