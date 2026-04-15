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
 * Regression coverage for the QuantityPerAccount enforcement in
 * ApplyPromoCodeTask::run() (SummitOrderService.php).
 *
 * The guard uses strict `>` (not `>=`) and does NOT add $qty because the
 * in-flight order's tickets are already counted by
 * ISummitRegistrationPromoCodeRepository::getTicketCountByMemberAndPromoCode —
 * ReserveOrderTask persists + commits them (with PromoCodeID set via
 * applyTo()) before this task runs. Changing the saga order or the count
 * query would break the semantics pinned here.
 *
 * See PR #525 — reviewer `romanetar` suggested `($existingCount + $qty) > limit`
 * and `>=`; both would introduce a false-reject at the exactly-at-limit case.
 */
class ApplyPromoCodeTaskQuantityPerAccountTest extends TestCase
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
     * Scenario: limit = 2, no prior tickets, buying 2.
     * $existingCount = 2 (the current order's two tickets, post-ReserveOrderTask).
     * Guard is `2 > 2` → false → must ALLOW.
     *
     * This pins the semantics against a naive "use `>=`" change.
     */
    public function testAllowsExactlyAtLimitWhenCountIncludesCurrentOrder(): void
    {
        $this->runTaskAndAssert(
            quantityPerAccountLimit: 2,
            existingTicketCount: 2,
            qtyInPayload: 2,
            expectException: false,
        );
    }

    /**
     * Scenario: limit = 2, no prior tickets, buying 3 → $existingCount = 3 → 3 > 2 → reject.
     */
    public function testRejectsWhenOrderExceedsLimit(): void
    {
        $this->runTaskAndAssert(
            quantityPerAccountLimit: 2,
            existingTicketCount: 3,
            qtyInPayload: 3,
            expectException: true,
            expectedMessageFragment: 'reached the maximum of 2',
        );
    }

    /**
     * Scenario: limit = 2, prior tickets = 2 (from previous order), buying 1 now.
     * $existingCount = 3 → 3 > 2 → reject. Confirms the guard still fires when
     * the overflow comes from historical + current combined.
     */
    public function testRejectsWhenPriorTicketsPlusCurrentExceedLimit(): void
    {
        $this->runTaskAndAssert(
            quantityPerAccountLimit: 2,
            existingTicketCount: 3,
            qtyInPayload: 1,
            expectException: true,
            expectedMessageFragment: 'reached the maximum of 2',
        );
    }

    /**
     * Scenario: limit = 2, prior = 0, buying 1 → $existingCount = 1 → 1 > 2 false → allow.
     */
    public function testAllowsWellUnderLimit(): void
    {
        $this->runTaskAndAssert(
            quantityPerAccountLimit: 2,
            existingTicketCount: 1,
            qtyInPayload: 1,
            expectException: false,
        );
    }

    /**
     * Scenario: limit = 0 means "unlimited" — guard is skipped regardless of count.
     * The repository count method MUST NOT be called in this branch.
     */
    public function testLimitOfZeroSkipsGuardEntirely(): void
    {
        $this->runTaskAndAssert(
            quantityPerAccountLimit: 0,
            existingTicketCount: 999,
            qtyInPayload: 5,
            expectException: false,
            expectCountQuery: false,
        );
    }

    /**
     * Scenario: non-domain-authorized promo code — guard MUST be bypassed.
     * The repository count method MUST NOT be called.
     */
    public function testNonDomainAuthorizedPromoCodeIsNotGated(): void
    {
        $this->runNonDomainTaskAndAssert();
    }

    // ----- Driver --------------------------------------------------------

    private function runTaskAndAssert(
        int $quantityPerAccountLimit,
        int $existingTicketCount,
        int $qtyInPayload,
        bool $expectException,
        ?string $expectedMessageFragment = null,
        bool $expectCountQuery = true,
    ): void {
        $ticket_type_id = 42;
        $promo_code_value = 'DOMAIN-CODE-1';

        $ticket_type = Mockery::mock(SummitTicketType::class);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1);
        $summit->shouldReceive('getTicketTypeById')->with($ticket_type_id)->andReturn($ticket_type);
        // getRegistrationCompanyById is reachable from TaskUtils when payload has owner_company_id — payload omits it so this isn't called.

        // Domain-authorized promo code mock (also a SummitRegistrationPromoCode).
        $promo_code = Mockery::mock(SummitRegistrationPromoCode::class, IDomainAuthorizedPromoCode::class);
        $promo_code->shouldReceive('getSummitId')->andReturn(1);
        $promo_code->shouldReceive('getId')->andReturn(101);
        $promo_code->shouldReceive('getCode')->andReturn($promo_code_value);
        $promo_code->shouldReceive('validate')->once();
        $promo_code->shouldReceive('canBeAppliedTo')->with($ticket_type)->andReturn(true);
        $promo_code->shouldReceive('getQuantityPerAccount')->andReturn($quantityPerAccountLimit);

        if ($expectException) {
            $promo_code->shouldNotReceive('addUsage');
        } else {
            $promo_code->shouldReceive('addUsage')->once();
        }

        $owner = Mockery::mock(Member::class);

        $repo = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $repo->shouldReceive('getByValueExclusiveLock')
            ->with($summit, $promo_code_value)
            ->andReturn($promo_code);

        if ($expectCountQuery) {
            $repo->shouldReceive('getTicketCountByMemberAndPromoCode')
                ->once()
                ->with($owner, $promo_code)
                ->andReturn($existingTicketCount);
        } else {
            $repo->shouldNotReceive('getTicketCountByMemberAndPromoCode');
        }

        $tx_service = Mockery::mock(ITransactionService::class);
        $tx_service->shouldReceive('transaction')->andReturnUsing(fn($fn) => $fn());

        $lock_service = Mockery::mock(ILockManagerService::class);
        if ($expectException) {
            $lock_service->shouldNotReceive('lock');
        } else {
            $lock_service->shouldReceive('lock')->once()->andReturnUsing(fn($_k, $fn) => $fn());
        }

        $this->bindSummitRepository($summit);

        $task = new ApplyPromoCodeTask(
            $summit,
            ['owner_email' => 'buyer@example.com'],
            $owner,
            $repo,
            $tx_service,
            $lock_service,
        );

        $formerState = [
            'promo_codes_usage' => [
                $promo_code_value => [
                    'qty'   => $qtyInPayload,
                    'types' => [$ticket_type_id],
                ],
            ],
        ];

        if ($expectException) {
            try {
                $task->run($formerState);
                $this->fail('Expected ValidationException for over-limit QuantityPerAccount');
            } catch (ValidationException $ex) {
                if ($expectedMessageFragment !== null) {
                    $this->assertStringContainsString($expectedMessageFragment, $ex->getMessage());
                }
            }
            return;
        }

        $result = $task->run($formerState);
        $this->assertTrue($result['promo_codes_usage'][$promo_code_value]['redeem']);
    }

    private function runNonDomainTaskAndAssert(): void
    {
        $ticket_type_id = 42;
        $promo_code_value = 'PLAIN-CODE-1';

        $ticket_type = Mockery::mock(SummitTicketType::class);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1);
        $summit->shouldReceive('getTicketTypeById')->with($ticket_type_id)->andReturn($ticket_type);

        // NOT an IDomainAuthorizedPromoCode — the guard must be skipped.
        $promo_code = Mockery::mock(SummitRegistrationPromoCode::class);
        $promo_code->shouldReceive('getSummitId')->andReturn(1);
        $promo_code->shouldReceive('getId')->andReturn(202);
        $promo_code->shouldReceive('getCode')->andReturn($promo_code_value);
        $promo_code->shouldReceive('validate')->once();
        $promo_code->shouldReceive('canBeAppliedTo')->with($ticket_type)->andReturn(true);
        $promo_code->shouldReceive('addUsage')->once();
        // QuantityPerAccount accessors must NEVER be reached for a non-domain code:
        $promo_code->shouldNotReceive('getQuantityPerAccount');

        $owner = Mockery::mock(Member::class);

        $repo = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $repo->shouldReceive('getByValueExclusiveLock')
            ->with($summit, $promo_code_value)
            ->andReturn($promo_code);
        $repo->shouldNotReceive('getTicketCountByMemberAndPromoCode');

        $tx_service = Mockery::mock(ITransactionService::class);
        $tx_service->shouldReceive('transaction')->andReturnUsing(fn($fn) => $fn());

        $lock_service = Mockery::mock(ILockManagerService::class);
        $lock_service->shouldReceive('lock')->once()->andReturnUsing(fn($_k, $fn) => $fn());

        $this->bindSummitRepository($summit);

        $task = new ApplyPromoCodeTask(
            $summit,
            ['owner_email' => 'buyer@example.com'],
            $owner,
            $repo,
            $tx_service,
            $lock_service,
        );

        $result = $task->run([
            'promo_codes_usage' => [
                $promo_code_value => [
                    'qty'   => 1,
                    'types' => [$ticket_type_id],
                ],
            ],
        ]);

        $this->assertTrue($result['promo_codes_usage'][$promo_code_value]['redeem']);
    }

    /**
     * ApplyPromoCodeTask::run() re-attaches the summit via App::make(ISummitRepository::class).
     * Bind a mock that returns our in-memory summit.
     */
    private function bindSummitRepository(Summit $summit): void
    {
        $summit_repo = Mockery::mock(ISummitRepository::class);
        $summit_repo->shouldReceive('getById')->andReturn($summit);

        $container = \Illuminate\Support\Facades\Facade::getFacadeApplication();
        $container->instance(ISummitRepository::class, $summit_repo);
    }
}
