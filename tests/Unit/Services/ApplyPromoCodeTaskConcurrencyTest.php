<?php namespace Tests\Unit\Services;
/**
 * Copyright 2026 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 **/

use App\Services\Model\ApplyPromoCodeTask;
use App\Services\Utils\ILockManagerService;
use libs\utils\ITransactionService;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\IDomainAuthorizedPromoCode;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
use models\summit\SummitTicketType;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Simulates concurrent QuantityPerAccount enforcement in ApplyPromoCodeTask
 * by controlling what getTicketCountByMemberAndPromoCode returns for each call.
 *
 * The race condition: two ReserveOrderTask executions commit tickets before
 * either ApplyPromoCodeTask runs, so both tasks see the combined count and
 * both reject — even though individually each was valid. These tests document
 * that behavior and verify the serialized (correct) path.
 *
 * No real DB or pcntl_fork needed — the race is deterministic once we control
 * the mock repository return values for each task invocation.
 *
 * See PR #525 for full context on the TOCTOU risk.
 */
class ApplyPromoCodeTaskConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Facade::clearResolvedInstances();
        $container = new \Illuminate\Container\Container();
        $container->instance('app', $container);
        $container->instance('log', new class {
            public function __call($name, $args) { /* swallow */ }
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
     * Serialized execution (FOR UPDATE lock works correctly):
     *
     * - Limit = 1, each request buys 1 ticket.
     * - Task A runs first: count = 1 (own ticket only, B hasn't committed yet).
     *   Guard: 1 > 1 = false → passes, calls addUsage.
     * - Task B runs second: count = 2 (A's committed ticket + B's own).
     *   Guard: 2 > 1 = true → rejects.
     *
     * This is the correct behavior under serialization.
     */
    public function testSerializedExecution_FirstSucceeds_SecondRejects(): void
    {
        $promo_code_value = 'DOMAIN-CODE-1';
        $ticket_type_id = 42;
        $quantityPerAccountLimit = 1;

        $ticket_type = Mockery::mock(SummitTicketType::class);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1);
        $summit->shouldReceive('getTicketTypeById')->with($ticket_type_id)->andReturn($ticket_type);

        $promo_code = Mockery::mock(SummitRegistrationPromoCode::class, IDomainAuthorizedPromoCode::class);
        $promo_code->shouldReceive('getSummitId')->andReturn(1);
        $promo_code->shouldReceive('getId')->andReturn(101);
        $promo_code->shouldReceive('getCode')->andReturn($promo_code_value);
        $promo_code->shouldReceive('validate');
        $promo_code->shouldReceive('canBeAppliedTo')->with($ticket_type)->andReturn(true);
        $promo_code->shouldReceive('getQuantityPerAccount')->andReturn($quantityPerAccountLimit);
        // Only Task A succeeds — exactly one addUsage call.
        $promo_code->shouldReceive('addUsage')->once();

        $owner = Mockery::mock(Member::class);

        $repo = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $repo->shouldReceive('getByValueExclusiveLock')
            ->with($summit, $promo_code_value)
            ->andReturn($promo_code);

        // Serialized: Task A sees count=1, Task B sees count=2.
        $repo->shouldReceive('getTicketCountByMemberAndPromoCode')
            ->with($owner, $promo_code)
            ->twice()
            ->andReturnValues([1, 2]);

        $tx_service = Mockery::mock(ITransactionService::class);
        $tx_service->shouldReceive('transaction')->andReturnUsing(fn($fn) => $fn());

        $lock_service = Mockery::mock(ILockManagerService::class);
        $lock_service->shouldReceive('lock')->andReturnUsing(fn($_k, $fn) => $fn());

        $this->bindSummitRepository($summit);

        $formerState = [
            'promo_codes_usage' => [
                $promo_code_value => [
                    'qty'   => 1,
                    'types' => [$ticket_type_id],
                ],
            ],
        ];

        // --- Task A: should succeed ---
        $taskA = new ApplyPromoCodeTask(
            $summit, ['owner_email' => 'buyer@example.com'], $owner,
            $repo, $tx_service, $lock_service,
        );
        $resultA = $taskA->run($formerState);
        $this->assertTrue($resultA['promo_codes_usage'][$promo_code_value]['redeem']);

        // --- Task B: should reject ---
        $taskB = new ApplyPromoCodeTask(
            $summit, ['owner_email' => 'buyer@example.com'], $owner,
            $repo, $tx_service, $lock_service,
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/reached the maximum of 1/');
        $taskB->run($formerState);
    }

    /**
     * TOCTOU double-rejection bug — demonstrates the race condition.
     *
     * Both ReserveOrderTask executions commit before either ApplyPromoCodeTask runs.
     * Both tasks see count = 2 (both requests' tickets visible in the DB).
     *
     * - Limit = 1, each request buys 1 ticket.
     * - CORRECT behavior: Task A should succeed (it was the first valid request),
     *   only Task B should reject.
     * - ACTUAL behavior (bug): both see count = 2 → 2 > 1 → both reject.
     *
     * This test asserts the CORRECT behavior and is expected to FAIL until
     * the TOCTOU race is fixed (e.g., by moving the count inside the
     * exclusive lock or deducting the current order's own tickets from the count).
     */
    public function testDoubleRejection_BothReservedBeforeEitherValidates(): void
    {
        $promo_code_value = 'DOMAIN-CODE-1';
        $ticket_type_id = 42;
        $quantityPerAccountLimit = 1;

        $ticket_type = Mockery::mock(SummitTicketType::class);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1);
        $summit->shouldReceive('getTicketTypeById')->with($ticket_type_id)->andReturn($ticket_type);

        $promo_code = Mockery::mock(SummitRegistrationPromoCode::class, IDomainAuthorizedPromoCode::class);
        $promo_code->shouldReceive('getSummitId')->andReturn(1);
        $promo_code->shouldReceive('getId')->andReturn(101);
        $promo_code->shouldReceive('getCode')->andReturn($promo_code_value);
        $promo_code->shouldReceive('validate');
        $promo_code->shouldReceive('canBeAppliedTo')->with($ticket_type)->andReturn(true);
        $promo_code->shouldReceive('getQuantityPerAccount')->andReturn($quantityPerAccountLimit);
        // Permissive — correct behavior calls addUsage once, bug calls it zero times.
        $promo_code->shouldReceive('addUsage')->zeroOrMoreTimes();

        $owner = Mockery::mock(Member::class);

        $repo = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $repo->shouldReceive('getByValueExclusiveLock')
            ->with($summit, $promo_code_value)
            ->andReturn($promo_code);

        // Both tasks see the inflated count (both orders' tickets visible).
        $repo->shouldReceive('getTicketCountByMemberAndPromoCode')
            ->with($owner, $promo_code)
            ->andReturn(2);

        $tx_service = Mockery::mock(ITransactionService::class);
        $tx_service->shouldReceive('transaction')->andReturnUsing(fn($fn) => $fn());

        // Permissive — correct behavior reaches lock, bug throws before it.
        $lock_service = Mockery::mock(ILockManagerService::class);
        $lock_service->shouldReceive('lock')->zeroOrMoreTimes()->andReturnUsing(fn($_k, $fn) => $fn());

        $this->bindSummitRepository($summit);

        $formerState = [
            'promo_codes_usage' => [
                $promo_code_value => [
                    'qty'   => 1,
                    'types' => [$ticket_type_id],
                ],
            ],
        ];

        // --- Task A: SHOULD succeed (first valid request) ---
        // BUG: Task A sees count=2 (includes Task B's tickets) and rejects.
        $taskA = new ApplyPromoCodeTask(
            $summit, ['owner_email' => 'buyer@example.com'], $owner,
            $repo, $tx_service, $lock_service,
        );
        try {
            $resultA = $taskA->run($formerState);
        } catch (ValidationException $ex) {
            $this->fail(
                'TOCTOU BUG: Task A was incorrectly rejected. '
                . 'When two ReserveOrderTask executions commit before either ApplyPromoCodeTask runs, '
                . 'both tasks see an inflated ticket count (2) and both reject — even though '
                . 'Task A is a valid first request within the limit of 1. '
                . 'Exception: ' . $ex->getMessage()
            );
        }
        $this->assertTrue($resultA['promo_codes_usage'][$promo_code_value]['redeem']);

        // --- Task B: should reject (over limit) ---
        $taskB = new ApplyPromoCodeTask(
            $summit, ['owner_email' => 'buyer@example.com'], $owner,
            $repo, $tx_service, $lock_service,
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/reached the maximum of 1/');
        $taskB->run($formerState);
    }

    /**
     * Serialized execution with higher limit — both requests succeed:
     *
     * - Limit = 2, each request buys 1 ticket.
     * - Task A runs first: count = 1 → 1 > 2 = false → passes.
     * - Task B runs second: count = 2 → 2 > 2 = false → passes.
     *
     * Confirms that serialized execution correctly allows both requests
     * when the combined total stays within the limit.
     */
    public function testSerializedExecution_BothAllowedWithinLimit(): void
    {
        $promo_code_value = 'DOMAIN-CODE-1';
        $ticket_type_id = 42;
        $quantityPerAccountLimit = 2;

        $ticket_type = Mockery::mock(SummitTicketType::class);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1);
        $summit->shouldReceive('getTicketTypeById')->with($ticket_type_id)->andReturn($ticket_type);

        $promo_code = Mockery::mock(SummitRegistrationPromoCode::class, IDomainAuthorizedPromoCode::class);
        $promo_code->shouldReceive('getSummitId')->andReturn(1);
        $promo_code->shouldReceive('getId')->andReturn(101);
        $promo_code->shouldReceive('getCode')->andReturn($promo_code_value);
        $promo_code->shouldReceive('validate');
        $promo_code->shouldReceive('canBeAppliedTo')->with($ticket_type)->andReturn(true);
        $promo_code->shouldReceive('getQuantityPerAccount')->andReturn($quantityPerAccountLimit);
        // Both tasks succeed — two addUsage calls.
        $promo_code->shouldReceive('addUsage')->twice();

        $owner = Mockery::mock(Member::class);

        $repo = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $repo->shouldReceive('getByValueExclusiveLock')
            ->with($summit, $promo_code_value)
            ->andReturn($promo_code);

        // Serialized: Task A sees count=1, Task B sees count=2.
        $repo->shouldReceive('getTicketCountByMemberAndPromoCode')
            ->with($owner, $promo_code)
            ->twice()
            ->andReturnValues([1, 2]);

        $tx_service = Mockery::mock(ITransactionService::class);
        $tx_service->shouldReceive('transaction')->andReturnUsing(fn($fn) => $fn());

        $lock_service = Mockery::mock(ILockManagerService::class);
        $lock_service->shouldReceive('lock')->andReturnUsing(fn($_k, $fn) => $fn());

        $this->bindSummitRepository($summit);

        $formerState = [
            'promo_codes_usage' => [
                $promo_code_value => [
                    'qty'   => 1,
                    'types' => [$ticket_type_id],
                ],
            ],
        ];

        // --- Task A: should succeed ---
        $taskA = new ApplyPromoCodeTask(
            $summit, ['owner_email' => 'buyer@example.com'], $owner,
            $repo, $tx_service, $lock_service,
        );
        $resultA = $taskA->run($formerState);
        $this->assertTrue($resultA['promo_codes_usage'][$promo_code_value]['redeem']);

        // --- Task B: should also succeed ---
        $taskB = new ApplyPromoCodeTask(
            $summit, ['owner_email' => 'buyer@example.com'], $owner,
            $repo, $tx_service, $lock_service,
        );
        $resultB = $taskB->run($formerState);
        $this->assertTrue($resultB['promo_codes_usage'][$promo_code_value]['redeem']);
    }

    /**
     * Bind a mock ISummitRepository so ApplyPromoCodeTask::run() can re-attach the summit.
     */
    private function bindSummitRepository(Summit $summit): void
    {
        $summit_repo = Mockery::mock(ISummitRepository::class);
        $summit_repo->shouldReceive('getById')->andReturn($summit);

        $container = \Illuminate\Support\Facades\Facade::getFacadeApplication();
        $container->instance(ISummitRepository::class, $summit_repo);
    }
}
