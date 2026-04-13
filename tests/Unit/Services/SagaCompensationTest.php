<?php namespace Tests\Unit\Services;
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

use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use App\Services\Model\AbstractTask;
use App\Services\Model\ReserveOrderTask;
use App\Services\Model\Saga;
use libs\utils\ITransactionService;
use Mockery;
use models\main\Member;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitOrder;
use PHPUnit\Framework\TestCase;

/**
 * Class SagaCompensationTest
 *
 * Regression tests for the saga reorder introduced by the domain-authorized
 * promo code feature (ApplyPromoCodeTask moved after ReserveOrderTask). Two
 * concerns are exercised:
 *
 * 1. If any task downstream of ReserveOrderTask throws, Saga::abort() invokes
 *    undo() on previously-run tasks in reverse order. ReserveOrderTask::undo()
 *    must therefore remove the persisted order and its tickets.
 * 2. Tasks that have not yet run must not be undone.
 *
 * Uses Mockery on concrete classes; no Laravel, DB, or Redis required. The
 * actual dispatch of CreatedSummitRegistrationOrder after a successful
 * SummitOrderService::reserve() is covered by
 * OAuth2SummitPromoCodesApiTest/OAuth2SummitOrdersApiTest integration tests.
 *
 * @package Tests\Unit\Services
 */
class SagaCompensationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Minimal container so the Log/App facades the code under test touches
        // resolve to no-ops. No DB, no full Laravel bootstrap.
        $container = new \Illuminate\Container\Container();
        $container->instance('app', $container);
        $container->instance('log', new class {
            public function __call($name, $args) { /* silently swallow log calls */ }
        });
        \Illuminate\Support\Facades\Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\Facade::clearResolvedInstances();
        \Illuminate\Support\Facades\Facade::setFacadeApplication(null);
        Mockery::close();
        parent::tearDown();
    }

    /**
     * ReserveOrderTask::undo() with no order in formerState is a safe no-op
     * (run() may have thrown before persisting the order).
     */
    public function testUndoIsNoOpWhenOrderWasNotPersisted(): void
    {
        $tx_service = Mockery::mock(ITransactionService::class);
        // transaction() must NOT be called — nothing to compensate.
        $tx_service->shouldNotReceive('transaction');

        $task = $this->buildTask(
            $tx_service,
            Mockery::mock(Summit::class),
            Mockery::mock(Member::class)
        );

        // formerState deliberately missing the 'order' key
        $this->invokeUndo($task, []);

        // Assertion is implicit via Mockery expectations
        $this->addToAssertionCount(1);
    }

    /**
     * When ReserveOrderTask::run() persisted an order, undo() must:
     *  - detach each ticket from its attendee owner (so stale references don't linger)
     *  - remove the order from the summit
     *  - delete the order via the repository (cascade removes tickets via orphanRemoval)
     */
    public function testUndoDeletesOrderAndDetachesTicketsFromAttendees(): void
    {
        $attendee1 = Mockery::mock(SummitAttendee::class);
        $attendee2 = Mockery::mock(SummitAttendee::class);

        $ticket1 = Mockery::mock(SummitAttendeeTicket::class);
        $ticket1->shouldReceive('getOwner')->andReturn($attendee1);
        $ticket2 = Mockery::mock(SummitAttendeeTicket::class);
        $ticket2->shouldReceive('getOwner')->andReturn($attendee2);
        // Unassigned ticket: getOwner may return null, undo must not explode
        $ticket3 = Mockery::mock(SummitAttendeeTicket::class);
        $ticket3->shouldReceive('getOwner')->andReturn(null);

        $attendee1->shouldReceive('removeTicket')->once()->with($ticket1);
        $attendee2->shouldReceive('removeTicket')->once()->with($ticket2);

        $order = Mockery::mock(SummitOrder::class);
        $order->shouldReceive('getId')->andReturn(9001);
        $order->shouldReceive('getNumber')->andReturn('ORD-TEST-0001');
        $order->shouldReceive('getTickets')->andReturn([$ticket1, $ticket2, $ticket3]);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('removeOrder')->once()->with($order);

        $owner = Mockery::mock(Member::class);

        $order_repo = Mockery::mock(ISummitOrderRepository::class);
        $order_repo->shouldReceive('delete')->once()->with($order);

        // Bind the repo into the container so App::make() inside undo() resolves it.
        $container = \Illuminate\Support\Facades\Facade::getFacadeApplication();
        $container->instance(ISummitOrderRepository::class, $order_repo);

        $tx_service = Mockery::mock(ITransactionService::class);
        $tx_service->shouldReceive('transaction')->once()->andReturnUsing(function ($fn) {
            return $fn();
        });

        $task = $this->buildTask($tx_service, $summit, $owner);
        $this->invokeUndo($task, ['order' => $order]);

        $this->addToAssertionCount(1);
    }

    /**
     * Integration-ish: drive a real Saga whose last task throws and verify
     * that a preceding "ReserveOrder-like" task has its undo() invoked exactly
     * once, in reverse order.
     */
    public function testSagaAbortCallsUndoInReverseOrder(): void
    {
        $order_of_calls = [];

        $first  = new RecordingTask('first', $order_of_calls);
        $second = new RecordingTask('second', $order_of_calls);
        $failing = new class extends AbstractTask {
            public function run(array $formerState): array
            {
                throw new \RuntimeException('downstream failure');
            }
            public function undo() { /* never runs — it threw in run() */ }
        };

        $saga = Saga::start()
            ->addTask($first)
            ->addTask($second)
            ->addTask($failing);

        try {
            $saga->run();
            $this->fail('Expected saga to propagate the downstream exception');
        } catch (\RuntimeException $ex) {
            $this->assertSame('downstream failure', $ex->getMessage());
        }

        // run: first, second, (failing throws); undo: second, first
        $this->assertSame(
            ['run:first', 'run:second', 'undo:second', 'undo:first'],
            $order_of_calls
        );
    }

    /**
     * Construct a ReserveOrderTask with only the fields undo() needs. run() is
     * not exercised here, so most collaborators can be plain Mockery doubles.
     */
    private function buildTask(ITransactionService $tx, Summit $summit, Member $owner): ReserveOrderTask
    {
        $reflector = new \ReflectionClass(ReserveOrderTask::class);
        /** @var ReserveOrderTask $task */
        $task = $reflector->newInstanceWithoutConstructor();

        $this->setPrivate($task, 'tx_service', $tx);
        $this->setPrivate($task, 'summit', $summit);
        $this->setPrivate($task, 'owner', $owner);

        return $task;
    }

    private function setPrivate(object $instance, string $property, $value): void
    {
        $r = new \ReflectionClass($instance);
        $p = $r->getProperty($property);
        $p->setAccessible(true);
        $p->setValue($instance, $value);
    }

    private function invokeUndo(ReserveOrderTask $task, array $formerState): void
    {
        $this->setPrivate($task, 'formerState', $formerState);
        $task->undo();
    }
}

/**
 * Minimal AbstractTask implementation that records run/undo invocation order.
 * Declared at file scope (not inside the TestCase) so PHP can resolve the
 * AbstractTask parent at class-load time without coupling to test lifecycle.
 */
final class RecordingTask extends AbstractTask
{
    private $label;
    private $log;

    public function __construct(string $label, array &$log)
    {
        $this->label = $label;
        $this->log = &$log;
    }

    public function run(array $formerState): array
    {
        $this->log[] = 'run:' . $this->label;
        return $formerState;
    }

    public function undo()
    {
        $this->log[] = 'undo:' . $this->label;
    }
}
