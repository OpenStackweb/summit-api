<?php namespace App\Models\Foundation\Summit\Registration\Traits;
/*
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

/**
 * Trait TaxTrait
 * @package App\Models\Foundation\Summit\Registration\Traits
 */
trait TaxTrait {
  /**
   * @return float
   */
  public function getRate(): float {
    return $this->rate;
  }

  /**
   * @param float $rate
   */
  public function setRate(float $rate): void {
    $this->rate = $rate;
  }

  public function getRoundingStrategy(): int {
    return PHP_ROUND_HALF_UP;
  }

  public function getRoundingPrecision(): int {
    return 2;
  }

  /**
   * @param float $amount
   * @param bool $should_apply_rounding
   * @return float
   */
  public function applyTo(float $amount, bool $should_apply_rounding = true): float {
    $res = $amount * $this->getRate();
    return $should_apply_rounding ? $this->round($res) / 100.0 : $res / 100.0;
  }

  /**
   * @param float $amount
   * @return float
   */
  public function round(float $amount): float {
    return round($amount, $this->getRoundingPrecision(), $this->getRoundingStrategy());
  }
}
