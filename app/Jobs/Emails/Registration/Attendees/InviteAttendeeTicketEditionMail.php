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
use models\summit\SummitAttendeeTicket;
use models\summit\SummitRegistrationDiscountCode;
/**
 * Class InviteAttendeeTicketEditionMail
 * @package App\Jobs\Emails
 */
class InviteAttendeeTicketEditionMail extends AbstractSummitAttendeeTicketEmail
{
    /**
     * InviteAttendeeTicketEditionMail constructor.
     * @param SummitAttendeeTicket $ticket
     */
    public function __construct(SummitAttendeeTicket $ticket)
    {
        $owner = $ticket->getOwner();
        $order = $ticket->getOrder();
        $summit = $order->getSummit();
        $payload = [];

        $payload['order_owner_full_name'] = $order->getOwnerFullName();
        $payload['order_owner_company'] = $order->getOwnerCompany();
        $payload['order_owner_email'] = $order->getOwnerEmail();

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
        $payload['summit_virtual_site_oauth2_client_id'] = $summit->getVirtualSiteOAuth2ClientId();
        $payload['summit_marketing_site_oauth2_client_id'] = $summit->getMarketingSiteOAuth2ClientId();

        $base_url = Config::get('registration.dashboard_base_url', null);
        $edit_ticket_link = Config::get('registration.dashboard_attendee_edit_form_url', null);

        if (empty($base_url))
            throw new \InvalidArgumentException("missing dashboard_base_url value");
        if (empty($edit_ticket_link))
            throw new \InvalidArgumentException("missing dashboard_attendee_edit_form_url value");

        $payload['edit_ticket_link'] = sprintf($edit_ticket_link, $base_url, $payload['hash']);
        $payload['ticket_number'] = $ticket->getNumber();
        $payload['ticket_type_name'] = $ticket->getTicketType()->getName();
        $payload['ticket_raw_amount'] = $ticket->getRawCost();
        $payload['ticket_currency'] = $ticket->getCurrency();
        $payload['ticket_currency_symbol'] = '$';
        $payload['ticket_discount'] = $ticket->getDiscount();
        $payload['ticket_taxes'] = $ticket->getTaxesAmount();
        $payload['ticket_amount'] = $ticket->getFinalAmount();
        $payload['need_details'] = $owner->needToFillDetails();

        $promo_code = $ticket->hasPromoCode() ? $ticket->getPromoCode() : null;
        $payload['promo_code'] = '';
        if (!is_null($promo_code)) {
            $payload['promo_code'] = $promo_code->getCode();

            if ($promo_code instanceof SummitRegistrationDiscountCode) {
                $payload['promo_code_discount_rate'] = $promo_code->getRate();
                $payload['promo_code_discount_amount'] = $promo_code->getAmount();
            }
        }

        $support_email = $summit->getSupportEmail();
        $payload['support_email'] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);

        if (empty($payload['support_email']))
            throw new \InvalidArgumentException("missing support_email value");

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

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