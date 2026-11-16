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

use App\Events\SponsorServices\SponsorDomainEvents;
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
 * Wraps the real transaction service and, on one configured call, does one of:
 * - throw instead of running the callback (clearing the EntityManager, as a failed root transaction does);
 * - run it and then clear the EntityManager;
 * - reset the EntityManager first and then run it (what a retry after a connection reset looks like).
 */
final class FaultInjectingTransactionService implements ITransactionService
{
    const Throw = 'throw';
    const ClearAfter = 'clear_after';
    const ResetBefore = 'reset_before';

    public $calls = 0;

    private $inner;
    private $on_call;
    private $mode;
    private $throw;

    public function __construct(ITransactionService $inner, int $on_call, string $mode, ?\Throwable $throw = null)
    {
        $this->inner = $inner;
        $this->on_call = $on_call;
        $this->mode = $mode;
        $this->throw = $throw;
    }

    public function transaction(Closure $callback, int $isolationLevel = 2)
    {
        $this->calls++;
        $hit = $this->calls === $this->on_call;
        if ($hit && $this->mode === self::Throw) {
            Registry::getManager(SilverstripeBaseModel::EntityManager)->clear();
            throw $this->throw;
        }
        if ($hit && $this->mode === self::ResetBefore) {
            Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }
        $result = $this->inner->transaction($callback, $isolationLevel);
        if ($hit && $this->mode === self::ClearAfter) {
            Registry::getManager(SilverstripeBaseModel::EntityManager)->clear();
        }
        return $result;
    }
}

/**
 * Class SummitSponsorServiceImportFaultTest
 * Write failures during a sponsor CSV import: a failed row is skipped, an unknown commit outcome stops the run.
 * @package Tests\Unit\Services
 */
class SummitSponsorServiceImportFaultTest extends TestCase
{
    use InsertSummitTestData;

    const CouldNotCreateMessage = 'Could not create this sponsor.';
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
        // a ResetBefore test leaves self::$em pointing at the discarded manager
        self::$em = Registry::getManager(SilverstripeBaseModel::EntityManager);
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

    private function bindFake(int $on_call, string $mode, ?\Throwable $throw = null): FaultInjectingTransactionService
    {
        $fake = new FaultInjectingTransactionService(app(ITransactionService::class), $on_call, $mode, $throw);
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

    /**
     * @return int[] ids carried by the queued SponsorCreated payloads
     */
    private function publishedSponsorIds(): array
    {
        $jobs = Queue::pushed(PublishSponsorServiceDomainEventsJob::class, function ($job) {
            return $job->getEventType() === SponsorDomainEvents::SponsorCreated;
        })->all();
        return array_values(array_map(static fn($job) => $job->getPayload()['id'], $jobs));
    }

    private function assertAllRowsCreated(array $report): void
    {
        $this->assertSame(['create', 'create', 'create'], array_column($report['rows'], 'action'));
        $this->assertSame(3, $report['summary']['sponsors_created']);
        $sponsor_ids = array_column($report['rows'], 'sponsor_id');
        foreach ($sponsor_ids as $sponsor_id) {
            $this->assertGreaterThan(0, $sponsor_id);
        }
        $this->assertSame($sponsor_ids, $this->publishedSponsorIds());
    }

    public function testAmbiguousCommitOnSponsorStopsWithConfirmMessage(): void
    {
        Queue::fake();
        // call 1 = row 1 company, call 2 = row 1 addSponsor
        $fake = $this->bindFake(2, FaultInjectingTransactionService::Throw, new AmbiguousCommitException('x'));

        $report = $this->importThreeNewCompanies();

        $this->assertSame(['error', 'not_attempted', 'not_attempted'], array_column($report['rows'], 'action'));
        $row = $report['rows'][0];
        $this->assertSame(self::CouldNotConfirmMessage . sprintf(' Company "%s" was created.', $this->names[0]), $row['error']);
        $this->assertGreaterThan(0, $row['company']['id']);
        $this->assertNull($row['sponsor_id']);
        $this->assertSame(1, $report['summary']['companies_created']);
        $this->assertSame(2, $report['summary']['not_attempted']);
        $this->assertSame(2, $fake->calls);
    }

    public function testWriteFailureOnSponsorSkipsThatRowAndImportsTheRest(): void
    {
        Queue::fake();
        // call 1 = row 1 company, call 2 = row 1 addSponsor
        $fake = $this->bindFake(2, FaultInjectingTransactionService::Throw, new \RuntimeException('x'));

        $report = $this->importThreeNewCompanies();

        $this->assertSame(['error', 'create', 'create'], array_column($report['rows'], 'action'));
        $this->assertSame(self::CouldNotCreateMessage . sprintf(' Company "%s" was created.', $this->names[0]), $report['rows'][0]['error']);
        $this->assertNull($report['rows'][0]['sponsor_id']);
        $this->assertGreaterThan(0, $report['rows'][1]['sponsor_id']);
        $this->assertGreaterThan(0, $report['rows'][2]['sponsor_id']);
        $this->assertSame(['sponsors_created' => 2, 'companies_created' => 3, 'errors' => 1, 'not_attempted' => 0],
            array_intersect_key($report['summary'], array_flip(['sponsors_created', 'companies_created', 'errors', 'not_attempted'])));
        $this->assertSame(6, $fake->calls);
    }

    public function testAmbiguousCommitOnCompanyStopsWithCompanyConfirmMessage(): void
    {
        Queue::fake();
        // call 1 = row 1 company
        $fake = $this->bindFake(1, FaultInjectingTransactionService::Throw, new AmbiguousCommitException('x'));

        $report = $this->importThreeNewCompanies();

        $this->assertSame(['error', 'not_attempted', 'not_attempted'], array_column($report['rows'], 'action'));
        $this->assertSame(sprintf(
            'Could not confirm whether company "%s" was saved; the import stopped here. Check the sponsor list before importing this file again.',
            $this->names[0]), $report['rows'][0]['error']);
        $this->assertNull($report['rows'][0]['company']['id']);
        $this->assertSame(0, $report['summary']['companies_created']);
        $this->assertSame(1, $fake->calls);
        Queue::assertNotPushed(PublishSponsorServiceDomainEventsJob::class);
    }

    public function testClearedEntityManagerAfterARowDoesNotAffectLaterRows(): void
    {
        Queue::fake();
        // clears after row 1's addSponsor, detaching the request's summit
        $this->bindFake(2, FaultInjectingTransactionService::ClearAfter);

        $this->assertAllRowsCreated($this->importThreeNewCompanies());
    }

    public function testRetryOnAFreshEntityManagerPersistsTheSponsorAndPublishesItsId(): void
    {
        Queue::fake();
        // resets before row 1's addSponsor callback runs, as a retry after a connection reset does
        $this->bindFake(2, FaultInjectingTransactionService::ResetBefore);

        $this->assertAllRowsCreated($this->importThreeNewCompanies());
    }
}
