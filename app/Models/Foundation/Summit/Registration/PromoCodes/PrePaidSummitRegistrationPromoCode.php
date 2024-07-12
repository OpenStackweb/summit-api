<?php namespace models\summit;
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

use App\Models\Foundation\Summit\PromoCodes\PromoCodesConstants;
use Doctrine\ORM\Mapping as ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="PrePaidSummitRegistrationPromoCode")
 * Class PrePaidSummitRegistrationPromoCode
 * @package models\summit
 */
class PrePaidSummitRegistrationPromoCode extends SummitRegistrationPromoCode {
  use PrePaidPromoCodeTrait;
  const ClassName = "PRE_PAID_PROMO_CODE";

  public static $metadata = [
    "class_name" => self::ClassName,
  ];

  /**
   * @return array
   */
  public static function getMetadata() {
    return array_merge(SummitRegistrationPromoCode::getMetadata(), self::$metadata);
  }

  /**
   * @return string
   */
  public function getClassName() {
    return self::ClassName;
  }
}
