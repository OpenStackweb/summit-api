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
use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;

/**
 * Class SummitRefundRequest
 * @ORM\Entity
 * @ORM\Table(name="SummitRefundRequest")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="ClassName", type="string")
 * @ORM\DiscriminatorMap({
 *     "SummitRefundRequest" = "SummitRefundRequest",
 *     "SummitAttendeeTicketRefundRequest" = "SummitAttendeeTicketRefundRequest"
 * })
 * @package models\summit
 */
class SummitRefundRequest extends SilverstripeBaseModel
{
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
     * @ORM\ManyToOne(targetEntity="models\main\Member", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="RequestedByID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Member
     */
    protected $requested_by = null;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="ActionByID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Member
     */
    protected $action_by = null;

    /**
     * @ORM\Column(name="RefundedAmount", type="float")
     * @var float
     */
    protected $refunded_amount;

    /**
     * @ORM\Column(name="ActionDate", type="datetime")
     * @var \DateTime
     */
    protected $action_date;

    /**
     * @ORM\Column(name="Status", type="string")
     * @var string
     */
    protected $status;

    /**
     * @ORM\Column(name="Notes", type="string")
     * @var string
     */
    protected $notes;

    /**
     * @ORM\Column(name="PaymentGatewayResult", type="string")
     * @var string
     */
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
     * @param float $amount
     * @param string|null $paymentGatewayResult
     * @param string|null $notes
     * @throws \Exception
     */
    public function approve(?Member $approvedBy, float $amount, ?string $paymentGatewayResult= null, ?string $notes = null){
        $this->status = ISummitRefundRequestConstants::ApprovedStatus;
        $this->action_by = $approvedBy;
        $this->action_date = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->refunded_amount = $amount;
        $this->notes = $notes;
        $this->payment_gateway_result = $paymentGatewayResult;
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

}