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
use models\summit\SummitOrder;
use models\summit\SummitRegistrationDiscountCode;
/**
 * Class RegisteredMemberOrderPaidMail
 * @package App\Jobs\Emails
 */
class RegisteredMemberOrderPaidMail extends AbstractEmailJob
{

    /**
     * RegisteredMemberOrderPaidMail constructor.
     * @param SummitOrder $order
     */
    public function __construct(SummitOrder $order)
    {
        $payload = [];
        $tickets = [];
        $payload['owner_full_name'] = $order->getOwnerFullName();
        $payload['owner_company'] = $order->getOwnerCompany();
        $owner_email = $order->getOwnerEmail();
        $payload['owner_email'] = $owner_email;

        if(empty($payload['owner_full_name'])){
            $payload['owner_full_name'] = $payload['owner_email'];
        }

        $summit = $order->getSummit();
        $payload['order_raw_amount'] = round($order->getRawAmount(),2);
        $payload['order_amount'] = round($order->getFinalAmount(),2);
        $payload['order_currency'] = $order->getCurrency();
        $payload['order_currency_symbol'] = '$';
        $payload['order_taxes'] = $order->getTaxesAmount();
        $payload['order_discount'] = $order->getDiscountAmount();
        $payload['order_number'] = $order->getNumber();
        $payload['order_qr_value'] = $order->getQRCode();
        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['summit_marketing_site_url'] = $summit->getMarketingSiteUrl();

        $summit_reassign_ticket_till_date = $summit->getReassignTicketTillDateLocal();
        if(!is_null($summit_reassign_ticket_till_date)) {
            $payload['summit_reassign_ticket_till_date'] = $summit_reassign_ticket_till_date->format("l j F Y h:i A T");
        }

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

        foreach ($order->getTickets() as $ticket) {
            $ticket_dto = [
                'number' => $ticket->getNumber(),
                'ticket_type_name' => $ticket->getTicketType()->getName(),
                'has_owner' => false,
                'price' => round($ticket->getFinalAmount(),2),
                'currency' => $ticket->getCurrency(),
                'currency_symbol' => '$',
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
                    $promo_code_dto['discount_amount'] = round($promo_code->getAmount(),2);
                    $promo_code_dto['discount_rate'] = $promo_code->getRate();
                }

                $ticket_dto['promo_code'] = $promo_code_dto;
            }

            if ($ticket->hasOwner()) {
                $ticket_dto['has_owner'] = true;
                $ticket_owner = $ticket->getOwner();
                $ticket_dto['owner_email'] = $ticket_owner->getEmail();
                $ticket_dto['owner_full_name'] = $ticket_owner->getFullName();
                $ticket_dto['owner_first_name'] = $ticket_owner->getFirstName();
                $ticket_dto['owner_company'] = $ticket_owner->getCompanyName();
                $ticket_dto['owner_last_name'] = $ticket_owner->getSurname();
                $ticket_dto['need_details'] = $ticket_owner->needToFillDetails();
            }
            $tickets[] = $ticket_dto;
        }
        $payload['tickets'] = $tickets;

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);
        Log::debug(sprintf("RegisteredMemberOrderPaidMail::__construct template_identifier %s", $template_identifier));
        parent::__construct($payload, $template_identifier, $owner_email);
    }

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_REGISTERED_MEMBER_ORDER_PAID';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_REGISTERED_MEMBER_ORDER_PAID';
    const DEFAULT_TEMPLATE = 'REGISTRATION_REGISTERED_MEMBER_ORDER_PAID';
}