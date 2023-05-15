<?php namespace App\Models\Foundation\Summit\ProposedSchedule;
/*
 * Copyright 2023 OpenStack Foundation
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
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity
 * @ORM\Table(name="SummitProposedScheduleAllowedDay")
 * Class SummitProposedScheduleAllowedDay
 * @package App\Models\Foundation\Summit\ProposedSchedule
 */
class SummitProposedScheduleAllowedDay extends SilverstripeBaseModel
{

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getAllowedLocationId' => 'allowed_location',
    ];

    protected $hasPropertyMappings = [
        'hasAllowedLocation' => 'allowed_location',
    ];

    /**
     * @ORM\Column(name="`Day`", type="string")
     * @var string
     */
    private $day;

    /**
     * @ORM\Column(name="`From`", type="smallint")
     * @var int
     */
    private $from;

    /**
     * @ORM\Column(name="`To`", type="smallint")
     * @var int
     */
    private $to;

    /**
     * @ORM\ManyToOne(targetEntity="SummitProposedScheduleAllowedLocation")
     * @ORM\JoinColumn(name="AllowedLocationID", referencedColumnName="ID")
     * @var SummitProposedScheduleAllowedLocation
     */
    private $allowed_location;

    /**
     * @param SummitProposedScheduleAllowedLocation $allowed_location
     * @param string $day
     * @param int|null $from
     * @param int|null $to
     */
    public function __construct(
        SummitProposedScheduleAllowedLocation $allowed_location,
        string $day,
        ?int $from=null,
        ?int $to=null
    )
    {
        parent::__construct();
        $this->allowed_location = $allowed_location;
        $this->day = $day;
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @return string
     */
    public function getDay(): string
    {
        return $this->day;
    }

    /**
     * @return int|null
     */
    public function getFrom(): ?int
    {
        return $this->from;
    }

    /**
     * @return int|null
     */
    public function getTo(): ?int
    {
        return $this->to;
    }

    /**
     * @return SummitProposedScheduleAllowedLocation
     */
    public function getAllowedLocation(): SummitProposedScheduleAllowedLocation
    {
        return $this->allowed_location;
    }


}