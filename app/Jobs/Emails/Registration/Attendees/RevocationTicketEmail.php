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
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeTicket;
/**
 * Class RevocationTicketEmail
 * @package App\Jobs\Emails
 */
class RevocationTicketEmail extends AbstractSummitEmailJob {
  protected function getEmailEventSlug(): string {
    return self::EVENT_SLUG;
  }

  // metadata
  const EVENT_SLUG = "SUMMIT_REGISTRATION_TICKET_REVOCATION";
  const EVENT_NAME = "SUMMIT_REGISTRATION_TICKET_REVOCATION";
  const DEFAULT_TEMPLATE = "REGISTRATION_REVOCATION_TICKET";

  /**
   * RevocationTicketEmail constructor.
   * @param SummitAttendee $attendee
   * @param SummitAttendeeTicket $ticket
   */
  public function __construct(SummitAttendee $attendee, SummitAttendeeTicket $ticket) {
    $owner_email = $attendee->getEmail();
    $summit = $attendee->getSummit();
    $order = $ticket->getOrder();
    $payload = [];

    $payload[IMailTemplatesConstants::owner_full_name] = $attendee->getFullName();
    $payload[IMailTemplatesConstants::owner_email] = $attendee->getEmail();
    $payload[IMailTemplatesConstants::owner_first_name] = $attendee->getFirstName();
    $payload[IMailTemplatesConstants::owner_last_name] = $attendee->getSurname();
    $payload[IMailTemplatesConstants::owner_company] = $attendee->getCompanyName();
    if (empty($payload[IMailTemplatesConstants::owner_full_name])) {
      $payload[IMailTemplatesConstants::owner_full_name] =
        $payload[IMailTemplatesConstants::owner_email];
    }

    $payload[IMailTemplatesConstants::order_owner_full_name] = $order->getOwnerFullName();
    $payload[IMailTemplatesConstants::order_owner_email] = $order->getOwnerEmail();
    $payload[IMailTemplatesConstants::order_owner_company] = $order->getOwnerCompanyName();
    if (empty($payload[IMailTemplatesConstants::order_owner_full_name])) {
      $payload[IMailTemplatesConstants::order_owner_full_name] =
        $payload[IMailTemplatesConstants::order_owner_email];
    }

    $payload[IMailTemplatesConstants::ticket_number] = $ticket->getNumber();
    $payload[IMailTemplatesConstants::ticket_type_name] = $ticket->getTicketTypeName();

    $support_email = $summit->getSupportEmail();
    $payload[IMailTemplatesConstants::support_email] = !empty($support_email)
      ? $support_email
      : Config::get("registration.support_email", null);

    if (empty($payload[IMailTemplatesConstants::support_email])) {
      throw new \InvalidArgumentException("missing support_email value");
    }

    $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

    parent::__construct($summit, $payload, $template_identifier, $owner_email);
  }

  /**
   * @return array
   */
  public static function getEmailTemplateSchema(): array {
    $payload = parent::getEmailTemplateSchema();

    $payload[IMailTemplatesConstants::order_owner_full_name]["type"] = "string";
    $payload[IMailTemplatesConstants::order_owner_company]["type"] = "string";
    $payload[IMailTemplatesConstants::order_owner_email]["type"] = "string";
    $payload[IMailTemplatesConstants::owner_full_name]["type"] = "string";
    $payload[IMailTemplatesConstants::owner_company]["type"] = "string";
    $payload[IMailTemplatesConstants::owner_email]["type"] = "string";
    $payload[IMailTemplatesConstants::owner_first_name]["type"] = "string";
    $payload[IMailTemplatesConstants::owner_last_name]["type"] = "string";
    $payload[IMailTemplatesConstants::ticket_number]["type"] = "string";
    $payload[IMailTemplatesConstants::ticket_type_name]["type"] = "string";
    $payload[IMailTemplatesConstants::support_email]["type"] = "string";

    return $payload;
  }
}
