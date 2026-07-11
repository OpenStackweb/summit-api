<?php namespace Tests;
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

use App\Services\Model\ISummitSelectionPlanService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

/**
 * Class SummitSelectionPlanServiceTest
 *
 * Covers SummitSelectionPlanService::processAllowedMemberData(). Unlike the other
 * CSV importers, this loop has NO per-row try/catch: rows that cannot be imported
 * are SKIPPED by the row callback's own guards (empty email, already-present email)
 * rather than failing - an actual exception would abort the remaining rows and leave
 * the file undeleted.
 */
final class SummitSelectionPlanServiceTest extends BrowserKitTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    /**
     * Volume happy path: every row of a 20-row CSV is a new, valid email, so all 20
     * allowed members must exist afterwards and the source file must be deleted.
     */
    public function testProcessAllowedMemberDataImportsAllRowsWhenEveryRowIsValid()
    {
        Storage::fake('swift');

        $service = App::make(ISummitSelectionPlanService::class);
        $summit_id = self::$summit->getId();
        $selection_plan_id = self::$default_selection_plan->getId();

        $uid = uniqid();
        $emails = [];
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            $email = sprintf('allowed-ok-%02d-%s@test.com', $i, $uid);
            $emails[] = $email;
            $rows[] = $email;
        }
        $csv_content = "email\n" . implode("\n", $rows);

        $filename = 'allowed_members_all_valid.csv';
        $path = "tmp/selection_plans_allowed_members/{$filename}";
        Storage::disk('swift')->put($path, $csv_content);

        $service->processAllowedMemberData($summit_id, $selection_plan_id, $filename);

        self::$em->clear();
        $summit = self::$summit_repository->find($summit_id);
        $selection_plan = $summit->getSelectionPlanById($selection_plan_id);

        foreach ($emails as $email) {
            $this->assertTrue(
                $selection_plan->containsMember($email),
                sprintf('Allowed member %s should have been imported', $email)
            );
        }
        $this->assertFalse(
            Storage::disk('swift')->exists($path),
            'A fully-processed import must delete its source file'
        );
    }

    /**
     * Volume mixed outcome: 20 rows where 15 are not importable - 10 emails already
     * present on the selection plan (skipped by the containsMember guard) and 5 empty
     * emails (skipped by the empty guard) - interleaved with 5 new valid emails. The
     * 5 new members must import, no duplicates may be created, the method must not
     * throw, and the file is still deleted (skips are not failures).
     */
    public function testProcessAllowedMemberDataImportsOnlyValidRowsWhenMostRowsAreSkipped()
    {
        Storage::fake('swift');

        $service = App::make(ISummitSelectionPlanService::class);
        $summit_id = self::$summit->getId();
        $selection_plan_id = self::$default_selection_plan->getId();

        $uid = uniqid();

        // pre-load 10 emails so their CSV rows hit the already-present guard
        $existing_emails = [];
        for ($i = 1; $i <= 10; $i++) {
            $email = sprintf('allowed-dup-%02d-%s@test.com', $i, $uid);
            $existing_emails[] = $email;
            self::$default_selection_plan->addAllowedMember($email);
        }
        self::$em->persist(self::$default_selection_plan);
        self::$em->flush();
        $initial_count = self::$default_selection_plan->getAllowedMembers()->count();

        $new_emails = [];
        for ($i = 1; $i <= 5; $i++) {
            $new_emails[] = sprintf('allowed-new-%02d-%s@test.com', $i, $uid);
        }

        // interleave: 10 duplicates + 5 empty emails + 5 new, spread across the file
        $rows = [];
        foreach ($existing_emails as $idx => $email) {
            $rows[] = $email;
            if ($idx % 2 === 0) $rows[] = ''; // 5 empty-email rows
        }
        foreach ($new_emails as $email) {
            $rows[] = $email;
        }
        $this->assertCount(20, $rows);
        $csv_content = "email\n" . implode("\n", $rows);

        $filename = 'allowed_members_mixed.csv';
        $path = "tmp/selection_plans_allowed_members/{$filename}";
        Storage::disk('swift')->put($path, $csv_content);

        // must not throw: non-importable rows are skipped by the row guards
        $service->processAllowedMemberData($summit_id, $selection_plan_id, $filename);

        self::$em->clear();
        $summit = self::$summit_repository->find($summit_id);
        $selection_plan = $summit->getSelectionPlanById($selection_plan_id);

        foreach ($new_emails as $email) {
            $this->assertTrue(
                $selection_plan->containsMember($email),
                sprintf('New allowed member %s should have been imported', $email)
            );
        }
        $this->assertEquals(
            $initial_count + 5,
            $selection_plan->getAllowedMembers()->count(),
            'Only the 5 new emails should have been added - no duplicates, no empties'
        );
        $this->assertFalse(
            Storage::disk('swift')->exists($path),
            'Skipped rows are not failures - the file must still be deleted'
        );
    }
}
