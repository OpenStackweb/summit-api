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

use App\Models\Foundation\Summit\Registration\PromoCodes\PromoCodesUtils;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\SummitRegistrationPromoCode;
use models\summit\SummitTicketType;

/**
 * Class RegularTicketTypePromoCodeValidationStrategy
 * @package App\Services\Model\Strategies\PromoCodes
 */
final class RegularTicketTypePromoCodeValidationStrategy implements IPromoCodeValidationStrategy
{
    /**
     * @var SummitTicketType
     */
    private $ticket_type;

    /**
     * @var Member
     */
    private $owner;

    /**
     * @var int
     */
    private $qty;

    /**
     * @param SummitTicketType $ticket_type
     * @param Member $owner
     * @param int $qty
     */
    public function __construct(SummitTicketType $ticket_type, Member $owner, int $qty)
    {
        Log::debug
        (
            sprintf
            (
                "RegularTicketTypePromoCodeValidationStrategy::construct ticket type %s owner %s qty %s",
                $ticket_type->getId(),
                $owner->getId(),
                $qty
            )
        );

        $this->ticket_type = $ticket_type;
        $this->owner = $owner;
        $this->qty = $qty;
    }

    /**
     * @throws ValidationException
     */
    public function isValid(SummitRegistrationPromoCode $promo_code): bool
    {
        if(PromoCodesUtils::isPrePaidPromoCode($promo_code)) return false;

        Log::debug
        (
            sprintf
            (
                "RegularTicketTypePromoCodeValidationStrategy::isValid promo_code %s ticket type %s owner %s qty %s",
                $promo_code->getCode(),
                $this->ticket_type->getId(),
                $this->owner->getId(),
                $this->qty
            )
        );

        $owner_email = $this->owner->getEmail();
        $owner_company_name = $this->owner->getCompany();

        $promo_code->validate($owner_email, $owner_company_name);

        if (!$promo_code->canBeAppliedTo($this->ticket_type)) {
            $error = sprintf("Promo code %s can not be applied to Ticket Type %s.", $promo_code->getCode(), $this->ticket_type->getName());
            Log::debug($error);
            throw new ValidationException($error);
        }

        if (!$promo_code->isInfinite() && $this->qty > $promo_code->getQuantityAvailable()) {
            $error = sprintf("Promo code %s can not be applied to Ticket Type %s more than %s times.",
                $promo_code->getCode(), $this->ticket_type->getName(), $promo_code->getQuantityAvailable());
            Log::debug($error);
            throw new ValidationException($error);
        }

        return true;
    }
}