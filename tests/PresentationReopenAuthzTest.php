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

use App\Models\Foundation\Main\IGroup;
use App\Models\ResourceServer\IAccessTokenService;
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\Presentation;
use models\utils\SilverstripeBaseModel;

/**
 * Everything in the reopen feature that requires a NON-global-admin identity.
 *
 * Split from PresentationReopenApiTest rather than shared through a trait on purpose: the fixture
 * here is materially different (a downgraded identity, plus a second presentation owned by another
 * member for the authorship test), so a shared trait would need conditionals. The duplicated
 * helpers below are copies of that class's, deliberately.
 *
 * WHY A DOWNGRADED IDENTITY IS THE WHOLE POINT
 * Member::isAdmin() (Member.php:895-921) consults persisted groups AND the token's IdP groups, and
 * BOTH default to administrator in this harness. Every guard exercised here short-circuits for a
 * global admin -- the reopen/close endpoints (OAuth2PresentationApiController.php:578-580, :632-634),
 * PresentationService::deletePresentation (:590-591) and SummitService::deleteEvent (:1075-1077) --
 * so an admin-run version of this class would be 403-free and prove nothing. See
 * assertIdentityIsNotAdmin(), which runs in setUp() for EVERY test in this class, not just the
 * named canary, so this cannot be silently regressed by deleting one test.
 *
 * CHOICE OF PERSISTED GROUP: IGroup::SummitRegistrationAdmins.
 *  - isAdmin() is false: only SuperAdmins/Administrators satisfy it.
 *  - hasPermissionForOnGroup($summit, SummitAdministrators) is false: it requires a
 *    SummitAdministratorPermissionGroup row linking THIS member to THIS summit
 *    (Member.php:2183-2206), and the fixture's permission group has the summit but no members
 *    (InsertSummitTestData.php:864-866). So this is exactly "a member with no summit admin
 *    permission" -- and a stronger persona than a random member, because it also proves the
 *    per-summit scoping is what refuses, not mere group absence.
 *  - It is on delete-event's authz_groups list (ApiEndpointsSeeder.php:4504-4513), so the
 *    'auth.user' middleware (UserAuthEndpoint.php:129-149) lets the request through to the
 *    SummitService guard under test. IGroup::TrackChairs would have been 403'd by that middleware
 *    before ever reaching the fold, making Step 4's SummitService half untestable.
 *
 * Class PresentationReopenAuthzTest
 * @package Tests
 */
class PresentationReopenAuthzTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    /**
     * Created by self::$member (so canEdit()/memberCanEdit() pass) -- used for the reopen/close 403s,
     * both delete paths, and as the "other presentation carrying a grant" in the create test.
     * @var Presentation
     */
    static $presentation;

    /**
     * Created by self::$member2 with NO speaker/moderator link to self::$member: the Step 5
     * authorship fixture.
     * @var Presentation
     */
    static $foreign_presentation;

    protected function setUp(): void
    {
        // Lever 1: the persisted group. setCurrentGroup() must precede parent::setUp(), which is
        // what forwards it to insertMemberTestData() (ProtectedApiTestCase.php:365-375).
        $this->setCurrentGroup(IGroup::SummitRegistrationAdmins);
        parent::setUp();

        // Lever 2: the token's IdP groups. AccessTokenServiceStub's constructor DEFAULTS
        // $idp_user_groups to ['badge-printers','administrators'] (ProtectedApiTestCase.php:38-47)
        // and createApplication() installs it with no arguments (:327), so isAdmin() returns true
        // via isOnExternalGroup(Administrators) (Member.php:916-917) no matter what lever 1 says.
        // Replace the stub AND re-bind it: App::singleton() -> bind() drops any already-resolved
        // instance, which reassigning the static alone would not do.
        self::$service = new AccessTokenServiceStub([['slug' => IGroup::BadgePrinters]]);
        App::singleton(IAccessTokenService::class, function () { return self::$service; });
        // parent::setUp() seeded the identity onto the OLD stub; re-seed it onto the new one or the
        // token carries no user and every endpoint 403s on is_null($current_member) instead.
        self::$service->setUserId(self::$member->getUserExternalId());
        self::$service->setUserExternalId(self::$member->getUserExternalId());
        self::$service->setUserEmail(self::$member->getEmail());
        self::$service->setUserFirstName(self::$member->getFirstName());
        self::$service->setUserLastName(self::$member->getLastName());

        self::insertSummitTestData();

        self::$presentation = new Presentation();
        self::$presentation->setTitle("REOPEN AUTHZ TEST");
        self::$presentation->setType(self::$defaultPresentationType);
        self::$presentation->setSelectionPlan(self::$default_selection_plan);
        self::$presentation->setCreatedBy(self::$member);
        self::$presentation->setCategory(self::$defaultTrack);
        self::$summit->addEvent(self::$presentation);

        // Step 5's fixture: same summit/plan/type/track, but created by a DIFFERENT member and with
        // no speaker or moderator link to the token member, so canEdit() is false for it.
        self::$foreign_presentation = new Presentation();
        self::$foreign_presentation->setTitle("OWNED BY SOMEONE ELSE");
        self::$foreign_presentation->setType(self::$defaultPresentationType);
        self::$foreign_presentation->setSelectionPlan(self::$default_selection_plan);
        self::$foreign_presentation->setCreatedBy(self::$member2);
        self::$foreign_presentation->setCategory(self::$defaultTrack);
        self::$summit->addEvent(self::$foreign_presentation);

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

        $this->assertIdentityIsNotAdmin();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    /**
     * Both admin levers, asserted separately so a regression names which one came back.
     *
     * isAdmin(true) skips the external check, which isolates lever 1 (a real DB read).
     * Lever 2 cannot be isolated through isAdmin(): isOnExternalGroup() (Member.php:987-1006) reads
     * the request-scoped resource-server context, which is empty outside a dispatched request, so
     * isAdmin() would report false here even with the admin-defaulted stub still installed -- a
     * false negative. So assert on the token the container actually hands the middleware instead.
     *
     * Called from setUp(), i.e. it guards every test in this class, and separately as the named
     * canary test below.
     */
    private function assertIdentityIsNotAdmin(): void
    {
        // lever 1: persisted groups
        $this->assertNull(
            self::$member->getGroupByCode(IGroup::Administrators),
            'fixture member is persisted into the administrators group'
        );
        $this->assertNull(
            self::$member->getGroupByCode(IGroup::SuperAdmins),
            'fixture member is persisted into the super-admins group'
        );
        $this->assertFalse(
            self::$member->isAdmin(true),
            'fixture is still a global admin by persisted group; 403 tests here are meaningless'
        );

        // lever 2: the IdP groups on the token the container is serving
        $slugs = array_column(
            App::make(IAccessTokenService::class)->get($this->access_token)->getUserGroups(),
            'slug'
        );
        $this->assertNotContains(
            IGroup::Administrators,
            $slugs,
            'the access-token stub still reports the administrators IdP group; isAdmin() will be '
            . 'true inside a request and every 403 test here is meaningless'
        );
        $this->assertNotContains(IGroup::SuperAdmins, $slugs);

        $this->assertFalse(self::$member->isAdmin(), 'fixture is still a global admin');
    }

    /**
     * Same manager-refresh dance as PresentationReopenApiTest::reloadPresentation(): self::$em is
     * the 'model' manager, the bare EntityManager facade resolves to 'config'/api_config where
     * Presentation does not exist, and each dispatched request closes 'model' on the way out
     * (DoctrineMiddleware::handle), so re-fetch from Registry before reading.
     */
    private function reload(int $id): ?Presentation
    {
        self::$em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!self::$em->isOpen()) {
            self::$em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }
        self::$em->clear();
        return self::$em->getRepository(Presentation::class)->find($id);
    }

    /**
     * buildForSubmission() marks exactly title/type_id/track_id/selection_plan_id required
     * (SummitEventValidationRulesFactory.php:139-157). A partial payload 400s before any gate runs.
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

    /**
     * Arrange the grant on the model, never through the reopen endpoint: a BrowserKit test cannot
     * do two sequential HTTP writes against the same entity (DoctrineMiddleware::handle closes the
     * 'model' EM after every request and singleton repositories pin the manager they were first
     * resolved with, so the second write mutates an untracked object and flush() silently persists
     * nothing while still returning success). Every test below spends its one HTTP write on the
     * endpoint under test. This identity could not call the admin endpoint anyway.
     */
    private function grantWindow(Presentation $presentation, int $hours = 24): void
    {
        $presentation->reopenSubmission($hours, self::$member2);
        self::$em->flush();
    }

    private function assertRefusedByDeleteGuard($response, int $presentation_id): void
    {
        $this->assertResponseStatus(412);
        $body = json_decode($response->getContent(), true);
        $this->assertIsArray($body['errors'] ?? null, $response->getContent());
        $this->assertContains(
            sprintf("Presentation %s can not be deleted because the submission is closed.", $presentation_id),
            $body['errors'],
            'refused with 412, but NOT by the closed-submission delete guard: ' . $response->getContent()
        );
    }

    private function assertErrorsContain($response, string $needle): void
    {
        $body = json_decode($response->getContent(), true);
        $this->assertIsArray($body['errors'] ?? null, $response->getContent());
        $this->assertContains($needle, $body['errors'], $response->getContent());
    }

    // ---------------------------------------------------------------------------------------------
    // Canary
    // ---------------------------------------------------------------------------------------------

    public function testFixtureIdentityIsGenuinelyNotAdmin()
    {
        $this->assertFalse(self::$member->isAdmin(), 'fixture is still a global admin; 403 tests below are meaningless');
        // and the full two-lever check, in case isAdmin() alone ever stops covering both
        $this->assertIdentityIsNotAdmin();
    }

    // ---------------------------------------------------------------------------------------------
    // Step 3: a non-admin can neither reopen nor close.
    //
    // 403-vs-201 is self-validating against the admin trap: a global admin would get 201/204 here,
    // so these cannot pass because the identity is too privileged. The other way to get a spurious
    // 403 is the token failing to resolve to a member at all
    // (OAuth2PresentationApiController.php:575-576) -- ruled out class-wide by the delete tests
    // below, which 204 only because the token resolves to self::$member, the presentation's creator
    // with a speaker profile. Each test also asserts the grant state is UNCHANGED, so a refusal
    // that somehow still mutated would fail.
    // ---------------------------------------------------------------------------------------------

    public function testMemberWithNoSummitAdminPermissionCannotReopen()
    {
        // precondition: no grant, and the window really has closed (so 403 is not standing in for
        // the service's "nothing to reopen" 412)
        $this->assertNull(self::$presentation->getSubmissionReopenedUntil());
        $this->assertFalse(self::$default_selection_plan->isSubmissionOpen());

        $this->action(
            "PUT", "OAuth2PresentationApiController@reopenSubmissionPeriod",
            ['id' => self::$summit->getId(), 'presentation_id' => self::$presentation->getId()],
            [], [], [], $this->getAuthHeaders(), json_encode(['hours' => 24])
        );
        $this->assertResponseStatus(403);

        $this->assertNull(
            $this->reload(self::$presentation->getId())->getSubmissionReopenedUntil(),
            'refused with 403 but the grant was written anyway'
        );
    }

    public function testMemberWithNoSummitAdminPermissionCannotClose()
    {
        $this->grantWindow(self::$presentation);
        // precondition: there IS a grant to clear, so a 403 cannot be a no-op passing vacuously
        $this->assertTrue(
            $this->reload(self::$presentation->getId())->isSubmissionReopened(),
            'grant did not persist; this test would not be exercising the close endpoint'
        );

        $this->action(
            "DELETE", "OAuth2PresentationApiController@closeSubmissionPeriod",
            ['id' => self::$summit->getId(), 'presentation_id' => self::$presentation->getId()],
            [], [], [], $this->getAuthHeaders()
        );
        $this->assertResponseStatus(403);

        $this->assertTrue(
            $this->reload(self::$presentation->getId())->isSubmissionReopened(),
            'refused with 403 but the grant was cleared anyway'
        );
    }

    /**
     * Persona (b): being a speaker ON the presentation is deliberately not a fallback -- §4 gives no
     * speaker path to the reopen controls. This is the strongest form: the member is the creator AND
     * an assigned speaker, i.e. memberCanEdit() is true, and it is still refused.
     */
    public function testSpeakerOnThePresentationStillCannotReopen()
    {
        self::$presentation->addSpeaker(self::$speaker);
        self::$em->flush();

        $reloaded = $this->reload(self::$presentation->getId());
        $this->assertTrue($reloaded->memberCanEdit(self::$member), 'speaker/creator link did not persist');
        $this->assertNull($reloaded->getSubmissionReopenedUntil());

        $this->action(
            "PUT", "OAuth2PresentationApiController@reopenSubmissionPeriod",
            ['id' => self::$summit->getId(), 'presentation_id' => self::$presentation->getId()],
            [], [], [], $this->getAuthHeaders(), json_encode(['hours' => 24])
        );
        $this->assertResponseStatus(403);

        $this->assertNull(
            $this->reload(self::$presentation->getId())->getSubmissionReopenedUntil(),
            'refused with 403 but the grant was written anyway'
        );
    }

    public function testSpeakerOnThePresentationStillCannotClose()
    {
        self::$presentation->addSpeaker(self::$speaker);
        $this->grantWindow(self::$presentation);

        $reloaded = $this->reload(self::$presentation->getId());
        $this->assertTrue($reloaded->memberCanEdit(self::$member), 'speaker/creator link did not persist');
        $this->assertTrue($reloaded->isSubmissionReopened(), 'grant did not persist');

        $this->action(
            "DELETE", "OAuth2PresentationApiController@closeSubmissionPeriod",
            ['id' => self::$summit->getId(), 'presentation_id' => self::$presentation->getId()],
            [], [], [], $this->getAuthHeaders()
        );
        $this->assertResponseStatus(403);

        $this->assertTrue(
            $this->reload(self::$presentation->getId())->isSubmissionReopened(),
            'refused with 403 but the grant was cleared anyway'
        );
    }

    // ---------------------------------------------------------------------------------------------
    // The _by fields are admin-only ON THE WIRE.
    //
    // PresentationReopenApiTest's Public-serializer test asserts this by calling SerializerRegistry
    // directly, which bypasses the thing that actually decides the type on a real read:
    // OAuth2SummitEventsApiController::getSerializerType() (:117-133) returns Private only for an
    // ApplicationType_Service token or a current user who isAdmin()/isSummitAdmin(), and Public
    // otherwise. This class holds the only genuinely non-admin identity in the feature's tests
    // (SummitRegistrationAdmins satisfies neither predicate -- Member::isSummitAdmin() :923-931 keys
    // on SummitAdministrators, and the harness token is WEB_APPLICATION, not SERVICE), so this is
    // where that branch can be exercised end to end. getEvent carries no 'auth.user' middleware
    // (routes/api_v1.php:698), so the request reaches the controller.
    // ---------------------------------------------------------------------------------------------

    public function testNonAdminEventReadDoesNotExposeTheByFields()
    {
        $this->grantWindow(self::$presentation);
        $this->assertTrue(
            $this->reload(self::$presentation->getId())->isSubmissionReopened(),
            'grant did not persist; an absent field would prove nothing'
        );

        $response = $this->action(
            "GET", "OAuth2SummitEventsApiController@getEvent",
            ['id' => self::$summit->getId(), 'event_id' => self::$presentation->getId()],
            [], [], [], $this->getAuthHeaders()
        );
        $this->assertResponseStatus(200);

        $payload = json_decode($response->getContent(), true);
        // the assertions below are not vacuous on an empty/error body
        $this->assertEquals(self::$presentation->getId(), $payload['id'] ?? null, $response->getContent());
        $this->assertEquals('REOPEN AUTHZ TEST', $payload['title'] ?? null, $response->getContent());

        $this->assertArrayNotHasKey('submission_reopened_by', $payload, $response->getContent());
        $this->assertArrayNotHasKey('submission_reopened_by_id', $payload, $response->getContent());
    }

    // ---------------------------------------------------------------------------------------------
    // Step 4: both delete paths honor a reopen, with no change at either call site.
    //
    // Path A: DELETE /summits/{id}/presentations/{presentation_id}
    //         -> OAuth2PresentationApiController@deletePresentation -> PresentationService::deletePresentation
    //         guard at PresentationService.php:590-591.
    // Path B: DELETE /summits/{id}/events/{event_id}
    //         -> OAuth2SummitEventsApiController@deleteEvent -> SummitService::deleteEvent
    //         guard at SummitService.php:1076-1077.
    // Both guards read Presentation::isSubmissionClosed(), which Task 1 folded the reopen check
    // into -- that fold is the only reason the reopened cases below can pass.
    // ---------------------------------------------------------------------------------------------

    public function testReopenedPresentationCanBeDeletedByANonAdminCreator()
    {
        $this->grantWindow(self::$presentation);

        $reloaded = $this->reload(self::$presentation->getId());
        $this->assertTrue($reloaded->isSubmissionReopened(), 'grant did not persist');
        $this->assertFalse($reloaded->isSubmissionClosed(), 'the fold did not treat the grant as re-opening');

        $id = self::$presentation->getId();
        $this->action(
            "DELETE", "OAuth2PresentationApiController@deletePresentation",
            ['id' => self::$summit->getId(), 'presentation_id' => $id],
            [], [], [], $this->getAuthHeaders()
        );
        $this->assertResponseStatus(204);
        $this->assertNull($this->reload($id), '204 returned but the presentation is still there');
    }

    public function testMerelyClosedPresentationCannotBeDeletedByANonAdminCreator()
    {
        // precondition: no grant, window ended => isSubmissionClosed() is true
        $reloaded = $this->reload(self::$presentation->getId());
        $this->assertNull($reloaded->getSubmissionReopenedUntil());
        $this->assertTrue($reloaded->isSubmissionClosed(), 'fixture window is not actually closed');

        $id = self::$presentation->getId();
        $response = $this->action(
            "DELETE", "OAuth2PresentationApiController@deletePresentation",
            ['id' => self::$summit->getId(), 'presentation_id' => $id],
            [], [], [], $this->getAuthHeaders()
        );
        $this->assertRefusedByDeleteGuard($response, $id);
        $this->assertNotNull($this->reload($id), 'refused with 412 but the presentation was deleted anyway');
    }

    public function testReopenedPresentationCanBeDeletedViaTheEventDeletePath()
    {
        $this->grantWindow(self::$presentation);

        $reloaded = $this->reload(self::$presentation->getId());
        $this->assertTrue($reloaded->isSubmissionReopened(), 'grant did not persist');
        $this->assertFalse($reloaded->isSubmissionClosed(), 'the fold did not treat the grant as re-opening');

        $id = self::$presentation->getId();
        $this->action(
            "DELETE", "OAuth2SummitEventsApiController@deleteEvent",
            ['id' => self::$summit->getId(), 'event_id' => $id],
            [], [], [], $this->getAuthHeaders()
        );
        // a 403 here would mean the auth.user middleware refused before SummitService ran
        $this->assertResponseStatus(204);
        $this->assertNull($this->reload($id), '204 returned but the presentation is still there');
    }

    public function testMerelyClosedPresentationCannotBeDeletedViaTheEventDeletePath()
    {
        $reloaded = $this->reload(self::$presentation->getId());
        $this->assertNull($reloaded->getSubmissionReopenedUntil());
        $this->assertTrue($reloaded->isSubmissionClosed(), 'fixture window is not actually closed');

        $id = self::$presentation->getId();
        $response = $this->action(
            "DELETE", "OAuth2SummitEventsApiController@deleteEvent",
            ['id' => self::$summit->getId(), 'event_id' => $id],
            [], [], [], $this->getAuthHeaders()
        );
        $this->assertRefusedByDeleteGuard($response, $id);
        $this->assertNotNull($this->reload($id), 'refused with 412 but the presentation was deleted anyway');
    }

    // ---------------------------------------------------------------------------------------------
    // Step 5: reopen relaxes TIMING, never WHO.
    //
    // updatePresentationSubmission checks canEdit() (PresentationService.php:530-534) BEFORE the
    // window gate (:543-548), so the refusal message discriminates authorship from timing: this
    // must be the canEdit message, NOT "Submission Period is Closed." -- otherwise the grant would
    // not actually be active and the test would prove nothing about authorship.
    // ---------------------------------------------------------------------------------------------

    public function testNonEditorStillCannotUpdateAReopenedPresentation()
    {
        $this->grantWindow(self::$foreign_presentation);

        $reloaded = $this->reload(self::$foreign_presentation->getId());
        $this->assertTrue($reloaded->isSubmissionReopened(), 'grant did not persist; timing would refuse first');
        $this->assertFalse(
            $reloaded->memberCanEdit(self::$member),
            'fixture member CAN edit this presentation; the test is not exercising the authorship gate'
        );

        $response = $this->action(
            "PUT", "OAuth2PresentationApiController@updatePresentationSubmission",
            ['id' => self::$summit->getId(), 'presentation_id' => self::$foreign_presentation->getId()],
            [], [], [], $this->getAuthHeaders(), json_encode($this->validUpdatePayload())
        );
        $this->assertResponseStatus(412);
        $this->assertErrorsContain(
            $response,
            sprintf('Current Speaker can not edit %s presentation', self::$foreign_presentation->getId())
        );

        $this->assertEquals(
            'OWNED BY SOMEONE ELSE',
            $this->reload(self::$foreign_presentation->getId())->getTitle(),
            'refused with 412 but the edit landed anyway'
        );
    }

    // ---------------------------------------------------------------------------------------------
    // Step 6: creation is untouched by a reopen.
    //
    // submitPresentation gates on the PLAN only (PresentationService.php:277-306) and never consults
    // any presentation, so a live grant on a sibling presentation must not leak into it.
    // ---------------------------------------------------------------------------------------------

    public function testCreateIsStillBlockedWhileAnotherPresentationOnThePlanHasAnActiveGrant()
    {
        $this->grantWindow(self::$presentation);

        $reloaded = $this->reload(self::$presentation->getId());
        $this->assertTrue($reloaded->isSubmissionReopened(), 'grant did not persist; nothing could leak');
        $this->assertTrue($reloaded->getSelectionPlan()->IsEnabled());
        $this->assertFalse($reloaded->getSelectionPlan()->isSubmissionOpen(), 'plan window is not actually closed');

        $before = self::$em->getRepository(Presentation::class)
            ->count(['summit' => self::$summit->getId()]);

        $response = $this->action(
            "POST", "OAuth2PresentationApiController@submitPresentation",
            ['id' => self::$summit->getId()],
            [], [], [], $this->getAuthHeaders(),
            json_encode([
                'title' => 'SHOULD NOT BE CREATED',
                'type_id' => self::$defaultPresentationType->getId(),
                'track_id' => self::$defaultTrack->getId(),
                'selection_plan_id' => self::$default_selection_plan->getId(),
            ])
        );
        $this->assertResponseStatus(412);
        $this->assertErrorsContain($response, 'Submission Period is Closed.');

        self::$em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!self::$em->isOpen()) {
            self::$em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }
        $this->assertEquals(
            $before,
            self::$em->getRepository(Presentation::class)->count(['summit' => self::$summit->getId()]),
            'refused with 412 but a presentation was created anyway'
        );
    }
}
