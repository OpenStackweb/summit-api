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
use models\summit\Presentation;
use models\summit\PresentationSpeaker;
/**
 * Class PresentationNotificationToModeratorEMail
 * @package App\Jobs\Emails\PresentationSubmissions\SelectionProcess
 */
final class PresentationNotificationToModeratorEMail extends PresentationNotificationEmail
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_PRESENTATION_MODERATOR_NOTIFICATION';
    const EVENT_NAME = 'SUMMIT_PRESENTATION_MODERATOR_NOTIFICATION';
    const DEFAULT_TEMPLATE = 'SUMMIT_PRESENTATION_MODERATOR_NOTIFICATION';

    public function __construct(Presentation $presentation, PresentationSpeaker $modeator, bool $isDryRun = false)
    {
        parent::__construct($presentation, $isDryRun ? Config::get("mail.to_dry_run"): $modeator->getEmail());

        $this->payload['recipient_email'] = $modeator->getEmail();
        $this->payload['recipient_full_name'] = $modeator->getFullName();
        $this->payload['recipient_first_name'] = $modeator->getFirstName();
        $this->payload['recipient_last_name'] = $modeator->getLastName();

        if(empty($this->payload['recipient_full_name'])){
            $this->payload['recipient_full_name'] = $this->payload['recipient_email'] ;
        }

    }

}