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
     * @param SummitAttendee $attendee
     * @param string|null $test_email_recipient
     */
    public function __construct
    (
        SummitAttendee $attendee,
        ?string $test_email_recipient = null
    )
    {
        $payload = [];
        $payload[IMailTemplatesConstants::owner_full_name] = $attendee->getFullName();
        $payload[IMailTemplatesConstants::owner_first_name] =$attendee->getFirstName();
        $payload[IMailTemplatesConstants::owner_last_name] = $attendee->getSurname();
        $payload[IMailTemplatesConstants::owner_company] = $attendee->getCompanyName();
        $payload[IMailTemplatesConstants::owner_email]  = $attendee->getEmail();

        if(empty($payload[IMailTemplatesConstants::owner_full_name])){
            Log::warning(sprintf("SummitAttendeeRegistrationIncompleteReminderEmail owner_full_name is empty setting email"));
            $payload[IMailTemplatesConstants::owner_full_name] = $payload[IMailTemplatesConstants::owner_email];
        }

        if(empty($payload[IMailTemplatesConstants::owner_first_name])){
            Log::warning(sprintf("SummitAttendeeRegistrationIncompleteReminderEmail owner_first_name is empty setting email"));
            $payload[IMailTemplatesConstants::owner_first_name] = $payload[IMailTemplatesConstants::owner_email];
        }

        if(empty($payload[IMailTemplatesConstants::owner_last_name])){
            Log::warning(sprintf("SummitAttendeeRegistrationIncompleteReminderEmail owner_last_name is empty setting email"));
            $payload[IMailTemplatesConstants::owner_last_name] = $payload[IMailTemplatesConstants::owner_email];
        }

        $summit = $attendee->getSummit();

        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_client_id] = $summit->getMarketingSiteOAuth2ClientId();
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_scopes] = $summit->getMarketingSiteOauth2ClientScopes();
        $support_email = $summit->getSupportEmail();
        $payload[IMailTemplatesConstants::support_email] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);

        if (empty($payload[IMailTemplatesConstants::support_email]))
            throw new \InvalidArgumentException("missing support_email value");

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);
        Log::debug(sprintf("SummitAttendeeRegistrationIncompleteReminderEmail::__construct payload %s template %s",
            json_encode($payload), $template_identifier));

        $payload[IMailTemplatesConstants::manage_orders_url] = sprintf("%s/a/my-tickets", $summit->getMarketingSiteUrl());

        if (!empty($test_email_recipient)) {
            Log::debug
            (
                sprintf
                (
                    "SummitAttendeeRegistrationIncompleteReminderEmail::__construct replacing original email %s by %s and clearing cc field",
                    $payload[IMailTemplatesConstants::owner_email],
                    $test_email_recipient
                )
            );

            $payload[IMailTemplatesConstants::owner_email] = $test_email_recipient;
            $payload[IMailTemplatesConstants::cc_email] = '';

        }
        parent::__construct($summit, $payload, $template_identifier, $payload[IMailTemplatesConstants::owner_email] );
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{

        $payload = parent::getEmailTemplateSchema();

        $payload[IMailTemplatesConstants::owner_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_first_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_last_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_company]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_client_id]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_scopes]['type'] = 'string';
        $payload[IMailTemplatesConstants::support_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::manage_orders_url]['type'] = 'string';

        return $payload;
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