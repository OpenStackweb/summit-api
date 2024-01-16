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
use models\summit\SummitAttendeeTicketRefundRequest;

/**
 * Class SummitTicketRefundRequestOwner
 * @package App\Jobs\Emails\Registration\Refunds
 */
class SummitTicketRefundRequestOwner extends AbstractSummitEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_TICKET_REFUND_REQUEST_BY_OWNER';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_TICKET_REFUND_REQUEST_BY_OWNER';
    const DEFAULT_TEMPLATE = 'REGISTRATION_TICKET_REFUND_REQUESTED_OWNER';

    /**
     * SummitTicketRefundRequestOwner constructor.
     * @param SummitAttendeeTicket $ticket
     * @param SummitAttendeeTicketRefundRequest $request
     */
    public function __construct(SummitAttendeeTicket $ticket, SummitAttendeeTicketRefundRequest $request = null)
    {
        $payload = [];
        $order = $ticket->getOrder();
        $summit = $order->getSummit();
        $payload[IMailTemplatesConstants::order_number] = $order->getNumber();
        $payload[IMailTemplatesConstants::order_amount] = FormatUtils::getNiceFloat($order->getFinalAmount());
        $payload[IMailTemplatesConstants::order_currency] = $order->getCurrency();
        $payload[IMailTemplatesConstants::order_currency_symbol] = $order->getCurrencySymbol();
        $payload[IMailTemplatesConstants::owner_full_name] = $order->getOwnerFullName();
        $payload[IMailTemplatesConstants::owner_email] = $order->getOwnerEmail();
        $payload[IMailTemplatesConstants::owner_company] = $order->getOwnerCompanyName();

        $payload[IMailTemplatesConstants::ticket_number] = $ticket->getNumber();
        $payload[IMailTemplatesConstants::ticket_type_name] = $ticket->getTicketType()->getName();
        $payload[IMailTemplatesConstants::ticket_currency] = $ticket->getCurrency();
        $payload[IMailTemplatesConstants::ticket_amount] = FormatUtils::getNiceFloat($ticket->getFinalAmount());
        $payload[IMailTemplatesConstants::ticket_currency_symbol] = $ticket->getCurrencySymbol();
        $payload[IMailTemplatesConstants::ticket_refund_amount] = !is_null($request)? FormatUtils::getNiceFloat($request->getRefundedAmount()):'';
        $payload[IMailTemplatesConstants::ticket_refund_status] = !is_null($request)? $request->getStatus(): '';
        $payload[IMailTemplatesConstants::ticket_promo_code] = '';
        if ($ticket->hasPromoCode()) {
            $payload[IMailTemplatesConstants::ticket_promo_code] = $ticket->getPromoCode()->getCode();
        }

        $payload[IMailTemplatesConstants::ticket_owner] = '';
        if ($ticket->hasOwner()) {
            $payload[IMailTemplatesConstants::ticket_owner] = $ticket->getOwner()->getFullName();
        }

        $support_email = $summit->getSupportEmail();
        $payload[IMailTemplatesConstants::support_email] = !empty($support_email) ? $support_email: Config::get("registration.support_email", null);

        if (empty($payload[IMailTemplatesConstants::support_email]))
            throw new \InvalidArgumentException("missing support_email value");

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        parent::__construct($summit, $payload, $template_identifier, $payload[IMailTemplatesConstants::owner_email]);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{

        $payload = parent::getEmailTemplateSchema();

        $payload[IMailTemplatesConstants::order_number]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_amount]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_currency]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_currency_symbol]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_company]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_number]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_type_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_currency]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_amount]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_currency_symbol]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_refund_amount]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_refund_status]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_promo_code]['type'] = 'string';
        $payload[IMailTemplatesConstants::ticket_owner]['type'] = 'string';
        $payload[IMailTemplatesConstants::support_email]['type'] = 'string';

        return $payload;
    }
}