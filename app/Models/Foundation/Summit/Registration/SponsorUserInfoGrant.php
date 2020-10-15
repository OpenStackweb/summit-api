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
use models\utils\SilverstripeBaseModel;
use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSponsorUserInfoGrantRepository")
 * @ORM\Table(name="SponsorUserInfoGrant")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="ClassName", type="string")
 * @ORM\DiscriminatorMap({
 *     "SponsorUserInfoGrant" = "SponsorUserInfoGrant",
 *     "SponsorBadgeScan" = "SponsorBadgeScan"
 * })
 * Class SponsorUserInfoGrant
 * @package models\summit
 */
class SponsorUserInfoGrant extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    const ClassName = 'SponsorUserInfoGrant';
    /**
     * @ORM\ManyToOne(targetEntity="models\summit\Sponsor", inversedBy="badge_scans")
     * @ORM\JoinColumn(name="SponsorID", referencedColumnName="ID")
     * @var Sponsor
     */
    protected $sponsor;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member")
     * @ORM\JoinColumn(name="AllowedUserID", referencedColumnName="ID")
     * @var Member|null
     */
    protected $allowed_user;

    protected $getIdMappings = [
        'getSponsorId' => 'sponsor',
        'getAllowedUserId'    => 'allowed_user',
    ];

    protected $hasPropertyMappings = [
        'hasSponsor' => 'sponsor',
        'hasAllowedUser' => 'allowed_user',
    ];

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
     * @return Member|null
     */
    public function getAllowedUser(): ?Member
    {
        return $this->allowed_user;
    }

    /**
     * @param Member|null $allowed_user
     */
    public function setAllowedUser(?Member $allowed_user): void
    {
        $this->allowed_user = $allowed_user;
    }

    public function getAttendeeFirstName():?string{
        return $this->allowed_user->getFirstName();
    }

    public function getAttendeeLastName():?string{
        return $this->allowed_user->getLastName();
    }

    public function getAttendeeEmail():?string{
        return $this->allowed_user->getEmail();
    }
}