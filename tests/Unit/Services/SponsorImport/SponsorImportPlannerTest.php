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
use App\Services\Model\Imp\SponsorImport\SponsorImportPlanner;
use PHPUnit\Framework\TestCase;

/**
 * Class SponsorImportPlannerTest
 * @package Tests\Unit\Services\SponsorImport
 */
final class SponsorImportPlannerTest extends TestCase
{
    private function planner(): SponsorImportPlanner
    {
        $matcher = new CompanyNameMatcher([
            ['id' => 7, 'name' => 'Acme'],
            ['id' => 12, 'name' => 'NVIDIA'],
            ['id' => 20, 'name' => 'Arm Ltd.'],
            ['id' => 21, 'name' => 'arm, inc'],
            ['id' => 10, 'name' => 'Dup Co'],
            ['id' => 99, 'name' => 'Dup Co'],
            ['id' => 50, 'name' => 'Existing Sponsor'],
        ]);
        $tiers = ['gold' => ['id' => 1, 'name' => 'Gold'], 'silver' => ['id' => 2, 'name' => 'Silver']];
        return new SponsorImportPlanner($matcher, $tiers, [99, 50]);
    }

    private function row(int $n, string $company, string $tier): array
    {
        return ['row' => $n, 'company' => $company, 'tier' => $tier];
    }

    public function testCreatesWithExactMatchAndCanonicalTierNames(): void
    {
        $out = $this->planner()->plan([$this->row(2, 'acme', 'gold|SILVER')]);
        $this->assertSame([
            'row' => 2,
            'company_input' => 'acme',
            'tier_input' => 'gold|SILVER',
            'action' => 'create',
            'company' => ['id' => 7, 'name' => 'Acme', 'match' => 'exact'],
            'tiers' => ['Gold', 'Silver'],
            'sponsor_id' => null,
            'duplicate_of_row' => null,
            'candidates' => [],
            'error' => null,
            'tier_ids' => [1, 2],
        ], $out[0]);
    }

    public function testMissingCompanyOrTierIsError(): void
    {
        $out = $this->planner()->plan([$this->row(2, '', 'Gold'), $this->row(3, 'Acme', '')]);
        $this->assertSame('error', $out[0]['action']);
        $this->assertSame('Company is required.', $out[0]['error']);
        $this->assertNull($out[0]['company']);
        $this->assertSame('error', $out[1]['action']);
        $this->assertSame('Tier is required.', $out[1]['error']);
    }

    public function testUnknownTierListsAvailableTiers(): void
    {
        $out = $this->planner()->plan([$this->row(2, 'Acme', 'Platnum')]);
        $this->assertSame('error', $out[0]['action']);
        $this->assertSame('Unknown tier "Platnum". This show\'s tiers: Gold, Silver.', $out[0]['error']);
    }

    public function testAmbiguousIsErrorWithCandidates(): void
    {
        $out = $this->planner()->plan([$this->row(2, 'Arm Corp', 'Gold')]);
        $this->assertSame('error', $out[0]['action']);
        $this->assertNull($out[0]['company']);
        $this->assertSame(['Arm Ltd.', 'arm, inc'], $out[0]['candidates']);
        $this->assertSame('Several companies match "Arm Corp". Use the exact name of one of: Arm Ltd., arm, inc', $out[0]['error']);
    }

    public function testExistingSponsorIsSkipped(): void
    {
        $out = $this->planner()->plan([$this->row(2, 'Existing Sponsor', 'Gold')]);
        $this->assertSame('skip_existing_sponsor', $out[0]['action']);
        $this->assertSame(50, $out[0]['company']['id']);
    }

    public function testDuplicateBySpellingWithinFile(): void
    {
        $out = $this->planner()->plan([$this->row(2, 'NVIDIA', 'Gold'), $this->row(9, 'Nvidia Corp.', 'Silver')]);
        $this->assertSame('create', $out[0]['action']);
        $this->assertSame(12, $out[0]['company']['id']);
        $this->assertSame('skip_duplicate', $out[1]['action']);
        $this->assertSame(2, $out[1]['duplicate_of_row']);
        $this->assertSame(12, $out[1]['company']['id']);
        $this->assertSame('normalized', $out[1]['company']['match']);
    }

    public function testNewCompanyDuplicateDoesNotCreateTwice(): void
    {
        $out = $this->planner()->plan([$this->row(2, 'Acme Robotics Inc', 'Gold'), $this->row(3, 'ACME Robotics', 'Gold')]);
        $this->assertSame('create', $out[0]['action']);
        $this->assertSame(['id' => null, 'name' => 'Acme Robotics Inc', 'match' => 'new'], $out[0]['company']);
        $this->assertSame('skip_duplicate', $out[1]['action']);
        $this->assertSame(2, $out[1]['duplicate_of_row']);
    }

    public function testErrorRowDoesNotReserveCompany(): void
    {
        $out = $this->planner()->plan([$this->row(2, 'Acme', 'Platnum'), $this->row(3, 'Acme', 'Gold')]);
        $this->assertSame('error', $out[0]['action']);
        $this->assertSame('create', $out[1]['action']);
        $this->assertNull($out[1]['duplicate_of_row']);
    }

    public function testTierCellDedupesAndDropsEmptySegments(): void
    {
        $out = $this->planner()->plan([$this->row(2, 'Acme', 'Gold| |gold'), $this->row(3, 'NVIDIA', '||')]);
        $this->assertSame('create', $out[0]['action']);
        $this->assertSame(['Gold'], $out[0]['tiers']);
        $this->assertSame([1], $out[0]['tier_ids']);
        $this->assertSame('error', $out[1]['action']);
        $this->assertSame('Tier is required.', $out[1]['error']);
    }

    public function testExactDuplicatePrefersCompanyAlreadySponsoring(): void
    {
        $out = $this->planner()->plan([$this->row(2, 'Dup Co', 'Gold')]);
        $this->assertSame('skip_existing_sponsor', $out[0]['action']);
        $this->assertSame(99, $out[0]['company']['id']);
        $this->assertSame('exact', $out[0]['company']['match']);
    }

    public function testSummarizeCountsEachAction(): void
    {
        $rows = $this->planner()->plan([
            $this->row(2, 'Acme', 'Gold'),
            $this->row(3, 'Brand New Co Labs', 'Gold'),
            $this->row(4, 'Existing Sponsor', 'Gold'),
            $this->row(5, 'acme', 'Gold'),
            $this->row(6, '', 'Gold'),
        ]);
        $this->assertSame(
            ['total' => 5, 'sponsors_created' => 2, 'companies_created' => 1, 'skipped' => 2, 'errors' => 1, 'not_attempted' => 0],
            SponsorImportPlanner::summarize($rows, true)['summary']
        );
        $this->assertTrue(SponsorImportPlanner::summarize($rows, true)['dry_run']);
        $this->assertFalse(SponsorImportPlanner::summarize($rows, false)['dry_run']);
    }

    public function testMarkStoppedErrorsRowAndMarksLaterCreatesNotAttempted(): void
    {
        $rows = [
            ['row' => 2, 'action' => 'create', 'error' => null],
            ['row' => 3, 'action' => 'create', 'error' => null],
            ['row' => 4, 'action' => 'error', 'error' => 'Tier is required.'],
            ['row' => 5, 'action' => 'create', 'error' => null],
        ];
        $out = SponsorImportPlanner::markStopped($rows, 1, 'boom');
        $this->assertSame(['create', 'error', 'error', 'not_attempted'], array_column($out, 'action'));
        $this->assertSame('boom', $out[1]['error']);
        $this->assertNull($out[0]['error']);
        $this->assertSame('Tier is required.', $out[2]['error']);
        $this->assertNull($out[3]['error']);

        $reset = SponsorImportPlanner::markStopped($rows, 1, 'reset', 'not_attempted');
        $this->assertSame(['create', 'not_attempted', 'error', 'not_attempted'], array_column($reset, 'action'));
        $this->assertSame('reset', $reset[1]['error']);
    }

    public function testMarkStoppedAlsoStopsDuplicatesOfStoppedRows(): void
    {
        $rows = $this->planner()->plan([
            $this->row(2, 'Acme Robotics Inc', 'Gold'),
            $this->row(3, 'ACME Robotics', 'Gold'),
            $this->row(4, 'Beta Labs', 'Gold'),
            $this->row(5, 'beta labs', 'Gold'),
            $this->row(6, 'NVIDIA', 'Gold'),
        ]);
        $this->assertSame(['create', 'skip_duplicate', 'create', 'skip_duplicate', 'create'], array_column($rows, 'action'));

        $out = SponsorImportPlanner::markStopped($rows, 0, 'boom');

        // row 3 duplicates the stopped row, row 5 duplicates a row that is now not_attempted
        $this->assertSame(['error', 'not_attempted', 'not_attempted', 'not_attempted', 'not_attempted'], array_column($out, 'action'));
        $this->assertNull($out[1]['error']);
        $s = SponsorImportPlanner::summarize($out, false)['summary'];
        $this->assertSame(0, $s['skipped']);
        $this->assertSame(4, $s['not_attempted']);
        $this->assertSame(1, $s['errors']);
    }

    public function testMarkStoppedKeepsDuplicateOfRowCreatedBeforeTheStop(): void
    {
        $rows = $this->planner()->plan([
            $this->row(2, 'Acme Robotics Inc', 'Gold'),
            $this->row(3, 'NVIDIA', 'Gold'),
            $this->row(4, 'ACME Robotics', 'Gold'),
        ]);
        $out = SponsorImportPlanner::markStopped($rows, 1, 'boom');
        $this->assertSame(['create', 'error', 'skip_duplicate'], array_column($out, 'action'));
        $this->assertSame(1, SponsorImportPlanner::summarize($out, false)['summary']['skipped']);
    }

    public function testCompanyCellTrimsNbsp(): void
    {
        $out = $this->planner()->plan([$this->row(2, "\u{00A0}Acme\u{00A0}", 'Gold')]);
        $this->assertSame('create', $out[0]['action']);
        $this->assertSame(['id' => 7, 'name' => 'Acme', 'match' => 'exact'], $out[0]['company']);
    }

    public function testSummarizeCountsCompanyCreatedBeforeFailedSponsor(): void
    {
        $rows = [
            ['row' => 2, 'action' => 'error', 'company' => ['id' => 301, 'name' => 'X', 'match' => 'new']],
            ['row' => 3, 'action' => 'not_attempted', 'company' => ['id' => null, 'name' => 'Y', 'match' => 'new']],
        ];
        $s = SponsorImportPlanner::summarize($rows, false)['summary'];
        $this->assertSame(1, $s['companies_created']);
        $this->assertSame(1, $s['errors']);
        $this->assertSame(1, $s['not_attempted']);
        $this->assertSame(0, $s['sponsors_created']);
    }
}
