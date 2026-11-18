<?php namespace Tests;
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

use App\Jobs\ProcessTicketRefundRequest;
use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use App\Services\Model\ISummitOrderService;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use libs\utils\ITransactionService;
use models\summit\ISummitRefundRequestConstants;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitAttendeeTicketRefundRequest;
use models\summit\SummitRefundPolicyType;

/**
 * CONTRACT: refunds of the same order are approved one at a time, and each one
 * works out its taxes from the refunds approved before it, even when another
 * process committed them after this process loaded the order. A queue worker
 * keeps its entity manager from one job to the next, so it can hold the
 * order's tickets as they were before that commit.
 *
 * Fixture: one order with three 100.00 tickets taxed at 10.5% and 1.5%.
 * A refund of 10.04 carries 1.0542 of the first tax. On its own it gives back
 * 1.05; as the second refund of the order it gives back
 * round(2.1084) - 1.05 = 1.06, so the order follows what it was charged.
 */
final class SummitOrderRefundSerializationTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    use InsertOrdersTestData;

    private const Amount = 10.04;

    // MySQL: lock(s) could not be acquired immediately and NOWAIT is set
    private const LockNotAvailable = 3572;

    /**
     * @var int
     */
    private $order_id;

    /**
     * @var SummitAttendeeTicket[]
     */
    private $tickets;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
        self::InsertOrdersTestData();

        // the fixture orders have one ticket each: give the first one two more
        $order = self::$summit_orders[0];
        for ($i = 0; $i < 2; $i++) {
            $ticket = new SummitAttendeeTicket();
            $ticket->setOrder($order);
            $ticket->setTicketType(self::$default_ticket_type);
            $ticket->generateNumber();
            self::$default_ticket_type->sell(1);
            $ticket->generateHash();
            $ticket->generateQRCode();
            $ticket->applyTaxes(self::$summit->getTaxTypes()->toArray());
            $order->addTicket($ticket);
            $ticket->setPaid();
        }
        self::$em->persist(self::$summit);
        self::$em->flush();

        $this->order_id = $order->getId();
        $this->tickets = $order->getTickets()->toArray();

        // what a long running worker holds: the tickets with their refund requests loaded, none yet
        foreach ($this->tickets as $ticket)
            $this->assertCount(0, $ticket->getRefundedRequests()->toArray());

        // refunds queue emails and the payment gateway job
        Bus::fake();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    /**
     * Another worker approves a refund. It goes through its own entity manager,
     * so the one under test knows nothing about it.
     */
    private function refundFromAnotherWorker(SummitAttendeeTicket $ticket, float $amount): void
    {
        $other = new EntityManager(self::$em->getConnection(), self::$em->getConfiguration());
        $other->find(SummitAttendeeTicket::class, $ticket->getId())->refund(null, $amount, null, null, false);
        $other->flush();

        $this->assertCount(0, $ticket->getRefundedRequests(), 'the entity manager under test must not know the refund');
    }

    /**
     * @return int[] cents given back by the ticket's approved refunds, by tax name
     */
    private function approvedTaxRefundsInCents(SummitAttendeeTicket $ticket): array
    {
        $cents = [];
        foreach ($ticket->getRefundedRequests() as $request) {
            if ($request->getStatus() !== ISummitRefundRequestConstants::ApprovedStatus) continue;
            foreach ($request->getRefundedTaxes() as $tax_refund) {
                $name = $tax_refund->getTax()->getName();
                $cents[$name] = ($cents[$name] ?? 0) + $tax_refund->getRefundedAmountInCents();
            }
        }
        ksort($cents);
        return $cents;
    }

    public function testLockingTheOrderReadsAgainTheRefundsOfItsTickets(): void
    {
        list($first) = $this->tickets;
        $this->refundFromAnotherWorker($first, self::Amount);

        App::make(ITransactionService::class)->transaction(function () {
            App::make(ISummitOrderRepository::class)->getByIdExclusiveLockRefreshingRefunds($this->order_id);
        });

        $this->assertSame(['TAX 1' => 105, 'TAX 2' => 15], $this->approvedTaxRefundsInCents($first));
    }

    public function testTheOrderStaysLockedUntilTheTransactionEnds(): void
    {
        $other = DriverManager::getConnection(self::$em->getConnection()->getParams());
        $lock_order = function () use ($other) {
            // autocommit: the lock is released as soon as the statement ends
            return $other->fetchOne('SELECT ID FROM SummitOrder WHERE ID = ? FOR UPDATE NOWAIT', [$this->order_id]);
        };

        $refused_with = null;
        App::make(ITransactionService::class)->transaction(function () use ($lock_order, &$refused_with) {
            App::make(ISummitOrderRepository::class)->getByIdExclusiveLockRefreshingRefunds($this->order_id);
            try {
                $lock_order();
            } catch (DriverException $ex) {
                $refused_with = $ex->getCode();
            }
        });

        $this->assertSame(self::LockNotAvailable, $refused_with, 'another connection must not get the order while it is locked');
        $this->assertEquals($this->order_id, $lock_order(), 'the order is free again once the transaction ends');
        $other->close();
    }

    public function testAdminRefundCountsARefundAnotherWorkerCommitted(): void
    {
        list($first, $second) = $this->tickets;
        $this->refundFromAnotherWorker($first, self::Amount);

        $ticket = App::make(ISummitOrderService::class)
            ->refundTicket(self::$summit, self::$member, $second->getId(), self::Amount, null);

        $this->assertSame(['TAX 1' => 106, 'TAX 2' => 15], $this->approvedTaxRefundsInCents($ticket));
    }

    public function testRefundPolicyJobCountsARefundAnotherWorkerCommitted(): void
    {
        list($first, $second) = $this->tickets;

        // a policy that gives back 10.04% of a 100.00 ticket, and the buyer's pending request
        $policy = new SummitRefundPolicyType();
        $policy->setName('REFUND POLICY');
        $policy->setUntilXDaysBeforeEventStarts(0);
        $policy->setRefundRate(self::Amount);
        self::$summit->addRefundPolicy($policy);
        $pending = new SummitAttendeeTicketRefundRequest(self::$member);
        $pending->setTicket($second);
        $second->getRefundedRequests()->add($pending);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $this->refundFromAnotherWorker($first, self::Amount);

        App::call([new ProcessTicketRefundRequest($second->getId(), 10), 'handle']);

        $this->assertSame(['TAX 1' => 106, 'TAX 2' => 15], $this->approvedTaxRefundsInCents($second));
    }
}
