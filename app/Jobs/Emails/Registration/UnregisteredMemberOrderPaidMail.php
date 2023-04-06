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
use Illuminate\Support\Facades\Log;
use models\summit\SummitOrder;
/**
 * Class UnregisteredMemberOrderPaidMail
 * @package App\Jobs\Emails
 */
class UnregisteredMemberOrderPaidMail extends RegisteredMemberOrderPaidMail
{
    protected function getEmailEventSlug(): string
    {
        return self::EVENT_SLUG;
    }

    // metadata
    const EVENT_SLUG = 'SUMMIT_REGISTRATION_UNREGISTERED_MEMBER_ORDER_PAID';
    const EVENT_NAME = 'SUMMIT_REGISTRATION_UNREGISTERED_MEMBER_ORDER_PAID';
    const DEFAULT_TEMPLATE = 'REGISTRATION_UNREGISTERED_MEMBER_ORDER_PAID';

    /**
     * UnregisteredMemberOrderPaidMail constructor.
     * @param SummitOrder $order
     * @param string $set_password_link
     */
    public function __construct(SummitOrder $order, string $set_password_link)
    {
        Log::debug("UnregisteredMemberOrderPaidMail::__construct");
        parent::__construct($order);
        Log::debug(sprintf("UnregisteredMemberOrderPaidMail::__construct %s", $this->template_identifier));

        $summit = $order->getSummit();
        $this->payload['set_password_link'] = $set_password_link;

        $this->payload['set_password_link_to_registration'] = sprintf(
            "%s?client_id=%s&redirect_uri=%s",
            $set_password_link,
            $summit->getMarketingSiteOAuth2ClientId(),
            urlencode($summit->getMarketingSiteUrl())
        );

        $this->payload['manage_orders_url'] = sprintf("%s/a/my-tickets", $summit->getMarketingSiteUrl());

    }
}