<?php namespace App\Models\Foundation\Marketplace;
/*
 * Copyright 2026 OpenStack Foundation
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
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity
 * @ORM\Table(name="TrainingCourse")
 * Class TrainingCourse
 * @package App\Models\Foundation\Marketplace
 */
class TrainingCourse extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getTypeId' => 'type',
        'getLevelId' => 'level',
        'getTrainingServiceId' => 'training_service',
    ];

    protected $hasPropertyMappings = [
        'hasType' => 'type',
        'hasLevel' => 'level',
        'hasTrainingService' => 'training_service',
    ];

    const ClassName = 'TrainingCourse';

    /**
     * @return string
     */
    public function getClassName():string
    {
        return self::ClassName;
    }

    /**
     * @ORM\Column(name="Name", type="string")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(name="Link", type="string")
     * @var string
     */
    protected $link;

    /**
     * @ORM\Column(name="Description", type="string")
     * @var string
     */
    protected $description;

    /**
     * @ORM\Column(name="Paid", type="boolean")
     * @var bool
     */
    protected $is_paid;

    /**
     * @ORM\Column(name="Online", type="boolean")
     * @var bool
     */
    protected $is_online;

    /**
     * @ORM\ManyToOne(targetEntity="TrainingCourseType")
     * @ORM\JoinColumn(name="TypeID", referencedColumnName="ID")
     * @var TrainingCourseType
     */
    protected $type;

    /**
     * @ORM\ManyToOne(targetEntity="TrainingCourseLevel")
     * @ORM\JoinColumn(name="LevelID", referencedColumnName="ID")
     * @var TrainingCourseLevel
     */
    protected $level;

    /**
     * @ORM\ManyToOne(targetEntity="TrainingService",inversedBy="courses", fetch="LAZY")
     * @ORM\JoinColumn(name="TrainingServiceID", referencedColumnName="ID")
     * @var TrainingService
     */
    protected $training_service;

    /**
     * @ORM\OneToMany(targetEntity="TrainingCourseSchedule", mappedBy="course", cascade={"persist"}, orphanRemoval=true)
     * @var TrainingCourseSchedule[]
     */
    protected $schedules;


    /**
     * @ORM\ManyToMany(targetEntity="Project", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="TrainingCourse_Projects",
     *      joinColumns={@ORM\JoinColumn(name="TrainingCourseID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="ProjectID", referencedColumnName="ID")}
     *      )
     * @var Project[]
     */
    private $projects;

    /**
     * @ORM\ManyToMany(targetEntity="TrainingCoursePrerequisite", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="TrainingCourse_Prerequisites",
     *      joinColumns={@ORM\JoinColumn(name="TrainingCourseID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="TrainingCoursePrerequisiteID", referencedColumnName="ID")}
     *      )
     * @var TrainingCoursePrerequisite[]
     */
    private $prerequisites;

    public function getName(): string
    {
        return $this->name;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isPaid(): bool
    {
        return $this->is_paid;
    }

    public function isOnline(): bool
    {
        return $this->is_online;
    }

    public function getType(): TrainingCourseType
    {
        return $this->type;
    }

    public function getLevel(): TrainingCourseLevel
    {
        return $this->level;
    }

    public function getTrainingService(): TrainingService
    {
        return $this->training_service;
    }

    public function __construct(){
        parent::__construct();
        $this->schedules = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->prerequisites = new ArrayCollection();
    }

    public function getSchedules(){
        return $this->schedules;
    }

    public function getProjects(){
        return $this->projects;
    }

    public function getPrerequisites(){
        return $this->prerequisites;
    }

}