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
        $payload['owner_email'] = $owner_email;
        $payload['first_name'] = $invitation->getFirstName();
        $payload['last_name'] = $invitation->getLastName();
        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['summit_marketing_site_url'] = $summit->getMarketingSiteUrl();
        $payload['raw_summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['raw_summit_marketing_site_url'] = $summit->getMarketingSiteUrl();

        $base_url = Config::get('registration.dashboard_base_url', null);
        $invitation_form_url = Config::get('registration.dashboard_invitation_form_url', null);

        if (empty($base_url))
            throw new \InvalidArgumentException("missing dashboard_base_url value");
        if (empty($invitation_form_url))
            throw new \InvalidArgumentException("missing dashboard_invitation_form_url value");

        $payload['invitation_form_url'] = sprintf($invitation_form_url, $base_url, $invitation->getToken());
        $payload['invitation_token'] = $invitation->getToken();
        if(!empty($invitation->getSetPasswordLink())){
            $payload['invitation_form_url']  = sprintf(
                "%s?client_id=%s&redirect_uri=%s",
                $invitation->getSetPasswordLink(),
                Config::get("registration.dashboard_client_id"),
                urlencode($payload['invitation_form_url'])
            );
        }

        $ticket_types = [];

        foreach ($invitation->getTicketTypes() as $ticketType){
            $ticket_type_dto = [
                'name' => $ticketType->getName(),
                'description' => $ticketType->getDescription(),
                'price' => FormatUtils::getNiceFloat($ticketType->getFinalAmount()),
                'currency' => $ticketType->getCurrency(),
                'currency_symbol' => $ticketType->getCurrencySymbol(),
            ];

            $ticket_types[] = $ticket_type_dto;
        }

        $payload['ticket_types'] = $ticket_types;

        $support_email = $summit->getSupportEmail();
        $payload['support_email'] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);

        if (empty($payload['support_email']))
            throw new \InvalidArgumentException("missing support_email value");

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($payload, $template_identifier, $owner_email);
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