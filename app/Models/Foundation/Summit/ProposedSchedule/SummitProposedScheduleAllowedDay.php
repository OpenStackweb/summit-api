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
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitProposedScheduleAllowedDayRepository")
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
     * @ORM\Column(name="`Day`", type="datetime")
     * @var \DateTime
     */
    private $day;

    /**
     * @ORM\Column(name="`OpeningHour`", type="smallint")
     * @var int
     */
    private $opening_hour;

    /**
     * @ORM\Column(name="`ClosingHour`", type="smallint")
     * @var int
     */
    private $closing_hour;

    /**
     * @ORM\ManyToOne(targetEntity="SummitProposedScheduleAllowedLocation",inversedBy="allowed_timeframes")
     * @ORM\JoinColumn(name="AllowedLocationID", referencedColumnName="ID")
     * @var SummitProposedScheduleAllowedLocation
     */
    private $allowed_location;

    /**
     * @param SummitProposedScheduleAllowedLocation $allowed_location
     * @param string $day
     * @param int|null $opening_hour
     * @param int|null $closing_hour
     */
    public function __construct(
        SummitProposedScheduleAllowedLocation $allowed_location,
        \DateTime $day,
        ?int $opening_hour=null,
        ?int $closing_hour=null
    )
    {
        parent::__construct();
        $this->allowed_location = $allowed_location;
        $this->day = $day;
        $this->opening_hour = $opening_hour;
        $this->closing_hour = $closing_hour;
    }

    /**
     * @return \DateTime
     */
    public function getDay(): \DateTime
    {
        return $this->day;
    }

    /**
     * @return int|null
     */
    public function getOpeningHour(): ?int
    {
        return $this->opening_hour;
    }

    /**
     * @param int|null $opening_hour
     */
    public function setOpeningHour(?int $opening_hour)
    {
        $this->opening_hour = $opening_hour;
    }

    /**
     * @return int|null
     */
    public function getClosingHour(): ?int
    {
        return $this->closing_hour;
    }

    /**
     * @param int|null $closing_hour
     */
    public function setClosingHour(?int $closing_hour)
    {
        $this->closing_hour = $closing_hour;
    }

    /**
     * @return SummitProposedScheduleAllowedLocation
     */
    public function getAllowedLocation(): SummitProposedScheduleAllowedLocation
    {
        return $this->allowed_location;
    }

    public function clearAllowedLocation():void{
        $this->allowed_location = null;
    }

    /**
     * @param \DateTime $day
     */
    public function setDay(\DateTime $day): void
    {
        $this->day = $day;
    }

}