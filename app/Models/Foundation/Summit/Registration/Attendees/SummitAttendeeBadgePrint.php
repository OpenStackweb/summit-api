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
use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="SummitAttendeeBadgePrint")
 * Class SummitAttendeeBadgePrint
 * @package models\summit
 */
class SummitAttendeeBadgePrint extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getRequestorId' => 'requestor',
        'getBadgeId'     => 'badge',
    ];

    protected $hasPropertyMappings = [
        'hasRequestor' => 'requestor',
        'hasBadge'     => 'badge',
    ];

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitAttendeeBadge", inversedBy="prints")
     * @ORM\JoinColumn(name="BadgeID", referencedColumnName="ID")
     * @var SummitAttendeeBadge
     */
    private $badge;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member")
     * @ORM\JoinColumn(name="RequestorID", referencedColumnName="ID")
     * @var Member
     */
    private $requestor;

    /**
     * @ORM\Column(name="PrintDate", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $print_date;

    /**
     * @return SummitAttendeeBadge
     */
    public function getBadge(): SummitAttendeeBadge
    {
        return $this->badge;
    }

    /**
     * @return Member
     */
    public function getRequestor(): Member
    {
        return $this->requestor;
    }

    /**
     * @return \DateTime
     */
    public function getPrintDate(): \DateTime
    {
        return $this->print_date;
    }

    /**
     * @param SummitAttendeeBadge $badge
     * @param Member $requestor
     * @return SummitAttendeeBadgePrint
     */
    public static function build(SummitAttendeeBadge $badge, Member $requestor){
        $print             = new SummitAttendeeBadgePrint();
        $print->badge      = $badge;
        $print->requestor  = $requestor;
        $print->print_date = new \DateTime('now', new \DateTimeZone('UTC'));
        return $print;
    }

}