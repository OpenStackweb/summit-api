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
use libs\utils\FormatUtils;
use models\summit\SummitOrder;
use models\summit\SummitRegistrationDiscountCode;

/**
 * Class SummitOrderRefundRequestOwner
 * @package App\Jobs\Emails\Registration\Refunds
 */
class SummitOrderRefundRequestOwner extends AbstractEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_ORDER_REFUND_REQUEST_BY_OWNER';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_ORDER_REFUND_REQUEST_BY_OWNER';
    const DEFAULT_TEMPLATE = 'REGISTRATION_ORDER_REFUND_REQUESTED_OWNER';

    public function __construct(SummitOrder $order)
    {
        $payload = [];
        $summit = $order->getSummit();
        $payload['owner_full_name'] = $order->getOwnerFullName();
        $payload['owner_first_name'] = $order->getOwnerFirstName();
        $payload['owner_full_name'] = $order->getOwnerFullName();
        $payload['owner_company'] = $order->getOwnerCompanyName();
        $payload['owner_email'] = $order->getOwnerEmail();
        $payload['order_number'] = $order->getNumber();
        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['summit_marketing_site_url'] = $summit->getMarketingSiteUrl();
        $payload['raw_summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['raw_summit_marketing_site_url'] = $summit->getMarketingSiteUrl();

        $payload['order_amount'] = FormatUtils::getNiceFloat($order->getFinalAmount());
        $payload['order_currency'] = $order->getCurrency();
        $payload['order_currency_symbol'] = $order->getCurrencySymbol();
        $payload['tickets'] = [];

        $tickets = [];
        foreach ($order->getTickets() as $ticket) {
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
                $ticket_dto['owner_email'] = $ticket_owner->getEmail();
                $ticket_dto['owner_full_name'] = $ticket_owner->getFullName();
                $ticket_dto['owner_company'] = $ticket_owner->getCompanyName();
                $ticket_dto['owner_first_name'] = $ticket_owner->getFirstName();
                $ticket_dto['owner_last_name'] = $ticket_owner->getSurname();
                $ticket_dto['need_details'] = $ticket_owner->needToFillDetails();
            }
            $tickets[] = $ticket_dto;
        }

        $payload['tickets'] = $tickets;

        $support_email = $summit->getSupportEmail();
        $payload['support_email'] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);

        if (empty($payload['support_email']))
            throw new \InvalidArgumentException("missing support_email value");

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $payload['owner_email']);
    }
}