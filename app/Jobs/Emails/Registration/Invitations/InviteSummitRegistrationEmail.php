<?php namespace App\Jobs\Emails\Registration\Invitations;
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
use App\Jobs\Emails\IMailTemplatesConstants;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use libs\utils\FormatUtils;
use models\summit\SummitRegistrationInvitation;
/**
 * Class InviteSummitRegistrationEmail
 * @package App\Jobs\Emails\Registration\Invitations
 */
class InviteSummitRegistrationEmail extends AbstractEmailJob
{

    /**
     * InviteSummitRegistrationEmail constructor.
     * @param SummitRegistrationInvitation $invitation
     */
    public function __construct(SummitRegistrationInvitation $invitation){
        Log::debug
        (
            sprintf
            (
                "InviteSummitRegistrationEmail::____construct invitation %s token %s hash %s email %s",
                $invitation->getId(),
                $invitation->getToken(),
                $invitation->getHash(),
                $invitation->getEmail()
            )
        );
        $summit = $invitation->getSummit();
        $owner_email = $invitation->getEmail();
        $payload = [];
        $payload[IMailTemplatesConstants::owner_email] = $owner_email;
        $payload[IMailTemplatesConstants::first_name] = $invitation->getFirstName();
        $payload[IMailTemplatesConstants::last_name] = $invitation->getLastName();
        $payload[IMailTemplatesConstants::summit_name] = $summit->getName();
        $payload[IMailTemplatesConstants::summit_logo] = $summit->getLogoUrl();
        $payload[IMailTemplatesConstants::summit_virtual_site_url] = $summit->getVirtualSiteUrl();
        $payload[IMailTemplatesConstants::summit_marketing_site_url] = $summit->getMarketingSiteUrl();
        $payload[IMailTemplatesConstants::raw_summit_virtual_site_url] = $summit->getVirtualSiteUrl();
        $payload[IMailTemplatesConstants::raw_summit_marketing_site_url] = $summit->getMarketingSiteUrl();
        $ticket_types = [];

        foreach ($invitation->getRemainingAllowedTicketTypes() as $ticketType){
            $ticket_type_dto = [
                'name' => $ticketType->getName(),
                'description' => $ticketType->getDescription(),
                'price' => FormatUtils::getNiceFloat($ticketType->getFinalAmount()),
                'currency' => $ticketType->getCurrency(),
                'currency_symbol' => $ticketType->getCurrencySymbol(),
            ];

            $ticket_types[] = $ticket_type_dto;
        }

        $payload[IMailTemplatesConstants::ticket_types] = $ticket_types;

        $support_email = $summit->getSupportEmail();
        $payload[IMailTemplatesConstants::support_email] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);

        if (empty($payload[IMailTemplatesConstants::support_email]))
            throw new \InvalidArgumentException("missing support_email value");

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $owner_email);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{
        $payload = [];
        $payload[IMailTemplatesConstants::owner_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::first_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::last_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_logo]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_virtual_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_marketing_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::raw_summit_virtual_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::raw_summit_marketing_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::support_email]['type'] = 'string';

        $ticket_type_schema = [];
        $ticket_schema['type'] = 'object';
        $ticket_schema['properties'][IMailTemplatesConstants::name]['type'] = 'string';
        $ticket_schema['properties'][IMailTemplatesConstants::description]['type'] = 'string';
        $ticket_schema['properties'][IMailTemplatesConstants::price]['type'] = 'float';
        $ticket_schema['properties'][IMailTemplatesConstants::currency]['type'] = 'string';
        $ticket_schema['properties'][IMailTemplatesConstants::currency_symbol]['type'] = 'string';

        $payload[IMailTemplatesConstants::ticket_types]['type'] = 'array';
        $payload[IMailTemplatesConstants::ticket_types]['items'] = $ticket_type_schema;

        return $payload;
    }

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_INVITE_REGISTRATION';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_INVITE_REGISTRATION';
    const DEFAULT_TEMPLATE = 'SUMMIT_REGISTRATION_INVITE_REGISTRATION';
}