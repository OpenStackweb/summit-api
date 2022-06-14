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
use Illuminate\Support\Facades\Config;
use models\summit\SummitAttendeeTicket;

/**
 * Class SummitTicketReminderEmail
 * @package App\Jobs\Emails\Registration\Reminders
 */
class SummitTicketReminderEmail extends AbstractEmailJob
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
            $payload['summit_reassign_ticket_till_date'] = $summit_reassign_ticket_till_date->format("l j F Y h:i A T");
        }

        $payload['order_owner_full_name'] = $order->getOwnerFullName();
        $payload['order_owner_company'] = $order->getOwnerCompanyName();
        $payload['order_owner_email'] = $order->getOwnerEmail();

        if(empty($payload['order_owner_full_name'])){
            $payload['order_owner_full_name'] = $payload['order_owner_email'];
        }

        $payload['owner_full_name'] = $attendee->getFullName();
        $payload['owner_email'] = $attendee->getEmail();
        $payload['owner_company'] = $attendee->getCompanyName();

        if(empty($payload['owner_full_name'])){
            $payload['owner_full_name'] = $payload['owner_email'];
        }

        $support_email = $summit->getSupportEmail();
        $payload['support_email'] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);
        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['summit_marketing_site_url'] = $summit->getMarketingSiteUrl();
        $payload['raw_summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['raw_summit_marketing_site_url'] = $summit->getMarketingSiteUrl();

        $base_url = Config::get('registration.dashboard_base_url', null);
        $edit_ticket_link = Config::get('registration.dashboard_attendee_edit_form_url', null);

        if (empty($base_url))
            throw new \InvalidArgumentException("missing dashboard_base_url value");
        if (empty($edit_ticket_link))
            throw new \InvalidArgumentException("missing dashboard_attendee_edit_form_url value");

        $payload['edit_ticket_link'] = sprintf($edit_ticket_link, $base_url, $ticket->getHash());

        if (empty($payload['support_email']))
            throw new \InvalidArgumentException("missing support_email value");

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $payload['owner_email']);
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