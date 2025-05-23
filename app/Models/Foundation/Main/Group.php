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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @package models\main
 */
#[ORM\Table(name: '`Group`')]
#[ORM\Entity(repositoryClass: \repositories\main\DoctrineGroupRepository::class)]
class Group extends SilverstripeBaseModel
{

    public function __construct(){
        parent::__construct();
        $this->members  = new ArrayCollection();
        $this->groups   = new ArrayCollection();
        $this->is_external = false;
    }

    /**
     * @param Member $member
     */
    public function addMember(Member $member){
        if($this->members->contains($member)) return;
        $this->members->add($member);
    }

    /**
     * @param Member $member
     */
    public function removeMember(Member $member){
        if(!$this->members->contains($member)) return;
        $this->members->removeElement($member);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return mixed
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * @param mixed $members
     */
    public function setMembers($members)
    {
        $this->members = $members;
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param mixed $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return mixed
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param mixed $groups
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
    }

    /**
     * @var string
     */
    #[ORM\Column(name: 'Title', type: 'string')]
    private $title;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Description', type: 'string')]
    private $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Code', type: 'string')]
    private $code;

    #[ORM\ManyToMany(targetEntity: \models\main\Member::class, mappedBy: 'groups', fetch: 'EXTRA_LAZY')]
    private $members;

    #[ORM\JoinColumn(name: 'ParentID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \Group::class, inversedBy: 'groups')]
    private $parent;

    #[ORM\OneToMany(targetEntity: \Group::class, mappedBy: 'parent')]
    private $groups;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'IsExternal', type: 'boolean')]
    private $is_external;

    public function setExternal(){
        $this->is_external = true;
    }

    public function isExternal():bool {
        return $this->is_external;
    }
}