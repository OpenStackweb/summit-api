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

use App\Models\Utils\BaseEntity;
use models\utils\One2ManyPropertyTrait;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="SummitAttendeeTicket_Taxes")
 * Class SummitAttendeeTicket_Taxes
 * @package models\summit
 */
class SummitAttendeeTicketTax extends BaseEntity
{
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
     * @param SummitAttendeeTicket $ticket
     */
    public function setTicket(SummitAttendeeTicket $ticket): void
    {
        $this->ticket = $ticket;
    }

    /**
     * @return SummitTaxType
     */
    public function getTax(): SummitTaxType
    {
        return $this->tax;
    }

    /**
     * @param SummitTaxType $tax
     */
    public function setTax(SummitTaxType $tax): void
    {
        $this->tax = $tax;
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
        return intval(round($this->amount * 100));
    }

    /**
     * @param float $amount
     */
    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @ORM\ManyToOne(targetEntity="SummitAttendeeTicket", inversedBy="applied_taxes")
     * @ORM\JoinColumn(name="SummitAttendeeTicketID", referencedColumnName="ID")
     * @var SummitAttendeeTicket
     */
    private $ticket;

    /**
     * @ORM\ManyToOne(targetEntity="SummitTaxType")
     * @ORM\JoinColumn(name="SummitTaxTypeID", referencedColumnName="ID")
     * @var SummitTaxType
     */
    private $tax;

    /**
     * @ORM\Column(name="Amount", type="float")
     * @var float
     */
    private $amount;

    public function __construct()
    {

    }

}