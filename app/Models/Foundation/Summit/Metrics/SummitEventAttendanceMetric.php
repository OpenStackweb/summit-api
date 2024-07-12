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

use Doctrine\ORM\Mapping as ORM;
use models\exceptions\ValidationException;
use models\main\Member;
use models\utils\One2ManyPropertyTrait;

/**
 * Class SummitEventAttendanceMetric
 * @ORM\Entity
 * @ORM\Table(name="SummitEventAttendanceMetric")
 * @package models\summit
 */
class SummitEventAttendanceMetric extends SummitMetric {
  use One2ManyPropertyTrait;

  protected $getIdMappings = [
    "getRoomId" => "room",
    "getEventId" => "event",
    "getAttendeeId" => "attendee",
    "getCreatedById" => "created_by",
  ];

  protected $hasPropertyMappings = [
    "hasRoom" => "room",
    "hasEvent" => "event",
    "hasAttendee" => "attendee",
    "hasCreatedBy" => "created_by",
  ];

  const SubTypeVirtual = "VIRTUAL";
  const SubTypeOnSite = "ON_SITE";

  /**
   * @ORM\Column(name="SubType", type="string")
   * @var string|null
   */
  private $sub_type;

  /**
   * @ORM\ManyToOne(targetEntity="models\summit\SummitEvent", inversedBy="attendance_metrics", fetch="LAZY")
   * @ORM\JoinColumn(name="SummitEventID", referencedColumnName="ID", onDelete="CASCADE")
   * @var SummitEvent|null
   */
  private $event;

  /**
   * @ORM\ManyToOne(targetEntity="models\summit\SummitVenueRoom", fetch="LAZY")
   * @ORM\JoinColumn(name="SummitVenueRoomID", referencedColumnName="ID", onDelete="CASCADE")
   * @var SummitVenueRoom|null
   */
  private $room;

  /**
   * @ORM\ManyToOne(targetEntity="models\summit\SummitAttendee", fetch="LAZY")
   * @ORM\JoinColumn(name="SummitAttendeeID", referencedColumnName="ID", onDelete="CASCADE")
   * @var SummitAttendee|null
   */
  private $attendee;

  /**
   * @ORM\ManyToOne(targetEntity="models\main\Member")
   * @ORM\JoinColumn(name="CreatedByID", referencedColumnName="ID", onDelete="CASCADE")
   * @var Member|null
   */
  private $created_by;

  /**
   * @return SummitEvent|null
   */
  public function getEvent(): ?SummitEvent {
    return $this->event;
  }

  /**
   * @param SummitEvent $event
   */
  public function setEvent(SummitEvent $event): void {
    $this->event = $event;
  }

  /**
   * @return SummitVenueRoom|null
   */
  public function getRoom(): ?SummitVenueRoom {
    return $this->room;
  }

  /**
   * @param SummitVenueRoom $room
   */
  public function setRoom(SummitVenueRoom $room): void {
    $this->room = $room;
  }

  /**
   * @return SummitAttendee|null
   */
  public function getAttendee(): ?SummitAttendee {
    return $this->attendee;
  }

  /**
   * @param SummitAttendee $attendee
   */
  public function setAttendee(SummitAttendee $attendee): void {
    $this->attendee = $attendee;
  }

  /**
   * @return Member|null
   */
  public function getCreatedBy(): ?Member {
    return $this->created_by;
  }

  /**
   * @param Member|null $created_by
   */
  public function setCreatedBy(?Member $created_by): void {
    $this->created_by = $created_by;
  }

  public function __construct() {
    parent::__construct();
    $this->sub_type = self::SubTypeVirtual;
  }

  /**
   * @param SummitAttendee $attendee
   * @param SummitVenueRoom|null $room
   * @param SummitEvent|null $event
   * @return SummitMetric|static
   * @throws \Exception
   */
  public static function buildOnSiteMetric(
    ?Member $creator,
    SummitAttendee $attendee,
    ?SummitVenueRoom $room = null,
    ?SummitEvent $event = null,
  ) {
    $metric = new static();
    $metric->attendee = $attendee;
    if ($attendee->hasMember()) {
      $metric->member = $attendee->getMember();
    }
    $metric->ingress_date = new \DateTime("now", new \DateTimeZone("UTC"));
    $metric->room = $room;
    $metric->event = $event;
    $metric->created_by = $creator;

    $metric->type = ISummitMetricType::Room;
    if (!is_null($event)) {
      $metric->type = ISummitMetricType::Event;
    }
    $metric->markAsOnSite();
    return $metric;
  }

  /**
   * @throws ValidationException
   */
  public function abandon() {
    $this->outgress_date = new \DateTime("now", new \DateTimeZone("UTC"));
  }

  /**
   * @return string|null
   */
  public function getSubType(): ?string {
    return $this->sub_type;
  }

  public function markAsVirtual(): void {
    $this->sub_type = self::SubTypeVirtual;
  }

  public function markAsOnSite(): void {
    $this->sub_type = self::SubTypeOnSite;
  }
}
