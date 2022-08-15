<?php namespace models\summit;
/*
 * Copyright 2022 OpenStack Foundation
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
use models\exceptions\ValidationException;
use models\utils\One2ManyPropertyTrait;
/**
 * Class SummitRoomMetric
 * @ORM\Entity
 * @ORM\Table(name="SummitRoomMetric")
 * @package models\summit
 */
class SummitRoomMetric extends SummitMetric
{

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getRoomId' => 'room',
        'getEventId' => 'event',
        'getAttendeeId' => 'attendee',
    ];

    protected $hasPropertyMappings = [
        'hasRoom' => 'room',
        'hasEvent' => 'event',
        'hasAttendee' => 'attendee',
    ];

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitEvent", inversedBy="physical_attendance_metrics", fetch="LAZY")
     * @ORM\JoinColumn(name="SummitEventID", referencedColumnName="ID", onDelete="CASCADE")
     * @var SummitEvent
     */
    private $event;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitVenueRoom", inversedBy="physical_attendance_metrics", fetch="LAZY")
     * @ORM\JoinColumn(name="SummitVenueRoomID", referencedColumnName="ID", onDelete="CASCADE")
     * @var SummitVenueRoom
     */
    private $room;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitAttendee", inversedBy="physical_metrics", fetch="LAZY")
     * @ORM\JoinColumn(name="SummitAttendeeID", referencedColumnName="ID", onDelete="CASCADE")
     * @var SummitAttendee
     */
    private $attendee;

    /**
     * @return SummitEvent
     */
    public function getEvent(): SummitEvent
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
     * @return SummitVenueRoom
     */
    public function getRoom(): SummitVenueRoom
    {
        return $this->room;
    }

    /**
     * @param SummitVenueRoom $room
     */
    public function setRoom(SummitVenueRoom $room): void
    {
        $this->room = $room;
    }

    /**
     * @return SummitAttendee
     */
    public function getAttendee(): SummitAttendee
    {
        return $this->attendee;
    }

    /**
     * @param SummitAttendee $attendee
     */
    public function setAttendee(SummitAttendee $attendee): void
    {
        $this->attendee = $attendee;
    }


    /**
     * @param SummitAttendee $attendee
     * @param SummitVenueRoom|null $room
     * @param SummitEvent|null $event
     * @return SummitMetric|static
     * @throws \Exception
     */
    public static function buildMetric(SummitAttendee $attendee, ?SummitVenueRoom $room = null, ?SummitEvent $event = null){
        $metric = new static();
        $metric->attendee = $attendee;
        $metric->ingress_date = new \DateTime('now', new \DateTimeZone('UTC'));
        $metric->room = $room;
        $metric->event = $event;
        $metric->type = ISummitMetricType::Room;
        return $metric;
    }

    /**
     * @throws ValidationException
     */
    public function abandon(){
        $this->outgress_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}