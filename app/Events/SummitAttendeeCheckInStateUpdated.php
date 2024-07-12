<?php namespace App\Events;
/*
 * Copyright 2024 OpenStack Foundation
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
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
/**
 * Class SummitAttendeeCheckInStateUpdated
 * @package App\Events
 */
class SummitAttendeeCheckInStateUpdated extends Event {
  use Dispatchable, SerializesModels;

  /**
   * @var int
   */
  private $attendee_id;

  /**
   * @param int $attendee_id
   */
  public function __construct(int $attendee_id) {
    Log::debug(sprintf("SummitAttendeeCheckInStateUpdated::constructor attendee %s", $attendee_id));
    $this->attendee_id = $attendee_id;
  }

  public function getAttendeeId(): int {
    return $this->attendee_id;
  }
}
