<?php namespace App\Services\Model\Strategies\EmailActions;
/**
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

use App\Jobs\Emails\SummitAttendeeAllTicketsEditionEmail;
use App\Services\utils\IEmailExcerptService;
use Illuminate\Support\Facades\Log;
use models\summit\SummitAttendee;

/**
 * Class SummitAttendeeAllCurrentTicketsEmailStrategy
 * @package App\Services\Model\Strategies\EmailActions
 */
final class SummitAttendeeAllCurrentTicketsEmailStrategy extends AbstractEmailAction {
  /**
   * SummitAttendeeAllCurrentTicketsEmailStrategy constructor.
   * @param String $flow_event
   */
  public function __construct(string $flow_event) {
    parent::__construct($flow_event);
  }

  /**
   * @param SummitAttendee $attendee
   * @param string|null $test_email_recipient
   * @param callable|null $onSuccess
   * @param callable|null $onError
   * @return void
   */
  public function process(
    SummitAttendee $attendee,
    ?string $test_email_recipient = null,
    callable $onSuccess = null,
    callable $onError = null,
  ) {
    Log::debug(
      sprintf(
        "SummitAttendeeAllCurrentTicketsEmailStrategy::sending all tickets to attendee %s - flow event %s",
        $attendee->getEmail(),
        $this->flow_event,
      ),
    );
    SummitAttendeeAllTicketsEditionEmail::dispatch($attendee, $test_email_recipient);

    if (!is_null($onSuccess)) {
      $onSuccess($attendee->getEmail(), IEmailExcerptService::EmailLineType, $this->flow_event);
    }
  }
}
