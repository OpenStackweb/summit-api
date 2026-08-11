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
use models\summit\Presentation;

/**
 * Class PresentationReopenModelTest
 * @package Tests
 */
class PresentationReopenModelTest extends BrowserKitTestCase
{
    use InsertMemberTestData;
    use InsertSummitTestData;

    /**
     * @var Presentation
     */
    static $presentation;

    protected function setUp(): void
    {
        parent::setUp();
        // insertMemberTestData takes a required group slug (tests/InsertMemberTestData.php:96)
        self::insertMemberTestData(IGroup::SummitAdministrators);
        // summit fixtures read self::$defaultMember when building orders/attendees
        self::$defaultMember = self::$member;
        self::insertSummitTestData();

        self::$presentation = new Presentation();
        self::$presentation->setTitle("REOPEN MODEL TEST");
        self::$presentation->setType(self::$defaultPresentationType);
        self::$presentation->setSelectionPlan(self::$default_selection_plan);
        self::$summit->addEvent(self::$presentation);

        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    private function endSubmissionWindow(): void
    {
        $plan = self::$default_selection_plan;
        $plan->setIsEnabled(true);
        $plan->setSubmissionBeginDate((new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('P10D')));
        $plan->setSubmissionEndDate((new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('P1D')));
    }

    public function testNoGrantMeansNotReopenedAndDeadlineIsNull()
    {
        $this->endSubmissionWindow();

        $this->assertNull(self::$presentation->getSubmissionReopenedUntil());
        $this->assertFalse(self::$presentation->isSubmissionReopened());
        $this->assertTrue(self::$presentation->isSubmissionClosed());
    }

    public function testActiveGrantOnEndedPlanFoldsIntoIsSubmissionClosed()
    {
        $this->endSubmissionWindow();
        self::$presentation->reopenSubmission(24, self::$member);

        $this->assertNotNull(self::$presentation->getSubmissionReopenedUntil());
        $this->assertTrue(self::$presentation->isSubmissionReopened());
        $this->assertFalse(self::$presentation->isSubmissionClosed());
        $this->assertEquals(24, self::$presentation->getSubmissionReopenedHours());
        $this->assertEquals(self::$member->getId(), self::$presentation->getSubmissionReopenedById());
        $this->assertTrue(self::$presentation->hasSubmissionReopenedBy());
        $this->assertEquals(self::$member->getEmail(), self::$presentation->getSubmissionReopenedBy()->getEmail());
    }

    public function testExpiredGrantIsNotReopened()
    {
        $this->endSubmissionWindow();
        self::$presentation->reopenSubmission(1, self::$member);
        self::$presentation->setSubmissionReopenedDate(
            (new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('PT2H'))
        );

        $this->assertFalse(self::$presentation->isSubmissionReopened());
        $this->assertTrue(self::$presentation->isSubmissionClosed());
    }

    public function testGrantIsIgnoredWhilePlanWindowHasNotEndedYet()
    {
        $plan = self::$default_selection_plan;
        $plan->setIsEnabled(true);
        $plan->setSubmissionBeginDate((new \DateTime('now', new \DateTimeZone('UTC')))->sub(new \DateInterval('P1D')));
        $plan->setSubmissionEndDate((new \DateTime('now', new \DateTimeZone('UTC')))->add(new \DateInterval('P1D')));

        self::$presentation->reopenSubmission(24, self::$member);

        $this->assertFalse(self::$presentation->isSubmissionReopened());
    }

    public function testGrantIsIgnoredOnDisabledPlan()
    {
        $this->endSubmissionWindow();
        self::$presentation->reopenSubmission(24, self::$member);
        $this->assertTrue(self::$presentation->isSubmissionReopened());

        self::$default_selection_plan->setIsEnabled(false);

        $this->assertFalse(self::$presentation->isSubmissionReopened());
        // and the delete guard closes again, which is the whole point of the invariant
        $this->assertTrue(self::$presentation->isSubmissionClosed());
    }

    public function testCloseSubmissionNowClearsTheGrantRegardlessOfPlanState()
    {
        $this->endSubmissionWindow();
        self::$presentation->reopenSubmission(24, self::$member);
        self::$default_selection_plan->setIsEnabled(false);

        self::$presentation->closeSubmissionNow();

        $this->assertNull(self::$presentation->getSubmissionReopenedHours());
        $this->assertNull(self::$presentation->getSubmissionReopenedDate());
        $this->assertNull(self::$presentation->getSubmissionReopenedUntil());
        $this->assertFalse(self::$presentation->isSubmissionReopened());
    }

    public function testReopenReStampsRatherThanAccumulating()
    {
        $this->endSubmissionWindow();

        self::$presentation->reopenSubmission(24, self::$member);
        $first = self::$presentation->getSubmissionReopenedUntil();

        self::$presentation->reopenSubmission(48, self::$member);

        $this->assertEquals(48, self::$presentation->getSubmissionReopenedHours());
        $this->assertGreaterThan($first, self::$presentation->getSubmissionReopenedUntil());
    }

    public function testReviewStatusIsUnaffectedByAReopen()
    {
        $this->endSubmissionWindow();

        // A PHASE_NEW presentation returns NotSubmitted either way (Presentation.php:2490-2493),
        // so asserting on the default fixture would compare a constant to itself and prove
        // nothing. Drive it to a status that actually flows through the date logic first.
        self::$presentation->setProgress(Presentation::PHASE_COMPLETE);
        self::$presentation->setStatus(Presentation::STATUS_RECEIVED);

        $before = self::$presentation->getReviewStatus();
        $this->assertNotEquals(Presentation::ReviewStatusNoSubmitted, $before, 'fixture is not exercising the date logic');

        self::$presentation->reopenSubmission(24, self::$member);

        $this->assertEquals($before, self::$presentation->getReviewStatus());
    }
}
