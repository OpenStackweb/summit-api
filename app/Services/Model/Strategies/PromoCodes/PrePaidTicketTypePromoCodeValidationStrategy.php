<?php namespace App\Services\Model\Strategies\PromoCodes;

/**
 * Copyright 2024 OpenStack Foundation
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

use App\Models\Foundation\Summit\Registration\PromoCodes\PromoCodesUtils;
use Illuminate\Support\Facades\Log;
use models\summit\SummitRegistrationPromoCode;

/**
 * Class PrePaidTicketTypePromoCodeValidationStrategy
 * @package App\Services\Model\Strategies\PromoCodes
 */
final class PrePaidTicketTypePromoCodeValidationStrategy implements IPromoCodeValidationStrategy {
  public function isValid(SummitRegistrationPromoCode $promo_code): bool {
    Log::debug(
      sprintf(
        "PrePaidTicketTypePromoCodeValidationStrategy::isValid promo code %s",
        $promo_code->getId(),
      ),
    );

    return PromoCodesUtils::isPrePaidPromoCode($promo_code);
  }
}
