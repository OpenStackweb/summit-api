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
use Doctrine\Common\Collections\ArrayCollection;
/**
 * Class SummitTicketTypeWithPromo
 * @package models\summit
 */
class SummitTicketTypeWithPromo implements ISummitTicketType {
  /**
   * @var SummitTicketType
   */
  protected $type;

  /**
   * @var SummitRegistrationPromoCode
   */
  protected $promo_code;

  public function __construct(SummitTicketType $type, SummitRegistrationPromoCode $promo_code) {
    $this->type = $type;
    $this->promo_code = $promo_code;
  }

  /**
   * @return int
   */
  public function getId() {
    return $this->type->getId();
  }

  /**
   * @return int
   */
  public function getIdentifier() {
    return $this->type->getIdentifier();
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->type->getName();
  }

  /**
   * @return string
   */
  public function getDescription() {
    return $this->type->getDescription();
  }

  /**
   * @return string
   */
  public function getExternalId(): ?string {
    return $this->type->getExternalId();
  }

  /**
   * @return float
   */
  public function getCost(): float {
    return $this->type->getCost();
  }

  /**
   * @return float
   */
  public function getCostWithAppliedDiscount(): float {
    $raw_cost = $this->type->getCost();

    if ($this->promo_code instanceof SummitRegistrationDiscountCode) {
      return $raw_cost - $this->promo_code->getDiscountAmount($this->type, $raw_cost);
    }
    return $raw_cost;
  }

  /**
   * @return bool
   */
  public function canSell(): bool {
    return $this->type->canSell();
  }

  /**
   * @return string
   */
  public function getCurrencySymbol(): ?string {
    return $this->type->getCurrencySymbol();
  }

  /**
   * @return float
   */
  public function getFinalAmount(): float {
    return $this->type->getFinalAmount();
  }

  /**
   * @return bool
   */
  public function isSoldOut(): bool {
    return $this->type->isSoldOut();
  }

  /**
   * @return bool
   */
  public function isLive(): bool {
    return $this->type->isLive();
  }

  /**
   * @return int
   */
  public function getBadgeTypeId(): int {
    return $this->type->getBadgeTypeId();
  }

  /**
   * @return SummitBadgeType
   */
  public function getBadgeType(): ?SummitBadgeType {
    return $this->type->getBadgeType();
  }

  /**
   * @return bool
   */
  public function hasBadgeType(): bool {
    return $this->type->hasBadgeType();
  }

  /**
   * @return string
   */
  public function getAudience(): string {
    return $this->type->getAudience();
  }

  /**
   * @return bool
   */
  public function isFree(): bool {
    return $this->type->isFree();
  }

  /**
   * @return string
   */
  public function getSubType(): string {
    return $this->type->getSubType();
  }

  /**
   * @return Summit|null
   */
  public function getSummit(): ?Summit {
    return $this->type->getSummit();
  }

  /**
   * @return int
   */
  public function getSummitId(): int {
    return $this->type->getSummitId();
  }

  /**
   * @return bool
   */
  public function hasSummit(): bool {
    return $this->type->hasSummit();
  }

  /**
   * @return \DateTime
   */
  public function getCreated() {
    return $this->type->getCreated();
  }

  /**
   * @return \DateTime
   */
  public function getLastEdited() {
    return $this->type->getLastEdited();
  }

  /**
   * @return int
   */
  public function getQuantity2Sell(): int {
    return $this->type->getQuantity2Sell();
  }

  /**
   * @return int
   */
  public function getMaxQuantityPerOrder(): int {
    return $this->type->getMaxQuantityPerOrder();
  }

  /**
   * @return int
   */
  public function getQuantitySold(): int {
    return $this->type->getQuantitySold();
  }

  /**
   * @return \DateTime|null
   */
  public function getSalesStartDate(): ?\DateTime {
    return $this->type->getSalesStartDate();
  }

  /**
   * @return \DateTime|null
   */
  public function getSalesEndDate(): ?\DateTime {
    return $this->type->getSalesEndDate();
  }

  /**
   * @return ArrayCollection|SummitTaxType[]
   */
  public function getAppliedTaxes() {
    return $this->type->getAppliedTaxes();
  }

  /**
   * @return string|null
   */
  public function getCurrency(): ?string {
    return $this->type->getCurrency();
  }
}
