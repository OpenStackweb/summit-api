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
use models\summit\SummitAttendeeTicket;
use models\summit\SummitRegistrationDiscountCode;
/**
 * Class InviteAttendeeTicketEditionMail
 * @package App\Jobs\Emails
 */
class InviteAttendeeTicketEditionMail extends AbstractSummitAttendeeTicketEmail
{
    /**
     * @param SummitAttendeeTicket $ticket
     * @param array $payload
     */
    public function __construct(SummitAttendeeTicket $ticket, array $payload = [])
    {

        Log::debug("InviteAttendeeTicketEditionMail::__construct");

        $owner = $ticket->getOwner();
        $order = $ticket->getOrder();
        $summit = $order->getSummit();

        $payload['order_owner_full_name'] = $order->getOwnerFullName();
        $payload['order_owner_company'] = $order->getOwnerCompanyName();
        $payload['order_owner_email'] = $order->getOwnerEmail();
        if(empty($payload['order_owner_full_name'])){
            $payload['order_owner_full_name'] = $payload['order_owner_email'];
        }

        $payload['owner_full_name'] = $owner->getFullName();
        $payload['owner_company'] = $owner->getCompanyName();
        $payload['owner_email']  = $owner->getEmail();
        $payload['owner_first_name'] = $owner->getFirstName();
        $payload['owner_last_name'] = $owner->getSurname();

        $payload['summit_reassign_ticket_till_date'] = '';

        $summit_reassign_ticket_till_date = $summit->getReassignTicketTillDateLocal();
        if(!is_null($summit_reassign_ticket_till_date)) {
            $payload['summit_reassign_ticket_till_date'] = $summit_reassign_ticket_till_date->format("l j F Y h:i A T");
        }

        if(empty($payload['owner_full_name'])){
            Log::warning(sprintf("InviteAttendeeTicketEditionMail owner_full_name is empty setting email"));
            $payload['owner_full_name'] = $payload['owner_email'];
        }

        if(empty($payload['owner_first_name'])){
            Log::warning(sprintf("InviteAttendeeTicketEditionMail owner_first_name is empty setting email"));
            $payload['owner_first_name'] = $payload['owner_email'];
        }

        if(empty($payload['owner_last_name'])){
            Log::warning(sprintf("InviteAttendeeTicketEditionMail owner_last_name is empty setting email"));
            $payload['owner_last_name'] = $payload['owner_email'];
        }

        $payload['hash'] = $ticket->getHash();

        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['summit_marketing_site_url'] = $summit->getMarketingSiteUrl();
        $payload['raw_summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['raw_summit_marketing_site_url'] = $summit->getMarketingSiteUrl();
        $payload['summit_virtual_site_oauth2_client_id'] = $summit->getVirtualSiteOAuth2ClientId();
        $payload['summit_marketing_site_oauth2_client_id'] = $summit->getMarketingSiteOAuth2ClientId();
        $payload['summit_marketing_site_oauth2_scopes'] = $summit->getMarketingSiteOauth2ClientScopes();
        $payload['ticket_number'] = $ticket->getNumber();
        $payload['ticket_type_name'] = $ticket->getTicketType()->getName();
        $payload['ticket_raw_amount'] = FormatUtils::getNiceFloat($ticket->getRawCost());
        $payload['ticket_currency'] = $ticket->getCurrency();
        $payload['ticket_currency_symbol'] = $ticket->getCurrencySymbol();
        $payload['ticket_discount'] = FormatUtils::getNiceFloat($ticket->getDiscount());
        $payload['ticket_taxes'] = FormatUtils::getNiceFloat($ticket->getTaxesAmount());
        $payload['ticket_amount'] = FormatUtils::getNiceFloat($ticket->getFinalAmount());
        $payload['need_details'] = $owner->needToFillDetails();

        $promo_code = $ticket->hasPromoCode() ? $ticket->getPromoCode() : null;
        $payload['promo_code'] = '';
        if (!is_null($promo_code)) {
            $payload['promo_code'] = $promo_code->getCode();

            if ($promo_code instanceof SummitRegistrationDiscountCode) {
                $payload['promo_code_discount_rate'] = $promo_code->getRate();
                $payload['promo_code_discount_amount'] = FormatUtils::getNiceFloat($promo_code->getAmount());
            }
        }

        $support_email = $summit->getSupportEmail();
        $payload['support_email'] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);

        if (empty($payload['support_email']))
            throw new \InvalidArgumentException("missing support_email value");

        // default value
        if(!isset($payload['message']))
            $payload['message'] = '';

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        Log::debug(sprintf("InviteAttendeeTicketEditionMail::__construct payload %s template %s", json_encode($payload), $template_identifier));

        parent::__construct($payload, $template_identifier,  $payload['owner_email'] );
    }

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_INVITE_ATTENDEE_TICKET_EDITION';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_INVITE_ATTENDEE_TICKET_EDITION';
    const DEFAULT_TEMPLATE = 'REGISTRATION_INVITE_ATTENDEE_TICKET_EDITION';
}