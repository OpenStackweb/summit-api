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

use Exception;
use Illuminate\Support\Facades\Log;
use models\main\Member;
use models\summit\ISummitTicketType;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
use models\summit\SummitTicketType;
use models\summit\SummitTicketTypeWithPromo;

/**
 * Class RegularPromoCodeTicketTypesStrategy
 * @package App\Models\Foundation\Summit\Registration\PromoCodes\Strategies
 */
class RegularPromoCodeTicketTypesStrategy implements IPromoCodeAllowedTicketTypesStrategy
{
    /**
     * @var Member
     */
    private $member;

    /**
     * @var SummitRegistrationPromoCode
     */
    protected $promo_code;

    /**
     * @var Summit
     */
    private $summit;

    /**
     * @param Summit $summit
     * @param Member $member
     * @param SummitRegistrationPromoCode|null $promo_code
     */
    public function __construct(Summit $summit, Member $member, ?SummitRegistrationPromoCode $promo_code)
    {
        Log::debug
        (
            sprintf
            (
                "RegularPromoCodeTicketTypesStrategy::build summit %s member %s promo code %s",
                $summit->getId(),
                $member->getId(),
                !is_null($promo_code) ? $promo_code->getCode() : 'NONE'
            )
        );
        $this->summit = $summit;
        $this->promo_code = $promo_code;
        $this->member = $member;
    }

    /**
     * @param SummitTicketType $type
     * @return ISummitTicketType
     */
    private function applyPromo2TicketType(SummitTicketType $type): ISummitTicketType {
        if (!is_null($this->promo_code) && $this->promo_code->canBeAppliedTo($type)) {
            return new SummitTicketTypeWithPromo($type, $this->promo_code);
        }
        return $type;
    }

    /**
     * @inheritDoc
     */
    public function getTicketTypes(): array
    {
        try {
            if (!is_null($this->promo_code)) {
                Log::debug(
                    sprintf(
                        "RegularPromoCodeTicketTypesStrategy::getTicketTypes validating promocode %s to apply to ticket types",
                        $this->promo_code->getCode()
                    )
                );
                $this->promo_code->validate($this->member->getEmail(), $this->member->getCompany());
            }
        } catch(Exception $ex){
            Log::warning($ex);
            // promo code is not valid, then don't use it
            $this->promo_code = null;
        }

        $all_ticket_types = [];

        // check if we can sell ticket type
        foreach ($this->summit->getTicketTypesByAudience(SummitTicketType::Audience_All) as $ticket_type) {
            if (!$ticket_type->canSell()) {
                Log::debug
                (
                    sprintf
                    (
                        "RegularPromoCodeTicketTypesStrategy::getTicketTypes ticket type %s can not be sell.",
                        $ticket_type->getId()
                    )
                );
                continue;
            }
            $all_ticket_types[] = $this->applyPromo2TicketType($ticket_type);
        }

        $invitation = $this->summit->getSummitRegistrationInvitationByEmail($this->member->getEmail());

        if (!is_null($invitation)) {

            Log::debug
            (
                sprintf
                (
                    "RegularPromoCodeTicketTypesStrategy::getTicketTypes summit %s member %s has an invitation.",
                    $this->summit->getId(),
                    $this->member->getId()
                )
            );

            if (!$invitation->isPending()) {
                Log::debug
                (
                    sprintf
                    (
                        "RegularPromoCodeTicketTypesStrategy::getTicketTypes summit %s member %s invitation already accepted or rejected.",
                        $this->summit->getId(),
                        $this->member->getId()
                    )
                );
                // only all
                return $all_ticket_types;
            }

            $invitation_ticket_types = array_map(
                function($type) { return $this->applyPromo2TicketType($type); },
                $invitation->getRemainingAllowedTicketTypes()
            );

            return array_merge($all_ticket_types, $invitation_ticket_types);
        }

        Log::debug
        (
            sprintf
            (
                "RegularPromoCodeTicketTypesStrategy::getTicketTypes summit %s member %s do not has an invitation.",
                $this->summit->getId(),
                $this->member->getId()
            )
        );

        $without_invitation_tickets_types = [];
        foreach ($this->summit->getTicketTypesByAudience(SummitTicketType::Audience_Without_Invitation) as $ticket_type) {
            if (!$ticket_type->canSell()) {
                Log::debug
                (
                    sprintf
                    (
                        "RegularPromoCodeTicketTypesStrategy::getTicketTypes ticket type %s can not be sell",
                        $ticket_type->getId()
                    )
                );
                continue;
            }
            $without_invitation_tickets_types[] = $this->applyPromo2TicketType($ticket_type);
        }
        // we do not have invitation
        return array_merge($all_ticket_types, $without_invitation_tickets_types);
    }
}