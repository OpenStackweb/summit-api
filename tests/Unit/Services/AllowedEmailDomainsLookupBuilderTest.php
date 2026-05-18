<?php namespace Tests\Unit\Models\Foundation\Summit\Registration\PromoCodes;

use App\Services\Model\AllowedEmailDomainsLookupBuilder;
use models\summit\AllowedEmailDomainsLookup;
use PHPUnit\Framework\TestCase;

class AllowedEmailDomainsLookupBuilderTest extends TestCase
{
    private AllowedEmailDomainsLookupBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new AllowedEmailDomainsLookupBuilder();
    }

    public function testPartitionsAtDomainAndExactEmailIntoExactSet(): void
    {
        $lookup = $this->builder->build(['@acme.com', 'user@acme.com']);
        $this->assertSame(['@acme.com' => true, 'user@acme.com' => true], $lookup->exactSet);
        $this->assertSame([], $lookup->suffixList);
    }

    public function testPartitionsLeadingDotIntoSuffixList(): void
    {
        $lookup = $this->builder->build(['.edu', '.gov']);
        $this->assertSame([], $lookup->exactSet);
        $this->assertSame(['.edu', '.gov'], $lookup->suffixList);
    }

    public function testCaseInsensitiveDedup(): void
    {
        $lookup = $this->builder->build(['@acme.com', '@ACME.com', '@Acme.COM']);
        $this->assertSame(['@acme.com' => true], $lookup->exactSet);
    }

    public function testTrimsWhitespace(): void
    {
        $lookup = $this->builder->build(['  @acme.com  ', "\t.edu\n"]);
        $this->assertArrayHasKey('@acme.com', $lookup->exactSet);
        $this->assertContains('.edu', $lookup->suffixList);
    }

    public function testDropsEmptyPatterns(): void
    {
        $lookup = $this->builder->build(['', '   ', '@acme.com']);
        $this->assertSame(['@acme.com' => true], $lookup->exactSet);
        $this->assertSame([], $lookup->suffixList);
    }

    public function testPatternsHashStableUnderReordering(): void
    {
        $a = $this->builder->build(['@acme.com', '.edu', 'user@beta.io']);
        $b = $this->builder->build(['user@beta.io', '@acme.com', '.edu']);
        $c = $this->builder->build(['.edu', 'user@beta.io', '@acme.com']);
        $this->assertSame($a->patternsHash, $b->patternsHash);
        $this->assertSame($a->patternsHash, $c->patternsHash);
    }

    public function testPatternsHashDiffersOnPatternSetChange(): void
    {
        $a = $this->builder->build(['@acme.com']);
        $b = $this->builder->build(['@acme.com', '.edu']);
        $this->assertNotSame($a->patternsHash, $b->patternsHash);
    }

    public function testRealisticOcpMix(): void
    {
        $patterns = array_map(fn($i) => "@company{$i}.com", range(1, 50));
        $patterns[] = '.edu';
        $lookup = $this->builder->build($patterns);
        $this->assertCount(50, $lookup->exactSet);
        $this->assertSame(['.edu'], $lookup->suffixList);
    }

    public function testEmptyInputProducesEmptyLookup(): void
    {
        $lookup = $this->builder->build([]);
        $this->assertSame([], $lookup->exactSet);
        $this->assertSame([], $lookup->suffixList);
        $this->assertNotEmpty($lookup->patternsHash);
    }
}
