<?php namespace models\summit;
/**
 * Copyright 2026 OpenStack Foundation
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

use models\main\Member;
use models\utils\IBaseRepository;

/**
 * Interface ISummitPromoCodeMemberReservationRepository
 * @package models\summit
 */
interface ISummitPromoCodeMemberReservationRepository extends IBaseRepository
{
    /**
     * Look up the per-member reservation row for a given promo code.
     *
     * Callers invoking this from the reservation path must already hold an
     * exclusive row lock on the parent SummitRegistrationPromoCode (via
     * ISummitRegistrationPromoCodeRepository::getByValueExclusiveLock). That
     * outer lock is what serializes concurrent access to this row — no
     * separate PESSIMISTIC_WRITE is taken here.
     *
     * @param SummitRegistrationPromoCode $code
     * @param Member $member
     * @return SummitPromoCodeMemberReservation|null
     */
    public function getByPromoCodeAndMember(
        SummitRegistrationPromoCode $code,
        Member $member
    ): ?SummitPromoCodeMemberReservation;
}
