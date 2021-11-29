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
     * SummitAttendeeTicketEmail constructor.
     * @param SummitAttendeeTicket $ticket
     */
    public function __construct(SummitAttendeeTicket $ticket)
    {
        Log::debug("SummitAttendeeTicketEmail::__construct");

        $payload = [];
        $attendee = $ticket->getOwner();
        $summit = $attendee->getSummit();
        $order = $ticket->getOrder();

        $base_url = Config::get('registration.dashboard_base_url', null);
        $edit_ticket_link = Config::get('registration.dashboard_attendee_edit_form_url', null);

        if (empty($base_url))
            throw new \InvalidArgumentException("missing dashboard_base_url value");
        if (empty($edit_ticket_link))
            throw new \InvalidArgumentException("missing dashboard_attendee_edit_form_url value");

        $payload['hash'] = $ticket->getHash();

        $payload['edit_ticket_link'] = sprintf($edit_ticket_link, $base_url, $payload['hash']);

        $payload['order_owner_full_name'] = $order->getOwnerFullName();
        $payload['order_owner_company'] = $order->getOwnerCompany();
        $payload['order_owner_email'] = $order->getOwnerEmail();
        if (empty($payload['order_owner_full_name'])) {
            $payload['order_owner_full_name'] = $payload['order_owner_email'];
        }

        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();

        $summit_reassign_ticket_till_date = $summit->getReassignTicketTillDateLocal();
        if (!is_null($summit_reassign_ticket_till_date)) {
            $payload['summit_reassign_ticket_till_date'] = $summit_reassign_ticket_till_date->format("l j F Y h:i A T");
        }

        $payload['summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['summit_marketing_site_url'] = $summit->getMarketingSiteUrl();
        $payload['summit_virtual_site_oauth2_client_id'] = $summit->getVirtualSiteOAuth2ClientId();
        $payload['summit_marketing_site_oauth2_client_id'] = $summit->getMarketingSiteOAuth2ClientId();

        $payload['ticket_number'] = $ticket->getNumber();
        $payload['ticket_type_name'] = $ticket->getTicketType()->getName();
        $payload['ticket_amount'] = FormatUtils::getNiceFloat($ticket->getFinalAmount());
        $payload['ticket_currency'] = $ticket->getCurrency();
        $payload['ticket_currency_symbol'] = $ticket->getCurrencySymbol();
        $owner_email = $attendee->getEmail();
        $payload['owner_email'] = $owner_email;
        $payload['owner_first_name'] = $attendee->getFirstName();
        $payload['owner_last_name'] = $attendee->getSurname();
        $payload['owner_full_name'] = $attendee->getFullName();
        $payload['owner_company'] = $attendee->getCompanyName();

        if (empty($payload['owner_full_name'])) {
            Log::warning(sprintf("SummitAttendeeTicketEmail owner_full_name is empty setting email"));
            $payload['owner_full_name'] = $payload['owner_email'];
        }

        if (empty($payload['owner_first_name'])) {
            Log::warning(sprintf("SummitAttendeeTicketEmail owner_first_name is empty setting email"));
            $payload['owner_first_name'] = $payload['owner_email'];
        }

        if (empty($payload['owner_last_name'])) {
            Log::warning(sprintf("SummitAttendeeTicketEmail owner_last_name is empty setting email"));
            $payload['owner_last_name'] = $payload['owner_email'];
        }

        $payload['promo_code'] = ($ticket->hasPromoCode()) ? $ticket->getPromoCode()->getCode() : '';

        $support_email = $summit->getSupportEmail();
        $payload['support_email'] = !empty($support_email) ? $support_email : Config::get("registration.support_email", null);

        if (empty($payload['support_email']))
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
            $payload['attachments'] = $attachments;

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);
        Log::debug(sprintf("SummitAttendeeTicketEmail::__construct payload %s template %s", json_encode($payload), $template_identifier));
        parent::__construct($payload, $template_identifier, $owner_email);
    }
}