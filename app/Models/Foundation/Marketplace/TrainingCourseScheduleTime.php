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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity
 * @ORM\Table(name="TrainingCourseScheduleTime")
 * Class TrainingCourseScheduleTime
 * @package App\Models\Foundation\Marketplace
 */
class TrainingCourseScheduleTime extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getLocationId' => 'location',
    ];

    protected $hasPropertyMappings = [
        'hasLocation' => 'location',
    ];

    const ClassName = 'TrainingCourseScheduleTime';

    /**
     * @return string
     */
    public function getClassName():string
    {
        return self::ClassName;
    }

    /**
     * @var \DateTime
     * @ORM\Column(name="StartDate", type="datetime", nullable=false)
     */
    protected $start_date;

    /**
     * @var \DateTime
     * @ORM\Column(name="EndDate", type="datetime", nullable=false)
     */
    protected $end_date;

    /**
     * @ORM\Column(name="Link", type="string")
     * @var string
     */
    protected $link;

    /**
     * @ORM\ManyToOne(targetEntity="TrainingCourseSchedule",inversedBy="times", fetch="LAZY")
     * @ORM\JoinColumn(name="LocationID", referencedColumnName="ID")
     * @var TrainingCourseSchedule
     */
    protected $location;

    public function getStartDate(): ?\DateTime
    {
        return $this->start_date;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->end_date;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function getLocation(): TrainingCourseSchedule
    {
        return $this->location;
    }

}