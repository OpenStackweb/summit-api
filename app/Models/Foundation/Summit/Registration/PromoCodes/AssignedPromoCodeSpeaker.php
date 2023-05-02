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
use App\Models\Utils\BaseEntity;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="AssignedPromoCodeSpeaker")
 * Class AssignedPromoCodeSpeaker
 * @package models\summit
 */
class AssignedPromoCodeSpeaker extends BaseEntity
{
    /**
     * @ORM\OneToOne(targetEntity="PresentationSpeaker")
     * @ORM\JoinColumn(name="SpeakerID", referencedColumnName="ID")
     * @var PresentationSpeaker
     */
    private $speaker;

    /**
     * @ORM\ManyToOne(targetEntity="SpeakersSummitRegistrationPromoCode")
     * @ORM\JoinColumn(name="RegistrationPromoCodeID", referencedColumnName="ID")
     * @var SpeakersSummitRegistrationPromoCode
     */
    protected $registration_promo_code;

    /**
     * @ORM\ManyToOne(targetEntity="SpeakersRegistrationDiscountCode")
     * @ORM\JoinColumn(name="RegistrationDiscountCodeID", referencedColumnName="ID")
     * @var SpeakersRegistrationDiscountCode
     */
    protected $registration_discount_code;

    /**
     * @ORM\Column(name="RedeemedAt", type="datetime")
     * @var \DateTime
     */
    protected $redeemed;

    /**
     * @ORM\Column(name="SentAt", type="datetime")
     * @var \DateTime
     */
    protected $sent;

    /**
     * @return bool
     */
    public function hasSpeaker(): bool
    {
        return $this->getSpeakerId() > 0;
    }

    /**
     * @return int
     */
    public function getSpeakerId(): int
    {
        try {
            if (is_null($this->speaker)) return 0;
            return $this->speaker->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return PresentationSpeaker
     */
    public function getSpeaker(): PresentationSpeaker
    {
        return $this->speaker;
    }

    /**
     * @param PresentationSpeaker $speaker
     */
    public function setSpeaker(PresentationSpeaker $speaker): void
    {
        $this->speaker = $speaker;
    }

    /**
     * @return SpeakersSummitRegistrationPromoCode
     */
    public function getRegistrationPromoCode(): ?SpeakersSummitRegistrationPromoCode
    {
        return $this->registration_promo_code;
    }

    /**
     * @param SpeakersSummitRegistrationPromoCode $registration_promo_code
     */
    public function setRegistrationPromoCode(SpeakersSummitRegistrationPromoCode $registration_promo_code): void
    {
        $this->registration_promo_code = $registration_promo_code;
    }

    /**
     * @return SpeakersRegistrationDiscountCode
     */
    public function getRegistrationDiscountCode(): ?SpeakersRegistrationDiscountCode
    {
        return $this->registration_discount_code;
    }

    /**
     * @param SpeakersRegistrationDiscountCode $registration_discount_code
     */
    public function setRegistrationDiscountCode(SpeakersRegistrationDiscountCode $registration_discount_code): void
    {
        $this->registration_discount_code = $registration_discount_code;
    }

    /**
     * @return \DateTime|null
     */
    public function getRedeemedAt(): ?\DateTime
    {
        return $this->redeemed;
    }

    /**
     * @param \DateTime $redeemed
     */
    public function setRedeemedAt(\DateTime $redeemed): void
    {
        $this->redeemed = $redeemed;
    }

    /**
     * @return \DateTime|null
     */
    public function getSentAt(): ?\DateTime
    {
        return $this->sent;
    }

    /**
     * @param \DateTime $sent
     */
    public function setSentAt(\DateTime $sent): void
    {
        $this->sent = $sent;
    }
}