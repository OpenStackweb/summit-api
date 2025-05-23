<?php namespace models\summit;
/**
 * Copyright 2021 OpenStack Foundation
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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;

/**
 * Class SummitRefundRequest
 * @package models\summit
 */
#[ORM\Table(name: 'SummitRefundRequest')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitRefundRequestRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'ClassName', type: 'string')]
#[ORM\DiscriminatorMap(['SummitRefundRequest' => 'SummitRefundRequest', 'SummitAttendeeTicketRefundRequest' => 'SummitAttendeeTicketRefundRequest'])]
class SummitRefundRequest extends SilverstripeBaseModel
{
    const ClassName = 'SummitRefundRequest';

    use One2ManyPropertyTrait;

    use FinancialTrait;

    protected $getIdMappings = [
        'getRequestedById' => 'requested_by',
        'getActionById' => 'action_by',
    ];

    protected $hasPropertyMappings = [
        'hasRequestedBy' => 'requested_by',
        'hasActionBy' => 'action_by',
    ];

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'RequestedByID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class, fetch: 'EXTRA_LAZY')]
    protected $requested_by = null;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'ActionByID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class, fetch: 'EXTRA_LAZY')]
    protected $action_by = null;

    /**
     * @var float
     */
    #[ORM\Column(name: 'RefundedAmount', type: 'float')]
    protected $refunded_amount;

    /**
     * @var SummitTaxRefund[]|Collection
     */
    #[ORM\OneToMany(targetEntity: \SummitTaxRefund::class, mappedBy: 'refund_request', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected $refunded_taxes;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'ActionDate', type: 'datetime')]
    protected $action_date;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Status', type: 'string')]
    protected $status;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Notes', type: 'string')]
    protected $notes;

    /**
     * @var string
     */
    #[ORM\Column(name: 'PaymentGatewayResult', type: 'string')]
    protected $payment_gateway_result;

    /**
     * SummitRefundRequest constructor.
     * @param Member|null $requested_by
     */
    public function __construct(?Member $requested_by = null)
    {
        parent::__construct();
        $this->requested_by = $requested_by;
        $this->status  = ISummitRefundRequestConstants::RefundRequestedStatus;
        $this->refunded_amount = 0.0;
        $this->refunded_taxes = new ArrayCollection();
    }

    /**
     * @param Member $rejectedBy
     * @param string|null $notes
     * @throws \Exception
     */
    public function reject(Member $rejectedBy, ?string $notes = null):void{
        $this->status = ISummitRefundRequestConstants::RejectedStatus;
        $this->notes = $notes;
        $this->action_by = $rejectedBy;
        $this->action_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @param Member|null $approvedBy
     * @param float $amount_2_refund
     * @param string|null $paymentGatewayResult
     * @param string|null $notes
     * @throws \Exception
     */
    public function approve(?Member $approvedBy, float $amount_2_refund, ?string $paymentGatewayResult= null, ?string $notes = null){
        $this->status = ISummitRefundRequestConstants::ApprovedStatus;
        $this->action_by = $approvedBy;
        $this->action_date = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->refunded_amount = $amount_2_refund;
        $this->refunded_taxes = $this->calculateTaxesRefundedAmountFrom($amount_2_refund);
        $this->setPaymentGatewayResult($paymentGatewayResult);
        $this->notes = $notes;
    }

    /**
     * @return Member
     */
    public function getRequestedBy(): ?Member
    {
        return $this->requested_by;
    }

    /**
     * @param Member $requested_by
     */
    public function setRequestedBy(Member $requested_by): void
    {
        $this->requested_by = $requested_by;
    }

    /**
     * @return Member
     */
    public function getActionBy(): ?Member
    {
        return $this->action_by;
    }

    /**
     * @param Member $action_by
     */
    public function setActionBy(Member $action_by): void
    {
        $this->action_by = $action_by;
    }

    /**
     * @return float
     */
    public function getRefundedAmount(): float
    {
        return $this->refunded_amount;
    }

    /**
     * @return int
     */
    public function getRefundedAmountInCents(): int
    {
        return self::convertToCents($this->refunded_amount);
    }

    /**
     * @return \DateTime
     */
    public function getActionDate(): ?\DateTime
    {
        return $this->action_date;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @return string
     */
    public function getPaymentGatewayResult(): ?string
    {
        return $this->payment_gateway_result;
    }

    public function setPaymentGatewayResult(?string $payment_gateway_result):void{
        $this->payment_gateway_result = $payment_gateway_result;
    }

    /**
     * @param float $amount_2_refund
     * @return Collection
     */
    protected function calculateTaxesRefundedAmountFrom(float $amount_2_refund):Collection {
        return $this->refunded_taxes;
    }

    /*
     * Total amount refunded Ticket Price + Tax/Fee price
     *  @return float
     */
    public function getTotalRefundedAmount(): float {
        return $this->refunded_amount + $this->getTaxesRefundedAmount();
    }

    /*
     * Total amount refunded Ticket Price + Tax/Fee price
     *  @return int
     */
    public function getTotalRefundedAmountInCents(): int {
        return self::convertToCents($this->getTotalRefundedAmount());
    }

    public function getTaxesRefundedAmount():float{
        $taxes_refund_amount = 0.0;
        foreach ($this->refunded_taxes as $tax_refund)
            $taxes_refund_amount += $tax_refund->getRefundedAmount();
        return $taxes_refund_amount;
    }

    /**
     * @return int
     */
    public function getTaxesRefundedAmountInCents(): int
    {
        return self::convertToCents($this->getTaxesRefundedAmount());
    }

    /**
     * @return ArrayCollection|Collection|SummitTaxRefund[]
     */
    public function getRefundedTaxes()
    {
        return $this->refunded_taxes;
    }


}