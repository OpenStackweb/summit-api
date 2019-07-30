<?php namespace models\summit;
/**
 * Copyright 2017 OpenStack Foundation
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
use Doctrine\ORM\Mapping AS ORM;
use models\main\Company;
/**
 * @ORM\Entity
 * @ORM\Table(name="SponsorSummitRegistrationPromoCode")
 * Class SponsorSummitRegistrationPromoCode
 * @package models\summit
 */
class SponsorSummitRegistrationPromoCode extends MemberSummitRegistrationPromoCode
{

    use SponsorPromoCodeTrait;

    const ClassName = 'SPONSOR_PROMO_CODE';

    /**
     * @return string
     */
    public function getClassName(){
        return self::ClassName;
    }

    public static $metadata = [
        'class_name' => self::ClassName,
        'sponsor_id' => 'integer',
        'type'       => PromoCodesConstants::SponsorSummitRegistrationPromoCodeTypes,
    ];

    /**
     * @return array
     */
    public static function getMetadata(){
        return array_merge(MemberSummitRegistrationPromoCode::getMetadata(), self::$metadata);
    }
}