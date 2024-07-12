<?php namespace App\Jobs\Emails\PresentationSubmissions\SelectionProcess;
/**
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

use Illuminate\Support\Facades\Log;
use models\main\Member;
use models\summit\Summit;
use utils\Filter;

/**
 * Class PresentationSubmitterSelectionProcessAcceptedAlternateEmail
 * @package App\Jobs\Emails\PresentationSubmissions\SelectionProcess
 */
class PresentationSubmitterSelectionProcessAcceptedAlternateEmail extends
  PresentationSubmitterSelectionProcessEmail {
  protected function getEmailEventSlug(): string {
    return self::EVENT_SLUG;
  }

  // metadata
  const EVENT_SLUG = "SUMMIT_SUBMISSIONS_PRESENTATION_SUBMITTER_ACCEPTED_ALTERNATE";
  const EVENT_NAME = "SUMMIT_SUBMISSIONS_PRESENTATION_SUBMITTER_ACCEPTED_ALTERNATE";
  const DEFAULT_TEMPLATE = "SUMMIT_SUBMISSIONS_PRESENTATION_SUBMITTER_ACCEPTED_ALTERNATE";

  /**
   * @param Summit $summit
   * @param Member $submitter
   * @param string|null $test_email_recipient
   * @param Filter|null $filter
   */
  public function __construct(
    Summit $summit,
    Member $submitter,
    ?string $test_email_recipient,
    ?Filter $filter = null,
  ) {
    parent::__construct($summit, $submitter, $test_email_recipient, $filter);

    Log::debug(
      sprintf(
        "PresentationSubmitterSelectionProcessAcceptedAlternateEmail::__construct payload %s",
        json_encode($this->payload),
      ),
    );
  }
}
