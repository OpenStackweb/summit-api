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
        list($so_far, $sent_so_far) = $this->getOrderTaxRefundsSoFar();
        $cents = [];
        foreach ($this->ticket->getAppliedTaxes() as $applied_tax) {
            $tax = $applied_tax->getTax();
            $so_far[$tax->getId()] ??= [$tax, 0.0, 0];
            $so_far[$tax->getId()][1] += $applied_tax->applyTo($amount_2_refund, false);
            $cents[$tax->getId()] = 0;
        }
        $owed = 0;
        foreach ($so_far as $tax_id => list($tax, $tax_so_far, $refunded_so_far)) {
            $tax_owed = self::convertToCents($tax->round($tax_so_far));
            $owed += $tax_owed;
            if (isset($cents[$tax_id])) $cents[$tax_id] = max(0, $tax_owed - $refunded_so_far);
        }
        // A refund approved before refunds followed the charge sent the gateway
        // its total rounded once, which can be a cent more or less than its tax
        // refunds add up to, also on a tax this ticket does not carry. This
        // refund makes up for it, so that the order as a whole gives back the
        // tax it was charged.
        $off = max(0, $owed - $sent_so_far) - array_sum($cents);
        arsort($cents);
        foreach ($cents as $tax_id => $tax_cents) {
            $cents[$tax_id] = max(0, $tax_cents + $off);
            $off -= $cents[$tax_id] - $tax_cents;
        }
        foreach ($this->ticket->getAppliedTaxes() as $applied_tax) {
            $tax = $applied_tax->getTax();
            $this->refunded_taxes->add(new SummitTaxRefund($this, $tax, $cents[$tax->getId()] / 100));
        }
        return $this->refunded_taxes;
    }

    /**
     * What the order's other approved refunds add up to. By tax id: the tax,
     * the tax on the refunded ticket price before rounding, and the cents of
     * it given back. And the cents of tax they sent to the payment gateway.
     * The tax is worked out again from the refunded ticket price because the
     * tax refunds stored before refunds followed the charge are not rounded.
     * @return array
     */
    private function getOrderTaxRefundsSoFar():array{
        $so_far = [];
        $sent_so_far = 0;
        foreach ($this->ticket->getOrder()->getTickets() as $ticket) {
            foreach ($ticket->getRefundedRequests() as $request) {
                // approve() marks this request as approved before its taxes are calculated
                if ($request === $this) continue;
                if ($request->getStatus() !== ISummitRefundRequestConstants::ApprovedStatus) continue;
                // a refund approved before tax refunds were recorded has none: no tax to follow
                if ($request->getRefundedTaxes()->isEmpty()) continue;
                foreach ($ticket->getAppliedTaxes() as $applied_tax) {
                    $tax = $applied_tax->getTax();
                    $so_far[$tax->getId()] ??= [$tax, 0.0, 0];
                    $so_far[$tax->getId()][1] += $applied_tax->applyTo($request->getRefundedAmount(), false);
                }
                foreach ($request->getRefundedTaxes() as $tax_refund) {
                    $tax = $tax_refund->getTax();
                    $so_far[$tax->getId()] ??= [$tax, 0.0, 0];
                    $so_far[$tax->getId()][2] += $tax_refund->getRefundedAmountInCents();
                }
                // the gateway is sent the total of the request rounded once
                $sent_so_far += $request->getTotalRefundedAmountInCents() - $request->getRefundedAmountInCents();
            }
        }
        return [$so_far, $sent_so_far];
    }

}