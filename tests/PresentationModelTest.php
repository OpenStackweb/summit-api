<?php namespace Tests;

/**
 * Copyright 2024 OpenStack Foundation
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

use models\summit\Presentation;

/**
 * Class PresentationActionModelTest
 * @package Tests
 */
class PresentationModelTest extends BrowserKitTestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    /**
     * @throws \Exception
     */
    public function testReviewStatusNoSubmitted() {
        $presentation = self::$summit->getPresentations()[0];
        $presentation->setStatus('');

        $review_status = $presentation->getReviewStatus();
        self::assertEquals(Presentation::ReviewStatusNoSubmitted, $review_status);
    }

    /**
     * @throws \Exception
     */
    public function testReviewStatusInReviewWhenLockDown() {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $tomorrow = $now->modify('1 day');

        $presentation = self::$summit->getPresentations()[0];
        $presentation->getSelectionPlan()->setSubmissionLockDownPresentationStatusDate($tomorrow);

        $review_status = $presentation->getReviewStatus();
        self::assertEquals(Presentation::ReviewStatusInReview, $review_status);
    }

    /**
     * @throws \Exception
     */
    public function testReviewStatusInReviewWhenSelectionTime() {
        $tomorrow = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('1 day');
        $yesterday = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day');
        $month_ago = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 month');

        $presentation = self::$summit->getPresentations()[0];
        $presentation->getSelectionPlan()->setSubmissionBeginDate($month_ago);
        $presentation->getSelectionPlan()->setSubmissionEndDate($yesterday);
        $presentation->getSelectionPlan()->setSelectionBeginDate($yesterday);
        $presentation->getSelectionPlan()->setSelectionEndDate($tomorrow);

        $review_status = $presentation->getReviewStatus();
        self::assertEquals(Presentation::ReviewStatusInReview, $review_status);
    }

    /**
     * @throws \Exception
     */
    public function testReviewStatusPublished() {
        $presentation = self::$summit->getPresentations()[0];
        $review_status = $presentation->getReviewStatus();
        self::assertEquals(Presentation::ReviewStatusPublished, $review_status);
    }

    /**
     * @throws \Exception
     */
    public function testReviewStatusReviewStatusRejected() {
        $yesterday = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day');
        $month_ago = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 month');

        $presentation = self::$summit->getPresentations()[0];
        $presentation->getSelectionPlan()->setSelectionBeginDate($month_ago);
        $presentation->getSelectionPlan()->setSelectionEndDate($yesterday);
        $presentation->unPublish();

        $review_status = $presentation->getReviewStatus();
        self::assertEquals(Presentation::ReviewStatusRejected, $review_status);
    }
}