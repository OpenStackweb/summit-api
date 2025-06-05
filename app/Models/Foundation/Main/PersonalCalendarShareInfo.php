<?php namespace models\main;
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
use models\summit\SummitOwned;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package models\main
 */
#[ORM\Table(name: 'PersonalCalendarShareInfo')]
#[ORM\Entity]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'schedule_shareable_links')])] // Class PersonalCalendarShareInfo
class PersonalCalendarShareInfo extends SilverstripeBaseModel
{

    /**
     * @var string
     */
    #[ORM\Column(name: 'Hash', type: 'string')]
    private $cid;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'Revoked', type: 'boolean')]
    private $revoked;

    use SummitOwned;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'OwnerID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \Member::class, inversedBy: 'schedule_shareable_links')]
    private $owner;

    public function __construct()
    {
        parent::__construct();
        $this->revoked = false;
    }

    /**
     * @return int
     */
    public function getOwnerId(){
        try {
            return is_null($this->owner) ? 0 : $this->owner->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function generateCid():string{
        $this->cid = md5(strval($this->getOwnerId()).strval($this->getSummitId()).random_bytes(8));
        return $this->cid;
    }

    /**
     * @return string
     */
    public function getCid(): string
    {
        return $this->cid;
    }

    /**
     * @return bool
     */
    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function revoke(): void
    {
        $this->revoked = true;
    }

    /**
     * @return Member
     */
    public function getOwner(): Member
    {
        return $this->owner;
    }

    /**
     * @param Member $owner
     */
    public function setOwner(Member $owner): void
    {
        $this->owner = $owner;
    }

    public function clearOwner(){
        $this->owner = null;
    }

    /**
     * @return string|null
     */
    public function getLink():?string{
        if($this->isRevoked()) return null;
        return action('OAuth2SummitMembersApiController@getCalendarFeedICS', ['id' => $this->getSummitId(), 'cid' => $this->cid]);
    }
}