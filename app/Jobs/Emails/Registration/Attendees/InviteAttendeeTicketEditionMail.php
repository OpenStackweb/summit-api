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

use App\Services\Apis\IMailApi;
use Illuminate\Support\Facades\Cache;
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
     * @var int
     */
    protected $ticket_id;

    /**
     * @param SummitAttendeeTicket $ticket
     * @param array $payload
     * @param string|null $test_email_recipient
     */
    public function __construct(SummitAttendeeTicket $ticket, array $payload = [], ?string $test_email_recipient = null)
    {

        Log::debug("InviteAttendeeTicketEditionMail::__construct");
        $this->ticket_id = $ticket->getId();

        $owner = $ticket->getOwner();
        $order = $ticket->getOrder();
        $summit = $order->getSummit();

        $payload[IMailTemplatesConstants::order_owner_full_name] = $order->getOwnerFullName();
        $payload[IMailTemplatesConstants::order_owner_company] = $order->getOwnerCompanyName();
        $payload[IMailTemplatesConstants::order_owner_email] = $order->getOwnerEmail();
        if(empty($payload[IMailTemplatesConstants::order_owner_full_name])){
            $payload[IMailTemplatesConstants::order_owner_full_name] = $payload[IMailTemplatesConstants::order_owner_email];
        }

        $payload[IMailTemplatesConstants::owner_full_name] = $owner->getFullName();
        $payload[IMailTemplatesConstants::owner_company] = $owner->getCompanyName();
        $payload[IMailTemplatesConstants::owner_email]  = $owner->getEmail();
        $payload[IMailTemplatesConstants::owner_first_name] = $owner->getFirstName();
        $payload[IMailTemplatesConstants::owner_last_name] = $owner->getSurname();

        $payload[IMailTemplatesConstants::summit_reassign_ticket_till_date] = '';

        $summit_reassign_ticket_till_date = $summit->getReassignTicketTillDateLocal();
        if(!is_null($summit_reassign_ticket_till_date)) {
            $payload[IMailTemplatesConstants::summit_reassign_ticket_till_date] = $summit_reassign_ticket_till_date->format("l j F Y h:i A T");
        }

        if(empty($payload[IMailTemplatesConstants::owner_full_name])){
            Log::warning(sprintf("InviteAttendeeTicketEditionMail owner_full_name is empty setting email"));
            $payload[IMailTemplatesConstants::owner_full_name] = $payload[IMailTemplatesConstants::owner_email];
        }

        if(empty($payload[IMailTemplatesConstants::owner_first_name])){
            Log::warning(sprintf("InviteAttendeeTicketEditionMail owner_first_name is empty setting email"));
            $payload[IMailTemplatesConstants::owner_first_name] = $payload[IMailTemplatesConstants::owner_email];
        }

        if(empty($payload[IMailTemplatesConstants::owner_last_name])){
            Log::warning(sprintf("InviteAttendeeTicketEditionMail owner_last_name is empty setting email"));
            $payload[IMailTemplatesConstants::owner_last_name] = $payload[IMailTemplatesConstants::owner_email];
        }

        $payload[IMailTemplatesConstants::hash] = $ticket->getHash();

        $payload[IMailTemplatesConstants::summit_virtual_site_oauth2_client_id] = $summit->getVirtualSiteOAuth2ClientId();
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_client_id] = $summit->getMarketingSiteOAuth2ClientId();
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_scopes] = $summit->getMarketingSiteOauth2ClientScopes();
        $payload[IMailTemplatesConstants::ticket_number] = $ticket->getNumber();
        $payload[IMailTemplatesConstants::ticket_type_name] = $ticket->getTicketType()->getName();
        $payload[IMailTemplatesConstants::ticket_raw_amount] = FormatUtils::getNiceFloat($ticket->getRawCost());
        $payload[IMailTemplatesConstants::ticket_currency] = $ticket->getCurrency();
        $payload[IMailTemplatesConstants::ticket_currency_symbol] = $ticket->getCurrencySymbol();
        $payload[IMailTemplatesConstants::ticket_discount] = FormatUtils::getNiceFloat($ticket->getDiscount());
        $payload[IMailTemplatesConstants::ticket_taxes] = FormatUtils::getNiceFloat($ticket->getTaxesAmount());
        $payload[IMailTemplatesConstants::ticket_amount] = FormatUtils::getNiceFloat($ticket->getFinalAmount());
        $payload[IMailTemplatesConstants::need_details] = $owner->needToFillDetails();

        $promo_code = $ticket->hasPromoCode() ? $ticket->getPromoCode() : null;
        $payload[IMailTemplatesConstants::promo_code] = '';
        if (!is_null($promo_code)) {
            $payload[IMailTemplatesConstants::promo_code] = $promo_code->getCode();

            if ($promo_code instanceof SummitRegistrationDiscountCode) {
                $payload[IMailTemplatesConstants::promo_code_discount_rate] = $promo_code->getRate();
                $payload[IMailTemplatesConstants::promo_code_discount_amount] = FormatUtils::getNiceFloat($promo_code->getAmount());
            }
        }

        $support_email = $summit->getSupportEmail();
        $payload[IMailTemplatesConstants::support_email] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);

        if (empty($payload[IMailTemplatesConstants::support_email]))
            throw new \InvalidArgumentException("missing support_email value");

        // default value

        $message = $payload[IMailTemplatesConstants::message] ?? '';

        if(!empty($message)){
            $invite_attendee_ticket_edition_mail_message_key = sprintf
            (
                "InviteAttendeeTicketEditionMail_message_%s", md5(sprintf("%s_%s", $this->to_email, $this->ticket_id))
            );
            // if message is not empty store it on cache, just in case that SummitAttendeeTicketEmail is emitted in the middle
            // before the actual dispatching of this email
            Cache::put($invite_attendee_ticket_edition_mail_message_key, $message);
        }

        $payload[IMailTemplatesConstants::message] = $message;

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        Log::debug(sprintf("InviteAttendeeTicketEditionMail::__construct payload %s template %s", json_encode($payload), $template_identifier));

        if (!empty($test_email_recipient)) {
            Log::debug
            (
                sprintf
                (
                    "InviteAttendeeTicketEditionMail::__construct replacing original email %s by %s and clearing cc field",
                    $payload[IMailTemplatesConstants::owner_email],
                    $test_email_recipient
                )
            );

            $payload[IMailTemplatesConstants::owner_email] = $test_email_recipient;
            $payload[IMailTemplatesConstants::cc_email] = '';

        }

        parent::__construct($summit, $payload, $template_identifier, $payload[IMailTemplatesConstants::owner_email]);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{
        $payload = parent::getEmailTemplateSchema();

        $payload[IMailTemplatesConstants::order_owner_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_owner_company]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_owner_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_company]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_first_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_last_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_reassign_ticket_till_date]['type'] = 'string';
        $payload[IMailTemplatesConstants::hash]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_virtual_site_oauth2_client_id]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_client_id]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_scopes]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_number]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_type_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_raw_amount]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_currency]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_currency_symbol]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_discount]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_taxes]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_amount]['type'] = 'string';
        $payload[IMailTemplatesConstants::need_details]['type'] = 'bool';
        $payload[IMailTemplatesConstants::promo_code]['type'] = 'string';
        $payload[IMailTemplatesConstants::promo_code_discount_rate]['type'] = 'string';
        $payload[IMailTemplatesConstants::promo_code_discount_amount]['type'] = 'string';
        $payload[IMailTemplatesConstants::support_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::message]['type'] = 'string';

        return $payload;
    }

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    public function handle
    (
        IMailApi $api
    )
    {
        Log::debug(sprintf("InviteAttendeeTicketEditionMail::handle template_identifier %s to_email %s", $this->template_identifier, $this->to_email));

        $summit_attendee_ticket_email_sent_key = sprintf("SummitAttendeeTicketEmail_%s_sent", md5(sprintf("%s_%s", $this->to_email, $this->ticket_id)));
        $invite_attendee_ticket_edition_mail_key = sprintf("InviteAttendeeTicketEditionMail_%s", md5(sprintf("%s_%s", $this->to_email, $this->ticket_id))) ;
        $delay = intval(Config::get("registration.attendee_invitation_email_threshold", 5));

        // check if we triggered a SummitAttendeeTicketEmail before send it
        if(Cache::has($summit_attendee_ticket_email_sent_key)){
            $timestamp = Cache::get($summit_attendee_ticket_email_sent_key);
            Log::warning(sprintf("InviteAttendeeTicketEditionMail::handle already sent email SummitAttendeeTicketEmail to %s at %s.", $this->to_email, $timestamp));
            return;
        }

        // check if we already sent this same email on the configured threshold
        if(Cache::has($invite_attendee_ticket_edition_mail_key)){
            $timestamp = Cache::get($invite_attendee_ticket_edition_mail_key);
            Log::warning(sprintf("InviteAttendeeTicketEditionMail::handle already sent email InviteAttendeeTicketEditionMail to %s at %s", $this->to_email, $timestamp));
            return;
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        Cache::put($invite_attendee_ticket_edition_mail_key, $now->getTimestamp(), now()->addMinutes($delay));

        parent::handle($api);
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_INVITE_ATTENDEE_TICKET_EDITION';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_INVITE_ATTENDEE_TICKET_EDITION';
    const DEFAULT_TEMPLATE = 'REGISTRATION_INVITE_ATTENDEE_TICKET_EDITION';
}