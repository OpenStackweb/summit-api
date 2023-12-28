<?php namespace App\Models\Foundation\Summit\Registration\PromoCodes;
/*
 * Copyright 2023 OpenStack Foundation
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

use models\summit\PrePaidSummitRegistrationDiscountCode;
use models\summit\PrePaidSummitRegistrationPromoCode;
use models\summit\SummitRegistrationPromoCode;

/**
 * Class PromoCodesUtils
 * @package App\Models\Foundation\Summit\Registration\PromoCodes
 */
final class PromoCodesUtils
{
    /**
     * @param SummitRegistrationPromoCode|null $promo_code
     * @return bool
     */
    public static function isPrePaidPromoCode(?SummitRegistrationPromoCode $promo_code):bool{
        return $promo_code instanceof PrePaidSummitRegistrationPromoCode ||
            $promo_code instanceof PrePaidSummitRegistrationDiscountCode;
    }
}