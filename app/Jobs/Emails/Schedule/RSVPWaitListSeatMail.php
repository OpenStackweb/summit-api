<?php namespace App\Jobs\Emails\Schedule;
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

/**
 * Class RSVPWaitListSeatMail
 * @package App\Jobs\Emails\Schedule
 */
class RSVPWaitListSeatMail extends RSVPMail {
  protected function getEmailEventSlug(): string {
    return self::EVENT_SLUG;
  }

  // metadata
  const EVENT_SLUG = "SUMMIT_SCHEDULE_RSVP_WAITLIST_SEAT_CREATION";
  const EVENT_NAME = "SUMMIT_SCHEDULE_RSVP_WAITLIST_SEAT_CREATION";
  const DEFAULT_TEMPLATE = "SUMMIT_SCHEDULE_RSVP_WAITLIST_SEAT";
}
