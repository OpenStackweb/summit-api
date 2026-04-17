<?php namespace App\Repositories\Summit;
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

use App\Repositories\SilverStripeDoctrineRepository;
use models\main\Member;
use models\summit\ISummitPromoCodeMemberReservationRepository;
use models\summit\SummitPromoCodeMemberReservation;
use models\summit\SummitRegistrationPromoCode;

/**
 * Class DoctrineSummitPromoCodeMemberReservationRepository
 * @package App\Repositories\Summit
 */
final class DoctrineSummitPromoCodeMemberReservationRepository
    extends SilverStripeDoctrineRepository
    implements ISummitPromoCodeMemberReservationRepository
{
    protected function getBaseEntity()
    {
        return SummitPromoCodeMemberReservation::class;
    }

    /**
     * @inheritDoc
     */
    public function getByPromoCodeAndMember(
        SummitRegistrationPromoCode $code,
        Member $member
    ): ?SummitPromoCodeMemberReservation
    {
        return $this->findOneBy([
            'promo_code' => $code,
            'member'     => $member,
        ]);
    }
}
