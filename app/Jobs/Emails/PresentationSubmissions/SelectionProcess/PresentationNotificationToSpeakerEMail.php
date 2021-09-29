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
 * Class PresentationNotificationToSpeakerEMail
 * @package App\Jobs\Emails\PresentationSubmissions\SelectionProcess
 */
final class PresentationNotificationToSpeakerEMail extends PresentationNotificationEmail
{

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_PRESENTATION_SPEAKER_NOTIFICATION';
    const EVENT_NAME = 'SUMMIT_PRESENTATION_SPEAKER_NOTIFICATION';
    const DEFAULT_TEMPLATE = 'SUMMIT_PRESENTATION_SPEAKER_NOTIFICATION';

    public function __construct(Presentation $presentation, PresentationSpeaker $speaker, bool $isDryRun = false)
    {
        parent::__construct($presentation, $isDryRun ? Config::get("mail.to_dry_run"): $speaker->getEmail());

        $this->payload['recipient_email'] = $speaker->getEmail();
        $this->payload['recipient_full_name'] = $speaker->getFullName();
        $this->payload['recipient_first_name'] = $speaker->getFirstName();
        $this->payload['recipient_last_name'] = $speaker->getLastName();

        if(empty($this->payload['recipient_full_name'])){
            $this->payload['recipient_full_name'] = $this->payload['recipient_email'] ;
        }

    }

}