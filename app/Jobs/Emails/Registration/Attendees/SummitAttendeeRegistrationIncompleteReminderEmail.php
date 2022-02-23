<?php namespace App\Jobs\Emails;

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
use Illuminate\Support\Facades\Log;
use models\summit\SummitAttendee;

/**
 * Class SummitAttendeeRegistrationIncompleteReminderEmail
 * @package App\Jobs\Emails
 */
class SummitAttendeeRegistrationIncompleteReminderEmail extends AbstractSummitAttendeeTicketEmail
{
    /**
     * SummitAttendeeRegistrationIncompleteReminderEmail constructor.
     * @param SummitAttendee $attendee
     */
    public function __construct(SummitAttendee $attendee)
    {
        $payload = [];
        $payload['owner_full_name'] = $attendee->getFullName();
        $payload['owner_first_name'] =$attendee->getFirstName();
        $payload['owner_last_name'] = $attendee->getSurname();
        $payload['owner_company'] = $attendee->getCompanyName();
        $payload['owner_email']  = $attendee->getEmail();

        if(empty($payload['owner_full_name'])){
            $payload['owner_full_name'] = $payload['owner_email'];
        }

        $summit = $attendee->getSummit();

        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['summit_marketing_site_url'] = $summit->getMarketingSiteUrl();

        $base_url = Config::get("registration.dashboard_base_url", null);
        if (empty($base_url))
            throw new \InvalidArgumentException("missing dashboard_base_url value");

        $back_url = Config::get("registration.dashboard_back_url", null);
        if (empty($back_url))
            throw new \InvalidArgumentException("missing dashboard_back_url value");

        $payload['manage_orders_url'] = sprintf($back_url, $base_url);

        $support_email = $summit->getSupportEmail();
        $payload['support_email'] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);

        if (empty($payload['support_email']))
            throw new \InvalidArgumentException("missing support_email value");

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);
        Log::debug(sprintf("SummitAttendeeRegistrationIncompleteReminderEmail::__construct payload %s template %s",
            json_encode($payload), $template_identifier));

        parent::__construct($payload, $template_identifier, $payload['owner_email'] );
    }

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_INCOMPLETE_ATTENDEE_REMINDER';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_INCOMPLETE_ATTENDEE_REMINDER';
    const DEFAULT_TEMPLATE = 'SUMMIT_REGISTRATION_INCOMPLETE_ATTENDEE_REMINDER';
}