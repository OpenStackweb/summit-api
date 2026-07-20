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

use App\Services\Model\ISummitRegistrationInvitationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use models\summit\SummitTicketType;

/**
 * Class SummitRegistrationInvitationServiceTest
 *
 * Covers SummitRegistrationInvitationService::importInvitationData(), which loops the
 * CSV with a per-row try/catch (log-and-skip, re-fetching the summit after a failure).
 */
final class SummitRegistrationInvitationServiceTest extends BrowserKitTestCase
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
     * Volume happy path: every row of a 20-row CSV is valid, so all 20 invitations
     * must exist afterwards.
     */
    public function testImportInvitationDataImportsAllRowsWhenEveryRowIsValid()
    {
        $service = App::make(ISummitRegistrationInvitationService::class);

        $uid = uniqid();
        $emails = [];
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            $email = sprintf('reg-inv-ok-%02d-%s@test.com', $i, $uid);
            $emails[] = $email;
            $rows[] = sprintf('%s,CSV,Row%02d', $email, $i);
        }
        $csv = "email,first_name,last_name\n" . implode("\n", $rows);

        $service->importInvitationData(self::$summit, $this->buildCsvUpload($csv));

        self::$em->clear();
        self::$summit = self::$summit_repository->find(self::$summit->getId());

        foreach ($emails as $email) {
            $this->assertNotNull(
                self::$summit->getSummitRegistrationInvitationByEmail($email),
                sprintf('Invitation %s should have been imported', $email)
            );
        }
    }

    /**
     * Volume mixed outcome: 20 rows where 15 carry a nonexistent allowed ticket type
     * id (add() throws ValidationException, the per-row catch logs and skips)
     * interleaved with 5 valid rows. The 5 valid invitations must import and the 15
     * failing rows must leave nothing behind - and the import must not throw.
     */
    public function testImportInvitationDataImportsOnlyValidRowsWhenMostRowsFail()
    {
        $service = App::make(ISummitRegistrationInvitationService::class);

        // invitations only accept ticket types with the "With Invitation" audience
        // (SummitRegistrationInvitation::addTicketType throws for any other audience)
        $invitation_ticket_type = new SummitTicketType();
        $invitation_ticket_type->setName('INVITATION ONLY TICKET TYPE');
        $invitation_ticket_type->setCost(100);
        $invitation_ticket_type->setCurrency('USD');
        $invitation_ticket_type->setQuantity2Sell(50);
        $invitation_ticket_type->setAudience(SummitTicketType::Audience_With_Invitation);
        self::$summit->addTicketType($invitation_ticket_type);
        self::$em->persist(self::$summit);
        self::$em->flush();
        $valid_ticket_type_id = $invitation_ticket_type->getId();

        $uid = uniqid();
        $good_emails = [];
        $bad_emails = [];
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            // every 4th row is valid: failures happen before and after each success
            $is_good = ($i % 4 === 0);
            $email = sprintf('reg-inv-mix-%02d-%s@test.com', $i, $uid);
            if ($is_good) $good_emails[] = $email; else $bad_emails[] = $email;
            $rows[] = sprintf('%s,CSV,Row%02d,%s', $email, $i, $is_good ? $valid_ticket_type_id : '999999999');
        }
        $csv = "email,first_name,last_name,allowed_ticket_types\n" . implode("\n", $rows);

        // must not throw: failing rows are logged and skipped
        $service->importInvitationData(self::$summit, $this->buildCsvUpload($csv));

        self::$em->clear();
        self::$summit = self::$summit_repository->find(self::$summit->getId());

        $this->assertCount(5, $good_emails);
        $this->assertCount(15, $bad_emails);
        foreach ($good_emails as $email) {
            $this->assertNotNull(
                self::$summit->getSummitRegistrationInvitationByEmail($email),
                sprintf('Valid row invitation %s should have been imported', $email)
            );
        }
        foreach ($bad_emails as $email) {
            $this->assertNull(
                self::$summit->getSummitRegistrationInvitationByEmail($email),
                sprintf('Failing row invitation %s should not exist', $email)
            );
        }
    }
}
