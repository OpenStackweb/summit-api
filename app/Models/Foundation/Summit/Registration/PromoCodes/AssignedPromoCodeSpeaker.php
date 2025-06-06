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
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'AssignedPromoCodeSpeaker')]
#[ORM\Entity]
class AssignedPromoCodeSpeaker extends BaseEntity
{
    /**
     * @var PresentationSpeaker
     */
    #[ORM\JoinColumn(name: 'SpeakerID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \PresentationSpeaker::class)]
    private $speaker;

    /**
     * @var SummitRegistrationPromoCode
     */
    #[ORM\JoinColumn(name: 'RegistrationPromoCodeID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \SummitRegistrationPromoCode::class)]
    protected $registration_promo_code;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'RedeemedAt', type: 'datetime')]
    protected $redeemed;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'SentAt', type: 'datetime')]
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
     * @return SummitRegistrationPromoCode
     */
    public function getRegistrationPromoCode(): ?SummitRegistrationPromoCode
    {
        return $this->registration_promo_code;
    }

    /**
     * @param SummitRegistrationPromoCode $registration_promo_code
     * @throws ValidationException
     */
    public function setRegistrationPromoCode(SummitRegistrationPromoCode $registration_promo_code): void
    {
        if (!$registration_promo_code instanceof SpeakersSummitRegistrationPromoCode &&
            !$registration_promo_code instanceof SpeakersRegistrationDiscountCode) {
            throw new ValidationException(
                "Promo code {$registration_promo_code->getCode()} is neither an instance of SpeakersSummitRegistrationPromoCode nor SpeakersRegistrationDiscountCode");
        }
        $this->registration_promo_code = $registration_promo_code;
    }

    /**
     * @return \DateTime|null
     */
    public function getRedeemedAt(): ?\DateTime
    {
        return $this->redeemed;
    }

    /**
     * @return bool
     */
    public function isRedeemed(): bool
    {
        Log::debug
        (
            sprintf
            (
                "AssignedPromoCodeSpeaker::isRedeemed %s redeemed %b speaker email %s",
                $this->getId(),
                !is_null($this->redeemed),
                $this->speaker->getEmail()
            )
        );
        return !is_null($this->redeemed);
    }

    /**
     * @param \DateTime $redeemed
     */
    public function markRedeemed(): void
    {
        $this->redeemed = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function clearRedeemedAt(): void
    {
        $this->redeemed = null;
    }

    /**
     * @return \DateTime|null
     */
    public function getSentAt(): ?\DateTime
    {
        return $this->sent;
    }

    /**
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->getSentAt() != null;
    }


    public function markSent(): void
    {
        $this->sent = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}