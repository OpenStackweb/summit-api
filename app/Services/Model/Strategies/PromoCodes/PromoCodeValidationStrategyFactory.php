<?php namespace App\Services\Model\Strategies\PromoCodes;
/**
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

use Illuminate\Support\Facades\Log;
use models\main\Member;
use models\summit\SummitTicketType;
/**
 * Interface PromoCodeValidationStrategyFactory
 * @package App\Services\Model\Strategies\PromoCodes
 */
final class PromoCodeValidationStrategyFactory {
  /**
   * @param SummitTicketType $ticket_type
   * @param string $ticket_type_subtype
   * @param int $qty
   * @param Member $owner
   * @return IPromoCodeValidationStrategy
   */
  public static function createStrategy(
    SummitTicketType $ticket_type,
    string $ticket_type_subtype,
    int $qty,
    Member $owner,
  ): IPromoCodeValidationStrategy {
    Log::debug(
      sprintf(
        "PromoCodeValidationStrategyFactory::createStrategy ticket type %s subtype %s qty %s owner %s",
        $ticket_type->getId(),
        $ticket_type_subtype,
        $qty,
        $owner->getId(),
      ),
    );

    return $ticket_type_subtype === SummitTicketType::Subtype_Regular
      ? new RegularTicketTypePromoCodeValidationStrategy($ticket_type, $owner, $qty)
      : new PrePaidTicketTypePromoCodeValidationStrategy();
  }
}
