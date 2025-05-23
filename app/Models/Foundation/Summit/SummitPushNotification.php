<?php namespace models\summit;
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
use models\main\Group;
use models\main\Member;
use models\main\PushNotificationMessage;
/**
 * Class SummitPushNotificationChannel
 * @package models\summit
 */
final class SummitPushNotificationChannel {

    const Everyone  = 'EVERYONE';
    const Speakers  = 'SPEAKERS';
    const Attendees = 'ATTENDEES';
    const Members   = 'MEMBERS';
    const Summit    = 'SUMMIT';
    const Event     = 'EVENT';
    const Group     = 'GROUP';

    /**
     * @return array
     */
    public static function getPublicChannels(){
        return [self::Everyone, self::Speakers, self::Attendees, self::Summit, self::Event, self::Group];
    }

    /**
     * @param string $channel
     * @return bool
     */
    public static function isPublicChannel($channel){
        return in_array($channel, self::getPublicChannels());
    }
}
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitPushNotification')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitNotificationRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'notifications')])]
class SummitPushNotification extends PushNotificationMessage
{
    use SummitOwned;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Channel', type: 'string')]
    private $channel;

    /**
     * @var SummitEvent
     */
    #[ORM\JoinColumn(name: 'EventID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitEvent::class)]
    private $summit_event;

    /**
     * @var Group
     */
    #[ORM\JoinColumn(name: 'GroupID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Group::class)]
    private $group;

    #[ORM\JoinTable(name: 'SummitPushNotification_Recipients')]
    #[ORM\JoinColumn(name: 'SummitPushNotificationID', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'MemberID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \models\main\Member::class)]
    private $recipients;

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    /**
     * @return SummitEvent
     */
    public function getSummitEvent()
    {
        return $this->summit_event;
    }

    /**
     * @param SummitEvent $summit_event
     */
    public function setSummitEvent($summit_event)
    {
        $this->summit_event = $summit_event;
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param Group $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return ArrayCollection
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    /**
     * SummitPushNotification constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->recipients = new ArrayCollection;
    }

    /**
     * @param Member $member
     * @return $this
     */
    public function addRecipient(Member $member){
        $this->recipients->add($member);
        return $this;
    }
}