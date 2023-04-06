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
use libs\utils\FormatUtils;
use models\summit\SummitOrder;
use models\summit\SummitRegistrationDiscountCode;


/**
 * Class SummitOrderReminderEmail
 * @package App\Jobs\Emails\Registration\Reminders
 */
class SummitOrderReminderEmail extends AbstractEmailJob
{

    /**
     * SummitOrderReminderEmail constructor.
     * @param SummitOrder $order
     */
    public function __construct(SummitOrder $order)
    {
        $payload = [];
        $summit = $order->getSummit();
        $payload['owner_full_name'] = $order->getOwnerFullName();
        $payload['owner_email'] = $order->getOwnerEmail();
        $payload['owner_company'] = $order->getOwnerCompanyName();

        if(empty($payload['owner_full_name'])){
            $payload['owner_full_name'] = $payload['owner_email'];
        }

        $summit_reassign_ticket_till_date = $summit->getReassignTicketTillDateLocal();
        $payload['summit_reassign_ticket_till_date'] = '';
        if(!is_null($summit_reassign_ticket_till_date)) {
            $payload['summit_reassign_ticket_till_date'] = $summit_reassign_ticket_till_date->format("l j F Y h:i A T");
        }
        $owner_email = $payload['owner_email'];

        $support_email = $summit->getSupportEmail();
        $payload['support_email'] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);

        $payload['summit_name'] = $order->getSummit()->getName();
        $payload['summit_logo'] = $order->getSummit()->getLogoUrl();
        $payload['summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['summit_marketing_site_url'] = $summit->getMarketingSiteUrl();
        $payload['raw_summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['raw_summit_marketing_site_url'] = $summit->getMarketingSiteUrl();

        if (empty($payload['support_email']))
            throw new \InvalidArgumentException("missing support_email value");

        $tickets = [];

        foreach ($order->getTickets() as $ticket) {
            if (!$ticket->hasTicketType()) continue;

            $ticket_dto = [
                'number' => $ticket->getNumber(),
                'ticket_type_name' => $ticket->getTicketType()->getName(),
                'has_owner' => false,
                'price' => FormatUtils::getNiceFloat($ticket->getFinalAmount()),
                'currency' => $ticket->getCurrency(),
                'currency_symbol' => $ticket->getCurrencySymbol(),
                'need_details' => false,
            ];

            if ($ticket->hasPromoCode()) {
                $promo_code = $ticket->getPromoCode();
                $promo_code_dto = [
                    'code' => $promo_code->getCode(),
                    'is_discount' => false,
                ];

                if ($promo_code instanceof SummitRegistrationDiscountCode) {
                    $promo_code_dto['is_discount'] = true;
                    $promo_code_dto['discount_amount'] = FormatUtils::getNiceFloat($promo_code->getAmount());
                    $promo_code_dto['discount_rate'] = $promo_code->getRate();
                }

                $ticket_dto['promo_code'] = $promo_code_dto;
            }

            if ($ticket->hasOwner()) {
                $ticket_dto['has_owner'] = true;
                $ticket_owner = $ticket->getOwner();
                $ticket_dto['owner_full_name'] = $ticket_owner->getFullName();
                $ticket_dto['owner_company'] = $ticket_owner->getCompanyName();
                $ticket_dto['owner_email'] = $ticket_owner->getEmail();
                $ticket_dto['owner_first_name'] = $ticket_owner->getFirstName();
                $ticket_dto['owner_last_name'] = $ticket_owner->getSurname();
                $ticket_dto['need_details'] = $ticket_owner->needToFillDetails();
            }

            $tickets[] = $ticket_dto;
        }

        $payload['tickets'] = $tickets;
        $payload['manage_orders_url'] = sprintf("%s/a/my-tickets", $summit->getMarketingSiteUrl());
        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $owner_email);
    }

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_ORDER_REMINDER';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_ORDER_REMINDER';
    const DEFAULT_TEMPLATE = 'REGISTRATION_ORDER_REMINDER_EMAIL';
}