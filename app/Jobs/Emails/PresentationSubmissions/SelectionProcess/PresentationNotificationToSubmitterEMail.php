<?php namespace App\Jobs\Emails\PresentationSubmissions\SelectionProcess;
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
use Illuminate\Support\Facades\Config;
use models\main\Member;
use models\summit\Presentation;
/**
 * Class PresentationNotificationToSubmitterEMail
 * @package App\Jobs\Emails\PresentationSubmissions\SelectionProcess
 */
final class PresentationNotificationToSubmitterEMail extends PresentationNotificationEmail
{

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_PRESENTATION_SUBMITTER_NOTIFICATION';
    const EVENT_NAME = 'SUMMIT_PRESENTATION_SUBMITTER_NOTIFICATION';
    const DEFAULT_TEMPLATE = 'SUMMIT_PRESENTATION_SUBMITTER_NOTIFICATION';

    public function __construct(Presentation $presentation, Member $creator, bool $isDryRun = false)
    {
        parent::__construct($presentation, $isDryRun ? Config::get("mail.to_dry_run"): $creator->getEmail());

        $this->payload['recipient_email'] = $creator->getEmail();
        $this->payload['recipient_full_name'] = $creator->getFullName();
        $this->payload['recipient_first_name'] = $creator->getFirstName();
        $this->payload['recipient_last_name'] = $creator->getLastName();

        if(empty($this->payload['recipient_full_name'])){
            $this->payload['recipient_full_name'] = $this->payload['recipient_email'] ;
        }

    }

}