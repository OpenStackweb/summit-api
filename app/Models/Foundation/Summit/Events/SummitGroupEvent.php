<?php namespace models\summit;
/**
 * Copyright 2017 OpenStack Foundation
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
use Doctrine\Common\Collections\ArrayCollection;
use models\main\Group;

/**
 * Class SummitGroupEvent
 * @package models\summit
 */
#[ORM\Table(name: 'SummitGroupEvent')]
#[ORM\Entity]
class SummitGroupEvent extends SummitEvent
{
    #[ORM\JoinTable(name: 'SummitGroupEvent_Groups')]
    #[ORM\JoinColumn(name: '`SummitGroupEventID`', referencedColumnName: 'ID')]
    #[ORM\InverseJoinColumn(name: 'GroupID', referencedColumnName: 'ID')]
    #[ORM\ManyToMany(targetEntity: \models\main\Group::class)]
    private $groups;

    const ClassName = 'SummitGroupEvent';
    /**
     * @return string
     */
    public function getClassName():string{
        return self::ClassName;
    }

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }

    /**
     * @return Group[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param ArrayCollection $groups
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
    }

    /**
     * @return int[]
     */
    public function getGroupsIds(){
        $ids = [];
        foreach ($this->getGroups() as $g){
            $ids[] = intval($g->getId());
        }
        return $ids;
    }

    /**
     * @param Group $group
     */
    public function addGroup(Group $group){
        $this->groups->add($group);
    }

    public function clearGroups(){
        $this->groups->clear();
    }

}