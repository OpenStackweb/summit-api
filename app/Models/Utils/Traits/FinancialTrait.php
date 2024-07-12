<?php namespace App\Models\Utils\Traits;
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
 * Trait FinancialTrait
 * @package App\Models\Utils\Traits
 */
trait FinancialTrait {
  /**
   * @param string $currency
   * @return bool
   * @see https://stripe.com/docs/currencies#zero-decimal
   */
  static function isZeroDecimalCurrency(string $currency): bool {
    $zeroDecimalCurrencies = [
      "JPY", // Japanese Yen
      "BIF", // Burundian Franc
      "CLP", // Chilean Peso
      "DJF", // Djiboutian Franc
      "GNF", // Guinean Franc
      "KMF", // Comorian Franc
      "KRW", // South Korean Won
      "MGA", // Malagasy Ariary
      "PYG", // Paraguayan Guarani
      "RWF", // Rwandan Franc
      "UGX", // Ugandan Shilling
      "VND", // Vietnamese Dong
      "VUV", // Vanuatu Vatu
      "XAF", // Central African CFA Franc
      "XOF", // West African CFA Franc
      "XPF", // CFP Franc
    ];

    return in_array($currency, $zeroDecimalCurrencies);
  }

  /**
   * @param float $value
   * @param bool $should_round
   * @return int
   */
  static function convertToCents(float $value, bool $should_round = true): int {
    return $should_round ? intval(round($value * 100)) : intval($value * 100);
  }

  /**
   * @param int $cents
   * @return float
   */
  static function convertToUnit(int $cents): float {
    return $cents > 0 ? $cents / 100.0 : 0;
  }
}
