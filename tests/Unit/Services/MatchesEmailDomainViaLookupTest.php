<?php namespace Tests\Unit\Services;

use App\Services\Model\AllowedEmailDomainsLookupBuilder;
use models\summit\DomainAuthorizedSummitRegistrationPromoCode;
use Mockery;
use PHPUnit\Framework\TestCase;

class MatchesEmailDomainViaLookupTest extends TestCase
{
    private AllowedEmailDomainsLookupBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new AllowedEmailDomainsLookupBuilder();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeCodeWithPatterns(array $patterns): DomainAuthorizedSummitRegistrationPromoCode
    {
        $code = new DomainAuthorizedSummitRegistrationPromoCode();
        $code->setAllowedEmailDomains($patterns);
        return $code;
    }

    private function assertParity(array $patterns, string $email): void
    {
        $code = $this->makeCodeWithPatterns($patterns);
        $lookup = $this->builder->build($patterns);

        $legacy   = $code->matchesEmailDomain($email);
        $viaIndex = $code->matchesEmailDomainViaLookup($email, $lookup);

        $this->assertSame(
            $legacy,
            $viaIndex,
            sprintf(
                'Parity break: patterns=%s email=%s legacy=%s viaLookup=%s',
                json_encode($patterns), $email,
                var_export($legacy, true), var_export($viaIndex, true)
            )
        );
    }

    public function testExactAtDomainMatch(): void
    {
        $this->assertParity(['@acme.com'], 'user@acme.com');
    }

    public function testExactAtDomainMiss(): void
    {
        $this->assertParity(['@acme.com'], 'user@beta.io');
    }

    public function testCasingInsensitive(): void
    {
        $this->assertParity(['@ACME.com'], 'user@acme.com');
        $this->assertParity(['@acme.com'], 'User@ACME.COM');
    }

    public function testSuffixTld(): void
    {
        $this->assertParity(['.edu'], 'student@harvard.edu');
        $this->assertParity(['.edu'], 'user@acme.com');
    }

    public function testSuffixMultiLabel(): void
    {
        $this->assertParity(['.mit.edu'], 'user@cs.mit.edu');
        $this->assertParity(['.mit.edu'], 'user@harvard.edu');
    }

    public function testExactEmail(): void
    {
        $this->assertParity(['user@acme.com'], 'user@acme.com');
        $this->assertParity(['user@acme.com'], 'other@acme.com');
    }

    public function testMixedPatterns(): void
    {
        $patterns = ['@acme.com', '.edu', 'vip@beta.io'];
        $this->assertParity($patterns, 'employee@acme.com');
        $this->assertParity($patterns, 'student@harvard.edu');
        $this->assertParity($patterns, 'vip@beta.io');
        $this->assertParity($patterns, 'random@elsewhere.com');
    }

    public function testEmptyPatternsMeansNoRestriction(): void
    {
        $this->assertParity([], 'anyone@anywhere.com');
    }

    public function testMalformedEmailReturnsFalse(): void
    {
        $code = $this->makeCodeWithPatterns(['@acme.com']);
        $lookup = $this->builder->build(['@acme.com']);

        $this->assertFalse($code->matchesEmailDomainViaLookup('', $lookup));
        $this->assertFalse($code->matchesEmailDomainViaLookup('no-at-sign', $lookup));
    }
}
