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

use Doctrine\ORM\Mapping AS ORM;
use App\Models\Foundation\Main\IGroup;
use Doctrine\Common\Collections\ArrayCollection;
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
use models\summit\Summit;
/**
 * @ORM\Entity(repositoryClass="repositories\main\DoctrineSummitAdministratorPermissionGroupRepository")
 * @ORM\Table(name="`SummitAdministratorPermissionGroup`")
 * Class Group
 * @package models\main
 */
class SummitAdministratorPermissionGroup extends SilverstripeBaseModel
{
    /**
     * @ORM\Column(name="Title", type="string")
     * @var string
     */
    private $title;

    const ValidGroups = [IGroup::SummitAdministrators, IGroup::TrackChairs, IGroup::TrackChairsAdmins];


    public function __construct()
    {
        parent::__construct();
        $this->members = new ArrayCollection();
        $this->summits = new ArrayCollection();
    }

    /**
     * @ORM\ManyToMany(targetEntity="models\main\Member", inversedBy="summit_permission_groups")
     * @ORM\JoinTable(name="SummitAdministratorPermissionGroup_Members",
     *      joinColumns={@ORM\JoinColumn(name="SummitAdministratorPermissionGroupID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="MemberID", referencedColumnName="ID")}
     *      )
     * @var Member[]
     */
    private $members;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\Summit", inversedBy="permission_groups")
     * @ORM\JoinTable(name="SummitAdministratorPermissionGroup_Summits",
     *      joinColumns={@ORM\JoinColumn(name="SummitAdministratorPermissionGroupID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="SummitID", referencedColumnName="ID")}
     *      )
     * @var Summit[]
     */
    private $summits;

    /**
     * @param Member $member
     * @throws ValidationException
     */
    public function addMember(Member $member)
    {
        if(!$this->canAddMember($member)){
            throw new ValidationException(sprintf("Member %s should belong to following groups (%s)", $member->getId(),
            implode(",", self::ValidGroups)));
        }
        if ($this->members->contains($member)) return;
        $this->members->add($member);
        $member->add2SummitAdministratorPermissionGroup($this);
    }

    public function canAddMember(Member $member):bool{
        return
        $member->isOnGroup(IGroup::SummitAdministrators) ||
        $member->isOnGroup(IGroup::TrackChairs) ||
        $member->isOnGroup(IGroup::TrackChairsAdmins);
    }

    /**
     * @param Member $member
     */
    public function removeMember(Member $member)
    {
        if (!$this->members->contains($member)) return;
        $this->members->removeElement($member);
        $member->removeFromSummitAdministratorPermissionGroup($this);
    }

    public function getMembers()
    {
        return $this->members;
    }

    public function getMembersIds(): array
    {

        $sql = <<<SQL
SELECT DISTINCT(SummitAdministratorPermissionGroup_Members.MemberID) 
FROM SummitAdministratorPermissionGroup_Members 
WHERE SummitAdministratorPermissionGroup_Members.SummitAdministratorPermissionGroupID = :group_id
SQL;

        $stmt = $this->prepareRawSQL($sql);
        $stmt->execute(
            [
                'group_id' => $this->getId(),
            ]
        );
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);

    }

    /**
     * @param Summit $summit
     */
    public function addSummit(Summit $summit)
    {
        if ($this->summits->contains($summit)) return;
        $this->summits->add($summit);
        $summit->add2SummitAdministratorPermissionGroup($this);
    }

    public function removeSummit(Summit $summit)
    {
        if (!$this->summits->contains($summit)) return;
        $this->summits->removeElement($summit);
        $summit->removeFromSummitAdministratorPermissionGroup($this);
    }

    public function getSummits()
    {
        return $this->summits;
    }

    public function getSummitsIds(): array
    {

        $sql = <<<SQL
SELECT DISTINCT(SummitAdministratorPermissionGroup_Summits.SummitID) 
FROM SummitAdministratorPermissionGroup_Summits 
WHERE SummitAdministratorPermissionGroup_Summits.SummitAdministratorPermissionGroupID = :group_id;
SQL;

        $stmt = $this->prepareRawSQL($sql);
        $stmt->execute(
            [
                'group_id' => $this->getId(),
            ]
        );
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);

    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function clearMembers(){
        $this->members->clear();
    }

    public function clearSummits(){
        $this->summits->clear();
    }

    /**
     * @param string $groupSlug
     * @return bool
     */
    public static function isValidGroup(string $groupSlug):bool {
        return in_array($groupSlug,self::ValidGroups);
    }
}