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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSpeakersSummitRegistrationPromoCodeRepository")
 * @ORM\Table(name="SpeakersSummitRegistrationPromoCode")
 * Class SpeakerSummitRegistrationPromoCode
 * This entity has the purpose of allowing to associate several speakers to a single promo code
 * @package models\summit
 */
class SpeakersSummitRegistrationPromoCode
    extends SummitRegistrationPromoCode
{
    use SpeakersPromoCodeTrait;

    const ClassName = 'SpeakersSummitRegistrationPromoCode';

    public static $metadata = [
        'class_name' => self::ClassName,
        'type'       => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypes,
    ];

    /**
     * @return array
     */
    public static function getMetadata()
    {
        return array_merge(SummitRegistrationPromoCode::getMetadata(), self::$metadata);
    }

    public function getClassName(): string
    {
        return self::ClassName;
    }

    public function __construct()
    {
        parent::__construct();
        $this->owners = new ArrayCollection();
    }
}