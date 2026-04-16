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

use App\Models\Foundation\Summit\Registration\IBuildDefaultPaymentGatewayProfileStrategy;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgePrintRuleRepository;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use App\Models\Foundation\Summit\Repositories\ISummitRefundRequestRepository;
use App\Services\FileSystem\IFileDownloadStrategy;
use App\Services\FileSystem\IFileUploadStrategy;
use App\Services\Model\ICompanyService;
use App\Services\Model\IMemberService;
use App\Services\Model\Strategies\TicketFinder\ITicketFinderStrategyFactory;
use App\Services\Model\SummitOrderService;
use App\Services\Utils\ILockManagerService;
use libs\utils\ITransactionService;
use models\main\ICompanyRepository;
use models\main\IMemberRepository;
use models\main\ITagRepository;
use models\main\Member;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\ISummitPromoCodeMemberReservationRepository;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\ISummitRepository;
use models\summit\ISummitTicketTypeRepository;
use models\summit\Summit;
use models\summit\SummitPromoCodeMemberReservation;
use models\summit\SummitRegistrationPromoCode;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Verifies that restoreTicketsPromoCodes (the cancel/refund path)
 * decrements SummitPromoCodeMemberReservation.QtyUsed alongside the
 * existing promo-code removeUsage() call.
 *
 * Uses reflection on the private method — building a full cancel()
 * fixture would require mocking the order repository, payment gateway,
 * and order state machine, which is outside the scope of this fix.
 *
 * @package Tests\Unit\Services
 */
class RestorePathReservationTest extends TestCase
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

    private function buildService(
        ISummitRegistrationPromoCodeRepository $promoRepo,
        ISummitPromoCodeMemberReservationRepository $reservationRepo
    ): SummitOrderService {
        return new SummitOrderService(
            Mockery::mock(ISummitTicketTypeRepository::class),
            Mockery::mock(IMemberRepository::class),
            $promoRepo,
            $reservationRepo,
            Mockery::mock(ISummitAttendeeRepository::class),
            Mockery::mock(ISummitOrderRepository::class),
            Mockery::mock(ISummitAttendeeTicketRepository::class),
            Mockery::mock(ISummitAttendeeBadgeRepository::class),
            Mockery::mock(ISummitRepository::class),
            Mockery::mock(ISummitAttendeeBadgePrintRuleRepository::class),
            Mockery::mock(IMemberService::class),
            Mockery::mock(IBuildDefaultPaymentGatewayProfileStrategy::class),
            Mockery::mock(IFileUploadStrategy::class),
            Mockery::mock(IFileDownloadStrategy::class),
            Mockery::mock(ICompanyRepository::class),
            Mockery::mock(ITagRepository::class),
            Mockery::mock(ISummitRefundRequestRepository::class),
            Mockery::mock(ICompanyService::class),
            Mockery::mock(ITicketFinderStrategyFactory::class),
            Mockery::mock(ITransactionService::class),
            Mockery::mock(ILockManagerService::class)
        );
    }

    private function invokeRestoreTicketsPromoCodes(
        SummitOrderService $service,
        Summit $summit,
        array $tickets_to_return,
        array $promo_codes_to_return,
        ?Member $owner
    ): void {
        $method = new \ReflectionMethod($service, 'restoreTicketsPromoCodes');
        $method->setAccessible(true);
        $method->invoke($service, $summit, $tickets_to_return, $promo_codes_to_return, $owner);
    }

    /**
     * @dataProvider successfulDecrementProvider
     */
    public function testCancelDecrementsReservationRow(int $priorQty, int $returnQty, int $expectedQty): void
    {
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1);

        $owner = Mockery::mock(Member::class);

        $promoCode = Mockery::mock(SummitRegistrationPromoCode::class);
        $promoCode->shouldReceive('removeUsage')->with($returnQty, 'buyer@acme.com')->once();

        $reservation = new SummitPromoCodeMemberReservation($promoCode, $owner, $priorQty);

        $promoRepo = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $promoRepo->shouldReceive('getByValueExclusiveLock')
            ->with($summit, 'TESTCODE')
            ->andReturn($promoCode);

        $reservationRepo = Mockery::mock(ISummitPromoCodeMemberReservationRepository::class);
        $reservationRepo->shouldReceive('getByPromoCodeAndMember')
            ->with($promoCode, $owner)
            ->andReturn($reservation);

        $service = $this->buildService($promoRepo, $reservationRepo);

        $this->invokeRestoreTicketsPromoCodes($service, $summit, [], [
            'TESTCODE' => ['qty' => $returnQty, 'owner_email' => 'buyer@acme.com']
        ], $owner);

        $this->assertSame($expectedQty, $reservation->getQtyUsed());
    }

    /**
     * @return array
     */
    public static function successfulDecrementProvider(): array
    {
        return [
            'single ticket cancel' => [3, 1, 2],
            'full cancel' => [2, 2, 0],
            'over-decrement clamps to zero' => [1, 5, 0],
        ];
    }

    public function testCancelWithNullOwnerSkipsReservationDecrement(): void
    {
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1);

        $promoCode = Mockery::mock(SummitRegistrationPromoCode::class);
        $promoCode->shouldReceive('removeUsage')->with(1, 'guest@acme.com')->once();

        $promoRepo = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $promoRepo->shouldReceive('getByValueExclusiveLock')
            ->with($summit, 'TESTCODE')
            ->andReturn($promoCode);

        $reservationRepo = Mockery::mock(ISummitPromoCodeMemberReservationRepository::class);
        $reservationRepo->shouldNotReceive('getByPromoCodeAndMember');

        $service = $this->buildService($promoRepo, $reservationRepo);

        $this->invokeRestoreTicketsPromoCodes($service, $summit, [], [
            'TESTCODE' => ['qty' => 1, 'owner_email' => 'guest@acme.com']
        ], null);

        $this->assertTrue(true, 'No reservation decrement attempted for guest order');
    }

    public function testCancelWithMissingReservationRowSkipsSilently(): void
    {
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1);

        $owner = Mockery::mock(Member::class);

        $promoCode = Mockery::mock(SummitRegistrationPromoCode::class);
        $promoCode->shouldReceive('removeUsage')->with(1, 'buyer@acme.com')->once();

        $promoRepo = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $promoRepo->shouldReceive('getByValueExclusiveLock')
            ->with($summit, 'TESTCODE')
            ->andReturn($promoCode);

        $reservationRepo = Mockery::mock(ISummitPromoCodeMemberReservationRepository::class);
        $reservationRepo->shouldReceive('getByPromoCodeAndMember')
            ->with($promoCode, $owner)
            ->andReturn(null);

        $service = $this->buildService($promoRepo, $reservationRepo);

        $this->invokeRestoreTicketsPromoCodes($service, $summit, [], [
            'TESTCODE' => ['qty' => 1, 'owner_email' => 'buyer@acme.com']
        ], $owner);

        $this->assertTrue(true, 'No error when reservation row does not exist');
    }

    public function testReservationDecrementExceptionPropagates(): void
    {
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1);

        $owner = Mockery::mock(Member::class);

        $promoCode = Mockery::mock(SummitRegistrationPromoCode::class);
        $promoCode->shouldReceive('removeUsage')->with(1, 'buyer@acme.com')->once();

        $promoRepo = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $promoRepo->shouldReceive('getByValueExclusiveLock')
            ->with($summit, 'TESTCODE')
            ->andReturn($promoCode);

        $reservationRepo = Mockery::mock(ISummitPromoCodeMemberReservationRepository::class);
        $reservationRepo->shouldReceive('getByPromoCodeAndMember')
            ->with($promoCode, $owner)
            ->andThrow(new \RuntimeException('Doctrine connection lost'));

        $service = $this->buildService($promoRepo, $reservationRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Doctrine connection lost');

        $this->invokeRestoreTicketsPromoCodes($service, $summit, [], [
            'TESTCODE' => ['qty' => 1, 'owner_email' => 'buyer@acme.com']
        ], $owner);
    }
}
