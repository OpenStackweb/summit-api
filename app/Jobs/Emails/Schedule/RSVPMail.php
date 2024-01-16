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

use App\Jobs\Emails\AbstractSummitEmailJob;
use App\Jobs\Emails\IMailTemplatesConstants;
use models\summit\RSVP;

/**
 * Class RSVPMail
 * @package App\Jobs\Emails\Schedule
 */
abstract class RSVPMail extends AbstractSummitEmailJob
{
    /**
     * RSVPMail constructor.
     * @param RSVP $rsvp
     */
    public function __construct(RSVP $rsvp)
    {
        $payload = [];
        $event = $rsvp->getEvent();
        $summit = $event->getSummit();
        $owner = $rsvp->getOwner();
        $payload[IMailTemplatesConstants::owner_fullname] = $owner->getFullName();
        $payload[IMailTemplatesConstants::owner_email] = $owner->getEmail();
        $payload[IMailTemplatesConstants::event_title] = $event->getTitle();
        $payload[IMailTemplatesConstants::event_date] = $event->getDateNice();
        $payload[IMailTemplatesConstants::confirmation_number] = $rsvp->getConfirmationNumber();
        $payload[IMailTemplatesConstants::summit_schedule_default_event_detail_url] = $summit->getScheduleDefaultEventDetailUrl();
        $event_uri = $rsvp->getEventUri();

        $payload[IMailTemplatesConstants::event_uri] = '';

        if (!empty($event_uri)) {
            // we got a valid origin
            $payload[IMailTemplatesConstants::event_uri] = $event_uri;
        }
        // if we dont have a custom event uri, try to get default one
        if (empty($payload[IMailTemplatesConstants::event_uri]) && !empty($payload[IMailTemplatesConstants::summit_schedule_default_event_detail_url])) {
            $payload[IMailTemplatesConstants::event_uri] = str_replace(":event_id", $event->getId(), $payload[IMailTemplatesConstants::summit_schedule_default_event_detail_url]);
        }

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($summit, $payload, $template_identifier, $payload[IMailTemplatesConstants::owner_email]);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{
        $payload = [];
        $payload[IMailTemplatesConstants::speaker_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_fullname]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::event_title]['type'] = 'string';
        $payload[IMailTemplatesConstants::event_date]['type'] = 'string';
        $payload[IMailTemplatesConstants::confirmation_number]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_schedule_default_event_detail_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::event_uri]['type'] = 'string';

        return $payload;
    }
}