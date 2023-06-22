<?php namespace App\Jobs\Emails\ProposedSchedule;
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
use App\Jobs\Emails\AbstractEmailJob;
use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleLock;
use Exception;

/**
 * Class SubmitForReviewEmail
 * @package App\Jobs\Emails\Schedule
 */
class SubmitForReviewEmail extends AbstractEmailJob
{

    /**
     * SubmitForReviewEmail constructor.
     * @param SummitProposedScheduleLock $lock
     */
    public function __construct(SummitProposedScheduleLock $lock)
    {
        $summit = $lock->getProposedSchedule()->getSummit();
        $submitter = $lock->getCreatedBy()->getMember();
        $payload = [];

        $payload['summit_name']         = $summit->getName();
        $payload['summit_logo']         = $summit->getLogoUrl();
        $payload['submitter_fullname']  = $submitter->getFullName();
        $payload['submitter_email']     = $submitter->getEmail();
        $payload['track']               = $lock->getTrack()->getTitle();
        $payload['track_id']            = $lock->getTrack()->getId();
        $payload['message']             = $lock->getReason() ?? "";

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);
        $to_email = $this->getEmailRecipientFromEmailEvent($summit);

        if (is_null($to_email))
            throw new Exception("SubmitForReviewEmail::__construct - there is no registered recipient");

        parent::__construct($payload, $template_identifier, $to_email);
    }

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_PROPOSED_SCHEDULE_SUBMIT_FOR_REVIEW';
    const EVENT_NAME = 'SUMMIT_PROPOSED_SCHEDULE_SUBMIT_FOR_REVIEW';
    const DEFAULT_TEMPLATE = 'PROPOSED_SCHEDULE_SUBMIT_FOR_REVIEW';
}