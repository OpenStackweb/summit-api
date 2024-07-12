<?php namespace App\Jobs\Emails\PresentationSubmissions\Invitations;
/*
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
use models\summit\SummitSubmissionInvitation;

/**
 * Class ReInviteSubmissionEmail
 * @package App\Jobs\Emails\PresentationSubmissions\Invitations
 */
class ReInviteSubmissionEmail extends InviteSubmissionEmail {
  /**
   * @param SummitSubmissionInvitation $invitation
   * @param array $extra_data
   */
  public function __construct(SummitSubmissionInvitation $invitation, array $extra_data) {
    Log::debug(
      sprintf(
        "ReInviteSubmissionEmail::____construct invitation %s email %s",
        $invitation->getId(),
        $invitation->getEmail(),
      ),
    );
    parent::__construct($invitation, $extra_data);
  }

  protected function getEmailEventSlug(): string {
    return self::EVENT_SLUG;
  }

  // metadata
  const EVENT_SLUG = "SUMMIT_SUBMISSION_REINVITE_REGISTRATION";
  const EVENT_NAME = "SUMMIT_SUBMISSION_REINVITE_REGISTRATION";
  const DEFAULT_TEMPLATE = "SUMMIT_SUBMISSION_REINVITE_REGISTRATION";
}
