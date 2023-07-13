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
use App\Jobs\Emails\AbstractEmailJob;
use App\Jobs\Emails\IMailTemplatesConstants;
use Illuminate\Support\Facades\Config;
use libs\utils\FormatUtils;
use models\summit\SummitOrder;
/**
 * Class SummitOrderRefundRequestAdmin
 * @package App\Jobs\Emails\Registration\Refunds
 */
class SummitOrderRefundRequestAdmin extends AbstractEmailJob
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_ORDER_REFUND_REQUEST_ADMIN';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_ORDER_REFUND_REQUEST_ADMIN';
    const DEFAULT_TEMPLATE = 'REGISTRATION_ORDER_REFUND_REQUESTED_ADMIN';

    /**
     * SummitOrderRefundRequestOwner constructor.
     * @param SummitOrder $order
     */
    public function __construct(SummitOrder $order)
    {
        $payload = [];
        $summit = $order->getSummit();
        $payload[IMailTemplatesConstants::owner_full_name] = $order->getOwnerFullName();
        $payload[IMailTemplatesConstants::owner_email]     = $order->getOwnerEmail();
        $payload[IMailTemplatesConstants::owner_company]   = $order->getOwnerCompanyName();
        $payload[IMailTemplatesConstants::order_number]    = $order->getNumber();
        $payload[IMailTemplatesConstants::order_amount] = FormatUtils::getNiceFloat($order->getFinalAmount());
        $payload[IMailTemplatesConstants::order_currency] = $order->getCurrency();
        $payload[IMailTemplatesConstants::order_currency_symbol] = $order->getCurrencySymbol();
        $payload[IMailTemplatesConstants::summit_name]     = $order->getSummit()->getName();
        $payload[IMailTemplatesConstants::summit_logo]     = $order->getSummit()->getLogoUrl();
        $payload[IMailTemplatesConstants::summit_virtual_site_url] = $summit->getVirtualSiteUrl();
        $payload[IMailTemplatesConstants::summit_marketing_site_url] = $summit->getMarketingSiteUrl();
        $payload[IMailTemplatesConstants::raw_summit_virtual_site_url] = $summit->getVirtualSiteUrl();
        $payload[IMailTemplatesConstants::raw_summit_marketing_site_url] = $summit->getMarketingSiteUrl();

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);

        $to = Config::get("registration.admin_email");
        if(empty($to)){
            throw new \InvalidArgumentException("registration.admin_email is not set");
        }

        parent::__construct($payload, $template_identifier, $to);
    }

    /**
     * @return array
     */
    public static function getEmailTemplateSchema(): array{
        $payload = [];
        $payload[IMailTemplatesConstants::owner_full_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_email]['type'] = 'string';
        $payload[IMailTemplatesConstants::owner_company]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_number]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_amount]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_currency]['type'] = 'string';
        $payload[IMailTemplatesConstants::order_currency_symbol]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_name]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_logo]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_virtual_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::summit_marketing_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::raw_summit_virtual_site_url]['type'] = 'string';
        $payload[IMailTemplatesConstants::raw_summit_marketing_site_url]['type'] = 'string';

        return $payload;
    }
}