<?php namespace models\summit;
/**
 * Copyright 2019 OpenStack Foundation
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
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSponsorBadgeScanRepository")
 * @ORM\Table(name="SponsorBadgeScan")
 * Class SponsorBadgeScan
 * @package models\summit
 */
class SponsorBadgeScan extends SilverstripeBaseModel
{


    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getSponsorId' => 'sponsor',
        'getUserId'    => 'user',
        'getBadgeId'   => 'badge',
    ];

    protected $hasPropertyMappings = [
        'hasSponsor' => 'sponsor',
        'hasUser'    => 'user',
        'hasBadge'   => 'badge',
    ];

    /**
     * @ORM\Column(name="QRCode", type="string")
     * @var string
     */
    private $qr_code;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\Sponsor", inversedBy="badge_scans")
     * @ORM\JoinColumn(name="SponsorID", referencedColumnName="ID")
     * @var Sponsor
     */
    private $sponsor;

    /**
     * @var \DateTime
     * @ORM\Column(name="ScanDate", type="datetime")
     */
    protected $scan_date;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member")
     * @ORM\JoinColumn(name="UserID", referencedColumnName="ID")
     * @var Member
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitAttendeeBadge")
     * @ORM\JoinColumn(name="BadgeID", referencedColumnName="ID")
     * @var SummitAttendeeBadge
     */
    private $badge;

    /**
     * @return string
     */
    public function getQRCode(): string
    {
        return $this->qr_code;
    }

    /**
     * @param string $qr_code
     */
    public function setQRCode(string $qr_code): void
    {
        $this->qr_code = $qr_code;
    }

    /**
     * @return Sponsor
     */
    public function getSponsor(): Sponsor
    {
        return $this->sponsor;
    }

    /**
     * @param Sponsor $sponsor
     */
    public function setSponsor(Sponsor $sponsor): void
    {
        $this->sponsor = $sponsor;
    }

    /**
     * @return Member
     */
    public function getUser(): Member
    {
        return $this->user;
    }

    /**
     * @param Member $user
     */
    public function setUser(Member $user): void
    {
        $this->user = $user;
    }

    /**
     * @return SummitAttendeeBadge
     */
    public function getBadge(): SummitAttendeeBadge
    {
        return $this->badge;
    }

    /**
     * @param SummitAttendeeBadge $badge
     */
    public function setBadge(SummitAttendeeBadge $badge): void
    {
        $this->badge = $badge;
    }

    /**
     * @return \DateTime
     */
    public function getScanDate(): \DateTime
    {
        return $this->scan_date;
    }

    /**
     * @param \DateTime $scan_date
     */
    public function setScanDate(\DateTime $scan_date): void
    {
        $this->scan_date = $scan_date;
    }

}