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

use App\Services\Model\PreProcessReservationTask;
use libs\utils\ITransactionService;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\IDomainAuthorizedPromoCode;
use models\summit\ISummitPromoCodeMemberReservationRepository;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\Summit;
use models\summit\SummitPromoCodeMemberReservation;
use models\summit\SummitRegistrationPromoCode;
use models\summit\SummitTicketType;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Per-member QuantityPerAccount enforcement now lives in
 * PreProcessReservationTask::reserveMemberQuotas — it runs BEFORE
 * ReserveTicketsTask / ReserveOrderTask, inside a transaction that
 * holds PESSIMISTIC_WRITE on the parent promo code row
 * (getByValueExclusiveLock). That outer lock is the serialization
 * point; the per-member counter on SummitPromoCodeMemberReservation
 * is durable state written inside the lock.
 *
 * These tests replace smarcet's PR #530 reproduction
 * (tests/Unit/Services/ApplyPromoCodeTaskConcurrencyTest, now removed)
 * because the post-facto check that PR demonstrated can't-work is
 * gone — the surface smarcet asserted against (a static
 * getTicketCountByMemberAndPromoCode) is no longer part of the write
 * path. The narrative is preserved: Task A should succeed, Task B
 * should reject, both with limit=1; and with limit=2 both requests
 * should succeed.
 */
class PreProcessReservationTaskConcurrencyTest extends TestCase
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
     * Task A is the first reserve for (member, code) with limit=1,
     * qty=1. No prior reservation row exists, so the repo's `add()`
     * is called with a fresh row carrying qty_used=1.
     */
    public function testFirstReserveSucceedsWhenNoPriorRow(): void
    {
        [$promoCode, $owner, $promoRepo, $reservationRepo, $tx] =
            $this->buildCollaborators(limit: 1, priorReservation: null);

        $reservationRepo->shouldReceive('add')
            ->once()
            ->with(Mockery::on(function ($row) use ($promoCode, $owner) {
                return $row instanceof SummitPromoCodeMemberReservation
                    && $row->getPromoCode() === $promoCode
                    && $row->getMember()    === $owner
                    && $row->getQtyUsed()   === 1;
            }));

        $task = $this->buildTask(owner: $owner, promoRepo: $promoRepo, reservationRepo: $reservationRepo, tx: $tx);

        // No exception = pass.
        $this->invokeReserve($task, [
            'DOMAIN-CODE-1' => ['qty' => 1, 'types' => [42]],
        ]);

        $this->assertSame(
            [['code' => 'DOMAIN-CODE-1', 'qty' => 1]],
            $this->readReserved($task),
            'Successful reservation must be recorded for undo compensation.'
        );
    }

    /**
     * Task B runs after Task A has committed its counter row.
     * Limit=1, prior qty_used=1 (from A), this request qty=1.
     * Check: 1 + 1 > 1 → reject.
     *
     * This is the serialized-second-request flow. No TOCTOU because
     * B blocks on A's PESSIMISTIC_WRITE lock and observes the
     * committed qty_used.
     */
    public function testSecondReserveRejectsOverLimit(): void
    {
        $priorReservation = Mockery::mock(SummitPromoCodeMemberReservation::class);
        $priorReservation->shouldReceive('getQtyUsed')->andReturn(1);
        // Fix must NOT call increment on an over-limit reservation.
        $priorReservation->shouldReceive('increment')->never();

        [$promoCode, $owner, $promoRepo, $reservationRepo, $tx] =
            $this->buildCollaborators(limit: 1, priorReservation: $priorReservation);

        $reservationRepo->shouldReceive('add')->never();

        $task = $this->buildTask(owner: $owner, promoRepo: $promoRepo, reservationRepo: $reservationRepo, tx: $tx);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/reached the maximum of 1/');

        $this->invokeReserve($task, [
            'DOMAIN-CODE-1' => ['qty' => 1, 'types' => [42]],
        ]);
    }

    /**
     * Limit=2, prior qty_used=1 (from a previous Task A), this
     * request qty=1. Check: 1 + 1 > 2 false → pass, increment by 1.
     * Mirrors smarcet's "both allowed within limit" scenario.
     */
    public function testReserveSucceedsWhenWithinLimitWithPriorRow(): void
    {
        $priorReservation = Mockery::mock(SummitPromoCodeMemberReservation::class);
        $priorReservation->shouldReceive('getQtyUsed')->andReturn(1);
        $priorReservation->shouldReceive('increment')->once()->with(1);

        [$promoCode, $owner, $promoRepo, $reservationRepo, $tx] =
            $this->buildCollaborators(limit: 2, priorReservation: $priorReservation);

        $reservationRepo->shouldReceive('add')->never();

        $task = $this->buildTask(owner: $owner, promoRepo: $promoRepo, reservationRepo: $reservationRepo, tx: $tx);

        $this->invokeReserve($task, [
            'DOMAIN-CODE-1' => ['qty' => 1, 'types' => [42]],
        ]);

        $this->assertSame(
            [['code' => 'DOMAIN-CODE-1', 'qty' => 1]],
            $this->readReserved($task)
        );
    }

    /**
     * QuantityPerAccount = 0 means "unlimited for this account" — no
     * reservation row should be touched regardless of qty.
     */
    public function testLimitOfZeroBypassesReservation(): void
    {
        $promoCode = Mockery::mock(SummitRegistrationPromoCode::class, IDomainAuthorizedPromoCode::class);
        $promoCode->shouldReceive('getQuantityPerAccount')->andReturn(0);

        $owner = Mockery::mock(Member::class);

        $promoRepo = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $promoRepo->shouldReceive('getByValueExclusiveLock')->andReturn($promoCode);

        $reservationRepo = Mockery::mock(ISummitPromoCodeMemberReservationRepository::class);
        $reservationRepo->shouldReceive('getByPromoCodeAndMember')->never();
        $reservationRepo->shouldReceive('add')->never();

        $tx = Mockery::mock(ITransactionService::class);
        $tx->shouldReceive('transaction')->andReturnUsing(fn($fn) => $fn());

        $task = $this->buildTask(owner: $owner, promoRepo: $promoRepo, reservationRepo: $reservationRepo, tx: $tx);

        $this->invokeReserve($task, [
            'DOMAIN-CODE-1' => ['qty' => 99, 'types' => [42]],
        ]);

        $this->assertSame([], $this->readReserved($task));
    }

    /**
     * Non-domain-authorized codes are opaque to per-member enforcement —
     * reserveMemberQuotas must no-op entirely (neither getQuantityPerAccount
     * nor the reservation repo is touched).
     */
    public function testNonDomainAuthorizedCodeIsSkipped(): void
    {
        $promoCode = Mockery::mock(SummitRegistrationPromoCode::class);
        // Explicitly NOT an IDomainAuthorizedPromoCode — per-member logic
        // must bail before even asking for the limit.
        $promoCode->shouldReceive('getQuantityPerAccount')->never();

        $owner = Mockery::mock(Member::class);

        $promoRepo = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $promoRepo->shouldReceive('getByValueExclusiveLock')->andReturn($promoCode);

        $reservationRepo = Mockery::mock(ISummitPromoCodeMemberReservationRepository::class);
        $reservationRepo->shouldReceive('getByPromoCodeAndMember')->never();
        $reservationRepo->shouldReceive('add')->never();

        $tx = Mockery::mock(ITransactionService::class);
        $tx->shouldReceive('transaction')->andReturnUsing(fn($fn) => $fn());

        $task = $this->buildTask(owner: $owner, promoRepo: $promoRepo, reservationRepo: $reservationRepo, tx: $tx);

        $this->invokeReserve($task, [
            'REGULAR-CODE' => ['qty' => 2, 'types' => [42]],
        ]);

        $this->assertSame(
            [],
            $this->readReserved($task),
            'Non-domain-authorized codes leave the reserved list untouched.'
        );
    }

    /**
     * When the saga must unwind, undo() acquires the same lock and
     * decrements each reserved counter exactly once. Calling undo
     * twice is a no-op (idempotency via the $undone guard).
     */
    public function testUndoDecrementsReservedCountersAndIsIdempotent(): void
    {
        $priorReservation = Mockery::mock(SummitPromoCodeMemberReservation::class);
        $priorReservation->shouldReceive('getQtyUsed')->andReturn(1);
        $priorReservation->shouldReceive('decrement')->once()->with(2);

        [$promoCode, $owner, $promoRepo, $reservationRepo, $tx] =
            $this->buildCollaborators(limit: 5, priorReservation: $priorReservation);

        $task = $this->buildTask(owner: $owner, promoRepo: $promoRepo, reservationRepo: $reservationRepo, tx: $tx);

        // Seed as if reserveMemberQuotas had already recorded a reservation.
        $this->writeReserved($task, [['code' => 'DOMAIN-CODE-1', 'qty' => 2]]);

        $task->undo();
        $task->undo(); // second call must not call decrement again.

        $this->assertSame(
            [],
            $this->readReserved($task),
            'Reserved list should be drained after a successful undo pass.'
        );
    }

    // ---------- helpers ----------

    private function buildCollaborators(
        int $limit,
        ?SummitPromoCodeMemberReservation $priorReservation
    ): array {
        $promoCode = Mockery::mock(SummitRegistrationPromoCode::class, IDomainAuthorizedPromoCode::class);
        $promoCode->shouldReceive('getQuantityPerAccount')->andReturn($limit);
        $promoCode->shouldReceive('getId')->andReturn(101);
        $promoCode->shouldReceive('getCode')->andReturn('DOMAIN-CODE-1');

        $owner = Mockery::mock(Member::class);
        $owner->shouldReceive('getId')->andReturn(7);

        $promoRepo = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $promoRepo->shouldReceive('getByValueExclusiveLock')->andReturn($promoCode);

        $reservationRepo = Mockery::mock(ISummitPromoCodeMemberReservationRepository::class);
        $reservationRepo->shouldReceive('getByPromoCodeAndMember')
            ->with($promoCode, $owner)
            ->andReturn($priorReservation);

        $tx = Mockery::mock(ITransactionService::class);
        $tx->shouldReceive('transaction')->andReturnUsing(fn($fn) => $fn());

        return [$promoCode, $owner, $promoRepo, $reservationRepo, $tx];
    }

    private function buildTask(
        Member $owner,
        ISummitRegistrationPromoCodeRepository $promoRepo,
        ISummitPromoCodeMemberReservationRepository $reservationRepo,
        ITransactionService $tx
    ): PreProcessReservationTask {
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1);

        return new PreProcessReservationTask(
            $summit,
            ['tickets' => []],
            $owner,
            $promoRepo,
            $reservationRepo,
            $tx
        );
    }

    /**
     * Invoke the protected reserveMemberQuotas() via reflection. We test
     * it in isolation from run()'s payload validation so a failure in
     * this test points squarely at the lock-and-count logic.
     */
    private function invokeReserve(PreProcessReservationTask $task, array $promo_codes_usage): void
    {
        $method = (new ReflectionClass($task))->getMethod('reserveMemberQuotas');
        $method->setAccessible(true);
        $method->invoke($task, $promo_codes_usage);
    }

    private function readReserved(PreProcessReservationTask $task): array
    {
        $property = (new ReflectionClass($task))->getProperty('reserved');
        $property->setAccessible(true);
        return $property->getValue($task);
    }

    private function writeReserved(PreProcessReservationTask $task, array $value): void
    {
        $property = (new ReflectionClass($task))->getProperty('reserved');
        $property->setAccessible(true);
        $property->setValue($task, $value);
    }
}
