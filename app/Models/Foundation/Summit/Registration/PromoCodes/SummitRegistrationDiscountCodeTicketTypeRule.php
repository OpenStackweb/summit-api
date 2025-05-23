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
use Doctrine\ORM\Mapping AS ORM;
use App\Models\Utils\BaseEntity;
use models\exceptions\ValidationException;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitRegistrationDiscountCode_AllowedTicketTypes')]
#[ORM\Entity]
class SummitRegistrationDiscountCodeTicketTypeRule extends BaseEntity
{
    /**
     * @var float
     */
    #[ORM\Column(name: 'DiscountRate', type: 'float')]
    protected $rate;

    /**
     * @var float
     */
    #[ORM\Column(name: 'DiscountAmount', type: 'float')]
    protected $amount;

    /**
     * @var SummitTicketType
     */
    #[ORM\JoinColumn(name: 'SummitTicketTypeID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \SummitTicketType::class)]
    protected $ticket_type;

    /**
     * @var SummitRegistrationDiscountCode
     */
    #[ORM\JoinColumn(name: 'SummitRegistrationDiscountCodeID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \SummitRegistrationDiscountCode::class, inversedBy: 'ticket_types_rules')]
    protected $discount_code;

    /**
     * @return float
     */
    public function getRate(): ?float
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
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * @return SummitTicketType
     */
    public function getTicketType(): SummitTicketType
    {
        return $this->ticket_type;
    }

    /**
     * @param SummitTicketType $ticket_type
     */
    public function setTicketType(SummitTicketType $ticket_type): void
    {
        $this->ticket_type = $ticket_type;
    }

    /**
     * @return SummitRegistrationDiscountCode
     */
    public function getDiscountCode(): SummitRegistrationDiscountCode
    {
        return $this->discount_code;
    }

    /**
     * @param SummitRegistrationDiscountCode $discount_code
     */
    public function setDiscountCode(SummitRegistrationDiscountCode $discount_code): void
    {
        $this->discount_code = $discount_code;
    }

    public function __construct()
    {
        $this->amount = 0.0;
        $this->rate = 0.0;
    }

    public function clearDiscountCode(){
        $this->discount_code = null;
    }

    public function getTicketTypeId(){
        try {
            return is_null($this->ticket_type) ? 0 : $this->ticket_type->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    public function getDiscountCodeId(){
        try {
            return is_null($this->discount_code) ? 0 : $this->discount_code->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }
}