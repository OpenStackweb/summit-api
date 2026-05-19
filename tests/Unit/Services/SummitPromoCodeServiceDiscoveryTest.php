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

use App\Services\Model\AllowedEmailDomainsLookupBuilder;
use App\Services\Model\SummitPromoCodeService;
use App\Services\Utils\ILockManagerService;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
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
use Psr\Log\NullLogger;

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
    protected function setUp(): void
    {
        parent::setUp();
        // Provide a minimal facade application so Log::info() calls in the SUT
        // (Track-1 metric emit) resolve via the facade without requiring a full
        // Laravel app or database.
        Facade::clearResolvedInstances();
        $app = new Container();
        $app->singleton('log', fn() => new NullLogger());
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication(null);
        Facade::clearResolvedInstances();
        Container::setInstance(null);
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
            Mockery::mock(ILockManagerService::class),
            new AllowedEmailDomainsLookupBuilder()
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
        $exhausted->shouldReceive('getAllowedEmailDomains')->andReturn(['@acme.com']);
        $exhausted->shouldReceive('matchesEmailDomainViaLookup')->andReturn(true);
        $exhausted->shouldReceive('getValidUntilDate')->andReturn(null);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1);
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getEmail')->andReturn('new-buyer@acme.com');
        $member->shouldReceive('getId')->andReturn(99);

        $repository = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        // Repository filter is isLive()-only, so it would pass the exhausted
        // code through — simulate that by returning it.
        $repository->shouldReceive('getDomainAuthorizedDiscoverableForSummit')
            ->with($summit)
            ->andReturn([$exhausted]);
        $repository->shouldReceive('getEmailLinkedDiscoverableForSummit')
            ->with($summit, 'new-buyer@acme.com')
            ->andReturn([]);

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
        $healthy->shouldReceive('getAllowedEmailDomains')->andReturn(['@acme.com']);
        $healthy->shouldReceive('matchesEmailDomainViaLookup')->andReturn(true);
        $healthy->shouldReceive('getValidUntilDate')->andReturn(null);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1);
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getEmail')->andReturn('buyer@acme.com');
        $member->shouldReceive('getId')->andReturn(42);

        $repository = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $repository->shouldReceive('getDomainAuthorizedDiscoverableForSummit')
            ->with($summit)
            ->andReturn([$healthy]);
        $repository->shouldReceive('getEmailLinkedDiscoverableForSummit')
            ->with($summit, 'buyer@acme.com')
            ->andReturn([]);

        $service = $this->buildService($repository);
        $result = $service->discoverPromoCodes($summit, $member);

        $this->assertCount(1, $result);
        $this->assertSame('HEALTHY', $result[0]->getCode());
    }

    /**
     * Infinite code (quantity_available == 0) must always pass through the
     * global-exhaustion guard. Pins the `hasQuantityAvailable()` semantics
     * that infinite codes short-circuit to true regardless of quantity_used.
     */
    public function testDiscoverReturnsInfiniteDomainAuthorizedCode(): void
    {
        $infinite = Mockery::mock(DomainAuthorizedSummitRegistrationPromoCode::class);
        $infinite->shouldReceive('getCode')->andReturn('INFINITE');
        // quantity_available == 0 means "unlimited"; hasQuantityAvailable() must return true.
        $infinite->shouldReceive('hasQuantityAvailable')->andReturn(true);
        $infinite->shouldReceive('getQuantityPerAccount')->andReturn(0);
        $infinite->shouldReceive('setRemainingQuantityPerAccount')->with(null)->once();
        $infinite->shouldReceive('getAllowedEmailDomains')->andReturn(['@acme.com']);
        $infinite->shouldReceive('matchesEmailDomainViaLookup')->andReturn(true);
        $infinite->shouldReceive('getValidUntilDate')->andReturn(null);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1);
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getEmail')->andReturn('buyer@acme.com');
        $member->shouldReceive('getId')->andReturn(11);

        $repository = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $repository->shouldReceive('getDomainAuthorizedDiscoverableForSummit')
            ->with($summit)
            ->andReturn([$infinite]);
        $repository->shouldReceive('getEmailLinkedDiscoverableForSummit')
            ->with($summit, 'buyer@acme.com')
            ->andReturn([]);

        $service = $this->buildService($repository);
        $result = $service->discoverPromoCodes($summit, $member);

        $this->assertCount(1, $result);
        $this->assertSame('INFINITE', $result[0]->getCode());
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
        $exhausted->shouldReceive('getAllowedEmailDomains')->andReturn(['@acme.com']);
        $exhausted->shouldReceive('matchesEmailDomainViaLookup')->andReturn(true);
        $exhausted->shouldReceive('getValidUntilDate')->andReturn(null);

        $healthy = Mockery::mock(DomainAuthorizedSummitRegistrationPromoCode::class);
        $healthy->shouldReceive('getCode')->andReturn('HEALTHY');
        $healthy->shouldReceive('hasQuantityAvailable')->andReturn(true);
        $healthy->shouldReceive('getQuantityPerAccount')->andReturn(0);
        $healthy->shouldReceive('setRemainingQuantityPerAccount')->with(null)->once();
        $healthy->shouldReceive('getAllowedEmailDomains')->andReturn(['@acme.com']);
        $healthy->shouldReceive('matchesEmailDomainViaLookup')->andReturn(true);
        $healthy->shouldReceive('getValidUntilDate')->andReturn(null);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1);
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getEmail')->andReturn('buyer@acme.com');
        $member->shouldReceive('getId')->andReturn(7);

        $repository = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $repository->shouldReceive('getDomainAuthorizedDiscoverableForSummit')
            ->with($summit)
            ->andReturn([$exhausted, $healthy]);
        $repository->shouldReceive('getEmailLinkedDiscoverableForSummit')
            ->with($summit, 'buyer@acme.com')
            ->andReturn([]);

        $service = $this->buildService($repository);
        $result = $service->discoverPromoCodes($summit, $member);

        $this->assertCount(1, $result);
        $this->assertSame('HEALTHY', $result[0]->getCode());
    }

    /**
     * Regression: a DA code returned by the repository but NOT matching the
     * member's email domain (via the real AllowedEmailDomainsLookupBuilder +
     * matchesEmailDomainViaLookup trait method) must be excluded from results.
     *
     * Existing tests all mock matchesEmailDomainViaLookup -> true; a regression
     * making the matcher return true regardless of input would silently expose
     * all in-date DA codes to every member. This pins the negative path by
     * exercising the real trait method against a real lookup structure
     * (allowed_email_domains=['@acme.com'] vs email user@other.com).
     */
    public function testDomainNonMatchingDACodeIsExcluded(): void
    {
        // makePartial(): leave matchesEmailDomainViaLookup unmocked so the real
        // trait method runs against the real AllowedEmailDomainsLookup built by
        // buildService()'s injected AllowedEmailDomainsLookupBuilder.
        $nonMatching = Mockery::mock(DomainAuthorizedSummitRegistrationPromoCode::class)->makePartial();
        $nonMatching->shouldReceive('getCode')->andReturn('OTHER_DOMAIN');
        $nonMatching->shouldReceive('hasQuantityAvailable')->andReturn(true);
        $nonMatching->shouldReceive('getQuantityPerAccount')->andReturn(0);
        // setRemainingQuantityPerAccount must NEVER be called — the code is
        // filtered out at the email-match step before reaching the quantity loop.
        $nonMatching->shouldNotReceive('setRemainingQuantityPerAccount');
        $nonMatching->shouldReceive('getAllowedEmailDomains')->andReturn(['@acme.com']);
        // Intentionally NOT mocking matchesEmailDomainViaLookup — real trait
        // method runs and returns false for user@other.com vs @acme.com.
        $nonMatching->shouldReceive('getValidUntilDate')->andReturn(null);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1);
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getEmail')->andReturn('user@other.com');
        $member->shouldReceive('getId')->andReturn(123);

        $repository = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $repository->shouldReceive('getDomainAuthorizedDiscoverableForSummit')
            ->with($summit)
            ->andReturn([$nonMatching]);
        // Email-linked lookup must return [] so the only candidate flows through
        // the DA email-domain match path and gets excluded there.
        $repository->shouldReceive('getEmailLinkedDiscoverableForSummit')
            ->with($summit, 'user@other.com')
            ->andReturn([]);

        $service = $this->buildService($repository);
        $result = $service->discoverPromoCodes($summit, $member);

        $this->assertSame([], $result,
            'DA code whose allowed_email_domains do not match the member email must be excluded');
    }

    /**
     * Regression: a Member whose getEmail() returns whitespace-only must not
     * trigger any repository call and must return an empty result.
     *
     * empty("   ") returns false in PHP, so the early-return guard must trim
     * before testing emptiness. See @smarcet review finding 1 on PR #546.
     * Guard lives at SummitPromoCodeService::discoverPromoCodes L1034:
     *   if (empty(trim($email))) return [];
     */
    public function testDiscoverReturnsEmptyForWhitespaceOnlyEmail(): void
    {
        $summit = Mockery::mock(Summit::class);
        // Guard returns before any Summit method is touched; do not stub getId().

        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getEmail')->andReturn("   \t\n  ");

        $repository = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        // Neither repo lookup may be called — the early-return guard at L1034
        // must short-circuit before any I/O. shouldNotReceive turns any call
        // into an assertion failure.
        $repository->shouldNotReceive('getDomainAuthorizedDiscoverableForSummit');
        $repository->shouldNotReceive('getEmailLinkedDiscoverableForSummit');

        $service = $this->buildService($repository);
        $result = $service->discoverPromoCodes($summit, $member);

        $this->assertSame([], $result,
            'Whitespace-only email must early-return [] without any repository call');
    }
}
