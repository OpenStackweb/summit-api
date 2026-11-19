<?php namespace Tests\Unit\Entities;
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

use App\Models\Utils\BaseEntity;
use Illuminate\Support\Facades\Log;
use libs\utils\JsonUtils;
use models\summit\Summit;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitOrder;
use models\summit\SummitRefundRequest;
use models\summit\SummitTaxRefund;
use models\summit\SummitTaxType;
use models\summit\SummitTicketType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * CONTRACT: an order is charged its net price plus each tax rounded to cents
 * once, over all its tickets (SummitOrder::getFinalAmount). Refunds give that
 * same money back: when every ticket is refunded in full, what was sent to the
 * payment gateway adds up to what was charged, never a cent less or more, and
 * a client reads the same refunded tax whether it adds up the refund requests
 * or subtracts the order's totals.
 *
 * Entities are built in memory: no database, no application container.
 */
final class SummitOrderRefundRoundingTest extends TestCase
{
    private static int $next_id = 1;

    protected function setUp(): void
    {
        parent::setUp();
        // the entities log through the facade
        Log::swap(new NullLogger());
    }

    protected function tearDown(): void
    {
        Log::clearResolvedInstances();
        parent::tearDown();
    }

    private static function withId(BaseEntity $entity): BaseEntity
    {
        // SummitOrder groups taxes by id, so unsaved entities need one
        $id = new \ReflectionProperty(BaseEntity::class, 'id');
        $id->setValue($entity, self::$next_id++);
        return $entity;
    }

    /**
     * @param float $price ticket price
     * @param int $qty tickets on the order
     * @param float[] $rates one tax per rate, in percent
     * @param float[][] $rates_of_ticket by ticket position, the rates of the
     * taxes a ticket carries when it does not carry all of them: a tax can be
     * limited to some ticket types (SummitTaxType::mustApplyTo)
     */
    private static function buildOrder(float $price, int $qty, array $rates, array $rates_of_ticket = []): SummitOrder
    {
        // a ticket type asks its summit for the default badge type, and a
        // ticket takes its summit from the order
        $summit = new Summit();

        $ticket_type = self::withId(new SummitTicketType());
        $ticket_type->setSummit($summit);
        $ticket_type->setName('General Admission');
        $ticket_type->setCost($price);

        $taxes = [];
        foreach ($rates as $rate) {
            $tax = self::withId(new SummitTaxType());
            $tax->setName(sprintf('%s%% tax', $rate));
            $tax->setRate($rate);
            $taxes[] = $tax;
        }

        $order = self::withId(new SummitOrder());
        $order->setSummit($summit);
        for ($i = 0; $i < $qty; $i++) {
            $ticket = self::withId(new SummitAttendeeTicket());
            $ticket->setTicketType($ticket_type);
            $ticket->applyTaxes(array_filter(
                $taxes, fn(SummitTaxType $tax) => in_array($tax->getRate(), $rates_of_ticket[$i] ?? $rates)
            ));
            $order->addTicket($ticket);
        }
        return $order;
    }

    /**
     * Refunds each ticket in the given parts and returns what each refund
     * sends to the payment gateway, in cents: StripeApi::refundPayment converts
     * SummitRefundRequest::getTotalRefundedAmount the same way.
     *
     * @param float[] $parts ticket price parts refunded on every ticket, in order
     * @return int[]
     */
    private static function refundEveryTicket(SummitOrder $order, array $parts): array
    {
        $sent = [];
        foreach ($order->getTickets() as $ticket) {
            foreach ($parts as $part) {
                $request = $ticket->refund(null, $part, null, null, false);
                $sent[] = $request->getTotalRefundedAmountInCents();
            }
        }
        return $sent;
    }

    public static function fullyRefundedOrders(): array
    {
        return [
            // 1.938 + 0.255 of tax: charged 1.94 + 0.26
            'one ticket, two taxes' => [10.20, 1, [19.0, 2.5], [10.20], 1240],
            // 1.9038 of tax per ticket: charged round(5.7114) = 5.71
            'three tickets, one tax, tax fractions under half a cent' => [10.02, 3, [19.0], [10.02], 3577],
            // 1.9076 of tax per ticket: charged round(5.7228) = 5.72
            'three tickets, one tax, tax fractions over half a cent' => [10.04, 3, [19.0], [10.04], 3584],
            // charged round(3.876) + round(0.51) = 3.88 + 0.51
            'two tickets, two taxes' => [10.20, 2, [19.0, 2.5], [10.20], 2479],
            // the same ticket refunded in three parts
            'one ticket, two taxes, refunded in parts' => [10.20, 1, [19.0, 2.5], [4.0, 4.0, 2.2], 1240],
            // whole cents all the way: nothing to round
            'one ticket, one tax, no fractions' => [1.00, 1, [19.0], [1.00], 119],
            // 1.938 of the first tax per ticket and 0.255 of the second on the
            // first ticket only: charged 3.88 + 0.26
            'two tickets, the second without the second tax' => [10.20, 2, [19.0, 2.5], [10.20], 2454, [1 => [19.0]]],
        ];
    }

    #[DataProvider('fullyRefundedOrders')]
    public function testFullRefundSendsBackExactlyWhatWasCharged(
        float $price, int $qty, array $rates, array $parts, int $charged_cents, array $rates_of_ticket = []
    ): void
    {
        $order = self::buildOrder($price, $qty, $rates, $rates_of_ticket);
        // what StripeApi::generatePayment charges
        $this->assertSame($charged_cents, $order->getFinalAmountInCents(), 'charged amount');

        $sent = self::refundEveryTicket($order, $parts);

        $this->assertSame($charged_cents, array_sum($sent), 'sent to the gateway: ' . implode(' + ', $sent));
    }

    #[DataProvider('fullyRefundedOrders')]
    public function testRefundsNeverGoOverTheCharge(
        float $price, int $qty, array $rates, array $parts, int $charged_cents, array $rates_of_ticket = []
    ): void
    {
        $order = self::buildOrder($price, $qty, $rates, $rates_of_ticket);
        $running = 0;
        foreach ($order->getTickets() as $ticket) {
            foreach ($parts as $part) {
                $running += $ticket->refund(null, $part, null, null, false)->getTotalRefundedAmountInCents();
                $this->assertLessThanOrEqual($charged_cents, $running);
            }
        }
    }

    #[DataProvider('fullyRefundedOrders')]
    public function testRefundedTaxReadsTheSameEitherWayAClientAddsItUp(
        float $price, int $qty, array $rates, array $parts, int $charged_cents, array $rates_of_ticket = []
    ): void
    {
        $order = self::buildOrder($price, $qty, $rates, $rates_of_ticket);
        self::refundEveryTicket($order, $parts);

        // the order's own totals, as the API serializes them (json_money)
        $from_order_totals =
            self::cents(JsonUtils::toJsonMoney($order->getTotalRefundedAmount())) -
            self::cents(JsonUtils::toJsonMoney($order->getRefundedAmount()));

        // each tax added up over the refund requests, then rounded to cents
        $by_tax = [];
        foreach ($order->getTickets() as $ticket) {
            foreach ($ticket->getRefundedRequests() as $request) {
                foreach ($request->getRefundedTaxes() as $tax_refund) {
                    $tax_id = $tax_refund->getTax()->getId();
                    $by_tax[$tax_id] = ($by_tax[$tax_id] ?? 0.0) + $tax_refund->getRefundedAmount();
                }
            }
        }
        $from_refund_requests = array_sum(array_map(
            fn(float $amount) => self::cents(JsonUtils::toJsonMoney($amount)),
            $by_tax
        ));

        $this->assertSame($from_refund_requests, $from_order_totals);
        // and it is the tax the order was charged
        $this->assertSame($order->getTaxesAmountInCents(), $from_order_totals);
    }

    public static function ordersPartlyRefundedBeforeThisChange(): array
    {
        return [
            // 1.9228 + 0.253 of tax per ticket: charged 3.85 + 0.51. The old
            // code sent 10.12 + 2.1758 as 12.30, a cent more than its two
            // taxes rounded one by one
            'two tickets, two taxes, the old refund a cent over its taxes' => [10.12, 2, [19.0, 2.5], 1, 2460],
            // 9.025 of tax per ticket, sent as 9.03 by the old code: charged round(36.10)
            'four tickets, one tax, half a cent of tax per ticket' => [47.50, 4, [19.0], 1, 22610],
            // 1.938 + 0.255 of tax per ticket: charged 3.88 + 0.51. The old
            // code sent 10.20 + 2.193 as 12.39
            'two tickets, two taxes, the old refund a cent short' => [10.20, 2, [19.0, 2.5], 1, 2479],
            // 1.9228 of the first tax per ticket and 0.253 of the second on the
            // first ticket only: charged 3.85 + 0.25. The old code sent
            // 10.12 + 2.1758 as 12.30, and the ticket left to refund has no
            // second tax to take that cent from
            'two tickets, the second without the second tax, the old refund a cent over its taxes' => [10.12, 2, [19.0, 2.5], 1, 2434, [1 => [19.0]]],
        ];
    }

    #[DataProvider('ordersPartlyRefundedBeforeThisChange')]
    public function testOrderPartlyRefundedBeforeThisChangeEndsOnTheCharge(
        float $price, int $qty, array $rates, int $refunded_before, int $charged_cents, array $rates_of_ticket = []
    ): void
    {
        $order = self::buildOrder($price, $qty, $rates, $rates_of_ticket);
        $this->assertSame($charged_cents, $order->getFinalAmountInCents(), 'charged amount');

        $sent = [];
        foreach ($order->getTickets()->toArray() as $i => $ticket) {
            $request = $ticket->refund(null, $price, null, null, false);
            if ($i < $refunded_before) self::storeTaxRefundsAsBeforeThisChange($ticket, $request);
            $sent[] = $request->getTotalRefundedAmountInCents();
        }

        $this->assertSame($charged_cents, array_sum($sent), 'sent to the gateway: ' . implode(' + ', $sent));
    }

    /**
     * Before this change a refund stored each tax on the refunded ticket price
     * as it came out, not rounded to cents, and the gateway was sent the total
     * of the request rounded once.
     */
    private static function storeTaxRefundsAsBeforeThisChange(
        SummitAttendeeTicket $ticket, SummitRefundRequest $request
    ): void
    {
        $amount = new \ReflectionProperty(SummitTaxRefund::class, 'refunded_amount');
        foreach ($request->getRefundedTaxes() as $tax_refund) {
            foreach ($ticket->getAppliedTaxes() as $applied_tax) {
                if ($applied_tax->getTax() === $tax_refund->getTax())
                    $amount->setValue($tax_refund, $applied_tax->applyTo($request->getRefundedAmount(), false));
            }
        }
    }

    /**
     * Tax refunds are on record since the SummitTaxRefund table was added
     * (migration Version20240226193210). A refund approved before has none, so
     * it is not part of the running total: a later refund on its order gives
     * back the tax on its own ticket price and nothing for the earlier one.
     */
    public function testRefundWithoutTaxRefundsOnRecordIsLeftOutOfTheRunningTotal(): void
    {
        $order = self::buildOrder(10.00, 2, [19.0]);
        [$first, $second] = $order->getTickets()->toArray();
        $first->refund(null, 10.00, null, null, false)->getRefundedTaxes()->clear();

        $request = $second->refund(null, 10.00, null, null, false);

        $this->assertSame(1190, $request->getTotalRefundedAmountInCents());
    }

    private static function cents(float $amount): int
    {
        return intval(round($amount * 100));
    }
}
