<?php namespace Tests\Unit\Services\SponsorImport;

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
use App\Services\Model\Imp\SponsorImport\CompanyNameMatcher;
use PHPUnit\Framework\TestCase;

/**
 * Class CompanyNameMatcherTest
 * @package Tests\Unit\Services\SponsorImport
 */
final class CompanyNameMatcherTest extends TestCase
{
    public function testKeyStripsPunctuationAndRepeatedSuffixes(): void
    {
        $this->assertSame('super micro computer', CompanyNameMatcher::key('Super Micro Computer, Inc.'));
        $this->assertSame('foo', CompanyNameMatcher::key('Foo Co., Ltd.'));
        $this->assertSame('te connectivity', CompanyNameMatcher::key('  TE   Connectivity Corporation '));
        $this->assertSame('co', CompanyNameMatcher::key('Co'));          // never strips the last word
        $this->assertSame('', CompanyNameMatcher::key(' -- '));
        $this->assertSame('münchen', CompanyNameMatcher::key('München GmbH'));
        $this->assertSame('münchen', CompanyNameMatcher::key('MÜNCHEN GMBH'));
    }

    public function testKeyKeepsCombiningMarks(): void
    {
        $this->assertNotSame(CompanyNameMatcher::key('कला'), CompanyNameMatcher::key('कली'));
    }

    public function testExactKeyIgnoresNbspPaddingOnStoredNames(): void
    {
        $m = new CompanyNameMatcher([['id' => 1, 'name' => "Acme\u{00A0}"], ['id' => 2, 'name' => 'Acme Inc']]);
        $this->assertSame(['match' => 'exact', 'id' => 1, 'name' => "Acme\u{00A0}", 'ids' => [1]], $m->resolve('Acme'));
    }

    public function testExactWinsOverNormalizedAndLowestIdBreaksTies(): void
    {
        $m = new CompanyNameMatcher([
            ['id' => 30, 'name' => 'Microsoft'], ['id' => 10, 'name' => 'MICROSOFT'],
            ['id' => 20, 'name' => 'Microsoft Corporation'],
        ]);
        $this->assertSame(['match' => 'exact', 'id' => 10, 'name' => 'MICROSOFT', 'ids' => [10, 30]], $m->resolve(' microsoft '));
    }

    public function testNfcAndNullNames(): void
    {
        $m = new CompanyNameMatcher([['id' => 1, 'name' => "Cafe\u{0301} Corp"], ['id' => 2, 'name' => null], ['id' => 3, 'name' => '  ']]);
        // precomposed input matches decomposed stored name (exact)
        $this->assertSame(['match' => 'exact', 'id' => 1, 'name' => "Cafe\u{0301} Corp", 'ids' => [1]], $m->resolve("Café Corp"));
        $r = new CompanyNameMatcher([['id' => 4, 'name' => "Café Corp"]]);
        $this->assertSame(['match' => 'exact', 'id' => 4, 'name' => "Café Corp", 'ids' => [4]], $r->resolve("Cafe\u{0301} Corp"));
        $this->assertSame(['match' => 'none'], $m->resolve(''));
    }

    public function testNormalizedSingleCandidate(): void
    {
        $m = new CompanyNameMatcher([['id' => 5, 'name' => 'Wiwynn Corporation']]);
        $this->assertSame(['match' => 'normalized', 'id' => 5, 'name' => 'Wiwynn Corporation'], $m->resolve('Wiwynn'));
    }

    public function testNormalizedSeveralCandidatesIsAmbiguousSorted(): void
    {
        $m = new CompanyNameMatcher([['id' => 2, 'name' => 'arm, inc'], ['id' => 1, 'name' => 'Arm Ltd.']]);
        $this->assertSame(['match' => 'ambiguous', 'candidates' => ['Arm Ltd.', 'arm, inc']], $m->resolve('Arm Corp'));
    }

    public function testNoMatchAndEmptyKeyNeverNormalizes(): void
    {
        $m = new CompanyNameMatcher([['id' => 1, 'name' => '--']]);
        $this->assertSame(['match' => 'none'], $m->resolve('Acme'));
        $this->assertSame(['match' => 'none'], $m->resolve('!!'));      // key '' must not match '--'
    }

    public function testDuplicateInputRowsAreDedupedById(): void
    {
        $m = new CompanyNameMatcher([['id' => 5, 'name' => 'Wiwynn Corporation'], ['id' => 5, 'name' => 'Wiwynn Corporation']]);
        $this->assertSame(['match' => 'normalized', 'id' => 5, 'name' => 'Wiwynn Corporation'], $m->resolve('Wiwynn'));
        $this->assertSame(['match' => 'exact', 'id' => 5, 'name' => 'Wiwynn Corporation', 'ids' => [5]], $m->resolve('wiwynn corporation'));
    }
}
