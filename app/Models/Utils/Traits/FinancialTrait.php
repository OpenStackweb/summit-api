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
trait FinancialTrait
{
    /**
     * @param string $currency
     * @return bool
     * @see https://stripe.com/docs/currencies#zero-decimal
     */
    static function isZeroDecimalCurrency(string $currency): bool
    {
        $zeroDecimalCurrencies = [
            'JPY',
            'BIF',
            'CLP',
            'DJF',
            'GNF',
            'KMF',
            'KRW',
            'MGA',
            'PYG',
            'RWF',
            'UGX',
            'VND',
            'VUV',
            'XAF',
            'XOF',
            'XPF',
        ];

        return in_array($currency, $zeroDecimalCurrencies);
    }

    /**
     * @param float $value
     * @return int
     */
    static function convertToCents(float $value):int{
        return intval(round($value * 100));
    }

    /**
     * @param int $cents
     * @return float
     */
    static function convertToUnit(int $cents):float{
        return $cents > 0 ? $cents / 100.00 : 0;
    }
}