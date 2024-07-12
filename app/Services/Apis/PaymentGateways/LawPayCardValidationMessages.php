<?php namespace App\Services\Apis\PaymentGateways;
/*
 * Copyright 2022 OpenStack Foundation
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

/**
 * Class ILawPayCardValidationMessages
 * @package App\Services\Apis\PaymentGateways
 * @see namespace App\Services\Apis\PaymentGateways;
 */
final class LawPayCardValidationMessages {
  const card_number_invalid = "card_number_invalid";
  const card_number_incorrect = "card_number_incorrect";
  const card_expired = "card_expired";
  const card_cvv_incorrect = "card_cvv_incorrect";
  const card_avs_rejected = "card_avs_rejected";

  /**
   * @param string $code
   * @return bool
   */
  public static function isCardValidationError(string $code): bool {
    return in_array($code, [
      self::card_number_invalid,
      self::card_number_incorrect,
      self::card_expired,
      self::card_cvv_incorrect,
      self::card_avs_rejected,
    ]);
  }
}
