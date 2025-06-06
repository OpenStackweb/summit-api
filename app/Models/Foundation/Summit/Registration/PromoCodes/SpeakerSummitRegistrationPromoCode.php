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
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SpeakerSummitRegistrationPromoCode')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSpeakerSummitRegistrationPromoCodeRepository::class)]
class SpeakerSummitRegistrationPromoCode
    extends SummitRegistrationPromoCode
    implements IOwnablePromoCode
{
    use SpeakerPromoCodeTrait;

    const ClassName = 'SPEAKER_PROMO_CODE';

    /**
     * @return string
     */
    public function getClassName(){
        return self::ClassName;
    }

    public static $metadata = [
        'class_name' => self::ClassName,
        'type'       => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypes,
        'speaker_id' => 'integer'
    ];

    /**
     * @return array
     */
    public static function getMetadata(){
        return array_merge(SummitRegistrationPromoCode::getMetadata(), self::$metadata);
    }

    public function hasOwner(): bool
    {
        return $this->hasSpeaker();
    }

    public function getOwnerFullname(): string
    {
        if(!$this->hasOwner()) return '';
        return  $this->getSpeaker()->getFullName();
    }

    public function getOwnerEmail(): string
    {
        if(!$this->hasOwner()) return '';
        return $this->getSpeaker()->getEmail();
    }

    public function getOwnerType(): string
    {
        return "SPEAKER";
    }
}