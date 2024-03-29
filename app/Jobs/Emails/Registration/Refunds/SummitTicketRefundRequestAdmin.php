<?php namespace App\Jobs\Emails\Registration\Refunds;
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

use App\Jobs\Emails\AbstractSummitEmailJob;
use App\Jobs\Emails\IMailTemplatesConstants;
use Illuminate\Support\Facades\Config;
use libs\utils\FormatUtils;
use models\summit\SummitAttendeeTicket;

/**
 * Class SummitTicketRefundRequestAdmin
 * @package App\Jobs\Emails\Registration\Refunds
 */
class SummitTicketRefundRequestAdmin extends AbstractSummitEmailJob
{

    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_TICKET_REFUND_REQUEST_ADMIN';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_TICKET_REFUND_REQUEST_ADMIN';
    const DEFAULT_TEMPLATE = 'REGISTRATION_TICKET_REFUND_REQUESTED_ADMIN';

    /**
     * SummitTicketRefundRequestAdmin constructor.
     * @param SummitAttendeeTicket $ticket
     */
    public function __construct(SummitAttendeeTicket $ticket)
    {
        $order = $ticket->getOrder();
        $summit = $order->getSummit();
        $payload = [];
        $payload[IMailTemplatesConstants::owner_full_name] = $order->getOwnerFullName();
        $payload[IMailTemplatesConstants::owner_email] = $order->getOwnerEmail();
        $payload[IMailTemplatesConstants::owner_company] = $order->getOwnerCompanyName();
        $payload[IMailTemplatesConstants::ticket_number] = $ticket->getNumber();
        $payload[IMailTemplatesConstants::ticket_id] = $ticket->getId();
        $payload[IMailTemplatesConstants::order_id] = $order->getId();
        $payload[IMailTemplatesConstants::order_number] = $order->getNumber();
        $payload[IMailTemplatesConstants::order_amount] = FormatUtils::getNiceFloat($order->getFinalAmount());
        $payload[IMailTemplatesConstants::order_currency] = $order->getCurrency();
        $payload[IMailTemplatesConstants::order_currency_symbol] = $order->getCurrencySymbol();

        $admin_ticket_edit_url = Config::get("registration.admin_ticket_edit_url", null);
        $payload[IMailTemplatesConstants::admin_ticket_edit_url] = !empty($admin_ticket_edit_url) ?
            sprintf($admin_ticket_edit_url, $summit->getId(), $order->getId(), $ticket->getId()) : '';

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        $to = Config::get("registration.admin_email");
        if(empty($to)){
            throw new \InvalidArgumentException("registration.admin_email is not set");
        }

        parent::__construct($summit, $payload, $template_identifier, $to);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{

        $payload = parent::getEmailTemplateSchema();

        $payload[IMailTemplatesConstants::owner_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_company]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_number]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_id]['type'] = 'int';
        $payload[IMailTemplatesConstants::order_id]['type'] = 'int';
        $payload[IMailTemplatesConstants::order_number]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_amount]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_currency]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_currency_symbol]['type'] = 'string';
        $payload[IMailTemplatesConstants::admin_ticket_edit_url]['type'] = 'string';

        return $payload;
    }
}