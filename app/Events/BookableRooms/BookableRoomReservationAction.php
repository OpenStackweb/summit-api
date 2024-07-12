<?php namespace App\Events;
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
use Illuminate\Queue\SerializesModels;
/**
 * Class BookableRoomReservationAction
 * @package App\Events
 */
abstract class BookableRoomReservationAction {
  use SerializesModels;

  /**
   * @var int
   */
  private $reservation_id;

  /**
   * BookableRoomReservationAction constructor.
   * @param int $reservation_id
   */
  public function __construct(int $reservation_id) {
    $this->reservation_id = $reservation_id;
  }

  /**
   * @return int
   */
  public function getReservationId(): int {
    return $this->reservation_id;
  }
}
