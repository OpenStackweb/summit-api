<?php namespace App\Models\Foundation\Summit\PromoCodes;
/**
 * Copyright 2018 OpenStack Foundation
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
use models\summit\MemberSummitRegistrationDiscountCode;
use models\summit\MemberSummitRegistrationPromoCode;
use models\summit\SpeakerSummitRegistrationDiscountCode;
use models\summit\SpeakerSummitRegistrationPromoCode;
use models\summit\SponsorSummitRegistrationDiscountCode;
use models\summit\SponsorSummitRegistrationPromoCode;
use models\summit\SummitRegistrationDiscountCode;
use models\summit\SummitRegistrationPromoCode;
/**
 * Class PromoCodesValidClasses
 * @package App\Models\Foundation\Summit\PromoCodes
 */
final class PromoCodesConstants
{
    public static $valid_class_names = [
        SummitRegistrationPromoCode::ClassName,
        SummitRegistrationDiscountCode::ClassName,
        SpeakerSummitRegistrationPromoCode::ClassName,
        SponsorSummitRegistrationPromoCode::ClassName,
        MemberSummitRegistrationPromoCode::ClassName,
        MemberSummitRegistrationDiscountCode::ClassName,
        SpeakerSummitRegistrationDiscountCode::ClassName,
        SponsorSummitRegistrationDiscountCode::ClassName,
    ];

    const SpeakerSummitRegistrationPromoCodeTypeAccepted  = 'ACCEPTED';
    const SpeakerSummitRegistrationPromoCodeTypeAlternate = 'ALTERNATE';

    const MemberSummitRegistrationPromoCodeTypes =  ["VIP","ATC","MEDIA ANALYST"];

    const SponsorSummitRegistrationPromoCodeTypes =  ["SPONSOR"];

    const SpeakerSummitRegistrationPromoCodeTypes = [self::SpeakerSummitRegistrationPromoCodeTypeAccepted, self::SpeakerSummitRegistrationPromoCodeTypeAlternate];
    /**
     * @return array
     */
    public static function getValidTypes(){
        return array_merge(self::MemberSummitRegistrationPromoCodeTypes, self::SpeakerSummitRegistrationPromoCodeTypes);
    }
}