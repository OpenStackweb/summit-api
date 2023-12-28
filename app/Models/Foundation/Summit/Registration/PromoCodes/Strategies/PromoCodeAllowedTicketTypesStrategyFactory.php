<?php namespace App\Models\Foundation\Summit\Registration\PromoCodes\Strategies;
/*
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

use App\Models\Foundation\Summit\Registration\PromoCodes\PromoCodesUtils;
use Illuminate\Support\Facades\Log;
use models\main\Member;
use models\summit\PrePaidSummitRegistrationDiscountCode;
use models\summit\PrePaidSummitRegistrationPromoCode;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;

/**
 * Class PromoCodeAllowedTicketTypesStrategyFactory
 * @package App\Models\Foundation\Summit\Registration\PromoCodes\Strategies
 */
class PromoCodeAllowedTicketTypesStrategyFactory implements IPromoCodeAllowedTicketTypesStrategyFactory
{
    /**
     * @inheritDoc
     */
    public function build(
        Summit $summit, Member $member, ?SummitRegistrationPromoCode $promo_code): IPromoCodeAllowedTicketTypesStrategy
    {
        if (PromoCodesUtils::isPrePaidPromoCode($promo_code)){
            Log::debug(
                sprintf(
                    "PromoCodeAllowedTicketTypesStrategyFactory::build applying prepaid promo code %s to ticket types for summit id %s and member %s",
                    $promo_code->getCode(),
                    $summit->getId(),
                    $member->getId()
                )
            );
            return new PrePaidPromoCodeTicketTypesStrategy($summit, $member, $promo_code);
        }
        return new RegularPromoCodeTicketTypesStrategy($summit, $member, $promo_code);
    }
}