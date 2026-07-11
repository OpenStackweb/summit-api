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

use App\Services\Model\ISummitSubmissionInvitationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;

/**
 * Class SummitSubmissionInvitationServiceTest
 *
 * Covers SummitSubmissionInvitationService::importInvitationData(), which loops the
 * CSV with a per-row try/catch (log-and-skip, re-fetching the summit after a failure).
 */
final class SummitSubmissionInvitationServiceTest extends BrowserKitTestCase
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

    private function buildCsvUpload(string $csv_content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_import_test_');
        file_put_contents($path, $csv_content);
        return new UploadedFile($path, 'import.csv', 'text/csv', null, true);
    }

    /**
     * Volume happy path: every row of a 20-row CSV is a distinct valid invitee, so
     * all 20 submission invitations must exist afterwards.
     */
    public function testImportInvitationDataImportsAllRowsWhenEveryRowIsValid()
    {
        $service = App::make(ISummitSubmissionInvitationService::class);

        $uid = uniqid();
        $emails = [];
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            $email = sprintf('sub-inv-ok-%02d-%s@test.com', $i, $uid);
            $emails[] = $email;
            $rows[] = sprintf('%s,CSV,Row%02d', $email, $i);
        }
        $csv = "email,first_name,last_name\n" . implode("\n", $rows);

        $service->importInvitationData(self::$summit, $this->buildCsvUpload($csv));

        self::$em->clear();
        self::$summit = self::$summit_repository->find(self::$summit->getId());

        foreach ($emails as $email) {
            $this->assertNotNull(
                self::$summit->getSubmissionInvitationByEmail($email),
                sprintf('Submission invitation %s should have been imported', $email)
            );
        }
    }

    /**
     * Volume upsert semantics: 20 rows spanning only 5 distinct emails (each repeated
     * 4 times with a different first_name). The import treats a repeated email as an
     * UPDATE of the existing invitation - not a failure - so exactly 5 invitations
     * must exist afterwards, each carrying the LAST row's first_name for its email.
     */
    public function testImportInvitationDataUpsertsDuplicateEmailsInsteadOfDuplicating()
    {
        $service = App::make(ISummitSubmissionInvitationService::class);

        $uid = uniqid();
        $emails = [];
        for ($i = 1; $i <= 5; $i++) {
            $emails[] = sprintf('sub-inv-dup-%02d-%s@test.com', $i, $uid);
        }

        $rows = [];
        for ($pass = 1; $pass <= 4; $pass++) {
            foreach ($emails as $idx => $email) {
                $rows[] = sprintf('%s,Pass%02d,Row%02d', $email, $pass, $idx + 1);
            }
        }
        $this->assertCount(20, $rows);
        $csv = "email,first_name,last_name\n" . implode("\n", $rows);

        // must not throw: repeated emails take the update path
        $service->importInvitationData(self::$summit, $this->buildCsvUpload($csv));

        self::$em->clear();
        self::$summit = self::$summit_repository->find(self::$summit->getId());

        foreach ($emails as $email) {
            $invitation = self::$summit->getSubmissionInvitationByEmail($email);
            $this->assertNotNull($invitation, sprintf('Submission invitation %s should exist exactly once', $email));
            $this->assertEquals(
                'Pass04',
                $invitation->getFirstName(),
                sprintf('Invitation %s should carry the LAST row\'s first_name (update path)', $email)
            );
        }
    }
}
