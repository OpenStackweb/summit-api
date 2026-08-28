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

use App\Jobs\Emails\IMailTemplatesConstants;
use App\Jobs\Emails\PresentationSubmissions\PresentationSubmissionReopenedEmail;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlow;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlowType;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailFlowType;
use Database\Migrations\Model\Version20260824090000;
use Database\Migrations\Model\Version20260824090001;
use Doctrine\DBAL\Schema\Schema;
use models\summit\Presentation;
use Psr\Log\NullLogger;
use ReflectionProperty;

/**
 * Class PresentationSubmissionReopenedEmailTest
 *
 * Constructing this job needs the full container -- AbstractSummitEmailJob::__construct()
 * resolves ISummitRepository via App::make() internally (service locator, not constructor
 * injection) -- so this extends ProtectedApiTestCase like the other reopen-feature tests,
 * rather than a bare PHPUnit\Framework\TestCase.
 *
 * @package Tests
 */
class PresentationSubmissionReopenedEmailTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    /**
     * @var Presentation
     */
    static $presentation;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();

        self::$presentation = new Presentation();
        self::$presentation->setTitle("REOPEN NOTIFICATION EMAIL TEST");
        self::$presentation->setType(self::$defaultPresentationType);
        self::$presentation->setSelectionPlan(self::$default_selection_plan);
        self::$presentation->setCreatedBy(self::$member);
        self::$presentation->setCategory(self::$defaultTrack);
        self::$summit->addEvent(self::$presentation);

        self::$summit->setTimeZoneId("America/Chicago");
        self::$default_selection_plan->setIsEnabled(true);
        self::$default_selection_plan->setSubmissionBeginDate(
            (new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('P10D'))
        );
        self::$default_selection_plan->setSubmissionEndDate(
            (new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('P1D'))
        );

        // live grant: 24h window from now
        self::$presentation->reopenSubmission(24, self::$member);

        self::$em->persist(self::$summit);
        self::$em->flush();

        $this->ensureEmailEventFlowTypeRegistered();
    }

    /**
     * The job's constructor refuses to build without a resolvable template identifier
     * (AbstractEmailJob::__construct throws on an empty one), which requires a
     * SummitEmailEventFlowType row for this event slug attached to the existing
     * "Presentation Submissions" SummitEmailFlowType. That row is normally created once by
     * Task 5's migration (deployed envs) or Task 6's SummitEmailFlowTypeSeeder entry (fresh
     * installs / this suite's one-time process-wide seed in BrowserKitTestCase::prepareForTests()).
     * Guarded so this test is self-contained regardless of whether those tasks have landed yet,
     * and a no-op once they have (the seeder/migration already created the row).
     */
    private function ensureEmailEventFlowTypeRegistered(): void
    {
        $repo = self::$em->getRepository(SummitEmailEventFlowType::class);
        $existing = $repo->findOneBy(['slug' => PresentationSubmissionReopenedEmail::EVENT_SLUG]);
        if (!is_null($existing)) return;

        $flow = self::$em->getRepository(SummitEmailFlowType::class)->findOneBy(['name' => 'Presentation Submissions']);
        $this->assertNotNull($flow, '"Presentation Submissions" SummitEmailFlowType must already be seeded by prepareForTests().');

        $event_type = new SummitEmailEventFlowType();
        $event_type->setFlow($flow);
        $event_type->setName(PresentationSubmissionReopenedEmail::EVENT_NAME);
        $event_type->setSlug(PresentationSubmissionReopenedEmail::EVENT_SLUG);
        $event_type->setDefaultEmailTemplate(PresentationSubmissionReopenedEmail::DEFAULT_TEMPLATE);

        self::$em->persist($event_type);
        self::$em->flush();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function readPayload(PresentationSubmissionReopenedEmail $job): array
    {
        $prop = new ReflectionProperty($job, 'payload');
        $prop->setAccessible(true);
        return $prop->getValue($job);
    }

    public function testPayloadContainsAllKeysWithLocalTimeZone()
    {
        $job = new PresentationSubmissionReopenedEmail(self::$presentation, 'speaker@example.com', 'Grace Hopper');
        $payload = $this->readPayload($job);

        $this->assertNotEmpty($payload[IMailTemplatesConstants::summit_slug]);
        $this->assertNotEmpty($payload[IMailTemplatesConstants::selection_plan_id]);
        $this->assertNotEmpty($payload[IMailTemplatesConstants::presentation_id]);
        $this->assertEquals('Grace Hopper', $payload[IMailTemplatesConstants::full_name]);
        $this->assertEquals('REOPEN NOTIFICATION EMAIL TEST', $payload[IMailTemplatesConstants::presentation_title]);
        $this->assertEquals(self::$default_selection_plan->getName(), $payload[IMailTemplatesConstants::selection_plan_name]);
        $this->assertEquals(self::$summit->getRawSlug(), $payload[IMailTemplatesConstants::summit_slug]);
        $this->assertEquals(self::$default_selection_plan->getId(), $payload[IMailTemplatesConstants::selection_plan_id]);
        $this->assertEquals(self::$presentation->getId(), $payload[IMailTemplatesConstants::presentation_id]);
        $this->assertNotEmpty($payload[IMailTemplatesConstants::support_email]);

        // date + time + zone label, not date-only
        $until = self::$presentation->getSubmissionReopenedUntil();
        $local = self::$default_selection_plan->convertDateFromUTC2TimeZone($until);
        $expected = $local->format('F d, Y g:i a') . ' ' . self::$summit->getTimeZoneLabel();
        $this->assertEquals($expected, $payload[IMailTemplatesConstants::until_date]);
    }

    public function testUnparseableTimeZoneFallsBackToUtcWithoutThrow()
    {
        self::$summit->setTimeZoneId('Not/A/Real/Zone');
        self::$em->persist(self::$summit);
        self::$em->flush();

        $job = new PresentationSubmissionReopenedEmail(self::$presentation, 'speaker@example.com', 'Grace Hopper');
        $payload = $this->readPayload($job);

        $until = self::$presentation->getSubmissionReopenedUntil();
        $expected = $until->format('F d, Y g:i a') . ' UTC';
        $this->assertEquals($expected, $payload[IMailTemplatesConstants::until_date]);
    }

    /**
     * The deployed-database registration path must be re-run-safe. SummitEmailFlowTypeSeeder::
     * createEventsTypes() inserts unconditionally and SummitEmailEventFlowType.Slug carries no
     * unique index, so an unguarded migration executed twice (migrations:execute --up, a restored
     * doctrine_migration_versions table) leaves two rows for the slug and the Email Flow Events
     * page lists the event twice. Starts from the deployed precondition (no row for the slug), so
     * it also proves the first run inserts -- a guard that always returns would fail here too.
     */
    public function testModelMigrationInsertsOnceAndDoesNotDuplicateTheEventTypeOnRerun()
    {
        $repo = self::$em->getRepository(SummitEmailEventFlowType::class);
        $flow = self::$em->getRepository(SummitEmailFlowType::class)->findOneBy(['name' => 'Presentation Submissions']);
        $this->assertNotNull($flow);

        // orphanRemoval on SummitEmailFlowType::$flow_event_types deletes the row through the ORM,
        // keeping the identity map consistent (a DQL delete would leave a stale managed entity).
        foreach ($repo->findBy(['slug' => PresentationSubmissionReopenedEmail::EVENT_SLUG]) as $existing)
            $flow->removeFlowEventType($existing);
        self::$em->flush();
        $this->assertCount(0, $repo->findBy(['slug' => PresentationSubmissionReopenedEmail::EVENT_SLUG]));

        // migration classes are discovered by Doctrine's finder, not composer's autoloader
        require_once base_path('database/migrations/model/Version20260824090000.php');
        $migration = new Version20260824090000(self::$em->getConnection(), new NullLogger());
        $migration->up(new Schema());
        $this->assertCount(1, $repo->findBy(['slug' => PresentationSubmissionReopenedEmail::EVENT_SLUG]), 'first run must insert the row');

        $migration->up(new Schema());
        $this->assertCount(1, $repo->findBy(['slug' => PresentationSubmissionReopenedEmail::EVENT_SLUG]), 'second run must not duplicate the row');
    }

    /**
     * The per-summit backfill companion to the type migration above. seedDefaultEmailFlowEvents()
     * creates a SummitEmailEventFlow only when getEmailEventByType() is null, so this proves both
     * halves the deploy relies on: the first run gives an existing summit its row for the new event
     * (what makes it visible on that show's Email Flow Events page), and a second run leaves that
     * row alone rather than adding a duplicate or replacing an operator's override.
     */
    public function testPerSummitBackfillMigrationCreatesTheEventFlowOnceAndDoesNotDuplicateOnRerun()
    {
        $type = self::$em->getRepository(SummitEmailEventFlowType::class)
            ->findOneBy(['slug' => PresentationSubmissionReopenedEmail::EVENT_SLUG]);
        $this->assertNotNull($type, 'type row must exist (seeded or created by setUp())');

        // deployed precondition: an existing summit with no per-summit row for the new event
        $existing = self::$summit->getEmailEventByType($type);
        if (!is_null($existing)) self::$summit->removeEmailEventFlow($existing); // orphanRemoval deletes it
        self::$em->flush();
        self::$em->clear();

        $countFor = function () use ($type): int {
            return count(self::$em->getRepository(SummitEmailEventFlow::class)->findBy([
                'summit' => self::$summit->getId(),
                'event_type' => $type->getId(),
            ]));
        };
        $this->assertSame(0, $countFor());

        require_once base_path('database/migrations/model/Version20260824090001.php');
        $migration = new Version20260824090001(self::$em->getConnection(), new NullLogger());
        $migration->up(new Schema());
        self::$em->clear();
        $this->assertSame(1, $countFor(), 'first run must create the per-summit row');

        $row = self::$em->getRepository(SummitEmailEventFlow::class)->findOneBy([
            'summit' => self::$summit->getId(),
            'event_type' => $type->getId(),
        ]);
        $this->assertEquals(PresentationSubmissionReopenedEmail::DEFAULT_TEMPLATE, $row->getEmailTemplateIdentifier());

        $migration->up(new Schema());
        self::$em->clear();
        $this->assertSame(1, $countFor(), 'second run must not duplicate the per-summit row');
    }

    public function testGetEmailTemplateSchemaDeclaresAllKeys()
    {
        $schema = PresentationSubmissionReopenedEmail::getEmailTemplateSchema();

        $this->assertEquals('string', $schema[IMailTemplatesConstants::full_name]['type']);
        $this->assertEquals('string', $schema[IMailTemplatesConstants::presentation_title]['type']);
        $this->assertEquals('string', $schema[IMailTemplatesConstants::until_date]['type']);
        $this->assertEquals('string', $schema[IMailTemplatesConstants::selection_plan_name]['type']);
        $this->assertEquals('string', $schema[IMailTemplatesConstants::summit_slug]['type']);
        $this->assertEquals('string', $schema[IMailTemplatesConstants::support_email]['type']);
        $this->assertEquals('int', $schema[IMailTemplatesConstants::selection_plan_id]['type']);
        $this->assertEquals('int', $schema[IMailTemplatesConstants::presentation_id]['type']);
    }
}
