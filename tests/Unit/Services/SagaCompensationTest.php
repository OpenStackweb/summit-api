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
use App\Services\Model\ApplyPromoCodeTask;
use App\Services\Model\ReserveOrderTask;
use App\Services\Model\Saga;
use App\Services\Utils\ILockManagerService;
use libs\utils\ITransactionService;
use models\exceptions\ValidationException;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\SummitRegistrationPromoCode;
use models\summit\SummitTicketType;
use Mockery;
use models\main\Member;
use models\summit\ISummitRepository;
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
        // Other tests in the suite may have resolved Log/App facades against a
        // full Laravel container; clear that cache so our minimal stub is used.
        \Illuminate\Support\Facades\Facade::clearResolvedInstances();
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
        $summit->shouldReceive('getId')->andReturn(77);
        $summit->shouldReceive('removeOrder')->once()->with($order);

        $owner = Mockery::mock(Member::class);

        // undo() reloads the order and the summit: the instances cached by run() are
        // detached once the failing task's root transaction cleared the EntityManager.
        $order_repo = Mockery::mock(ISummitOrderRepository::class);
        $order_repo->shouldReceive('getByIdExclusiveLock')->once()->with(9001)->andReturn($order);
        $order_repo->shouldReceive('delete')->once()->with($order);

        $summit_repo = Mockery::mock(ISummitRepository::class);
        $summit_repo->shouldReceive('getById')->once()->with(77)->andReturn($summit);

        // Bind the repos into the container so App::make() inside undo() resolves them.
        $container = \Illuminate\Support\Facades\Facade::getFacadeApplication();
        $container->instance(ISummitOrderRepository::class, $order_repo);
        $container->instance(ISummitRepository::class, $summit_repo);

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
     * A compensation that itself fails must neither mask the exception that aborted
     * the saga nor stop the remaining compensations (ticket stock, member quota)
     * from running.
     */
    public function testSagaAbortKeepsUndoingAndRethrowsOriginalWhenAnUndoFails(): void
    {
        $order_of_calls = [];

        $first = new RecordingTask('first', $order_of_calls);
        $broken_undo = new class extends AbstractTask {
            public function run(array $formerState): array
            {
                return $formerState;
            }
            public function undo()
            {
                throw new \LogicException('undo failure');
            }
        };
        $failing = new class extends AbstractTask {
            public function run(array $formerState): array
            {
                throw new \RuntimeException('downstream failure');
            }
            public function undo() { /* never runs — it threw in run() */ }
        };

        $saga = Saga::start()
            ->addTask($first)
            ->addTask($broken_undo)
            ->addTask($failing);

        try {
            $saga->run();
            $this->fail('Expected saga to propagate the downstream exception');
        } catch (\RuntimeException $ex) {
            $this->assertSame('downstream failure', $ex->getMessage());
        }

        $this->assertSame(['run:first', 'undo:first'], $order_of_calls);
    }

    /**
     * Saga::run() only marks a task as ran after run() returns, so when the second
     * promo code is rejected the saga never undoes this task. run() must release the
     * usage it already applied for the first code and rethrow the original exception.
     */
    public function testApplyPromoCodeReleasesAppliedCodesWhenALaterCodeIsRejected(): void
    {
        $rejection = new ValidationException('code B is not valid for the buyer');

        $code_a = $this->buildPromoCode(1, 'CODE_A');
        $code_a->shouldReceive('addUsage')->once()->with('buyer@test.com', 2);
        $code_a->shouldReceive('removeUsage')->once()->with(2, 'buyer@test.com');

        $code_b = $this->buildPromoCode(2, 'CODE_B');
        $code_b->shouldReceive('validate')->once()->andThrow($rejection);
        $code_b->shouldReceive('addUsage')->never();
        $code_b->shouldReceive('removeUsage')->never();

        $task = $this->buildApplyPromoCodeTask(['CODE_A' => $code_a, 'CODE_B' => $code_b]);

        try {
            $task->run($this->promoCodesFormerState());
            $this->fail('Expected the rejection of the second promo code to propagate');
        } catch (ValidationException $ex) {
            $this->assertSame($rejection, $ex);
        }

        // a later Saga::abort() (or any extra undo) must not release the usage twice
        $task->undo();
    }

    public function testApplyPromoCodeDoesNotReleaseAnythingWhenTheFirstCodeIsRejected(): void
    {
        $rejection = new ValidationException('code A is not valid for the buyer');

        $code_a = $this->buildPromoCode(1, 'CODE_A');
        $code_a->shouldReceive('validate')->once()->andThrow($rejection);
        $code_a->shouldReceive('addUsage')->never();
        $code_a->shouldReceive('removeUsage')->never();

        $code_b = $this->buildPromoCode(2, 'CODE_B');
        $code_b->shouldReceive('addUsage')->never();
        $code_b->shouldReceive('removeUsage')->never();

        $task = $this->buildApplyPromoCodeTask(['CODE_A' => $code_a, 'CODE_B' => $code_b]);

        try {
            $task->run($this->promoCodesFormerState());
            $this->fail('Expected the rejection of the first promo code to propagate');
        } catch (ValidationException $ex) {
            $this->assertSame($rejection, $ex);
        }
    }

    /**
     * A successful run leaves every usage applied; if a later saga task fails the
     * saga undoes this one, releasing each code exactly once however often undo() runs.
     */
    public function testApplyPromoCodeUndoAfterSuccessfulRunReleasesEachCodeOnce(): void
    {
        $code_a = $this->buildPromoCode(1, 'CODE_A');
        $code_a->shouldReceive('addUsage')->once()->with('buyer@test.com', 2);
        $code_a->shouldReceive('removeUsage')->once()->with(2, 'buyer@test.com');

        $code_b = $this->buildPromoCode(2, 'CODE_B');
        $code_b->shouldReceive('addUsage')->once()->with('buyer@test.com', 1);
        $code_b->shouldReceive('removeUsage')->once()->with(1, 'buyer@test.com');

        $task = $this->buildApplyPromoCodeTask(['CODE_A' => $code_a, 'CODE_B' => $code_b]);

        $task->run($this->promoCodesFormerState());
        $task->undo();
        $task->undo();

        // the once() expectations above are verified on Mockery::close()
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    /**
     * Without a successful run there is nothing to release: an undo() must never
     * touch a usage that belongs to another order of the same buyer.
     */
    public function testApplyPromoCodeUndoWithoutRunReleasesNothing(): void
    {
        $code_a = $this->buildPromoCode(1, 'CODE_A');
        $code_a->shouldReceive('removeUsage')->never();

        $task = $this->buildApplyPromoCodeTask(['CODE_A' => $code_a]);
        $this->setPrivate($task, 'formerState', $this->promoCodesFormerState());

        $task->undo();

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    /**
     * If releasing the first code fails while compensating, the caller still gets
     * the exception that rejected the reservation, not the compensation failure.
     */
    public function testApplyPromoCodeFailingReleaseDoesNotMaskTheRejection(): void
    {
        $rejection = new ValidationException('code B is not valid for the buyer');

        $code_a = $this->buildPromoCode(1, 'CODE_A');
        $code_a->shouldReceive('addUsage')->once();
        $code_a->shouldReceive('removeUsage')->once()->andThrow(new \RuntimeException('release failed'));

        $code_b = $this->buildPromoCode(2, 'CODE_B');
        $code_b->shouldReceive('validate')->once()->andThrow($rejection);

        $task = $this->buildApplyPromoCodeTask(['CODE_A' => $code_a, 'CODE_B' => $code_b]);

        try {
            $task->run($this->promoCodesFormerState());
            $this->fail('Expected the rejection of the second promo code to propagate');
        } catch (ValidationException $ex) {
            $this->assertSame($rejection, $ex);
        }
    }

    private function promoCodesFormerState(): array
    {
        return [
            'promo_codes_usage' => [
                'CODE_A' => ['qty' => 2, 'types' => [10]],
                'CODE_B' => ['qty' => 1, 'types' => [10]],
            ],
        ];
    }

    /**
     * @return \Mockery\MockInterface|SummitRegistrationPromoCode
     */
    private function buildPromoCode(int $id, string $code)
    {
        $promo_code = Mockery::mock(SummitRegistrationPromoCode::class);
        $promo_code->shouldReceive('getId')->andReturn($id);
        $promo_code->shouldReceive('getCode')->andReturn($code);
        $promo_code->shouldReceive('getSummitId')->andReturn(77);
        $promo_code->shouldReceive('validate')->andReturnNull()->byDefault();
        $promo_code->shouldReceive('canBeAppliedTo')->andReturn(true);
        return $promo_code;
    }

    /**
     * @param array<string, SummitRegistrationPromoCode> $promo_codes by code value
     */
    private function buildApplyPromoCodeTask(array $promo_codes): ApplyPromoCodeTask
    {
        $ticket_type = Mockery::mock(SummitTicketType::class);
        $ticket_type->shouldReceive('getName')->andReturn('Ticket');

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(77);
        $summit->shouldReceive('getTicketTypeById')->andReturn($ticket_type);

        // run() re attaches the summit through the repository
        $summit_repo = Mockery::mock(ISummitRepository::class);
        $summit_repo->shouldReceive('getById')->with(77)->andReturn($summit);
        $container = \Illuminate\Support\Facades\Facade::getFacadeApplication();
        $container->instance(ISummitRepository::class, $summit_repo);

        $promo_code_repo = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $promo_code_repo->shouldReceive('getByValueExclusiveLock')
            ->andReturnUsing(function ($summit, $code) use ($promo_codes) {
                return $promo_codes[$code] ?? null;
            });

        $tx_service = Mockery::mock(ITransactionService::class);
        $tx_service->shouldReceive('transaction')->andReturnUsing(function ($fn) {
            return $fn();
        });

        $lock_service = Mockery::mock(ILockManagerService::class);
        $lock_service->shouldReceive('lock')->andReturnUsing(function ($name, $fn) {
            return $fn();
        });

        return new ApplyPromoCodeTask(
            $summit,
            ['owner_email' => 'buyer@test.com', 'owner_company' => 'Acme'],
            null,
            $promo_code_repo,
            $tx_service,
            $lock_service
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
