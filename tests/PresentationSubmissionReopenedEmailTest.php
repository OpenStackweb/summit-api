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
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlowType;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailFlowType;
use models\summit\Presentation;
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
