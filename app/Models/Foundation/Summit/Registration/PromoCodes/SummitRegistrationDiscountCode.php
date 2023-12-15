<?php namespace models\summit;
/**
 * Copyright 2019 OpenStack Foundation
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
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
/**
 * @ORM\Entity
 * @ORM\Table(name="SummitRegistrationDiscountCode")
 * Class SummitRegistrationDiscountCode
 * @package models\summit
 */
class SummitRegistrationDiscountCode extends SummitRegistrationPromoCode
{
    /**
     * @ORM\Column(name="DiscountRate", type="float")
     * @var float
     */
    protected $rate;

    /**
     * @ORM\Column(name="DiscountAmount", type="float")
     * @var float
     */
    protected $amount;

    /**
     * @ORM\OneToMany(targetEntity="SummitRegistrationDiscountCodeTicketTypeRule", mappedBy="discount_code", cascade={"persist"}, orphanRemoval=true)
     * @var SummitRegistrationDiscountCodeTicketTypeRule[]
     */
    private $ticket_types_rules;

    /**
     * @return float
     */
    public function getRate(): float
    {
        return $this->rate;
    }

    /**
     * @param float $rate
     * @throws ValidationException
     */
    public function setRate(float $rate): void
    {
        if($this->amount > 0.0 && $rate > 0.0)
            throw new ValidationException("discount amount already set");
        $this->rate = $rate;
    }

    /**
     * @param float $amount
     * @throws ValidationException
     */
    public function setAmount(float $amount): void
    {
        if($this->rate > 0.0 && $amount > 0.0)
            throw new ValidationException("discount rate already set");
        $this->amount = $amount;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    public function __construct()
    {
        parent::__construct();
        $this->ticket_types_rules = new ArrayCollection();
        $this->amount = 0.0;
        $this->rate = 0.0;
    }

    public function getTicketTypesRules(){
        return $this->ticket_types_rules;
    }

    /**
     * @param SummitTicketType $ticket_type
     * @return bool
     */
    public function isOnRules(SummitTicketType $ticket_type)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('ticket_type', $ticket_type));
        return $this->ticket_types_rules->matching($criteria)->count() > 0;
    }

    /**
     * @param SummitRegistrationDiscountCodeTicketTypeRule $rule
     * @throws ValidationException
     */
    public function addTicketTypeRule(SummitRegistrationDiscountCodeTicketTypeRule $rule){
        $rule->setDiscountCode($this);
        if($this->ticket_types_rules->contains($rule)) return;
        if ($this->isOnRules($rule->getTicketType()))
            throw new ValidationException
            (
                sprintf('ticket type %s already belongs to discount code %s rules.', $rule->getTicketType()->getId(), $this->getId())
            );
        $this->ticket_types_rules->add($rule);
        $this->allowed_ticket_types->add($rule->getTicketType());
    }

    /**
     * @param SummitTicketType $ticket_type
     * @return SummitRegistrationDiscountCodeTicketTypeRule|null
     */
    public function getRuleByTicketType(SummitTicketType $ticket_type){
        try {
            $query = $this->createQuery("SELECT r from models\summit\SummitRegistrationDiscountCodeTicketTypeRule r 
        JOIN r.discount_code d
        JOIN r.ticket_type t    
        WHERE d.id = :discount_code_id and t.id = :ticket_type_id
        ");
            return $query
                ->setParameter('discount_code_id', $this->getIdentifier())
                ->setParameter('ticket_type_id', $ticket_type->getIdentifier())
                ->getSingleResult();
        }
        catch(NoResultException $ex1){
            return null;
        }
        catch(NonUniqueResultException $ex2){
            // should never happen
            return null;
        }
    }


    /**
     * @param SummitTicketType $ticketType
     * @throws ValidationException
     */
    public function removeTicketTypeRuleForTicketType(SummitTicketType $ticketType){
        $rule = $this->getRuleByTicketType($ticketType);
        if(is_null($rule))
            throw new ValidationException
            (
                sprintf('ticket type %s does not belongs to discount code %s rules.', $ticketType->getId(), $this->getId())
            );
        $this->ticket_types_rules->removeElement($rule);
        $this->allowed_ticket_types->removeElement($rule->getTicketType());
        $rule->clearDiscountCode();
    }

    /**
     * @param SummitRegistrationDiscountCodeTicketTypeRule $rule
     */
    public function removeTicketTypeRule(SummitRegistrationDiscountCodeTicketTypeRule $rule){
        if(!$this->ticket_types_rules->contains($rule)) return;
        $this->ticket_types_rules->removeElement($rule);
        $this->allowed_ticket_types->add($rule->getTicketType());
    }

    const ClassName = 'SUMMIT_DISCOUNT_CODE';

    /**
     * @return string
     */
    public function getClassName(){
        return self::ClassName;
    }

    public static $metadata = [
        'rate'               => 'float',
        'amount'             => 'float',
        'ticket_types_rules' => 'array'
    ];

    /**
     * @return array
     */
    public static function getMetadata(){
        $parent_metadata = SummitRegistrationPromoCode::getMetadata();
        $parent_metadata['class_name']= SummitRegistrationDiscountCode::ClassName;
        unset($parent_metadata['allowed_ticket_types']);
        return array_merge($parent_metadata, SummitRegistrationDiscountCode::$metadata);
    }

    /**
     * @param SummitRegistrationDiscountCode $discount_code
     * @param SummitTicketType $ticket_type
     * @param float $original_amount
     * @return float
     */
    public function getDiscountAmount(SummitTicketType $ticket_type, float $original_amount): float
    {
        if ($this->amount > 0.0) return $this->amount;
        if ($this->rate > 0.0) return ($original_amount * $this->rate) / 100.00;

        $rule = $this->getRuleByTicketType($ticket_type);
        if (!is_null($rule) && $rule->getAmount() > 0.0) return $rule->getAmount();
        if (!is_null($rule) && $rule->getRate() > 0.0) return ($original_amount * $rule->getRate()) / 100.00;

        return 0.0;
    }

    /**
     * @param SummitAttendeeTicket $ticket
     * @return SummitAttendeeTicket
     * @throws ValidationException
     */
    public function applyTo(SummitAttendeeTicket $ticket):SummitAttendeeTicket{
        $ticket = parent::applyTo($ticket);

        if(!$ticket->isFree()) {
            $amount2Discount = $this->getDiscountAmount($ticket->getTicketType(), $ticket->getRawCost());
            $ticket->setDiscount($amount2Discount);
        }
        return $ticket;
    }

    public function canBeAppliedTo(SummitTicketType $ticketType):bool{
        Log::debug(sprintf("SummitRegistrationDiscountCode::canBeAppliedTo Ticket type %s.", $ticketType->getId()));
        if($ticketType->isFree()){
            return false;
        }
        return parent::canBeAppliedTo($ticketType);
    }

}