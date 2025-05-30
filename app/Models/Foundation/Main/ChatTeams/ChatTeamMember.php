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
use Doctrine\ORM\Mapping as ORM;

/**
 * @package models\main
 */
#[ORM\Table(name: 'ChatTeam_Members')]
#[ORM\Entity]
class ChatTeamMember
{
    /**
     * @return Member
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * @param Member $member
     */
    public function setMember($member)
    {
        $this->member = $member;
    }

    /**
     * @return ChatTeam
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * @param ChatTeam $team
     */
    public function setTeam($team)
    {
        $this->team = $team;
    }

    /**
     * @return string
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * @return bool
     */
    public function isAdmin(){
        return $this->permission == ChatTeamPermission::Admin;
    }

    /**
     * @return bool
     */
    public function canPostMessages(){
        return $this->isAdmin() || $this->permission == ChatTeamPermission::Write;
    }

    /**
     * @return bool
     */
    public function canDeleteMembers(){
        return $this->isAdmin();
    }

    /**
     * @param mixed $permission
     */
    public function setPermission($permission)
    {
        $this->permission = $permission;
    }
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID', type: 'integer', unique: true, nullable: false)]
    private $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getMemberId(){
        try{
            return $this->member->getId();
        }
        catch (\Exception $ex){
            return 0;
        }
    }

    public function getTeamId(){
        try{
            return $this->team->getId();
        }
        catch (\Exception $ex){
            return 0;
        }
    }

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'MemberID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class, inversedBy: 'team_memberships')]
    private $member;

    /**
     * @var ChatTeam
     */
    #[ORM\JoinColumn(name: 'ChatTeamID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\ChatTeam::class, inversedBy: 'members')]
    private $team;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Permission', type: 'string')]
    private $permission;

}