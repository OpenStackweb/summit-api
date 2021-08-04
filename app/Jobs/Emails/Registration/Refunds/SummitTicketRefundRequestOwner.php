<?php namespace App\Jobs\Emails\Registration\Refunds;
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
 * Class SummitTicketRefundRequestOwner
 * @package App\Jobs\Emails\Registration\Refunds
 */
class SummitTicketRefundRequestOwner extends AbstractEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_TICKET_REFUND_REQUEST_BY_OWNER';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_TICKET_REFUND_REQUEST_BY_OWNER';
    const DEFAULT_TEMPLATE = 'REGISTRATION_TICKET_REFUND_REQUESTED_OWNER';

    /**
     * SummitTicketRefundRequestOwner constructor.
     * @param SummitAttendeeTicket $ticket
     */
    public function __construct(SummitAttendeeTicket $ticket)
    {
        $payload = [];
        $order = $ticket->getOrder();
        $summit = $order->getSummit();
        $payload['order_number'] = $order->getNumber();
        $payload['owner_full_name'] = $order->getOwnerFullName();
        $payload['owner_email'] = $order->getOwnerEmail();
        $payload['owner_company'] = $order->getOwnerCompany();
        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['summit_marketing_site_url'] = $summit->getMarketingSiteUrl();
        $payload['ticket_number'] = $ticket->getNumber();
        $payload['ticket_type_name'] = $ticket->getTicketType()->getName();
        $payload['ticket_currency'] = $ticket->getCurrency();
        $payload['ticket_amount'] = round($ticket->getFinalAmount(),2);
        $payload['ticket_currency_symbol'] = '$';

        $payload['ticket_promo_code'] = '';
        if ($ticket->hasPromoCode()) {
            $payload['ticket_promo_code'] = $ticket->getPromoCode()->getCode();
        }

        $payload['ticket_owner'] = '';
        if ($ticket->hasOwner()) {
            $payload['ticket_owner'] = $ticket->getOwner()->getFullName();
        }

        $support_email = $summit->getSupportEmail();
        $payload['support_email'] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);

        if (empty($payload['support_email']))
            throw new \InvalidArgumentException("missing support_email value");

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $payload['owner_email']);
    }
}