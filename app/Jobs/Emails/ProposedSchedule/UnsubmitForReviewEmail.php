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
/**
 * Class UnsubmitForReviewEmail
 * @package App\Jobs\Emails\Schedule
 */
class UnsubmitForReviewEmail extends AbstractEmailJob
{

    /**
     * UnsubmitForReviewEmail constructor.
     * @param SummitProposedScheduleLock $lock
     * @param string $message
     */
    public function __construct(SummitProposedScheduleLock $lock, string $message)
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
        $payload['message']             = $message ?? "";

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $submitter->getEmail());
    }

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_PROPOSED_SCHEDULE_UNSUBMIT_FOR_REVIEW';
    const EVENT_NAME = 'SUMMIT_PROPOSED_SCHEDULE_UNSUBMIT_FOR_REVIEW';
    const DEFAULT_TEMPLATE = 'PROPOSED_SCHEDULE_UNSUBMIT_FOR_REVIEW';
}