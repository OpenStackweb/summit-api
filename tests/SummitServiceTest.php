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

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use services\model\ISummitService;

/**
 * Class SummitServiceTest
 */
final class SummitServiceTest extends BrowserKitTestCase
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
     * SummitService::processRegistrationCompaniesData() (SummitService.php:3586) wraps each
     * CSV row's tx_service->transaction() call in a LOCAL try/catch (:3608-3646), outside the
     * transaction's own closure - unlike SummitOrderService::processTicketData(), which has no
     * per-row catch at all. Prove one bad row (a company already attached to the summit,
     * triggering addCompany()'s ValidationException at SummitService.php:3334) does NOT stop a
     * later, valid row from being processed.
     */
    public function testProcessRegistrationCompaniesDataSkipsFailingRowButProcessesLaterRows()
    {
        Storage::fake('swift');

        $service = App::make(ISummitService::class);
        $summit_id = self::$summit->getId();

        // pre-attach an existing company to the summit so the CSV row for it
        // triggers addCompany()'s "already has a company" ValidationException
        $existing_company = self::$companies[0];
        $service->addCompany($summit_id, $existing_company->getId());

        $new_company_name = 'New Row Company ' . uniqid();

        $csv_content = <<<CSV
name,
{$existing_company->getName()},
{$new_company_name},
CSV;

        $filename = 'registration_companies_isolation_test.csv';
        Storage::disk('swift')->put("tmp/registration_companies_import/{$filename}", $csv_content);

        // processRegistrationCompaniesData() catches each row's transaction failure
        // locally, so it returns normally even though the first row fails.
        $service->processRegistrationCompaniesData($summit_id, $filename);

        self::$em->clear();
        self::$summit = self::$summit_repository->find($summit_id);

        $new_company_on_summit = self::$summit->getRegistrationCompanyByName($new_company_name);
        $this->assertNotNull($new_company_on_summit);
    }
}
