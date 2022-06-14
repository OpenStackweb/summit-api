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
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeTicket;
/**
 * Class RevocationTicketEmail
 * @package App\Jobs\Emails
 */
class RevocationTicketEmail extends AbstractEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_TICKET_REVOCATION';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_TICKET_REVOCATION';
    const DEFAULT_TEMPLATE = 'REGISTRATION_REVOCATION_TICKET';

    /**
     * RevocationTicketEmail constructor.
     * @param SummitAttendee $attendee
     * @param SummitAttendeeTicket $ticket
     */
    public function __construct(SummitAttendee $attendee, SummitAttendeeTicket $ticket)
    {
        $owner_email = $attendee->getEmail();
        $summit = $attendee->getSummit();
        $order = $ticket->getOrder();
        $payload = [];

        $payload['owner_full_name'] = $attendee->getFullName();
        $payload['owner_email'] = $attendee->getEmail();
        $payload['owner_first_name'] = $attendee->getFirstName();
        $payload['owner_last_name'] = $attendee->getSurname();
        $payload['owner_company'] = $attendee->getCompanyName();
        if(empty($payload['owner_full_name'])){
            $payload['owner_full_name'] = $payload['owner_email'];
        }

        $payload['order_owner_full_name'] = $order->getOwnerFullName();
        $payload['order_owner_email'] = $order->getOwnerEmail();
        $payload['order_owner_company'] = $order->getOwnerCompanyName();
        if(empty($payload['order_owner_full_name'])){
            $payload['order_owner_full_name'] = $payload['order_owner_email'];
        }


        $payload['ticket_number'] = $ticket->getNumber();

        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['summit_marketing_site_url'] = $summit->getMarketingSiteUrl();
        $payload['raw_summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['raw_summit_marketing_site_url'] = $summit->getMarketingSiteUrl();

        $support_email = $summit->getSupportEmail();
        $payload['support_email'] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);

        if (empty($payload['support_email']))
            throw new \InvalidArgumentException("missing support_email value");

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $owner_email);
    }
}