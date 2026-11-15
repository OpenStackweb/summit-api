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

use App\Jobs\SponsorServices\PublishSponsorServiceDomainEventsJob;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use LaravelDoctrine\ORM\Facades\Registry;
use libs\utils\ITransactionService;
use models\main\Company;
use models\utils\SilverstripeBaseModel;
use services\model\ISummitSponsorService;
use services\utils\AmbiguousCommitException;
use Tests\InsertSummitTestData;
use Tests\TestCase;

/**
 * Wraps the real transaction service and, on one configured call, either throws instead of
 * running the callback or runs it and then clears the model EntityManager (what a retried
 * transaction on a fresh manager leaves behind: a detached summit and no exception).
 */
final class FaultInjectingTransactionService implements ITransactionService
{
    public $calls = 0;

    private $inner;
    private $on_call;
    private $throw;
    private $clear_after;

    public function __construct(ITransactionService $inner, int $on_call, ?\Throwable $throw, bool $clear_after)
    {
        $this->inner = $inner;
        $this->on_call = $on_call;
        $this->throw = $throw;
        $this->clear_after = $clear_after;
    }

    public function transaction(Closure $callback, int $isolationLevel = 2)
    {
        $this->calls++;
        if ($this->calls === $this->on_call && !is_null($this->throw)) {
            throw $this->throw;
        }
        $result = $this->inner->transaction($callback, $isolationLevel);
        if ($this->calls === $this->on_call && $this->clear_after) {
            Registry::getManager(SilverstripeBaseModel::EntityManager)->clear();
        }
        return $result;
    }
}

/**
 * Class SummitSponsorServiceImportFaultTest
 * Execution failures during a sponsor CSV import stop the run with a per-row report.
 * @package Tests\Unit\Services
 */
class SummitSponsorServiceImportFaultTest extends TestCase
{
    use InsertSummitTestData;

    const ConnectionResetMessage = 'The import stopped because the database connection was reset. Rows below were not imported.';
    const CouldNotConfirmMessage = 'Could not confirm whether this sponsor was saved; the import stopped here. Check the sponsor list before importing this file again.';

    /**
     * @var string[]
     */
    private $names = [];

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
        $this->names = [];
    }

    public function tearDown(): void
    {
        self::clearSummitTestData();
        $em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        foreach ($this->names as $name) {
            foreach ($em->getRepository(Company::class)->findBy(['name' => $name]) as $company) {
                $em->remove($company);
            }
        }
        $em->flush();
        parent::tearDown();
    }

    private function bindFake(int $on_call, ?\Throwable $throw, bool $clear_after): FaultInjectingTransactionService
    {
        $fake = new FaultInjectingTransactionService(app(ITransactionService::class), $on_call, $throw, $clear_after);
        $this->app->instance(ITransactionService::class, $fake);
        // singleton: a service resolved earlier would keep the real transaction service
        $this->app->forgetInstance(ISummitSponsorService::class);
        return $fake;
    }

    private function importThreeNewCompanies(): array
    {
        $csv = "company,tier\n";
        for ($i = 0; $i < 3; $i++) {
            $name = sprintf('Import Fault %s %s', $i, str_random(16));
            $this->names[] = $name;
            $csv .= "\"{$name}\",Default\n";
        }
        $path = sprintf('%s/sponsor_import_%s.csv', sys_get_temp_dir(), str_random(16));
        file_put_contents($path, $csv);
        try {
            $file = new UploadedFile($path, 'sponsors.csv', 'text/csv', null, true);
            return app(ISummitSponsorService::class)->importSponsors(self::$summit, $file, false);
        } finally {
            unlink($path);
        }
    }

    private function assertLaterRowsNotAttempted(array $report): void
    {
        $this->assertSame(['error', 'not_attempted', 'not_attempted'], array_column($report['rows'], 'action'));
        $this->assertSame(1, $report['summary']['errors']);
        $this->assertSame(2, $report['summary']['not_attempted']);
        $this->assertSame(0, $report['summary']['sponsors_created']);
        $this->assertNull($report['rows'][1]['sponsor_id']);
        $this->assertNull($report['rows'][2]['sponsor_id']);
    }

    public function testAmbiguousCommitOnSponsorStopsWithConfirmMessage(): void
    {
        Queue::fake();
        // call 1 = row 1 company, call 2 = row 1 addSponsor
        $fake = $this->bindFake(2, new AmbiguousCommitException('x'), false);

        $report = $this->importThreeNewCompanies();

        $this->assertLaterRowsNotAttempted($report);
        $row = $report['rows'][0];
        $this->assertSame(self::CouldNotConfirmMessage . sprintf(' Company "%s" was created.', $this->names[0]), $row['error']);
        $this->assertGreaterThan(0, $row['company']['id']);
        $this->assertNull($row['sponsor_id']);
        $this->assertSame(1, $report['summary']['companies_created']);
        $this->assertGreaterThan(0, $fake->calls);
    }

    public function testAmbiguousCommitOnCompanyStopsWithCompanyConfirmMessage(): void
    {
        Queue::fake();
        // call 1 = row 1 company
        $fake = $this->bindFake(1, new AmbiguousCommitException('x'), false);

        $report = $this->importThreeNewCompanies();

        $this->assertLaterRowsNotAttempted($report);
        $row = $report['rows'][0];
        $this->assertSame(sprintf(
            'Could not confirm whether company "%s" was saved; the import stopped here. Check the sponsor list before importing this file again.',
            $this->names[0]), $row['error']);
        $this->assertNull($row['company']['id']);
        $this->assertSame(0, $report['summary']['companies_created']);
        $this->assertSame(1, $fake->calls);
        Queue::assertNotPushed(PublishSponsorServiceDomainEventsJob::class);
    }

    public function testDetachAfterCompanyCreationStopsBeforeAddSponsor(): void
    {
        Queue::fake();
        $fake = $this->bindFake(1, null, true);

        $report = $this->importThreeNewCompanies();

        $this->assertLaterRowsNotAttempted($report);
        $row = $report['rows'][0];
        $this->assertSame(self::ConnectionResetMessage . sprintf(' Company "%s" was created.', $this->names[0]), $row['error']);
        $this->assertGreaterThan(0, $row['company']['id']);
        $this->assertSame(1, $report['summary']['companies_created']);
        // addSponsor never ran
        $this->assertSame(1, $fake->calls);
        Queue::assertNotPushed(PublishSponsorServiceDomainEventsJob::class);
    }

    public function testDetachAfterAddSponsorStopsWithConfirmMessage(): void
    {
        Queue::fake();
        $fake = $this->bindFake(2, null, true);

        $report = $this->importThreeNewCompanies();

        $this->assertLaterRowsNotAttempted($report);
        $row = $report['rows'][0];
        $this->assertSame(self::CouldNotConfirmMessage . sprintf(' Company "%s" was created.', $this->names[0]), $row['error']);
        $this->assertNull($row['sponsor_id']);
        $this->assertSame(2, $fake->calls);
    }
}
