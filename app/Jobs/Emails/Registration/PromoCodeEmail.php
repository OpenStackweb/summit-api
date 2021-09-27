<?php namespace App\Jobs\Emails\Registration;
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
use models\summit\IOwnablePromoCode;
use models\summit\SummitRegistrationPromoCode;
/**
 * Class PromoCodeEmail
 * @package App\Jobs\Emails\Registration
 */
abstract class PromoCodeEmail extends AbstractEmailJob
{

    /**
     * PromoCodeEmail constructor.
     * @param SummitRegistrationPromoCode $promo_code
     */
    public function __construct(SummitRegistrationPromoCode $promo_code){

        if(!$promo_code instanceof IOwnablePromoCode)
            throw new \InvalidArgumentException('promo code is not ownerable.');

        $summit = $promo_code->getSummit();
        $payload = [];
        $payload['summit_name'] = $summit->getName();
        $payload['summit_logo'] = $summit->getLogoUrl();
        $payload['summit_virtual_site_url'] = $summit->getVirtualSiteUrl();
        $payload['summit_marketing_site_url'] = $summit->getMarketingSiteUrl();
        $payload['promo_code'] = $promo_code->getCode();

        $payload['owner_email'] = $promo_code->getOwnerEmail();
        $payload['owner_fullname'] = $promo_code->getOwnerFullname();
        if(empty($payload['owner_fullname'])){
            $payload['owner_fullname'] = $payload['owner_email'];
        }

        $payload['registration_url'] = Config::get("registration.dashboard_base_url", null);
        if(empty($payload['registration_url']))
            throw new \InvalidArgumentException("missing dashboard_base_url value");

        $template_identifier = $this->getEmailTemplateIdentifierFromEmailEvent($summit);
        parent::__construct($payload, $template_identifier, $payload['owner_email']);
    }

}