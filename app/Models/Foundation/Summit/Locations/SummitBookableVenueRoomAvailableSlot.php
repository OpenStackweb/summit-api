<?php namespace models\summit;
/**
 * Copyright 2019 OpenStack Foundation
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
final class SummitBookableVenueRoomAvailableSlot {
  const StatusAvailable = "Available";
  const StatusBooked = "Booked";
  const StatusUnAvailable = "UnAvailable";
  /**
   * @var \DateTime
   */
  private $start_date;

  /**
   * @var \DateTime
   */
  private $end_date;

  /**
   * @var bool
   */
  private $is_free;

  /**
   * @var SummitBookableVenueRoom
   */
  private $room;

  /**
   * SummitBookableVenueRoomAvailableSlot constructor.
   * @param SummitBookableVenueRoom $room
   * @param \DateTime $start_date
   * @param \DateTime $end_date
   * @param bool $is_free
   */
  public function __construct(
    SummitBookableVenueRoom $room,
    \DateTime $start_date,
    \DateTime $end_date,
    bool $is_free,
  ) {
    $this->room = $room;
    $this->start_date = $start_date;
    $this->end_date = $end_date;
    $this->is_free = $is_free;
  }

  /**
   * @return \DateTime
   */
  public function getStartDate(): \DateTime {
    return $this->start_date;
  }

  /**
   * @return \DateTime
   */
  public function getEndDate(): \DateTime {
    return $this->end_date;
  }

  /**
   * @return \DateTime
   */
  public function getLocalStartDate(): \DateTime {
    return $this->room->getSummit()->convertDateFromUTC2TimeZone($this->start_date);
  }

  /**
   * @return \DateTime
   */
  public function getLocalEndDate(): \DateTime {
    return $this->room->getSummit()->convertDateFromUTC2TimeZone($this->end_date);
  }

  public function isFree(): bool {
    return $this->is_free;
  }

  /**
   * @return string
   */
  public function getStatus(): string {
    $res = $this->is_free ? self::StatusAvailable : self::StatusBooked;
    $now_utc = new \DateTime("now", new \DateTimeZone("UTC"));
    // we cant choose the slots on the past or slots that are going on
    if (
      $this->is_free &&
      ($now_utc > $this->end_date || ($this->start_date <= $now_utc && $now_utc <= $this->end_date))
    ) {
      $res = self::StatusUnAvailable;
    }
    return $res;
  }
}
