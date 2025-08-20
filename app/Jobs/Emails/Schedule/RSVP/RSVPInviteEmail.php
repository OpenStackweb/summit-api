<?php namespace App\Jobs\Emails\Schedule\RSVP;
/**
 * Copyright 2025 OpenStack Foundation
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
use App\Jobs\Emails\AbstractSummitEmailJob;
use App\Jobs\Emails\IMailTemplatesConstants;
use App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class RSVPInviteEmail extends AbstractSummitEmailJob
{
    /**
     * @param RSVPInvitation $invitation
     * @param string|null $test_email_recipient
     */
    public function __construct
    (
        RSVPInvitation $invitation,
        ?string $test_email_recipient = null
    ){
        Log::debug
        (
            sprintf
            (
                "RSVPInviteEmail::____construct invitation %s token %s hash %s email %s",
                $invitation->getId(),
                $invitation->getToken(),
                $invitation->getHash(),
                $invitation->getInvitee()->getEmail()
            )
        );

        $summit_event = $invitation->getEvent();
        $summit = $summit_event->getSummit();
        $attendee = $invitation->getInvitee();
        $owner_email = $attendee->getEmail();

        $payload = [];
        $payload[IMailTemplatesConstants::owner_email] = $owner_email;
        $payload[IMailTemplatesConstants::owner_first_name] = $attendee->getFirstName();
        $payload[IMailTemplatesConstants::owner_last_name] = $attendee->getSurname();
        $payload[IMailTemplatesConstants::owner_fullname] = $attendee->getFullName();
        $payload[IMailTemplatesConstants::event_title] = $summit_event->getTitle();
        $payload[IMailTemplatesConstants::event_date] = $summit_event->getDateNice();
        $payload[IMailTemplatesConstants::event_location] = $summit_event->getLocationName();
        $payload[IMailTemplatesConstants::invitation_token] = $invitation->getToken();

        $support_email = $summit->getSupportEmail();
        $payload[IMailTemplatesConstants::support_email] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);

        if (empty($payload[IMailTemplatesConstants::support_email]))
            throw new \InvalidArgumentException("missing support_email value");

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        if (!empty($test_email_recipient)) {
            Log::debug
            (
                sprintf
                (
                    "RSVPInviteEmail::__construct replacing original email %s by %s and clearing cc field",
                    $payload[IMailTemplatesConstants::owner_email],
                    $test_email_recipient
                )
            );

            $payload[IMailTemplatesConstants::owner_email] = $test_email_recipient;
            $owner_email = $test_email_recipient;
            $payload[IMailTemplatesConstants::cc_email] = '';
        }

        parent::__construct($summit, $payload, $template_identifier, $owner_email);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{

        $payload = parent::getEmailTemplateSchema();

        $payload[IMailTemplatesConstants::owner_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_first_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_last_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_fullname]['type'] = 'string';
        $payload[IMailTemplatesConstants::event_title]['type'] = 'string';
        $payload[IMailTemplatesConstants::event_date]['type'] = 'string';
        $payload[IMailTemplatesConstants::event_location]['type'] = 'string';
        $payload[IMailTemplatesConstants::invitation_token]['type'] = 'string';
        $payload[IMailTemplatesConstants::support_email]['type'] = 'string';

        return $payload;
    }

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    public const string EVENT_SLUG = 'SUMMIT_REGISTRATION_INVITE_RSVP';
    public const string EVENT_NAME = 'SUMMIT_REGISTRATION_INVITE_RSVP';
    public const string DEFAULT_TEMPLATE = 'SUMMIT_REGISTRATION_INVITE_RSVP';
}