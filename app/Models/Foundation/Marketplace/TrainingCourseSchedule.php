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
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity
 * @ORM\Table(name="TrainingCourseSchedule")
 * Class Distribution
 * @package App\Models\Foundation\Marketplace
 */
class TrainingCourseSchedule extends SilverstripeBaseModel
{
    const ClassName = 'TrainingCourseSchedule';

    /**
     * @return string
     */
    public function getClassName():string
    {
        return self::ClassName;
    }

    /**
     * @ORM\ManyToOne(targetEntity="TrainingCourse",inversedBy="schedules", fetch="LAZY")
     * @ORM\JoinColumn(name="CourseID", referencedColumnName="ID")
     * @var TrainingCourse
     */
    private $course;

    /**
     * @ORM\OneToMany(targetEntity="TrainingCourseScheduleTime", mappedBy="location", cascade={"persist"}, orphanRemoval=true)
     * @var TrainingCourseScheduleTime[]
     */
    private $times;

    /**
     * @ORM\Column(name="City", type="string")
     * @var string
     */
    private $city;

    /**
     * @ORM\Column(name="State", type="string")
     * @var string
     */
    private $state;

    /**
     * @ORM\Column(name="Country", type="string")
     * @var string
     */
    private $country;

    public function getCourse(): TrainingCourse
    {
        return $this->course;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function __construct(){
        parent::__construct();
        $this->times = new ArrayCollection();
    }

    /**
     * @return TrainingCourseScheduleTime[]
     */
    public function getTimes()
    {
        return $this->times;
    }
}