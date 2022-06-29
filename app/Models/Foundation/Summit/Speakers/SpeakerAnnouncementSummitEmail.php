<?php namespace models\summit;
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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
use DateTime;
/**
 * @ORM\Entity
 * @ORM\Table(name="SpeakerAnnouncementSummitEmail")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="speakers_announcement_emails"
 *     )
 * })
 * Class SpeakerAnnouncementSummitEmail
 * @package models\summit
 */
class SpeakerAnnouncementSummitEmail extends SilverstripeBaseModel
{

    const TypeAccepted                = 'ACCEPTED';
    const TypeRejected                = 'REJECTED';
    const TypeAlternate               = 'ALTERNATE';
    const TypeAcceptedAlternate       = 'ACCEPTED_ALTERNATE';
    const TypeAcceptedRejected        = 'ACCEPTED_REJECTED';
    const TypeAlternateRejected       = 'ALTERNATE_REJECTED';
    const TypeSecondBreakoutReminder  = 'SECOND_BREAKOUT_REMINDER';
    const TypeSecondBreakoutRegister  = 'SECOND_BREAKOUT_REGISTER';
    const TypeCreateMembership        = 'CREATE_MEMBERSHIP';
    const TypeNone                    = 'NONE';

    /**
     * @ORM\Column(name="AnnouncementEmailTypeSent", type="string")
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(name="AnnouncementEmailSentDate", type="datetime")
     * @var DateTime
     */
    private $send_date;

    Use SummitOwned;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\PresentationSpeaker", inversedBy="announcement_summit_emails")
     * @ORM\JoinColumn(name="SpeakerID", referencedColumnName="ID")
     * @var PresentationSpeaker
     */
    protected $speaker;

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
     * @return PresentationSpeaker
     */
    public function getSpeaker():?PresentationSpeaker
    {
        return $this->speaker;
    }

    /**
     * @param PresentationSpeaker $speaker
     */
    public function setSpeaker($speaker)
    {
        $this->speaker = $speaker;
    }

    public function clearSpeaker():void{
        $this->speaker = null;
    }

}