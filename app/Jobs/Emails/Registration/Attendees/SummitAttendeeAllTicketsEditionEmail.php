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
use models\summit\SummitAttendee;
use models\summit\SummitRegistrationDiscountCode;

/**
 * Class SummitAttendeeAllTicketsEditionEmail
 * @package App\Jobs\Emails
 */
class SummitAttendeeAllTicketsEditionEmail extends AbstractSummitAttendeeTicketEmail
{
    /**
     * SummitAttendeeAllTicketsEditionEmail constructor.
     * @param SummitAttendee $attendee
     */
    public function __construct(SummitAttendee $attendee)
    {
        $payload = [];
        $tickets = [];
        $payload['owner_first_name'] =$attendee->getFirstName();
        $payload['owner_last_name'] = $attendee->getSurname();
        $payload['owner_company'] = $attendee->getCompanyName();
        $payload['owner_email']  = $attendee->getEmail();

        if(empty($payload['owner_full_name'])){
            Log::warning(sprintf("SummitAttendeeAllTicketsEditionEmail owner_full_name is empty setting email"));
            $payload['owner_full_name'] = $payload['owner_email'];
        }

        if(empty($payload['owner_first_name'])){
            Log::warning(sprintf("SummitAttendeeAllTicketsEditionEmail owner_first_name is empty setting email"));
            $payload['owner_first_name'] = $payload['owner_email'];
        }

        if(empty($payload['owner_last_name'])){
            Log::warning(sprintf("SummitAttendeeAllTicketsEditionEmail owner_last_name is empty setting email"));
            $payload['owner_last_name'] = $payload['owner_email'];
        }

        $summit = $attendee->getSummit();

        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['summit_marketing_site_url'] = $summit->getMarketingSiteUrl();
        $payload['raw_summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['raw_summit_marketing_site_url'] = $summit->getMarketingSiteUrl();

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

        foreach ($attendee->getTickets() as $ticket) {
            if(!$ticket->isActive()) continue;
            if(!$ticket->isPaid()) continue;

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
                $ticket_dto['owner_first_name'] = $ticket_owner->getFirstName();
                $ticket_dto['owner_company'] = $ticket_owner->getCompanyName();
                $ticket_dto['owner_last_name'] = $ticket_owner->getSurname();
                $ticket_dto['need_details'] = $ticket_owner->needToFillDetails();
            }
            $tickets[] = $ticket_dto;
        }
        $payload['tickets'] = $tickets;

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);
        Log::debug(sprintf("SummitAttendeeAllTicketsEditionEmail::__construct payload %s template %s",
            json_encode($payload), $template_identifier));

        parent::__construct($payload, $template_identifier, $payload['owner_email'] );
    }

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_ATTENDEE_ALL_TICKETS_EDITION';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_ATTENDEE_ALL_TICKETS_EDITION';
    const DEFAULT_TEMPLATE = 'SUMMIT_REGISTRATION_ATTENDEE_ALL_TICKETS_EDITION';
}