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

use App\Models\Foundation\Summit\PromoCodes\PromoCodesConstants;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use models\exceptions\EntityNotFoundException;
use models\summit\SpeakersSummitRegistrationPromoCode;
use models\summit\SponsorSummitRegistrationPromoCode;
use models\summit\SummitRegistrationPromoCode;
use services\model\ISummitPromoCodeService;

/**
 * Class SummitPromoCodeServiceTest
 *
 * Functional counterpart to tests/Unit/Services/SummitPromoCodeServiceDiscoveryTest.php
 * (which covers discoverPromoCodes() with pure Mockery, no real DB). This class exercises
 * addPromoCode() against a real database, needed to prove its two-transaction, partial-commit
 * behavior - not expressible with mocks.
 */
final class SummitPromoCodeServiceTest extends BrowserKitTestCase
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
     * SummitPromoCodeService::addPromoCode() (SummitPromoCodeService.php:221) is actually TWO
     * SEPARATE, independent root transactions, not one root with everything nested inside: the
     * promo code itself commits in the first tx_service->transaction() call (:223-311); the
     * ticket-type-rules loop runs in a SECOND, separate tx_service->transaction() call
     * (:313-326) that only starts once the first has already committed. A failure inside the
     * rules loop (nested addPromoCodeTicketTypeRule() call, EntityNotFoundException at :496-497
     * for a nonexistent ticket_type_id) rolls back the SECOND transaction only - the
     * already-committed promo code from the first transaction survives with zero rules applied.
     * This test proves that partial-commit behavior explicitly, not a full rollback.
     */
    public function testAddPromoCodeSurvivesWhenTicketTypeRuleFails()
    {
        $service = App::make(ISummitPromoCodeService::class);

        $code = 'TEST_PC_' . uniqid();

        $data = [
            'type' => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAlternate,
            'class_name' => SpeakersSummitRegistrationPromoCode::ClassName,
            'code' => $code,
            'description' => 'TEST PROMO CODE',
            'quantity_available' => 10,
            'allowed_ticket_types' => [],
            'badge_features' => [],
            'valid_since_date' => 1649108093,
            'valid_until_date' => 1649109093,
            'ticket_types_rules' => [
                ['ticket_type_id' => 999999999],
            ],
        ];

        try {
            $service->addPromoCode(self::$summit, $data);
            $this->fail('Expected EntityNotFoundException was not thrown');
        } catch (EntityNotFoundException $ex) {
            $this->assertStringContainsString('Ticket Type', $ex->getMessage());
        }

        self::$em->clear();
        self::$summit = self::$summit_repository->find(self::$summit->getId());

        $promo_code = self::$summit->getPromoCodeByCode($code);
        $this->assertNotNull($promo_code);
        $this->assertEquals(0, $promo_code->getAllowedTicketTypes()->count());
    }

    private function buildCsvUpload(string $csv_content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_import_test_');
        file_put_contents($path, $csv_content);
        return new UploadedFile($path, 'import.csv', 'text/csv', null, true);
    }

    /**
     * Volume happy path for importPromoCodes(): every row of a 20-row CSV is a
     * valid SUMMIT_PROMO_CODE, so all 20 codes must exist afterwards.
     */
    public function testImportPromoCodesImportsAllRowsWhenEveryRowIsValid()
    {
        $service = App::make(ISummitPromoCodeService::class);

        $uid = strtoupper(uniqid());
        $codes = [];
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            $code = sprintf('BULK_PC_%02d_%s', $i, $uid);
            $codes[] = $code;
            $rows[] = sprintf('%s,%s,10', $code, SummitRegistrationPromoCode::ClassName);
        }
        $csv = "code,class_name,quantity_available\n" . implode("\n", $rows);

        $service->importPromoCodes(self::$summit, $this->buildCsvUpload($csv));

        self::$em->clear();
        self::$summit = self::$summit_repository->find(self::$summit->getId());

        foreach ($codes as $code) {
            $this->assertNotNull(
                self::$summit->getPromoCodeByCode($code),
                sprintf('Promo code %s should have been imported', $code)
            );
        }
    }

    /**
     * Volume mixed outcome for importPromoCodes(): 20 rows where 15 carry an invalid
     * class_name (addPromoCode() throws, the import's per-row catch logs and skips)
     * interleaved with 5 valid rows. The 5 valid codes must import and the 15
     * failing rows must leave nothing behind - and the import must not throw.
     */
    public function testImportPromoCodesImportsOnlyValidRowsWhenMostRowsFail()
    {
        $service = App::make(ISummitPromoCodeService::class);

        $uid = strtoupper(uniqid());
        $good_codes = [];
        $bad_codes = [];
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            // every 4th row is valid: failures happen before and after each success
            $is_good = ($i % 4 === 0);
            $code = sprintf('BULK_MIX_PC_%02d_%s', $i, $uid);
            if ($is_good) $good_codes[] = $code; else $bad_codes[] = $code;
            $rows[] = sprintf('%s,%s,10', $code, $is_good ? SummitRegistrationPromoCode::ClassName : 'NOT_A_VALID_CLASS_NAME');
        }
        $csv = "code,class_name,quantity_available\n" . implode("\n", $rows);

        // must not throw: failing rows are logged and skipped
        $service->importPromoCodes(self::$summit, $this->buildCsvUpload($csv));

        self::$em->clear();
        self::$summit = self::$summit_repository->find(self::$summit->getId());

        $this->assertCount(5, $good_codes);
        $this->assertCount(15, $bad_codes);
        foreach ($good_codes as $code) {
            $this->assertNotNull(
                self::$summit->getPromoCodeByCode($code),
                sprintf('Valid promo code %s should have been imported', $code)
            );
        }
        foreach ($bad_codes as $code) {
            $this->assertNull(
                self::$summit->getPromoCodeByCode($code),
                sprintf('Invalid-class row %s should not have created a promo code', $code)
            );
        }
    }

    /**
     * Volume happy path for importSponsorPromoCodes(): every row of a 20-row CSV is
     * a valid SPONSOR_PROMO_CODE tied to a seeded sponsor, so all 20 must import.
     */
    public function testImportSponsorPromoCodesImportsAllRowsWhenEveryRowIsValid()
    {
        $service = App::make(ISummitPromoCodeService::class);
        $sponsor_id = self::$sponsors[0]->getId();

        $uid = strtoupper(uniqid());
        $codes = [];
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            $code = sprintf('BULK_SP_%02d_%s', $i, $uid);
            $codes[] = $code;
            $rows[] = sprintf('%s,%s,10,%d', $code, SponsorSummitRegistrationPromoCode::ClassName, $sponsor_id);
        }
        $csv = "code,class_name,quantity_available,sponsor_id\n" . implode("\n", $rows);

        $service->importSponsorPromoCodes(self::$summit, $this->buildCsvUpload($csv));

        self::$em->clear();
        self::$summit = self::$summit_repository->find(self::$summit->getId());

        foreach ($codes as $code) {
            $promo_code = self::$summit->getPromoCodeByCode($code);
            $this->assertNotNull($promo_code, sprintf('Sponsor promo code %s should have been imported', $code));
            $this->assertInstanceOf(SponsorSummitRegistrationPromoCode::class, $promo_code);
        }
    }

    /**
     * Volume mixed outcome for importSponsorPromoCodes(): 20 rows where 15 carry a
     * class_name outside the sponsor allow-list (the import skips them via its own
     * `continue` guard - no exception involved) interleaved with 5 valid rows.
     *
     * NOTE: an empty sponsor_id is NOT a failure lever here - the service happily
     * creates a SponsorSummitRegistrationPromoCode with sponsor = null (verified
     * empirically), which is why the invalid-class guard is used instead.
     */
    public function testImportSponsorPromoCodesImportsOnlyValidRowsWhenMostRowsFail()
    {
        $service = App::make(ISummitPromoCodeService::class);
        $sponsor_id = self::$sponsors[0]->getId();

        $uid = strtoupper(uniqid());
        $good_codes = [];
        $bad_codes = [];
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            $is_good = ($i % 4 === 0);
            $code = sprintf('BULK_MIX_SP_%02d_%s', $i, $uid);
            if ($is_good) $good_codes[] = $code; else $bad_codes[] = $code;
            $rows[] = sprintf('%s,%s,10,%d', $code, $is_good ? SponsorSummitRegistrationPromoCode::ClassName : 'NOT_AN_ALLOWED_SPONSOR_CLASS', $sponsor_id);
        }
        $csv = "code,class_name,quantity_available,sponsor_id\n" . implode("\n", $rows);

        // must not throw: failing rows are logged and skipped
        $service->importSponsorPromoCodes(self::$summit, $this->buildCsvUpload($csv));

        self::$em->clear();
        self::$summit = self::$summit_repository->find(self::$summit->getId());

        $this->assertCount(5, $good_codes);
        $this->assertCount(15, $bad_codes);
        foreach ($good_codes as $code) {
            $this->assertNotNull(
                self::$summit->getPromoCodeByCode($code),
                sprintf('Valid sponsor promo code %s should have been imported', $code)
            );
        }
        foreach ($bad_codes as $code) {
            $this->assertNull(
                self::$summit->getPromoCodeByCode($code),
                sprintf('Sponsorless row %s should not have created a promo code', $code)
            );
        }
    }
}
