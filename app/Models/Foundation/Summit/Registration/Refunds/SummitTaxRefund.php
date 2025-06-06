<?php namespace models\summit;
/*
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

use App\Models\Utils\Traits\FinancialTrait;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;

/**
 * Class SummitRefundRequest
 * @package models\summit
 */
#[ORM\Table(name: 'SummitTaxRefund')]
#[ORM\Entity]
class SummitTaxRefund extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    use FinancialTrait;

    /**
     * @var SummitRefundRequest
     */
    #[ORM\JoinColumn(name: 'SummitRefundRequestID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitRefundRequest::class, inversedBy: 'refunded_taxes', fetch: 'EXTRA_LAZY')]
    private $refund_request;

    /**
     * @var float
     */
    #[ORM\Column(name: 'RefundedAmount', type: 'float')]
    private $refunded_amount;

    /**
     * @var SummitTaxType
     */
    #[ORM\JoinColumn(name: 'SummitTaxTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \SummitTaxType::class)]
    private $tax;

    protected $getIdMappings = [
        'getRefundRequestId' => 'refund_request',
        'getTaxId' => 'tax',
    ];

    protected $hasPropertyMappings = [
        'hasRefundRequest' => 'refund_request',
        'hasTax' => 'tax',
    ];

    public function __construct(SummitRefundRequest $refund_request, SummitTaxType $tax, float $refunded_amount)
    {
        parent::__construct();

        $this->tax = $tax;
        $this->refund_request = $refund_request;
        $this->refunded_amount = $refunded_amount;
    }

    public function getRefundRequest(): SummitRefundRequest
    {
        return $this->refund_request;
    }

    public function getRefundedAmount(): float
    {
        return $this->refunded_amount;
    }

    public function getRefundedAmountInCents(): int
    {
        return self::convertToCents($this->refunded_amount);
    }

    public function getTax(): SummitTaxType
    {
        return $this->tax;
    }

}