<?php namespace App\Services\Model\Strategies\PromoCodes;

/**
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

use models\summit\SpeakersSummitRegistrationPromoCode;

/**
 * Class PromoCodeGenerator
 * @package App\Services\Model\Strategies\PromoCodes
 */
final class PromoCodeGenerator implements IPromoCodeGenerator
{
    /**
     * @param string $promo_code_class_name
     * @return string
     */
    public function generate(string $promo_code_class_name): string
    {
        $code_sufix = $promo_code_class_name == SpeakersSummitRegistrationPromoCode::ClassName
            ? "_promo_code" : "_discount_code";
        return str_random(16) . $code_sufix;
    }
}