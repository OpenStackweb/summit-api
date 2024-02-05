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
use libs\utils\FormatUtils;
use models\summit\SummitOrder;
use models\summit\SummitRegistrationDiscountCode;
/**
 * Class RegisteredMemberOrderPaidMail
 * @package App\Jobs\Emails
 */
class RegisteredMemberOrderPaidMail extends AbstractSummitEmailJob
{

    /**
     * RegisteredMemberOrderPaidMail constructor.
     * @param SummitOrder $order
     */
    public function __construct(SummitOrder $order)
    {
        $payload = [];
        $tickets = [];
        $payload[IMailTemplatesConstants::owner_first_name] = $order->getOwnerFirstName();
        $payload[IMailTemplatesConstants::owner_last_name] = $order->getOwnerSurname();
        $payload[IMailTemplatesConstants::owner_full_name] = $order->getOwnerFullName();
        $payload[IMailTemplatesConstants::owner_company] = $order->getOwnerCompanyName();

        $owner_email = $order->getOwnerEmail();
        $payload[IMailTemplatesConstants::owner_email] = $owner_email;

        if(empty($payload[IMailTemplatesConstants::owner_full_name])){
            $payload[IMailTemplatesConstants::owner_full_name] = $payload[IMailTemplatesConstants::owner_email];
        }

        $summit = $order->getSummit();

        $payload[IMailTemplatesConstants::order_currency] = $order->getCurrency();
        $payload[IMailTemplatesConstants::order_currency_symbol] = $order->getCurrencySymbol();
        $payload[IMailTemplatesConstants::order_raw_amount] = FormatUtils::getNiceFloat($order->getRawAmount());
        $payload[IMailTemplatesConstants::order_amount] = FormatUtils::getNiceFloat($order->getFinalAmount());
        $payload[IMailTemplatesConstants::order_taxes] = FormatUtils::getNiceFloat($order->getTaxesAmount());
        $payload[IMailTemplatesConstants::order_discount] = FormatUtils::getNiceFloat($order->getDiscountAmount());
        $payload[IMailTemplatesConstants::order_refunded_amount] = FormatUtils::getNiceFloat($order->getRefundedAmount());
        $payload[IMailTemplatesConstants::order_amount_adjusted] = FormatUtils::getNiceFloat($order->getFinalAmountAdjusted());

        $payload[IMailTemplatesConstants::order_number] = $order->getNumber();
        $payload[IMailTemplatesConstants::order_qr_value] = $order->getQRCode();
        $payload[IMailTemplatesConstants::order_purchase_date] = FormatUtils::getNiceDateMonthDayYearTextual($summit->convertDateFromUTC2TimeZone($order->getCreatedUTC()));
        $payload[IMailTemplatesConstants::order_credit_card_type] = $order->getCreditCardType();
        $payload[IMailTemplatesConstants::order_credit_card_4number] = $order->getCreditCard4Number();

        $summit_reassign_ticket_till_date = $summit->getReassignTicketTillDateLocal();
        if(!is_null($summit_reassign_ticket_till_date)) {
            $payload[IMailTemplatesConstants::summit_reassign_ticket_till_date] = $summit_reassign_ticket_till_date->format("l j F Y h:i A T");
        }

        $support_email = $summit->getSupportEmail();
        $payload[IMailTemplatesConstants::support_email] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);

        if (empty($payload[IMailTemplatesConstants::support_email]))
            throw new \InvalidArgumentException("missing support_email value");

        foreach ($order->getTickets() as $ticket) {
            $ticket_dto = [
                IMailTemplatesConstants::number => $ticket->getNumber(),
                IMailTemplatesConstants::ticket_type_name => $ticket->getTicketType()->getName(),
                IMailTemplatesConstants::has_owner => false,
                IMailTemplatesConstants::price => FormatUtils::getNiceFloat($ticket->getFinalAmount()),
                IMailTemplatesConstants::net_selling_price => FormatUtils::getNiceFloat($ticket->getNetSellingPrice()),
                IMailTemplatesConstants::currency => $ticket->getCurrency(),
                IMailTemplatesConstants::currency_symbol => $ticket->getCurrencySymbol(),
                IMailTemplatesConstants::need_details => false,
            ];

            if ($ticket->hasPromoCode()) {
                $promo_code = $ticket->getPromoCode();
                $promo_code_dto = [
                    IMailTemplatesConstants::code => $promo_code->getCode(),
                    IMailTemplatesConstants::is_discount => false,
                ];

                if ($promo_code instanceof SummitRegistrationDiscountCode) {
                    $promo_code_dto[IMailTemplatesConstants::is_discount] = true;
                    $promo_code_dto[IMailTemplatesConstants::discount_amount] = FormatUtils::getNiceFloat($promo_code->getAmount());
                    $promo_code_dto[IMailTemplatesConstants::discount_rate] = $promo_code->getRate();
                }

                $ticket_dto[IMailTemplatesConstants::promo_code] = $promo_code_dto;
            }

            if ($ticket->hasOwner()) {
                $ticket_dto[IMailTemplatesConstants::has_owner] = true;
                $ticket_owner = $ticket->getOwner();
                $ticket_dto[IMailTemplatesConstants::owner_email] = $ticket_owner->getEmail();
                $ticket_dto[IMailTemplatesConstants::owner_full_name] = $ticket_owner->getFullName();
                $ticket_dto[IMailTemplatesConstants::owner_first_name] = $ticket_owner->getFirstName();
                $ticket_dto[IMailTemplatesConstants::owner_company] = $ticket_owner->getCompanyName();
                $ticket_dto[IMailTemplatesConstants::owner_last_name] = $ticket_owner->getSurname();
                $ticket_dto[IMailTemplatesConstants::need_details] = $ticket_owner->needToFillDetails();
            }
            $tickets[] = $ticket_dto;
        }
        $payload[IMailTemplatesConstants::tickets] = $tickets;
        $payload[IMailTemplatesConstants::manage_orders_url] = sprintf("%s/a/my-tickets", $summit->getMarketingSiteUrl());
        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);
        Log::debug(sprintf("RegisteredMemberOrderPaidMail::__construct template_identifier %s", $template_identifier));
        parent::__construct($summit, $payload, $template_identifier, $owner_email);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{

        $payload = parent::getEmailTemplateSchema();

        $payload[IMailTemplatesConstants::owner_first_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_last_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_company]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_credit_card_type]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_purchase_date]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_credit_card_4number]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_currency]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_currency_symbol]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_raw_amount]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_amount]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_taxes]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_discount]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_refunded_amount]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_amount_adjusted]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_number]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_qr_value]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_reassign_ticket_till_date]['type'] = 'string';
        $payload[IMailTemplatesConstants::support_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::manage_orders_url]['type'] = 'string';

        $promo_code_schema = [];
        $promo_code_schema['type'] = 'object';
        $promo_code_schema['properties'][IMailTemplatesConstants::code]['type'] = 'string';
        $promo_code_schema['properties'][IMailTemplatesConstants::is_discount]['type'] = 'bool';
        $promo_code_schema['properties'][IMailTemplatesConstants::discount_amount]['type'] = 'string';
        $promo_code_schema['properties'][IMailTemplatesConstants::discount_rate]['type'] = 'float';

        $ticket_schema = [];
        $ticket_schema['type'] = 'object';
        $ticket_schema['properties'][IMailTemplatesConstants::number]['type'] = 'string';
        $ticket_schema['properties'][IMailTemplatesConstants::ticket_type_name]['type'] = 'string';
        $ticket_schema['properties'][IMailTemplatesConstants::has_owner]['type'] = 'bool';
        $ticket_schema['properties'][IMailTemplatesConstants::owner_email]['type'] = 'string';
        $ticket_schema['properties'][IMailTemplatesConstants::owner_full_name]['type'] = 'string';
        $ticket_schema['properties'][IMailTemplatesConstants::owner_first_name]['type'] = 'string';
        $ticket_schema['properties'][IMailTemplatesConstants::owner_last_name]['type'] = 'string';
        $ticket_schema['properties'][IMailTemplatesConstants::owner_company]['type'] = 'string';
        $ticket_schema['properties'][IMailTemplatesConstants::price]['type'] = 'string';
        $ticket_schema['properties'][IMailTemplatesConstants::currency]['type'] = 'string';
        $ticket_schema['properties'][IMailTemplatesConstants::currency_symbol]['type'] = 'string';
        $ticket_schema['properties'][IMailTemplatesConstants::need_details]['type'] = 'bool';
        $ticket_schema['properties'][IMailTemplatesConstants::promo_code] = $promo_code_schema;

        $payload[IMailTemplatesConstants::tickets]['type'] = 'array';
        $payload[IMailTemplatesConstants::tickets]['items'] = $ticket_schema;

        return $payload;
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