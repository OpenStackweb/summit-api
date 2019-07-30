<?php namespace App\Models\Foundation\Summit\Events;
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
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\SummitEvent;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 *
 * @ORM\Entity
 * @ORM\Table(name="SummitEventAttendanceMetric")
 * Class SummitEventAttendanceMetric
 * @package App\Models\Foundation\Summit\Events
 */
class SummitEventAttendanceMetric extends SilverstripeBaseModel
{
    /**
     * @ORM\Column(name="IngressDate", type="datetime")
     * @var \DateTime
     */
    protected $ingress_date;

    /**
     * @ORM\Column(name="OutgressDate", type="datetime")
     * @var \DateTime
     */
    protected $outgress_date;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member", inversedBy="summit_attendance_metrics")
     * @ORM\JoinColumn(name="MemberID", referencedColumnName="ID", onDelete="CASCADE")
     * @var Member
     */
    private $member;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitEvent", inversedBy="attendance_metrics", fetch="LAZY")
     * @ORM\JoinColumn(name="SummitEventID", referencedColumnName="ID", onDelete="CASCADE")
     * @var SummitEvent
     */
    private $event;

    /**
     * @return \DateTime
     */
    public function getIngressDate(): \DateTime
    {
        return $this->ingress_date;
    }

    /**
     * @param \DateTime $ingress_date
     */
    public function setIngressDate(\DateTime $ingress_date): void
    {
        $this->ingress_date = $ingress_date;
    }

    /**
     * @return \DateTime
     */
    public function getOutgressDate(): ?\DateTime
    {
        return $this->outgress_date;
    }

    /**
     * @param \DateTime $outgress_date
     */
    public function setOutgressDate(\DateTime $outgress_date): void
    {
        $this->outgress_date = $outgress_date;
    }

    /**
     * @return Member
     */
    public function getMember(): Member
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
     * @return SummitEvent
     */
    public function getEvent(): SummitEvent
    {
        return $this->event;
    }

    /**
     * @param SummitEvent $event
     */
    public function setEvent(SummitEvent $event): void
    {
        $this->event = $event;
    }


    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param Member $member
     * @param SummitEvent $event
     * @return SummitEventAttendanceMetric
     * @throws \Exception
     */
    public static function build(Member $member, SummitEvent $event){
        $metric = new self();
        $metric->member = $member;
        $metric->event = $event;
        $metric->ingress_date = new \DateTime('now', new \DateTimeZone('UTC'));
        return $metric;
    }

    /**
     * @throws ValidationException
     */
    public function abandon(){
        if(is_null($this->ingress_date))
            throw new ValidationException('You must enter first.');
        $this->outgress_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function getMemberFirstName():?string{
        return $this->member->getFirstName();
    }

    public function getMemberLastName():?string{
        return $this->member->getLastName();
    }

    public function getMemberProfilePhotoUrl():?string{
        return $this->member->getProfilePhotoUrl();
    }
}