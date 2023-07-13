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
use App\Http\Renderers\SummitAttendeeTicketPDFRenderer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use libs\utils\FormatUtils;
use models\summit\SummitAttendeeTicket;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
/**
 * Class SummitAttendeeTicketEmail
 * @package App\Jobs\Emails
 */
class SummitAttendeeTicketEmail extends AbstractSummitAttendeeTicketEmail
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_ATTENDEE_TICKET_EMIT';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_ATTENDEE_TICKET_EMIT';
    const DEFAULT_TEMPLATE = 'REGISTRATION_ATTENDEE_TICKET';

    /**
     * @param SummitAttendeeTicket $ticket
     * @param array $payload
     */
    public function __construct(SummitAttendeeTicket $ticket, array $payload = [])
    {
        Log::debug("SummitAttendeeTicketEmail::__construct");

        $attendee = $ticket->getOwner();
        $summit = $attendee->getSummit();
        $order = $ticket->getOrder();
        $payload[IMailTemplatesConstants::hash] = $ticket->getHash();
        $payload[IMailTemplatesConstants::order_owner_full_name] = $order->getOwnerFullName();
        $payload[IMailTemplatesConstants::order_owner_company] = $order->getOwnerCompanyName();
        $payload[IMailTemplatesConstants::order_owner_email] = $order->getOwnerEmail();
        if (empty($payload[IMailTemplatesConstants::order_owner_full_name])) {
            $payload[IMailTemplatesConstants::order_owner_full_name] = $payload[IMailTemplatesConstants::order_owner_email];
        }

        $payload[IMailTemplatesConstants::summit_name] = $summit->getName();
        $payload[IMailTemplatesConstants::summit_logo] = $summit->getLogoUrl();

        $summit_reassign_ticket_till_date = $summit->getReassignTicketTillDateLocal();
        if (!is_null($summit_reassign_ticket_till_date)) {
            $payload[IMailTemplatesConstants::summit_reassign_ticket_till_date] = $summit_reassign_ticket_till_date->format("l j F Y h:i A T");
        }

        $payload[IMailTemplatesConstants::summit_virtual_site_url] = $summit->getVirtualSiteUrl();
        $payload[IMailTemplatesConstants::summit_marketing_site_url] = $summit->getMarketingSiteUrl();
        $payload[IMailTemplatesConstants::raw_summit_virtual_site_url] = $summit->getVirtualSiteUrl();
        $payload[IMailTemplatesConstants::raw_summit_marketing_site_url] = $summit->getMarketingSiteUrl();
        $payload[IMailTemplatesConstants::summit_virtual_site_oauth2_client_id] = $summit->getVirtualSiteOAuth2ClientId();
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_client_id] = $summit->getMarketingSiteOAuth2ClientId();
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_scopes] = $summit->getMarketingSiteOauth2ClientScopes();

        $payload[IMailTemplatesConstants::ticket_number] = $ticket->getNumber();
        $payload[IMailTemplatesConstants::ticket_type_name] = $ticket->getTicketType()->getName();
        $payload[IMailTemplatesConstants::ticket_amount] = FormatUtils::getNiceFloat($ticket->getFinalAmount());
        $payload[IMailTemplatesConstants::ticket_currency] = $ticket->getCurrency();
        $payload[IMailTemplatesConstants::ticket_currency_symbol] = $ticket->getCurrencySymbol();
        $owner_email = $attendee->getEmail();
        $payload[IMailTemplatesConstants::owner_email] = $owner_email;
        $payload[IMailTemplatesConstants::owner_first_name] = $attendee->getFirstName();
        $payload[IMailTemplatesConstants::owner_last_name] = $attendee->getSurname();
        $payload[IMailTemplatesConstants::owner_full_name] = $attendee->getFullName();
        $payload[IMailTemplatesConstants::owner_company] = $attendee->getCompanyName();

        if (empty($payload[IMailTemplatesConstants::owner_full_name])) {
            Log::warning(sprintf("SummitAttendeeTicketEmail owner_full_name is empty setting email"));
            $payload[IMailTemplatesConstants::owner_full_name] = $payload[IMailTemplatesConstants::owner_email];
        }

        if (empty($payload[IMailTemplatesConstants::owner_first_name])) {
            Log::warning(sprintf("SummitAttendeeTicketEmail owner_first_name is empty setting email"));
            $payload[IMailTemplatesConstants::owner_first_name] = $payload[IMailTemplatesConstants::owner_email];
        }

        if (empty($payload[IMailTemplatesConstants::owner_last_name])) {
            Log::warning(sprintf("SummitAttendeeTicketEmail owner_last_name is empty setting email"));
            $payload[IMailTemplatesConstants::owner_last_name] = $payload[IMailTemplatesConstants::owner_email];
        }

        $payload[IMailTemplatesConstants::promo_code] = ($ticket->hasPromoCode()) ? $ticket->getPromoCode()->getCode() : '';

        $support_email = $summit->getSupportEmail();
        $payload[IMailTemplatesConstants::support_email] = !empty($support_email) ? $support_email : Config::get("registration.support_email", null);

        if (empty($payload[IMailTemplatesConstants::support_email]))
            throw new \InvalidArgumentException("missing support_email value");

        $attachments = [];

        if ($summit->isRegistrationSendQrAsImageAttachmentOnTicketEmail()) {
            Log::debug(sprintf("SummitAttendeeTicketEmail::__construct adding QR as attachment for summit %s", $summit->getId()));
            $attachments[] = [
                'name' => 'qr.png',
                'content' => base64_encode(QrCode::format('png')->size(250, 250)->generate($ticket->getQRCode())),
                'type' => 'application/octet-stream',
                'disposition' => 'inline',
                'content_id' => 'qrcid',
            ];
        }

        if ($summit->isRegistrationSendTicketAsPdfAttachmentOnTicketEmail()){
            Log::debug(sprintf("SummitAttendeeTicketEmail::__construct adding Ticket PDF as attachment for summit %s", $summit->getId()));
            $renderer = new SummitAttendeeTicketPDFRenderer($ticket);
            $attachments[] = [
                'name' => 'ticket_' . $ticket->getNumber() . '.pdf',
                'content' => base64_encode($renderer->render()),
                'type' => 'application/pdf',
                'disposition' => 'attachment',
            ];
        }

        if(count($attachments) > 0)
            $payload[IMailTemplatesConstants::attachments] = $attachments;

        // default value
        if(!isset($payload[IMailTemplatesConstants::message]))
            $payload[IMailTemplatesConstants::message] = '';

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);
        Log::debug(sprintf("SummitAttendeeTicketEmail::__construct payload %s template %s", json_encode($payload), $template_identifier));
        parent::__construct($payload, $template_identifier, $owner_email);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{
        $payload = [];
        $payload[IMailTemplatesConstants::hash]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_owner_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_owner_company]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_owner_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_logo]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_virtual_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_marketing_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::raw_summit_virtual_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::raw_summit_marketing_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_virtual_site_oauth2_client_id]['type'] = 'int';
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_client_id]['type'] = 'int';
        $payload[IMailTemplatesConstants::summit_marketing_site_oauth2_scopes]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_number]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_type_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_amount]['type'] = 'float';
        $payload[IMailTemplatesConstants::ticket_currency]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_currency_symbol]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_first_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_last_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_company]['type'] = 'string';
        $payload[IMailTemplatesConstants::promo_code]['type'] = 'string';
        $payload[IMailTemplatesConstants::support_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::message]['type'] = 'string';

        $attachment_schema['type'] = 'object';
        $attachment_schema['properties'][IMailTemplatesConstants::name]['type'] = 'string';
        $attachment_schema['properties'][IMailTemplatesConstants::content]['type'] = 'string';
        $attachment_schema['properties'][IMailTemplatesConstants::type]['type'] = 'string';
        $attachment_schema['properties'][IMailTemplatesConstants::disposition]['type'] = 'string';

        $payload[IMailTemplatesConstants::attachments]['type'] = 'array';
        $payload[IMailTemplatesConstants::attachments]['items'] = $attachment_schema;

        return $payload;
    }
}