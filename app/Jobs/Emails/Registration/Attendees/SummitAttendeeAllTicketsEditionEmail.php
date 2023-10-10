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
        $payload[IMailTemplatesConstants::owner_first_name] =$attendee->getFirstName();
        $payload[IMailTemplatesConstants::owner_last_name] = $attendee->getSurname();
        $payload[IMailTemplatesConstants::owner_company] = $attendee->getCompanyName();
        $payload[IMailTemplatesConstants::owner_email]  = $attendee->getEmail();

        if(empty($payload[IMailTemplatesConstants::owner_full_name])){
            Log::warning(sprintf("SummitAttendeeAllTicketsEditionEmail owner_full_name is empty setting email"));
            $payload[IMailTemplatesConstants::owner_full_name] = $payload[IMailTemplatesConstants::owner_email];
        }

        if(empty($payload[IMailTemplatesConstants::owner_first_name])){
            Log::warning(sprintf("SummitAttendeeAllTicketsEditionEmail owner_first_name is empty setting email"));
            $payload[IMailTemplatesConstants::owner_first_name] = $payload[IMailTemplatesConstants::owner_email];
        }

        if(empty($payload[IMailTemplatesConstants::owner_last_name])){
            Log::warning(sprintf("SummitAttendeeAllTicketsEditionEmail owner_last_name is empty setting email"));
            $payload[IMailTemplatesConstants::owner_last_name] = $payload[IMailTemplatesConstants::owner_email];
        }

        $summit = $attendee->getSummit();

        $payload[IMailTemplatesConstants::summit_name] = $summit->getName();
        $payload[IMailTemplatesConstants::summit_logo] = $summit->getLogoUrl();
        $payload[IMailTemplatesConstants::summit_virtual_site_url] = $summit->getVirtualSiteUrl();
        $payload[IMailTemplatesConstants::summit_marketing_site_url] = $summit->getMarketingSiteUrl();
        $payload[IMailTemplatesConstants::raw_summit_virtual_site_url] = $summit->getVirtualSiteUrl();
        $payload[IMailTemplatesConstants::raw_summit_marketing_site_url] = $summit->getMarketingSiteUrl();
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_client_id] = $summit->getMarketingSiteOAuth2ClientId();
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_scopes] = $summit->getMarketingSiteOauth2ClientScopes();
        $support_email = $summit->getSupportEmail();
        $payload[IMailTemplatesConstants::support_email] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);

        if (empty($payload[IMailTemplatesConstants::support_email]))
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

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);
        Log::debug(sprintf("SummitAttendeeAllTicketsEditionEmail::__construct payload %s template %s",
            json_encode($payload), $template_identifier));

        $payload[IMailTemplatesConstants::manage_orders_url] = sprintf("%s/a/my-tickets", $summit->getMarketingSiteUrl());

        parent::__construct($payload, $template_identifier, $payload[IMailTemplatesConstants::owner_email] );
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{
        $payload = AbstractSummitAttendeeTicketEmail::getEmailTemplateSchema();

        $payload[IMailTemplatesConstants::owner_first_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_last_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_company]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_logo]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_virtual_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_marketing_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_client_id]['type'] = 'int';
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_scopes]['type'] = 'string';
        $payload[IMailTemplatesConstants::raw_summit_virtual_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::raw_summit_marketing_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_virtual_site_oauth2_client_id]['type'] = 'int';
        $payload[IMailTemplatesConstants::support_email]['type'] = 'int';
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
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_ATTENDEE_ALL_TICKETS_EDITION';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_ATTENDEE_ALL_TICKETS_EDITION';
    const DEFAULT_TEMPLATE = 'SUMMIT_REGISTRATION_ATTENDEE_ALL_TICKETS_EDITION';
}