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

use Illuminate\Support\Facades\Config;
use LaravelDoctrine\ORM\Facades\Registry;
use ModelSerializers\SerializerRegistry;
use models\summit\Presentation;
use models\summit\SummitEvent;
use models\utils\SilverstripeBaseModel;

/**
 * Class PresentationReopenApiTest
 * @package Tests
 */
class PresentationReopenApiTest extends ProtectedApiTestCase
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
        self::$presentation->setTitle("REOPEN API TEST");
        self::$presentation->setType(self::$defaultPresentationType);
        self::$presentation->setSelectionPlan(self::$default_selection_plan);
        // Creator is set HERE, not in a later task: getPresentationSubmission (Task 6) and the
        // role=creator table feeds (Task 7) both require it. memberCanEdit() recognizes only
        // creator / moderator / assigned speaker (Presentation.php:1254-1261), so without this
        // every read in Tasks 6-8 returns 403.
        self::$presentation->setCreatedBy(self::$member);
        // update/complete also need a track (SummitEventValidationRulesFactory requires track_id)
        self::$presentation->setCategory(self::$defaultTrack);
        self::$summit->addEvent(self::$presentation);

        // the feature only applies once the window has ended
        self::$default_selection_plan->setIsEnabled(true);
        self::$default_selection_plan->setSubmissionBeginDate(
            (new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('P10D'))
        );
        self::$default_selection_plan->setSubmissionEndDate(
            (new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('P1D'))
        );

        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    protected function reopen(array $payload, ?int $presentation_id = null, ?int $summit_id = null)
    {
        $params = [
            'id' => $summit_id ?? self::$summit->getId(),
            'presentation_id' => $presentation_id ?? self::$presentation->getId(),
        ];
        $headers = $this->getAuthHeaders(); // includes CONTENT_TYPE: application/json

        return $this->action(
            "PUT", "OAuth2PresentationApiController@reopenSubmissionPeriod",
            $params, [], [], [], $headers, json_encode($payload)
        );
    }

    protected function closeNow()
    {
        $params = [
            'id' => self::$summit->getId(),
            'presentation_id' => self::$presentation->getId(),
        ];
        $headers = $this->getAuthHeaders(); // includes CONTENT_TYPE: application/json

        return $this->action(
            "DELETE", "OAuth2PresentationApiController@closeSubmissionPeriod",
            $params, [], [], [], $headers
        );
    }

    /**
     * Reload from the DB rather than reading the response body.
     *
     * These assertions deliberately do NOT read submission_reopened_until out of the response:
     * the serializer mappings that would put it there do not exist until Task 6, so asserting
     * on the response here would make Task 5's "the API test now passes" gate unreachable.
     * Task 6 adds the response-shape assertions once the mappings land.
     *
     * Uses self::$em (the 'model' manager, set in InsertSummitTestData::insertSummitTestData
     * via Registry::getManager(SilverstripeBaseModel::EntityManager)), NOT the bare EntityManager
     * facade: the facade defaults to the 'config' manager (config/doctrine.php lists 'config'
     * first with no default override), and Presentation lives in the 'model' DB, so the facade
     * throws TableNotFoundException.
     *
     * Each simulated HTTP dispatch ($this->action(...)) closes the 'model' entity manager at
     * request end (confirmed live: after $this->reopen(), self::$em->isOpen() is already false
     * with no exception/retry logged -- this is normal per-request teardown, not an error path).
     * A second request (e.g. closeNow()) then transparently reopens 'model' via its own
     * Registry::resetManager() call -- but that only updates the framework's registry entry,
     * not our locally-cached self::$em, so re-fetching self::$em from Registry::getManager()
     * (not blindly resetManager()-ing our stale copy again, which would discard that
     * already-open, already-correct instance and start a THIRD one) is required before reading.
     * Mirrors the exact pattern InsertSummitTestData::insertSummitTestData() itself uses
     * (tests/InsertSummitTestData.php:311-314) to pick up the current 'model' manager.
     */
    private function reloadPresentation(): Presentation
    {
        self::$em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!self::$em->isOpen()) {
            self::$em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }
        self::$em->clear();
        return self::$em->getRepository(Presentation::class)->find(self::$presentation->getId());
    }

    /**
     * buildForSubmission(..., update: true) (SummitEventValidationRulesFactory.php:139-157) marks
     * exactly four fields 'required': title, type_id, track_id, selection_plan_id. Everything else
     * is 'sometimes'. A partial payload 400s in getJsonPayload BEFORE the reopen gate runs, which
     * would make the success case fail and every refusal case pass for the wrong reason.
     *
     * type_id/track_id must additionally be on the selection plan: saveOrUpdatePresentation checks
     * hasEventType() (PresentationService.php:427) and hasTrack() (:451). The fixture's
     * defaultPresentationType and defaultTrack both are (InsertSummitTestData.php:687, :686 via
     * defaultTrackGroup), and type_id must equal the presentation's current type or ":436" refuses
     * the change.
     */
    private function validUpdatePayload(): array
    {
        return [
            'title' => 'EDITED DURING REOPEN',
            'type_id' => self::$defaultPresentationType->getId(),
            'track_id' => self::$defaultTrack->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ];
    }

    protected function updateSubmission()
    {
        $params = [
            'id' => self::$summit->getId(),
            'presentation_id' => self::$presentation->getId(),
        ];
        $headers = $this->getAuthHeaders(); // includes CONTENT_TYPE: application/json

        return $this->action(
            "PUT", "OAuth2PresentationApiController@updatePresentationSubmission",
            $params, [], [], [], $headers, json_encode($this->validUpdatePayload())
        );
    }

    protected function completeSubmission()
    {
        $params = [
            'id' => self::$summit->getId(),
            'presentation_id' => self::$presentation->getId(),
        ];
        $headers = $this->getAuthHeaders(); // includes CONTENT_TYPE: application/json

        return $this->action(
            "PUT", "OAuth2PresentationApiController@completePresentationSubmission",
            $params, [], [], [], $headers
        );
    }

    /**
     * A 412 alone does not prove the window gate refused: update/complete raise ValidationException
     * (-> 412) from a dozen other places, and complete's post-gate media-upload and speaker checks
     * are indistinguishable from the gate at the HTTP layer. Both window guards
     * (PresentationService.php:542-548 for update, :666-672 for complete) carry the literal message
     * "Submission Period is Closed.", which processRequest surfaces in `errors`
     * (RequestProcessor.php:47 -> JsonController::error412 :140-144), so assert on that.
     */
    private function assertRefusedBySubmissionWindow($response): void
    {
        $this->assertResponseStatus(412);
        $body = json_decode($response->getContent(), true);
        $this->assertIsArray($body['errors'] ?? null, $response->getContent());
        $this->assertContains(
            'Submission Period is Closed.',
            $body['errors'],
            'refused with 412, but NOT by the submission-window gate: ' . $response->getContent()
        );
    }

    private function assertErrorsContain($response, string $needle): void
    {
        $body = json_decode($response->getContent(), true);
        $this->assertIsArray($body['errors'] ?? null, $response->getContent());
        $this->assertContains($needle, $body['errors'], $response->getContent());
    }

    /**
     * assertArrayHasKey() alone cannot catch a broken serializer mapping. AbstractSerializer
     * (libs/ModelSerializers/AbstractSerializer.php) assigns $new_values[$mapping[0]] = $value
     * UNCONDITIONALLY, and swallows a missing-getter exception into $value = null with only a log
     * warning -- so renaming getSubmissionReopenedUntil() without updating the 'datetime_epoch'
     * mapping would ship null to the Show Admin column and both speaker tables with the key still
     * present and a presence-only assertion still green. Every caller below grants a window first,
     * so the value must be a real epoch in the future.
     */
    private function assertFutureEpoch(array $payload, string $key, string $context = ''): void
    {
        $this->assertArrayHasKey($key, $payload, $context);
        $this->assertNotNull(
            $payload[$key],
            sprintf('%s present but null -- the serializer mapping no longer resolves a getter. %s', $key, $context)
        );
        $this->assertIsInt(
            $payload[$key],
            sprintf('%s is not an epoch integer: %s', $key, var_export($payload[$key], true))
        );
        $this->assertGreaterThan(
            time(),
            $payload[$key],
            sprintf('%s is not in the future; the granted window never reached the response', $key)
        );
    }

    /**
     * Grant the window directly on the model instead of through reopen().
     *
     * Same reason as testCloseNowClearsTheWindow below: a BrowserKit test cannot do two sequential
     * HTTP-simulated writes against the same entity -- DoctrineMiddleware::handle closes the 'model'
     * entity manager after every request and singleton repositories pin the manager instance they
     * were first resolved with, so the second write mutates an untracked object and flush() silently
     * persists nothing while still returning a success status. Every test below therefore arranges
     * its precondition on the model and spends its one HTTP write on the endpoint under test.
     */
    private function grantWindow(int $hours = 24): void
    {
        self::$presentation->reopenSubmission($hours, self::$member);
        self::$em->flush();
    }

    public function testAdminCanReopenAClosedSubmission()
    {
        $this->reopen(['hours' => 24]);
        $this->assertResponseStatus(201);

        $reloaded = $this->reloadPresentation();
        $this->assertTrue($reloaded->isSubmissionReopened());
        $this->assertEquals(24, $reloaded->getSubmissionReopenedHours());
        $this->assertGreaterThan(new \DateTime('now', new \DateTimeZone('UTC')), $reloaded->getSubmissionReopenedUntil());
    }

    /**
     * 7, not the shipped 24, and asserted as a literal: building the expectation out of the same
     * Config key the production code reads made this pass against a hardcoded 24 just as happily.
     * Config::set reaches the dispatched request -- seedCompletionEmailConfig() below relies on
     * exactly that.
     */
    public function testReopenDefaultsToTheConfiguredWindowWhenHoursIsOmitted()
    {
        Config::set('cfp.default_reopen_hours', 7);

        $this->reopen([]);
        $this->assertResponseStatus(201);

        $this->assertEquals(7, $this->reloadPresentation()->getSubmissionReopenedHours());
    }

    /**
     * Max 9, not the shipped 168: sending Config::get('cfp.max_reopen_hours')+1 against a ceiling
     * the service reads from the same key passed against a hardcoded 168 too. The message assertion
     * is what proves the CEILING refused rather than one of the plan-state guards, all of which also
     * surface as a bare 412.
     */
    public function testHoursAboveMaxIsRejected()
    {
        Config::set('cfp.max_reopen_hours', 9);

        $response = $this->reopen(['hours' => 10]);
        $this->assertResponseStatus(412);
        $this->assertErrorsContain($response, 'hours must be between 1 and 9.');

        $this->assertNull(
            $this->reloadPresentation()->getSubmissionReopenedUntil(),
            'refused with 412 but the grant was written anyway'
        );
    }

    /**
     * The other side of the same boundary, so an off-by-one ceiling cannot pass. Its own test
     * rather than a second request inside the one above: a BrowserKit test cannot do two
     * sequential HTTP writes against the same entity (see grantWindow()).
     */
    public function testHoursExactlyAtMaxIsAccepted()
    {
        Config::set('cfp.max_reopen_hours', 9);

        $this->reopen(['hours' => 9]);
        $this->assertResponseStatus(201);

        $this->assertEquals(9, $this->reloadPresentation()->getSubmissionReopenedHours());
    }

    /**
     * T3: nothing else sends a non-positive window. A2 moved this refusal to the request-validation
     * layer ('hours' => 'sometimes|integer|min:1'), so the body is the validator's field-keyed shape
     * -- NOT the service's flat "hours must be between 1 and %s." list. Asserted as what the code
     * actually returns.
     */
    public function testNonPositiveHoursIsRejected()
    {
        foreach ([0, -1] as $hours) {
            $response = $this->reopen(['hours' => $hours]);
            $this->assertResponseStatus(412);

            $body = json_decode($response->getContent(), true);
            $this->assertEquals(
                ['The hours must be at least 1.'],
                $body['errors']['hours'] ?? null,
                sprintf('hours=%d was not refused by the min:1 rule: %s', $hours, $response->getContent())
            );
        }

        $this->assertNull(
            $this->reloadPresentation()->getSubmissionReopenedUntil(),
            'refused but a grant was written anyway'
        );
    }

    public function testPresentationFromAnotherSummitReturns404()
    {
        // summit2 ships with no event types (InsertSummitTestData:587-602), so it needs one.
        // Build a DEDICATED type: Summit::addEventType() calls $event_type->setSummit($this)
        // (Summit.php:2748-2752), so reusing self::$defaultPresentationType would reassign it
        // away from self::$summit and corrupt the primary fixture mid-test.
        $foreign_type = new \models\summit\PresentationType();
        $foreign_type->setType("FOREIGN PRESENTATION TYPE");
        $foreign_type->setShouldBeAvailableOnCfp(true);
        self::$summit2->addEventType($foreign_type);

        $foreign = new Presentation();
        $foreign->setTitle("FOREIGN");
        $foreign->setType($foreign_type);
        self::$summit2->addEvent($foreign);
        self::$em->persist(self::$summit2);
        self::$em->flush();

        $this->reopen(['hours' => 24], $foreign->getId());
        $this->assertResponseStatus(404);

        // §8 requires the scoping assertion on BOTH endpoints, not just reopen
        $this->action(
            "DELETE", "OAuth2PresentationApiController@closeSubmissionPeriod",
            ['id' => self::$summit->getId(), 'presentation_id' => $foreign->getId()],
            [], [], [], $this->getAuthHeaders()
        );
        $this->assertResponseStatus(404);
    }

    public function testCloseNowClearsTheWindow()
    {
        // Precondition ("already reopened") set directly via the model rather than through
        // reopen(), so this test issues exactly one HTTP-simulated write instead of two.
        // Verified live: the global DoctrineMiddleware (app/Http/Middleware/DoctrineMiddleware.php:38-42)
        // closes the 'model' entity manager's connection after every request, and container-singleton
        // repositories (e.g. DoctrineSummitRepository) pin to whichever manager instance existed at
        // their first resolution -- so a second HTTP write in the same test resolves $summit via that
        // now-stale repository, mutates a Presentation object the transaction's freshly-reset manager
        // never tracked, and flush() silently has nothing to persist even though the response is 204.
        // Raw-SQL-confirmed: with two sequential HTTP writes, SubmissionReopenedHours stayed at the
        // reopen() value after a "successful" close. That's an orthogonal infra characteristic of
        // per-request entity-manager closing, not a defect in reopenSubmission()/closeSubmissionNow()
        // themselves, and out of scope for this test file -- so we avoid triggering it by using the
        // same "set the precondition directly on the model, then flush" pattern setUp() already uses
        // for the selection plan dates above.
        self::$presentation->reopenSubmission(24, self::$member);
        self::$em->flush();

        $this->closeNow();
        $this->assertResponseStatus(204);

        $this->assertNull($this->reloadPresentation()->getSubmissionReopenedUntil());
    }

    public function testReopenIsRefusedWhileTheWindowIsStillOpen()
    {
        self::$default_selection_plan->setSubmissionEndDate(
            (new \DateTime('now', new \DateTimeZone('UTC')))->add(new \DateInterval('P1D'))
        );
        self::$em->flush();

        $response = $this->reopen(['hours' => 24]);
        $this->assertResponseStatus(412);
        // a DIFFERENT message from assertRefusedBySubmissionWindow()'s: this one is the reopen
        // service's own "still open" guard, so an unrelated 412 cannot stand in for it
        $this->assertErrorsContain($response, 'Submission period has not ended yet; nothing to reopen.');
    }

    public function testReopenFieldsAppearOnDefaultAdminResponseWithoutAFieldsParam()
    {
        $this->reopen(['hours' => 24]);
        $this->assertResponseStatus(201);

        $params = [
            'id' => self::$summit->getId(),
            'event_id' => self::$presentation->getId(),
        ];
        $headers = $this->getAuthHeaders(); // includes CONTENT_TYPE: application/json

        $response = $this->action(
            "GET", "OAuth2SummitEventsApiController@getEvent", $params, [], [], [], $headers
        );
        $this->assertResponseStatus(200);

        $payload = json_decode($response->getContent(), true);
        $this->assertFutureEpoch($payload, 'submission_reopened_until', 'admin getEvent response');
        // the GRANTING member's id, not merely present: a bare new Presentation() serializes
        // submission_reopened_by_id as 0 (getSubmissionReopenedById() returns 0 when unset),
        // so a presence-only assertion proves nothing about the grant
        $this->assertArrayHasKey('submission_reopened_by_id', $payload);
        $this->assertEquals(self::$member->getId(), $payload['submission_reopened_by_id']);
        $this->assertArrayHasKey('submission_reopened_by', $payload);
        $this->assertIsString($payload['submission_reopened_by']);
    }

    public function testByFieldsAreAbsentFromTheSubmissionSerializer()
    {
        $this->reopen(['hours' => 24]);

        $params = [
            'id' => self::$summit->getId(),
            'presentation_id' => self::$presentation->getId(),
        ];
        $headers = $this->getAuthHeaders(); // includes CONTENT_TYPE: application/json

        $response = $this->action(
            "GET", "OAuth2PresentationApiController@getPresentationSubmission",
            $params, [], [], [], $headers
        );
        // 201, not 200: getPresentationSubmission returns $this->updated(...)
        // (OAuth2PresentationApiController.php:457), and JsonController::updated() is 201.
        $this->assertResponseStatus(201);

        $payload = json_decode($response->getContent(), true);
        $this->assertFutureEpoch($payload, 'submission_reopened_until', 'submission serializer response');
        $this->assertArrayNotHasKey('submission_reopened_by_id', $payload);
        $this->assertArrayNotHasKey('submission_reopened_by', $payload);
    }

    public function testReopenFieldsNeverAppearOnAPublicSerializedResponse()
    {
        $this->reopen(['hours' => 24]);
        // without this the three absence assertions below hold vacuously: a failed reopen leaves
        // no grant, so Public would omit the fields whether or not the mappings are Admin-only
        $this->assertResponseStatus(201);
        $reloaded = $this->reloadPresentation();
        $this->assertTrue($reloaded->isSubmissionReopened(), 'grant did not persist');

        $payload = SerializerRegistry::getInstance()->getSerializer(
            $reloaded, SerializerRegistry::SerializerType_Public
        )->serialize();

        $this->assertArrayNotHasKey('submission_reopened_until', $payload);
        $this->assertArrayNotHasKey('submission_reopened_by_id', $payload);
        $this->assertArrayNotHasKey('submission_reopened_by', $payload);
    }

    public function testSpeakerPresentationListReturnsTheReopenWindow()
    {
        $this->reopen(['hours' => 24]);
        $this->assertResponseStatus(201);

        $params = [
            'id' => self::$summit->getId(),
            'role' => 'creator',
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ];
        $headers = $this->getAuthHeaders(); // includes CONTENT_TYPE: application/json

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getMySpeakerPresentationsByRoleAndBySelectionPlan",
            $params, [], [], [], $headers
        );
        $this->assertResponseStatus(200);

        $page = json_decode($response->getContent(), true);
        $this->assertNotEmpty($page['data'], 'list returned no rows; the token member did not create the presentation');
        $this->assertFutureEpoch(
            $page['data'][0],
            'submission_reopened_until',
            'if the key is missing the list is Public-serialized: PagingResponse::toArray() was called without a serializer type'
        );
    }

    public function testSummitWideSpeakerListAlsoReturnsTheReopenWindow()
    {
        $this->reopen(['hours' => 24]);
        $this->assertResponseStatus(201);

        // NOTE: this route's param is {summit_id}, not {id} -- it lives under a different
        // prefix group (routes/api_v1.php:2309) than the selection-plan sibling above.
        $params = [
            'summit_id' => self::$summit->getId(),
            'role' => 'creator',
        ];
        $headers = $this->getAuthHeaders(); // includes CONTENT_TYPE: application/json

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersApiController@getMySpeakerPresentationsByRoleAndBySummit",
            $params, [], [], [], $headers
        );
        $this->assertResponseStatus(200);

        $page = json_decode($response->getContent(), true);
        $this->assertNotEmpty($page['data']);
        $this->assertFutureEpoch($page['data'][0], 'submission_reopened_until', 'summit-wide speaker list row');
    }

    // ---------------------------------------------------------------------------------------------
    // Acceptance: an active grant actually lets the speaker edit after the window closed.
    // The token member is both a global admin AND the presentation's creator with a speaker profile
    // (InsertMemberTestData.php:144-149, plus setCreatedBy() in setUp above), so one token drives
    // both roles. canEdit() matches on getCreatedById() == speaker->getMemberId()
    // (Presentation.php:1264-1270).
    // ---------------------------------------------------------------------------------------------

    public function testActiveGrantLetsTheSpeakerUpdateAfterTheWindowClosed()
    {
        $this->grantWindow(24);
        // prove the arrange step landed in the DB: without this, a passing refusal sibling and a
        // failing success case here would be indistinguishable from "the grant was never written"
        $this->assertTrue(
            $this->reloadPresentation()->isSubmissionReopened(),
            'grant did not persist; the assertions below would not be testing the gate'
        );

        $this->updateSubmission();
        $this->assertResponseStatus(201);
        // assert the edit actually landed -- a 201 from an ignored payload must not pass
        $this->assertEquals('EDITED DURING REOPEN', $this->reloadPresentation()->getTitle());
    }

    public function testWithoutAGrantTheSameUpdateIsRefused()
    {
        // precondition: no grant at all. setUp() never grants one, but assert it rather than assume.
        $this->assertNull(self::$presentation->getSubmissionReopenedUntil());
        $this->assertFalse(self::$presentation->isSubmissionReopened());

        $this->assertRefusedBySubmissionWindow($this->updateSubmission());
    }

    public function testExpiredGrantRefusesTheUpdate()
    {
        // a 1h grant whose start is backdated 2h => getSubmissionReopenedUntil() is 1h in the past
        self::$presentation->reopenSubmission(1, self::$member);
        self::$presentation->setSubmissionReopenedDate(
            (new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('PT2H'))
        );
        self::$em->flush();

        // precondition: a grant EXISTS (so this is not vacuously the no-grant case) but has expired
        $reloaded = $this->reloadPresentation();
        $this->assertNotNull($reloaded->getSubmissionReopenedUntil(), 'grant did not persist');
        $this->assertLessThan(new \DateTime('now', new \DateTimeZone('UTC')), $reloaded->getSubmissionReopenedUntil());
        $this->assertFalse($reloaded->isSubmissionReopened());

        $this->assertRefusedBySubmissionWindow($this->updateSubmission());
    }

    public function testCloseNowImmediatelyRefusesTheUpdate()
    {
        // closeSubmissionNow() is invoked on the model, not through the DELETE endpoint: chaining
        // close + update would be two HTTP writes on the same entity (see grantWindow() above).
        // testCloseNowClearsTheWindow already proves the endpoint clears the grant; this proves a
        // cleared grant refuses the edit -- i.e. closing is not a no-op that leaves the window live.
        self::$presentation->reopenSubmission(24, self::$member);
        self::$em->flush();
        $this->assertTrue($this->reloadPresentation()->isSubmissionReopened(), 'grant did not persist');

        self::$presentation = self::$em->getRepository(Presentation::class)->find(self::$presentation->getId());
        self::$presentation->closeSubmissionNow();
        self::$em->flush();

        // precondition: the grant is gone
        $this->assertNull($this->reloadPresentation()->getSubmissionReopenedUntil(), 'close did not persist');

        $this->assertRefusedBySubmissionWindow($this->updateSubmission());
    }

    public function testDisablingThePlanAfterAGrantRefusesTheUpdate()
    {
        $this->grantWindow(24);
        self::$default_selection_plan->setIsEnabled(false);
        self::$em->flush();

        // What the 412 below does and does NOT prove. The refusal comes from the PRE-EXISTING
        // !IsEnabled() guard (PresentationService.php:543-545), which throws the identical
        // "Submission Period is Closed." one branch before the reopen-aware guard at :547 -- so the
        // message cannot discriminate the two, and this test does not by itself prove the grant was
        // overridden rather than merely bypassed. The real proof that isSubmissionReopened() returns
        // false BECAUSE of IsEnabled() is the model-level test (PresentationReopenModelTest) plus the
        // preconditions asserted here: the grant is present and still in the future, the plan really
        // is disabled, and isSubmissionReopened() is nonetheless false. What this test adds is that
        // the HTTP path refuses too -- i.e. no controller/service layer re-opens the edit.
        $reloaded = $this->reloadPresentation();
        $this->assertNotNull($reloaded->getSubmissionReopenedUntil(), 'grant did not persist');
        $this->assertGreaterThan(new \DateTime('now', new \DateTimeZone('UTC')), $reloaded->getSubmissionReopenedUntil());
        $this->assertFalse($reloaded->getSelectionPlan()->IsEnabled(), 'plan disable did not persist');
        $this->assertFalse($reloaded->isSubmissionReopened());

        $this->assertRefusedBySubmissionWindow($this->updateSubmission());
    }

    // ---------------------------------------------------------------------------------------------
    // complete is a SEPARATE gate from update (PresentationService.php:666-672) and runs three more
    // checks AFTER it, so the fixture must satisfy all three or a failure downstream of the gate
    // reads as a false negative on the feature. Two are already satisfied; one is not:
    //  - isSubmitted() (:653 -> Presentation.php:1345): PHASE_COMPLETE && STATUS_RECEIVED. A freshly
    //    built Presentation is neither, so complete is allowed. No fixture work.
    //  - fulfilMediaUploadsConditions() (Presentation.php:1290): the 5 media upload types attached
    //    to defaultPresentationType (InsertSummitTestData.php:438-448) never call
    //    setMinUploadsQty(), and SummitMediaUploadType::__construct defaults min_uploads_qty to 0
    //    (SummitMediaUploadType.php:125), so getMandatoryAllowedMediaUploadTypesCount() is 0 and the
    //    method short-circuits true at :1297. No upload needs to be attached.
    //  - fulfilSpeakersConditions() (:1305) is the ONE that needs fixture work -> attachSpeaker().
    //    useModerator=false skips the moderator branch, but useSpeakers=true with minSpeakers=1
    //    (InsertSummitTestData.php:414-418) refuses an empty presentation at :1322: the fixture's
    //    setAreSpeakersMandatory(false) (:420) is inert, because
    //    PresentationType::isAreSpeakersMandatory() ignores that column and returns
    //    min_speakers > 0 (PresentationType.php:193-196). Verified empirically -- the precondition
    //    assertion below failed until attachSpeaker() was added. One speaker satisfies
    //    min 1 <= count 1 <= max 3.
    // The remaining hazard is the notification email: complete dispatches
    // PresentationCreatorNotificationEmail, whose constructor throws \InvalidArgumentException (->
    // 400, NOT 412) when cfp.base_url / idp.base_url / support email are empty. Set them so a config
    // gap cannot be mistaken for a gate refusal.
    // ---------------------------------------------------------------------------------------------

    private function seedCompletionEmailConfig(): void
    {
        Config::set('cfp.base_url', 'https://testcfp.openstack.org');
        Config::set('cfp.support_email', 'test@openstack.org');
        Config::set('idp.base_url', 'https://testidp.openstack.org');
    }

    /**
     * self::$speaker is the token member's own speaker profile (InsertMemberTestData.php:144-149),
     * so this satisfies fulfilSpeakersConditions() without introducing a second identity. canEdit()
     * already passed via the creator branch, so this changes nothing about authorization.
     */
    private function attachSpeaker(): void
    {
        self::$presentation->addSpeaker(self::$speaker);
        self::$em->flush();
    }

    public function testActiveGrantLetsTheSpeakerCompleteAfterTheWindowClosed()
    {
        $this->seedCompletionEmailConfig();
        $this->attachSpeaker();
        $this->grantWindow(24);

        $reloaded = $this->reloadPresentation();
        $this->assertTrue($reloaded->isSubmissionReopened(), 'grant did not persist');
        // prove the post-gate checks cannot be what fails, so a non-201 below indicts the gate
        $this->assertFalse($reloaded->isSubmitted());
        $this->assertTrue($reloaded->fulfilMediaUploadsConditions());
        $this->assertTrue($reloaded->fulfilSpeakersConditions());

        $this->completeSubmission();
        $this->assertResponseStatus(201);
        // the request passed THROUGH the gate rather than never reaching it: complete's only
        // side effect is progress/status, and isSubmitted() is exactly that pair
        $this->assertTrue($this->reloadPresentation()->isSubmitted());
    }

    public function testWithoutAGrantCompleteIsRefused()
    {
        $this->seedCompletionEmailConfig();
        $this->attachSpeaker();

        // precondition: no grant, and the presentation is otherwise completable -- so the 412 below
        // can only come from the window gate, not from isSubmitted()/media/speaker conditions
        $this->assertFalse(self::$presentation->isSubmissionReopened());
        $this->assertFalse(self::$presentation->isSubmitted());
        $this->assertTrue(self::$presentation->fulfilMediaUploadsConditions());
        $this->assertTrue(self::$presentation->fulfilSpeakersConditions());

        $this->assertRefusedBySubmissionWindow($this->completeSubmission());
        $this->assertFalse($this->reloadPresentation()->isSubmitted());
    }

    // ---------------------------------------------------------------------------------------------
    // Open-window parity (SDS §8, "the acceptance bar"; D7). The SDS states the bar as: while
    // reopened, the editable surface is exactly what the selection plan defines during the open
    // window. These tests SAMPLE that bar the way §8 asks -- one prohibited field, one permitted
    // field, plus the media-upload set -- they do not enumerate the whole surface.
    //
    // Parity holds by construction: the diff ORs the time gate in two places
    // (PresentationService.php:547 for update, :671 for complete) and changes nothing else on those
    // paths, so curatePayloadByPresentationAllowedQuestions / checkPresentationAllowedEdtiable
    // Questions (:555-556) still run unconditionally afterwards on the one shared code path. These
    // tests are a tripwire, so a later change that widens the surface for the reopen case
    // specifically cannot pass unnoticed.
    //
    // Fixture direction, which is the opposite of what it looks like: SelectionPlan::__construct
    // calls seedAllowedPresentationQuestions() and seedAllowedEditablePresentationQuestions()
    // (SelectionPlan.php:457-458), so a fresh plan permits EVERY allowed field, both as a question
    // and as editable. The restrictive case therefore has to be built by removing one, not by adding
    // one -- hence makeNonEditable() below.
    //
    // Run these with the whole file, not as a hand-picked --filter subset. Each of the five passes
    // on its own, the full file is green (25 tests) and so is the whole push.yml shard (59), which
    // is what CI runs -- but certain multi-test --filter subsets make the tests that expect a
    // successful update return 500 instead. Observed failure: PresentationSerializer::
    // getMediaUploadsSerializerType() (:142) calls isAdmin() on the resource-server context's Member
    // and Doctrine raises EntityNotFoundException ("Unable to find Proxies\__CG__\models\main\Member
    // entity identifier associated with the UnitOfWork"). Reproducible for the scalar pair below
    // when the five are filtered together; not reproducible for any of them alone. The mechanism was
    // not chased past that, so treat a red hand-picked subset here as unproven rather than as a
    // regression, and reproduce against the full file before believing it.
    //
    // 'links' rather than a scalar for the enforcement pair, on purpose. areFieldsEqual() has two
    // branches (SelectionPlan.php:1564-1571): the array branch is correct, and the scalar branch
    // compares html_entity_decode($field1) to itself, so it always reports equal and the guard is
    // dead for every scalar field. 'links' is array-typed in AllowedEditableFields and in
    // getSnapshot() (FieldLinks => []), so it takes the working branch and these tests need no fix
    // to that pre-existing defect. The scalar half of the SDS bullet cannot assert enforcement at
    // all and is covered as parity only, by the last pair below.
    // ---------------------------------------------------------------------------------------------

    /**
     * Drop one field from the plan's allowed-editable set, leaving the rest of the seeded set intact.
     *
     * SelectionPlan exposes no single-question remover -- only clearAllAllowedEditablePresentation
     * Questions() (:490) -- so this drops the element straight off the collection returned by
     * getAllowedEditablePresentationQuestions() (:485) rather than clearing and re-adding the other
     * four. Safe against the DB either way: the association is mapped cascade persist+remove with
     * orphanRemoval: true (:229), so the removed row is deleted rather than left behind to reappear
     * on the next read -- which the isAllowedEditablePresentationQuestion() precondition assertion in
     * each caller checks against a reloaded plan.
     *
     * Deliberately leaves the allowed-QUESTION set alone. curatePayloadByPresentationAllowedQuestions()
     * returns the curated payload by value and both call sites discard the return
     * (PresentationService.php:360 and :555), so curation is a no-op today. If that discard is ever
     * fixed, a field missing from the question set would be stripped from the payload,
     * isset($payload[$field]) would go false, and the editable check would never run -- so
     * testNonEditableFieldIsRefusedUnderReopen would get a 201 against its asserted 412 and fail,
     * even though stripping is the correct behavior at that point. Keeping the field an allowed
     * question keeps these tests pinned on editability, which is what they are about, instead of
     * making them a spurious casualty of that fix.
     */
    private function makeNonEditable(string $field): void
    {
        $questions = self::$default_selection_plan->getAllowedEditablePresentationQuestions();
        foreach ($questions as $question) {
            if ($question->getType() === $field) {
                $questions->removeElement($question);
            }
        }
        self::$em->flush();
    }

    private function updateSubmissionWithLinks(array $links)
    {
        $params = [
            'id' => self::$summit->getId(),
            'presentation_id' => self::$presentation->getId(),
        ];
        $headers = $this->getAuthHeaders(); // includes CONTENT_TYPE: application/json

        return $this->action(
            "PUT", "OAuth2PresentationApiController@updatePresentationSubmission",
            $params, [], [], [], $headers,
            json_encode(array_merge($this->validUpdatePayload(), ['links' => $links]))
        );
    }

    /**
     * The presentation starts with no links, so the snapshot's FieldLinks is [] -- still isset(),
     * which is what checkPresentationAllowedEdtiableQuestions requires (:1594) -- and a one-element
     * payload differs by count, so the array branch reports unequal and the guard fires.
     */
    public function testNonEditableFieldIsRefusedUnderReopen()
    {
        $this->makeNonEditable(Presentation::FieldLinks);
        $this->grantWindow(24);

        $reloaded = $this->reloadPresentation();
        $this->assertTrue($reloaded->isSubmissionReopened(), 'grant did not persist');
        $this->assertTrue(
            $reloaded->getSelectionPlan()->isAllowedPresentationQuestion(Presentation::FieldLinks),
            'links must remain an allowed QUESTION, or this test survives a curation fix vacuously'
        );
        $this->assertFalse(
            $reloaded->getSelectionPlan()->isAllowedEditablePresentationQuestion(Presentation::FieldLinks),
            'fixture did not land: links is still editable, so a 412 below would prove nothing'
        );

        $response = $this->updateSubmissionWithLinks(['https://example.org/deck']);
        $this->assertResponseStatus(412);
        // the PLAN's message, not assertRefusedBySubmissionWindow()'s -- a window refusal here would
        // mean the gate never opened, which is the opposite of what this test asserts
        $this->assertErrorsContain(
            $response,
            sprintf(
                'Field %s is not allowed for edition on Selection Plan %s.',
                Presentation::FieldLinks,
                self::$default_selection_plan->getName()
            )
        );

        $this->assertCount(
            0,
            $this->reloadPresentation()->getLinks(),
            'refused with 412 but the link was written anyway'
        );
    }

    /**
     * The other direction. No fixture change: the constructor already seeds links as editable, and
     * the assertion below states that rather than assuming it.
     */
    public function testEditableFieldStaysEditableUnderReopen()
    {
        $this->grantWindow(24);

        $reloaded = $this->reloadPresentation();
        $this->assertTrue($reloaded->isSubmissionReopened(), 'grant did not persist');
        $this->assertTrue(
            $reloaded->getSelectionPlan()->isAllowedEditablePresentationQuestion(Presentation::FieldLinks),
            'links must BE an allowed editable question here'
        );

        $this->updateSubmissionWithLinks(['https://example.org/deck']);
        $this->assertResponseStatus(201);

        // the edit landed -- a 201 alone would also come back if the factory ignored the field
        $links = [];
        foreach ($this->reloadPresentation()->getLinks() as $link) {
            $links[] = $link->getLink();
        }
        $this->assertEquals(['https://example.org/deck'], $links);
    }

    // ---------------------------------------------------------------------------------------------
    // The scalar half of the SDS bullet, as parity rather than as enforcement.
    //
    // A scalar change to a NON-editable field is accepted, because areFieldsEqual()'s scalar branch
    // self-compares (SelectionPlan.php:1570). That defect is outside this diff and deliberately not
    // fixed here, so the only honest assertion available is that reopen behaves exactly as the open
    // window does -- which is what parity means. Neither test below claims the guard works.
    //
    // Two tests rather than one because this suite gets one SUCCESSFUL HTTP write per entity. Not a
    // general BrowserKit law -- it is this app's wiring: DoctrineMiddleware closes the model entity
    // manager after each request (app/Http/Middleware/DoctrineMiddleware.php:38-42) while the
    // presentation service and its repositories are container singletons holding the manager they
    // first resolved, so the second write mutates an untracked object and flush() persists nothing
    // while still returning success (see grantWindow()). Doing both windows in one test would make
    // the second arm assert nothing.
    //
    // Named "HandledIdentically", not "IsAccepted": the 201 each arm asserts is the comparator defect
    // showing through, not a property worth preserving. When the comparator is fixed both arms move
    // to 412 TOGETHER and both fail together -- that is expected, and the fix should update both. If
    // only one moves, parity actually broke and that is the signal these two exist to give.
    // ---------------------------------------------------------------------------------------------

    public function testNonEditableScalarHandledIdenticallyInTheOpenWindow()
    {
        $this->makeNonEditable(SummitEvent::FieldTitle);
        self::$default_selection_plan->setSubmissionEndDate(
            (new \DateTime('now', new \DateTimeZone('UTC')))->add(new \DateInterval('P1D'))
        );
        self::$em->flush();

        $reloaded = $this->reloadPresentation();
        $this->assertTrue($reloaded->getSelectionPlan()->isSubmissionOpen(), 'window did not reopen');
        $this->assertFalse($reloaded->isSubmissionReopened(), 'this arm must NOT ride on a grant');
        $this->assertFalse(
            $reloaded->getSelectionPlan()->isAllowedEditablePresentationQuestion(SummitEvent::FieldTitle),
            'title must be non-editable for this to say anything'
        );

        $this->updateSubmission(); // validUpdatePayload() changes title
        $this->assertResponseStatus(201);
        $this->assertEquals('EDITED DURING REOPEN', $this->reloadPresentation()->getTitle());
    }

    public function testNonEditableScalarHandledIdenticallyUnderReopen()
    {
        $this->makeNonEditable(SummitEvent::FieldTitle);
        $this->grantWindow(24);

        $reloaded = $this->reloadPresentation();
        $this->assertFalse(
            $reloaded->getSelectionPlan()->isSubmissionOpen(),
            'this arm must run against a CLOSED window, or it is a copy of the one above'
        );
        $this->assertTrue($reloaded->isSubmissionReopened(), 'grant did not persist');
        $this->assertFalse(
            $reloaded->getSelectionPlan()->isAllowedEditablePresentationQuestion(SummitEvent::FieldTitle),
            'title must be non-editable for this to say anything'
        );

        $this->updateSubmission();
        $this->assertResponseStatus(201);
        $this->assertEquals('EDITED DURING REOPEN', $this->reloadPresentation()->getTitle());
    }

    /**
     * The third assertion of the SDS bullet, recorded as the structural guard it is rather than
     * presented as coverage it is not.
     *
     * getAllowedMediaUploadTypes() (PresentationType.php:413) unconditionally returns the type's
     * stored collection -- it takes no window, no selection plan and no presentation. The mutation
     * path agrees: addMediaUploadTo (PresentationService.php:1083-1125) checks the media upload type,
     * the presentation-type allowance and the max-qty cap, and never consults the submission window
     * or the plan at all. Media and speaker subresource mutations are ungated in EVERY window -- open,
     * closed, granted or not; adding that guard is ClickUp 86bba8388 and is out of scope here.
     *
     * So this asserts a property that cannot vary with the reopen state, and it would keep passing if
     * the reopen gate broke entirely. It cannot detect a fixture change either -- both sides derive
     * from the same fixture and would move together. It is kept only because the SDS bullet names
     * the assertion explicitly. Do NOT read a green here as evidence that reopen leaves media uploads
     * alone: what makes that true is 86bba8388 being unimplemented, not this test. It also says
     * nothing about mutation behavior, only about the advertised set.
     */
    public function testAllowedMediaUploadTypesAreUnchangedUnderReopen()
    {
        $before = [];
        foreach (self::$defaultPresentationType->getAllowedMediaUploadTypes() as $type) {
            $before[] = $type->getId();
        }
        sort($before);
        $this->assertNotEmpty($before, 'fixture attaches no media upload types; the comparison is vacuous');

        $this->grantWindow(24);
        $reloaded = $this->reloadPresentation();
        $this->assertTrue($reloaded->isSubmissionReopened(), 'grant did not persist');

        $after = [];
        foreach ($reloaded->getType()->getAllowedMediaUploadTypes() as $type) {
            $after[] = $type->getId();
        }
        sort($after);

        $this->assertEquals($before, $after);
    }
}
