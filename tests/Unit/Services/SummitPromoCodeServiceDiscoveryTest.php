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

use App\Services\Model\SummitPromoCodeService;
use App\Services\Utils\ILockManagerService;
use libs\utils\ITransactionService;
use Mockery;
use models\main\ICompanyRepository;
use models\main\IMemberRepository;
use models\main\ITagRepository;
use models\main\Member;
use models\summit\DomainAuthorizedSummitRegistrationPromoCode;
use models\summit\ISpeakerRepository;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\ISummitRepository;
use models\summit\Summit;
use PHPUnit\Framework\TestCase;

/**
 * Class SummitPromoCodeServiceDiscoveryTest
 *
 * Regression unit tests for {@see SummitPromoCodeService::discoverPromoCodes}
 * filtering logic. Focuses on the global-exhaustion guard added to match
 * checkout's validate() behavior.
 *
 * @package Tests\Unit\Services
 */
class SummitPromoCodeServiceDiscoveryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function buildService(ISummitRegistrationPromoCodeRepository $repository): SummitPromoCodeService
    {
        return new SummitPromoCodeService(
            Mockery::mock(IMemberRepository::class),
            Mockery::mock(ICompanyRepository::class),
            Mockery::mock(ISpeakerRepository::class),
            Mockery::mock(ISummitRepository::class),
            Mockery::mock(ISummitAttendeeTicketRepository::class),
            Mockery::mock(ITagRepository::class),
            $repository,
            Mockery::mock(ITransactionService::class),
            Mockery::mock(ILockManagerService::class)
        );
    }

    /**
     * Regression: a finite domain-authorized code whose quantity_used has
     * reached quantity_available must not appear in discovery. Previously,
     * the repository filter used isLive() (dates only), so globally
     * exhausted codes leaked through — frontend auto-apply would then hit
     * a hard checkout failure.
     */
    public function testDiscoverExcludesGloballyExhaustedDomainAuthorizedCode(): void
    {
        $exhausted = Mockery::mock(DomainAuthorizedSummitRegistrationPromoCode::class);
        $exhausted->shouldReceive('getCode')->andReturn('GLOBAL_EXHAUSTED');
        $exhausted->shouldReceive('hasQuantityAvailable')->andReturn(false);
        // getQuantityPerAccount should never be reached if the global-exhaustion
        // guard is in place — but define it defensively so a regression would
        // surface as a quota check, not an uncaught Mockery error.
        $exhausted->shouldReceive('getQuantityPerAccount')->andReturn(0);
        $exhausted->shouldReceive('setRemainingQuantityPerAccount')->andReturn(null);

        $summit = Mockery::mock(Summit::class);
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getEmail')->andReturn('new-buyer@acme.com');
        $member->shouldReceive('getId')->andReturn(99);

        $repository = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        // Repository filter is isLive()-only, so it would pass the exhausted
        // code through — simulate that by returning it.
        $repository->shouldReceive('getDiscoverableByEmailForSummit')
            ->with($summit, 'new-buyer@acme.com')
            ->andReturn([$exhausted]);

        $service = $this->buildService($repository);
        $result = $service->discoverPromoCodes($summit, $member);

        $this->assertSame([], $result,
            'Globally exhausted domain-authorized code must not appear in discovery');
    }

    /**
     * A healthy domain-authorized code (has global quantity, unlimited quota)
     * passes through. Guards against over-filtering: the exhaustion guard must
     * not drop valid codes.
     */
    public function testDiscoverReturnsHealthyDomainAuthorizedCode(): void
    {
        $healthy = Mockery::mock(DomainAuthorizedSummitRegistrationPromoCode::class);
        $healthy->shouldReceive('getCode')->andReturn('HEALTHY');
        $healthy->shouldReceive('hasQuantityAvailable')->andReturn(true);
        $healthy->shouldReceive('getQuantityPerAccount')->andReturn(0);
        $healthy->shouldReceive('setRemainingQuantityPerAccount')->with(null)->once();

        $summit = Mockery::mock(Summit::class);
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getEmail')->andReturn('buyer@acme.com');
        $member->shouldReceive('getId')->andReturn(42);

        $repository = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $repository->shouldReceive('getDiscoverableByEmailForSummit')
            ->with($summit, 'buyer@acme.com')
            ->andReturn([$healthy]);

        $service = $this->buildService($repository);
        $result = $service->discoverPromoCodes($summit, $member);

        $this->assertCount(1, $result);
        $this->assertSame('HEALTHY', $result[0]->getCode());
    }

    /**
     * Mixed case: exhausted code is dropped while a healthy sibling survives.
     * This proves the guard uses per-code `continue`, not a scalar short-circuit.
     */
    public function testDiscoverMixedHealthyAndExhaustedCodes(): void
    {
        $exhausted = Mockery::mock(DomainAuthorizedSummitRegistrationPromoCode::class);
        $exhausted->shouldReceive('getCode')->andReturn('EXHAUSTED');
        $exhausted->shouldReceive('hasQuantityAvailable')->andReturn(false);
        $exhausted->shouldReceive('getQuantityPerAccount')->andReturn(0);
        $exhausted->shouldReceive('setRemainingQuantityPerAccount')->andReturn(null);

        $healthy = Mockery::mock(DomainAuthorizedSummitRegistrationPromoCode::class);
        $healthy->shouldReceive('getCode')->andReturn('HEALTHY');
        $healthy->shouldReceive('hasQuantityAvailable')->andReturn(true);
        $healthy->shouldReceive('getQuantityPerAccount')->andReturn(0);
        $healthy->shouldReceive('setRemainingQuantityPerAccount')->with(null)->once();

        $summit = Mockery::mock(Summit::class);
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getEmail')->andReturn('buyer@acme.com');
        $member->shouldReceive('getId')->andReturn(7);

        $repository = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $repository->shouldReceive('getDiscoverableByEmailForSummit')
            ->with($summit, 'buyer@acme.com')
            ->andReturn([$exhausted, $healthy]);

        $service = $this->buildService($repository);
        $result = $service->discoverPromoCodes($summit, $member);

        $this->assertCount(1, $result);
        $this->assertSame('HEALTHY', $result[0]->getCode());
    }
}
