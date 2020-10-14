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
use models\main\Member;
use Doctrine\ORM\Mapping AS ORM;
/**
 * Class SummitEventAttendanceMetric
 * @ORM\Entity
 * @ORM\Table(name="SummitEventAttendanceMetric")
 * @package models\summit
 */
class SummitEventAttendanceMetric extends SummitMetric
{
    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitEvent", inversedBy="attendance_metrics", fetch="LAZY")
     * @ORM\JoinColumn(name="SummitEventID", referencedColumnName="ID", onDelete="CASCADE")
     * @var SummitEvent
     */
    private $event;

    /**
     * @return SummitEvent
     */
    public function getEvent(): ?SummitEvent
    {
        return $this->event;
    }

    /**
     * @param SummitEvent $event
     */
    public function setEvent(SummitEvent $event): void
    {
        $this->event = $event;
    }

    /**
     * @return int
     */
    public function getEventId(){
        try {
            return is_null($this->event) ? 0 : $this->event->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

}