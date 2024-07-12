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
use Doctrine\ORM\Mapping as ORM;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;

/**
 * @ORM\Entity
 * @ORM\Table(name="AssignedPromoCodeSpeaker")
 * Class AssignedPromoCodeSpeaker
 * @package models\summit
 */
class AssignedPromoCodeSpeaker extends BaseEntity {
  /**
   * @ORM\ManyToOne(targetEntity="PresentationSpeaker")
   * @ORM\JoinColumn(name="SpeakerID", referencedColumnName="ID")
   * @var PresentationSpeaker
   */
  private $speaker;

  /**
   * @ORM\ManyToOne(targetEntity="SummitRegistrationPromoCode")
   * @ORM\JoinColumn(name="RegistrationPromoCodeID", referencedColumnName="ID")
   * @var SummitRegistrationPromoCode
   */
  protected $registration_promo_code;

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
  public function hasSpeaker(): bool {
    return $this->getSpeakerId() > 0;
  }

  /**
   * @return int
   */
  public function getSpeakerId(): int {
    try {
      if (is_null($this->speaker)) {
        return 0;
      }
      return $this->speaker->getId();
    } catch (\Exception $ex) {
      return 0;
    }
  }

  /**
   * @return PresentationSpeaker
   */
  public function getSpeaker(): PresentationSpeaker {
    return $this->speaker;
  }

  /**
   * @param PresentationSpeaker $speaker
   */
  public function setSpeaker(PresentationSpeaker $speaker): void {
    $this->speaker = $speaker;
  }

  /**
   * @return SummitRegistrationPromoCode
   */
  public function getRegistrationPromoCode(): ?SummitRegistrationPromoCode {
    return $this->registration_promo_code;
  }

  /**
   * @param SummitRegistrationPromoCode $registration_promo_code
   * @throws ValidationException
   */
  public function setRegistrationPromoCode(
    SummitRegistrationPromoCode $registration_promo_code,
  ): void {
    if (
      !$registration_promo_code instanceof SpeakersSummitRegistrationPromoCode &&
      !$registration_promo_code instanceof SpeakersRegistrationDiscountCode
    ) {
      throw new ValidationException(
        "Promo code {$registration_promo_code->getCode()} is neither an instance of SpeakersSummitRegistrationPromoCode nor SpeakersRegistrationDiscountCode",
      );
    }
    $this->registration_promo_code = $registration_promo_code;
  }

  /**
   * @return \DateTime|null
   */
  public function getRedeemedAt(): ?\DateTime {
    return $this->redeemed;
  }

  /**
   * @return bool
   */
  public function isRedeemed(): bool {
    Log::debug(
      sprintf(
        "AssignedPromoCodeSpeaker::isRedeemed %s redeemed %b speaker email %s",
        $this->getId(),
        !is_null($this->redeemed),
        $this->speaker->getEmail(),
      ),
    );
    return !is_null($this->redeemed);
  }

  /**
   * @param \DateTime $redeemed
   */
  public function markRedeemed(): void {
    $this->redeemed = new \DateTime("now", new \DateTimeZone("UTC"));
  }

  public function clearRedeemedAt(): void {
    $this->redeemed = null;
  }

  /**
   * @return \DateTime|null
   */
  public function getSentAt(): ?\DateTime {
    return $this->sent;
  }

  /**
   * @return bool
   */
  public function isSent(): bool {
    return $this->getSentAt() != null;
  }

  public function markSent(): void {
    $this->sent = new \DateTime("now", new \DateTimeZone("UTC"));
  }
}
