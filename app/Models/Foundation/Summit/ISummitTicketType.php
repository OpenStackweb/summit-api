<?php namespace models\summit;

use Doctrine\Common\Collections\ArrayCollection;
use models\utils\IEntity;

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
interface ISummitTicketType extends IEntity {
  /**
   * @return int
   */
  public function getId();

  /**
   * @return int
   */
  public function getIdentifier();

  /**
   * @return string
   */
  public function getName();

  /**
   * @return string
   */
  public function getDescription();

  /**
   * @return string
   */
  public function getExternalId(): ?string;

  /**
   * @return float
   */
  public function getCost(): float;

  /**
   * @return bool
   */
  public function canSell(): bool;

  /**
   * @return string
   */
  public function getCurrency(): ?string;

  /**
   * @return string
   */
  public function getCurrencySymbol(): ?string;

  /**
   * @return float
   */
  public function getFinalAmount(): float;

  /**
   * @return bool
   */
  public function isSoldOut(): bool;

  /**
   * @return bool
   */
  public function isLive();

  /**
   * @return int
   */
  public function getBadgeTypeId();

  /**
   * @return SummitBadgeType
   */
  public function getBadgeType(): ?SummitBadgeType;

  /**
   * @return bool
   */
  public function hasBadgeType(): bool;

  /**
   * @return string
   */
  public function getAudience(): string;

  /**
   * @return bool
   */
  public function isFree(): bool;

  /**
   * @return string
   */
  public function getSubType(): string;

  /**
   * @return Summit|null
   */
  public function getSummit(): ?Summit;

  /**
   * @return int
   */
  public function getSummitId(): int;

  /**
   * @return bool
   */
  public function hasSummit(): bool;

  /**
   * @return \DateTime
   */
  public function getCreated();

  /**
   * @return \DateTime
   */
  public function getLastEdited();

  /**
   * @return int
   */
  public function getQuantity2Sell(): int;

  /**
   * @return int
   */
  public function getMaxQuantityPerOrder(): int;

  /**
   * @return int
   */
  public function getQuantitySold(): int;

  /**
   * @return \DateTime|null
   */
  public function getSalesStartDate(): ?\DateTime;

  /**
   * @return \DateTime|null
   */
  public function getSalesEndDate(): ?\DateTime;

  /**
   * @return ArrayCollection|SummitTaxType[]
   */
  public function getAppliedTaxes();
}
