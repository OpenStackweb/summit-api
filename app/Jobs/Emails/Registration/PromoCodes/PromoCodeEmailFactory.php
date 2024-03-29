<?php namespace App\Jobs\Emails\Registration\PromoCodes;
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

use models\summit\IOwnablePromoCode;
use models\summit\SummitRegistrationPromoCode;

/**
 * Class PromoCodeEmailFactory
 * @package App\Jobs\Emails\Registration
 */
final class PromoCodeEmailFactory
{
    /**
     * @param SummitRegistrationPromoCode $promo_code
     */
    public static function send(SummitRegistrationPromoCode $promo_code){
        if(!$promo_code instanceof IOwnablePromoCode) return;
        if($promo_code->getOwnerType() == 'MEMBER'){
            MemberPromoCodeEmail::dispatch($promo_code);
        }
        if($promo_code->getOwnerType() == 'SPEAKER'){
            SpeakerPromoCodeEMail::dispatch($promo_code);
        }
        if($promo_code->getOwnerType() == 'SPONSOR'){
            SponsorPromoCodeEmail::dispatch($promo_code);
        }
    }
}