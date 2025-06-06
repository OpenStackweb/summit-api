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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @package models\main
 */
#[ORM\Table(name: 'PushNotificationMessage')]
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'ClassName', type: 'string')]
#[ORM\DiscriminatorMap(['PushNotificationMessage' => 'PushNotificationMessage', 'SummitPushNotification' => 'models\summit\SummitPushNotification', 'ChatTeamPushNotificationMessage' => 'ChatTeamPushNotificationMessage'])] // Class PushNotificationMessage
class PushNotificationMessage extends SilverstripeBaseModel
{
    const PlatformMobile = 'MOBILE';
    const PlatformWeb    = 'WEB';

    const PriorityHigh = 'HIGH';
    const PriorityNormal = 'NORMAL';

    public function __construct()
    {
        parent::__construct();
        $this->is_sent  = false;
        $this->approved = false;
        $this->priority = self::PriorityNormal;
    }

    /**
     * @var string
     */
    #[ORM\Column(name: 'Message', type: 'string')]
    protected $message;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Priority', type: 'string')]
    protected $priority;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'SentDate', type: 'datetime')]
    protected $sent_date;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'IsSent', type: 'boolean')]
    protected $is_sent;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'Approved', type: 'boolean')]
    protected $approved;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'Platform', type: 'string')]
    protected $platform;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'OwnerID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class)]
    protected $owner;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'ApprovedByID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class)]
    protected $approved_by;

    /**
     * @return int
     */
    public function getOwnerId(){
        try{
            return is_null($this->owner) ? 0 : $this->owner->getId();
        }
        catch (\Exception $ex){
            return 0;
        }
    }

    /**
     * @return int
     */
    public function getApprovedById(){
        try{
            return is_null($this->approved_by) ? 0 : $this->approved_by->getId();
        }
        catch (\Exception $ex){
            return 0;
        }
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return $this
     */
    public function markSent(){
        $this->is_sent     = true;
        $now               = new \DateTime('now', new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone));
        $this->sent_date   = $now;
        return $this;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return \DateTime
     */
    public function getSentDate()
    {
        return $this->sent_date;
    }

    /**
     * @return \DateTime|null
     */
    public function getSentDateUTC(){
        return $this->getDateFromLocalToUTC($this->sent_date);
    }

    /**
     * @param \DateTime $sent_date
     */
    public function setSentDate($sent_date)
    {
        $this->sent_date = $sent_date;
    }

    /**
     * @return boolean
     */
    public function isSent()
    {
        return $this->is_sent;
    }

    /**
     * @return boolean
     */
    public function getIsSent()
    {
        return $this->isSent();
    }

    /**
     * @param boolean $is_sent
     */
    public function setIsSent($is_sent)
    {
        $this->is_sent = $is_sent;
    }

    /**
     * @return Member
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return bool
     */
    public function hasOwner(){
        return $this->getOwnerId() > 0;
    }

    /**
     * @param Member $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @param Member|null $approved_by
     * @return $this
     */
    public function approve(Member $approved_by = null){
        $this->approved = true;
        $this->approved_by = $approved_by;
        return $this;
    }

    /**
     * @return $this
     */
    public function unApprove(){
        $this->approved = false;
        $this->approved_by = null;
        return $this;
    }

    /**
     * @return string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param string $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return bool
     */
    public function isApproved()
    {
        return $this->approved;
    }

    /**
     * @param bool $approved
     */
    public function setApproved($approved)
    {
        $this->approved = $approved;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @param string $platform
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
    }

    /**
     * @return Member
     */
    public function getApprovedBy()
    {
        return $this->approved_by;
    }

    /**
     * @return bool
     */
    public function hasApprovedBy(){
        return $this->getApprovedById() > 0;
    }

    /**
     * @param Member $approved_by
     */
    public function setApprovedBy($approved_by)
    {
        $this->approved_by = $approved_by;
    }

}