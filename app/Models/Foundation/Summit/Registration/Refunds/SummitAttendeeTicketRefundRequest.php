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
use Doctrine\ORM\Mapping AS ORM;
use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use Doctrine\Common\Collections\Collection;
/**
 * Class SummitAttendeeTicketRefundRequest
 * @package models\summit
 */
#[ORM\Table(name: 'SummitAttendeeTicketRefundRequest')]
#[ORM\Entity]
class SummitAttendeeTicketRefundRequest extends SummitRefundRequest
{
    const ClassName = 'SummitAttendeeTicketRefundRequest';

    /**
     * @var SummitAttendeeTicket
     */
    #[ORM\JoinColumn(name: 'TicketID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \SummitAttendeeTicket::class, inversedBy: 'refund_requests', fetch: 'EXTRA_LAZY')]
    protected $ticket;

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getRequestedById' => 'requested_by',
        'getActionById' => 'action_by',
        'getTicketId' => 'ticket',
    ];

    protected $hasPropertyMappings = [
        'hasRequestedBy' => 'requested_by',
        'hasActionBy' => 'action_by',
        'hasTicket' => 'ticket',
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

    public function clearTicket():void{
        $this->ticket = null;
    }

    public function __construct(?Member $requested_by = null)
    {
        parent::__construct($requested_by);
    }

    /**
     * @param float $amount_2_refund
     * @return Collection
     */
    protected function calculateTaxesRefundedAmountFrom(float $amount_2_refund):Collection{
        $this->refunded_taxes->clear();
        if ($this->ticket->getAppliedTaxes()->isEmpty()) return $this->refunded_taxes;
        // The order is charged each tax rounded to cents once, over all its
        // tickets (SummitOrder::getFinalAmount). A refund follows that same
        // running total: the tax on everything refunded so far, rounded the
        // same way, less the tax already given back. Refunding the whole
        // order then gives back the tax that was charged, to the cent.
        $so_far = $this->getOrderTaxRefundsSoFar();
        foreach ($this->ticket->getAppliedTaxes() as $applied_tax) {
             $tax = $applied_tax->getTax();
             list($tax_so_far, $refunded_so_far) = $so_far[$tax->getId()] ?? [0.0, 0.0];
             $tax_so_far += $applied_tax->applyTo($amount_2_refund, false);
             $tax_refund = new SummitTaxRefund
             (
                 $this,
                 $tax,
                 max(0.0, $tax->round($tax->round($tax_so_far) - $refunded_so_far))
             );
             $this->refunded_taxes->add($tax_refund);
        }
        return $this->refunded_taxes;
    }

    /**
     * What the order's other approved refunds add up to, by tax id: the tax on
     * the refunded ticket price before rounding, and the tax given back.
     * The tax is worked out again from the refunded ticket price because the
     * tax refunds stored before refunds followed the charge are not rounded.
     * @return float[][]
     */
    private function getOrderTaxRefundsSoFar():array{
        $so_far = [];
        foreach ($this->ticket->getOrder()->getTickets() as $ticket) {
            foreach ($ticket->getRefundedRequests() as $request) {
                // approve() marks this request as approved before its taxes are calculated
                if ($request === $this) continue;
                if ($request->getStatus() !== ISummitRefundRequestConstants::ApprovedStatus) continue;
                foreach ($ticket->getAppliedTaxes() as $applied_tax) {
                    $tax_id = $applied_tax->getTax()->getId();
                    $so_far[$tax_id] ??= [0.0, 0.0];
                    $so_far[$tax_id][0] += $applied_tax->applyTo($request->getRefundedAmount(), false);
                }
                foreach ($request->getRefundedTaxes() as $tax_refund) {
                    $tax_id = $tax_refund->getTax()->getId();
                    $so_far[$tax_id] ??= [0.0, 0.0];
                    $so_far[$tax_id][1] += $tax_refund->getRefundedAmount();
                }
            }
        }
        return $so_far;
    }

}