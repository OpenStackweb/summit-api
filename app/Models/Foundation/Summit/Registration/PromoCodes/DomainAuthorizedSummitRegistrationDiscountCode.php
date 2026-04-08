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

use Doctrine\ORM\Mapping AS ORM;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'DomainAuthorizedSummitRegistrationDiscountCode')]
#[ORM\Entity]
class DomainAuthorizedSummitRegistrationDiscountCode extends SummitRegistrationDiscountCode
    implements IDomainAuthorizedPromoCode
{
    use DomainAuthorizedPromoCodeTrait;
    use AutoApplyPromoCodeTrait;

    const ClassName = 'DOMAIN_AUTHORIZED_DISCOUNT_CODE';

    /**
     * @return string
     */
    public function getClassName(){
        return self::ClassName;
    }

    public static $metadata = [
        'class_name'            => self::ClassName,
        'allowed_email_domains' => 'array',
        'quantity_per_account'  => 'integer',
        'auto_apply'            => 'boolean',
    ];

    /**
     * @return array
     */
    public static function getMetadata(){
        return array_merge(SummitRegistrationDiscountCode::getMetadata(), self::$metadata);
    }

    /**
     * Override: any ticket type can be added regardless of audience value.
     * @param SummitTicketType $ticket_type
     */
    public function addAllowedTicketType(SummitTicketType $ticket_type)
    {
        parent::addAllowedTicketType($ticket_type);
    }

    /**
     * Override: only writes to ticket_types_rules, NOT to allowed_ticket_types.
     * Requires the ticket type to already be in allowed_ticket_types.
     *
     * @param SummitRegistrationDiscountCodeTicketTypeRule $rule
     * @throws ValidationException
     */
    public function addTicketTypeRule(SummitRegistrationDiscountCodeTicketTypeRule $rule){
        $ticketType = $rule->getTicketType();

        // Verify ticket type is already in allowed_ticket_types
        if (!$this->canBeAppliedTo($ticketType)) {
            throw new ValidationException(
                sprintf(
                    'Ticket type %s must be in allowed_ticket_types before adding a discount rule for promo code %s.',
                    $ticketType->getId(),
                    $this->getId()
                )
            );
        }

        if ($this->isOnRules($ticketType)) {
            throw new ValidationException(
                sprintf(
                    'Ticket type %s already belongs to discount code %s rules.',
                    $ticketType->getId(),
                    $this->getId()
                )
            );
        }

        $rule->setDiscountCode($this);
        if ($this->getTicketTypesRules()->contains($rule)) return;

        // Only write to ticket_types_rules — do NOT touch allowed_ticket_types
        $this->getTicketTypesRules()->add($rule);
    }

    /**
     * Override: removes from ticket_types_rules only, does NOT touch allowed_ticket_types.
     *
     * @param SummitTicketType $ticketType
     * @throws ValidationException
     */
    public function removeTicketTypeRuleForTicketType(SummitTicketType $ticketType){
        $rule = $this->getRuleByTicketType($ticketType);
        if (is_null($rule)) {
            throw new ValidationException(
                sprintf(
                    'Ticket type %s does not belong to discount code %s rules.',
                    $ticketType->getId(),
                    $this->getId()
                )
            );
        }
        // Only remove from ticket_types_rules — do NOT touch allowed_ticket_types
        $this->getTicketTypesRules()->removeElement($rule);
        $rule->clearDiscountCode();
    }

    /**
     * Override: skip free-ticket guard. Domain-authorized discount codes can be applied to
     * ticket types in allowed_ticket_types regardless of price. This allows free WithPromoCode
     * ticket types (comp passes, speaker passes) to be used with discount codes.
     * See SDS Truth #15.
     *
     * @param SummitTicketType $ticketType
     * @return bool
     */
    public function canBeAppliedTo(SummitTicketType $ticketType): bool
    {
        Log::debug(sprintf("DomainAuthorizedSummitRegistrationDiscountCode::canBeAppliedTo Ticket type %s.", $ticketType->getId()));
        // Skip the free-ticket guard from SummitRegistrationDiscountCode::canBeAppliedTo
        // Go directly to the base class check (allowed_ticket_types membership, etc.)
        return SummitRegistrationPromoCode::canBeAppliedTo($ticketType);
    }

    /**
     * Transient property for remaining quantity per account (set by service layer).
     * @var int|null
     */
    private $remaining_quantity_per_account = null;

    /**
     * @return int|null
     */
    public function getRemainingQuantityPerAccount(): ?int
    {
        return $this->remaining_quantity_per_account;
    }

    /**
     * @param int|null $remaining
     */
    public function setRemainingQuantityPerAccount(?int $remaining): void
    {
        $this->remaining_quantity_per_account = $remaining;
    }
}
