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

use models\summit\SummitAttendee;

/**
 * Class AbstractEmailAction
 * @package App\Services\Model\Strategies\EmailActions
 */
abstract class AbstractEmailAction {
  /**
   * @var String
   */
  protected $flow_event;

  /**
   * AbstractEmailAction constructor.
   * @param String $flow_event
   */
  public function __construct(string $flow_event) {
    $this->flow_event = $flow_event;
  }

  /**
   * @param SummitAttendee $attendee
   * @param string|null $test_email_recipient
   * @param callable|null $onSuccess
   * @param callable|null $onError
   * @return mixed
   */
  abstract public function process(
    SummitAttendee $attendee,
    ?string $test_email_recipient = null,
    callable $onSuccess = null,
    callable $onError = null,
  );
}
