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
use models\main\Group;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitAttendeeBadgePrintRuleRepository")
 * @ORM\Table(name="SummitAttendeeBadgePrintRule")
 * Class SummitAttendeeBadgePrintRule
 * @package models\summit
 */
class SummitAttendeeBadgePrintRule extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getGroupId' => 'group',
    ];

    protected $hasPropertyMappings = [
        'hasGroup' => 'group',
    ];

    /**
     * @ORM\Column(name="MaxPrintTimes", type="integer")
     * @var int
     */
    private $max_print_times;

    /**
     * @ORM\OneToOne(targetEntity="models\main\Group")
     * @ORM\JoinColumn(name="GroupID", referencedColumnName="ID")
     * @var Group
     */
    private $group;

    public function __construct()
    {
        parent::__construct();
        $this->max_print_times = 0;
    }

    /**
     * @return int
     */
    public function getMaxPrintTimes(): int
    {
        return $this->max_print_times;
    }

    /**
     * @param int $max_print_times
     */
    public function setMaxPrintTimes(int $max_print_times): void
    {
        $this->max_print_times = $max_print_times;
    }

    /**
     * @return Group
     */
    public function getGroup(): Group
    {
        return $this->group;
    }

    /**
     * @param Group $group
     */
    public function setGroup(Group $group): void
    {
        $this->group = $group;
    }

    /**
     * @param SummitAttendeeBadge $badge
     * @return bool
     */
    public function canPrintBadge(SummitAttendeeBadge $badge):bool{
        if($this->max_print_times == 0) return true;
        $count = $badge->getPrintCountPerGroup($this->group);
        return $count < $this->max_print_times;
    }
}