<?php namespace App\Jobs\Emails\Registration\Reminders;
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

use App\Jobs\Emails\AbstractEmailJob;
use App\Jobs\Emails\AbstractSummitAttendeeTicketEmail;
use App\Jobs\Emails\IMailTemplatesConstants;
use Illuminate\Support\Facades\Config;
use models\summit\SummitAttendeeTicket;

/**
 * Class SummitTicketReminderEmail
 * @package App\Jobs\Emails\Registration\Reminders
 */
class SummitTicketReminderEmail extends AbstractSummitAttendeeTicketEmail
{

    /**
     * SummitTicketReminderEmail constructor.
     * @param SummitAttendeeTicket $ticket
     */
    public function __construct(SummitAttendeeTicket $ticket)
    {
        $attendee = $ticket->getOwner();
        $summit = $attendee->getSummit();
        $order = $ticket->getOrder();
        $payload = [];
        $summit_reassign_ticket_till_date = $summit->getReassignTicketTillDateLocal();
        if(!is_null($summit_reassign_ticket_till_date)) {
            $payload[IMailTemplatesConstants::summit_reassign_ticket_till_date] = $summit_reassign_ticket_till_date->format("l j F Y h:i A T");
        }

        $payload[IMailTemplatesConstants::order_owner_full_name] = $order->getOwnerFullName();
        $payload[IMailTemplatesConstants::order_owner_company] = $order->getOwnerCompanyName();
        $payload[IMailTemplatesConstants::order_owner_email] = $order->getOwnerEmail();

        if(empty($payload[IMailTemplatesConstants::order_owner_full_name])){
            $payload[IMailTemplatesConstants::order_owner_full_name] = $payload[IMailTemplatesConstants::order_owner_email];
        }

        $payload[IMailTemplatesConstants::owner_full_name] = $attendee->getFullName();
        $payload[IMailTemplatesConstants::owner_email] = $attendee->getEmail();
        $payload[IMailTemplatesConstants::owner_company] = $attendee->getCompanyName();

        if(empty($payload[IMailTemplatesConstants::owner_full_name])){
            $payload[IMailTemplatesConstants::owner_full_name] = $payload[IMailTemplatesConstants::owner_email];
        }

        $support_email = $summit->getSupportEmail();
        $payload[IMailTemplatesConstants::support_email] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);
        $payload[IMailTemplatesConstants::summit_virtual_site_oauth2_client_id] = $summit->getVirtualSiteOAuth2ClientId();
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_client_id] = $summit->getMarketingSiteOAuth2ClientId();
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_scopes] = $summit->getMarketingSiteOauth2ClientScopes();

        if (empty($payload[IMailTemplatesConstants::support_email]))
            throw new \InvalidArgumentException("missing support_email value");

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($summit, $payload, $template_identifier, $payload[IMailTemplatesConstants::owner_email]);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{

        $payload = parent::getEmailTemplateSchema();

        $payload[IMailTemplatesConstants::summit_reassign_ticket_till_date]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_owner_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_owner_company]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_owner_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_company]['type'] = 'string';
        $payload[IMailTemplatesConstants::support_email]['type'] = 'string';

        return $payload;
    }

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_TICKET_REMINDER';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_TICKET_REMINDER';
    const DEFAULT_TEMPLATE = 'REGISTRATION_TICKET_REMINDER_EMAIL';
}