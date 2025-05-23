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

use App\Models\Foundation\Summit\Registration\Traits\TaxTrait;
use App\Models\Utils\BaseEntity;
use App\Models\Utils\Traits\FinancialTrait;
use models\utils\One2ManyPropertyTrait;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitAttendeeTicket_Taxes')]
#[ORM\Entity]
class SummitAttendeeTicketTax extends BaseEntity
{
    use FinancialTrait;

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getTicketId' => 'ticket',
        'getTaxId' => 'tax',
    ];

    protected $hasPropertyMappings = [
        'hasTicket' => 'ticket',
        'hasTax' => 'tax',
    ];

    /**
     * @return SummitAttendeeTicket
     */
    public function getTicket(): SummitAttendeeTicket
    {
        return $this->ticket;
    }

    /**
     * @return SummitTaxType
     */
    public function getTax(): SummitTaxType
    {
        return $this->tax;
    }


    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return int
     */
    public function getAmountInCents(): int
    {
        return self::convertToCents($this->amount);
    }

    /**
     * @var SummitAttendeeTicket
     */
    #[ORM\JoinColumn(name: 'SummitAttendeeTicketID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \SummitAttendeeTicket::class, inversedBy: 'applied_taxes')]
    private $ticket;

    /**
     * @var SummitTaxType
     */
    #[ORM\JoinColumn(name: 'SummitTaxTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \SummitTaxType::class)]
    private $tax;

    /**
     * @var float
     */
    #[ORM\Column(name: 'Amount', type: 'float')]
    private $amount;

    /**
     * @var double
     */
    #[ORM\Column(name: 'Rate', type: 'float')]
    private $rate;

    /**
     * @param SummitTaxType $tax
     * @param SummitAttendeeTicket $ticket
     */
    public function __construct(SummitTaxType $tax, SummitAttendeeTicket $ticket)
    {
        $this->tax = $tax;
        $this->ticket = $ticket;
        $this->amount = $tax->applyTo($ticket->getNetSellingPrice(), false);
        $this->rate = $tax->getRate();
    }

    use TaxTrait;

}