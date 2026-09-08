<?php namespace Tests;
use App\Models\Foundation\Main\IGroup;
use Illuminate\Support\Facades\Queue;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\main\Member;
use models\summit\Presentation;
use models\summit\PresentationMediaUpload;
use utils\FilterParser;

/**
 * Copyright 2023 OpenStack Foundation
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
final class OAuth2SummitSubmittersApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::$defaultMember = self::$member;
        self::$defaultMember2 = self::$member2;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetCurrentSummitSubmittersOrderByID()
    {
        $params = [
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'filter'    => [
                'is_speaker==true'
            ],
            'order' => '+id',
            'expand' => 'accepted_presentations,alternate_presentations,rejected_presentations',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSubmittersApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $submitters_response = json_decode($content);
        $this->assertNotNull($submitters_response);
    }

    public function testGetCurrentSummitSubmittersByName()
    {
        $params = [
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'filter' => [
                'first_name=@b||a,last_name=@b,email=@b'
            ],
            'order' => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSubmittersApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $submitters = json_decode($content);
        $this->assertTrue(!is_null($submitters));
    }

    public function testGetCurrentSummitSubmittersWithAcceptedPresentations()
    {
        $params = [
            'id'        => self::$summit->getId(),
            'page'      => 1,
            'per_page'  => 10,
            'filter'    => [
                'has_accepted_presentations==true',
            ],
            'order'     => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSubmittersApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $submitters = json_decode($content);
        $this->assertTrue(!is_null($submitters));
    }

    public function testGetCurrentSummitSubmittersWithPendingPresentations()
    {
        $member  = self::$em->find(Member::class, self::$defaultMember->getId());
        $member2 = self::$em->find(Member::class, self::$defaultMember2->getId());

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        $pres = new Presentation();
        self::$summit->addEvent($pres);
        $pres->setTitle("Pending Test Presentation");
        $pres->setAbstract("Abstract");
        $pres->setCategory(self::$defaultTrack);
        $pres->setType(self::$defaultPresentationType);
        $pres->setStartDate($start);
        $pres->setEndDate($end);
        $pres->setCreatedBy($member);
        // Deliberately unfinished (default progress/status), NOT published and NOT
        // added to any SummitSelectedPresentation group list

        // Negative control: a submitter with a published presentation must NOT be
        // returned. Without this control, the assertion below would pass even if
        // the filter were a complete no-op, since the base query already returns
        // every submitter with summit activity.
        $publishedPres = new Presentation();
        self::$summit->addEvent($publishedPres);
        $publishedPres->setTitle("Published Control Presentation");
        $publishedPres->setAbstract("Abstract");
        $publishedPres->setCategory(self::$defaultTrack);
        $publishedPres->setType(self::$defaultPresentationType);
        $publishedPres->setProgress(Presentation::PHASE_COMPLETE);
        $publishedPres->setStatus(Presentation::STATUS_RECEIVED);
        $publishedPres->setStartDate($start);
        $publishedPres->setEndDate($end);
        $publishedPres->setCreatedBy($member2);
        $publishedPres->publish();

        self::$em->flush();

        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 100,
            'filter'   => [
                'has_pending_presentations==true',
            ],
            'order'    => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSubmittersApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(200);
        $ids = array_map(fn($s) => $s->id, json_decode($response->getContent())->data);

        $this->assertContains($member->getId(), $ids,
            'submitter with an unfinished, unpublished, unselected presentation must be returned');
        $this->assertNotContains($member2->getId(), $ids,
            'submitter with a published presentation must not be treated as pending');
    }

    /**
     * Regression test: has_pending_presentations must reflect an unfinished
     * submission, not merely "not yet selected by a track chair". A submitter
     * whose presentation is already complete/received must NOT be returned,
     * even if it is still unpublished and has no selection-list entry.
     */
    public function testGetCurrentSummitSubmittersWithPendingPresentationsExcludesCompletedSubmissions()
    {
        $member = self::$em->find(Member::class, self::$defaultMember2->getId());

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        $pres = new Presentation();
        self::$summit->addEvent($pres);
        $pres->setTitle("Completed Submission Presentation");
        $pres->setAbstract("Abstract");
        $pres->setCategory(self::$defaultTrack);
        $pres->setType(self::$defaultPresentationType);
        $pres->setProgress(Presentation::PHASE_COMPLETE);
        $pres->setStatus(Presentation::STATUS_RECEIVED);
        $pres->setStartDate($start);
        $pres->setEndDate($end);
        $pres->setCreatedBy($member);
        // Submission is complete/received, but deliberately NOT published and NOT
        // added to any SummitSelectedPresentation group list
        self::$em->flush();

        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'filter'   => [
                'has_pending_presentations==true',
            ],
            'order'    => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSubmittersApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(200);
        $ids = array_map(fn($s) => $s->id, json_decode($response->getContent())->data);
        $this->assertNotContains($member->getId(), $ids,
            'submitter with a completed/received submission must not be treated as pending');
    }

    /**
     * Regression test: has_pending_presentations==false on DoctrineMemberRepository
     * is a hand-assembled NOT EXISTS (...) DQL string with no prior coverage.
     * Assert the complement of the ==true behaviour: a submitter whose only
     * submission is complete/received must be returned, while a submitter with
     * a genuinely unfinished submission must be excluded.
     */
    public function testGetCurrentSummitSubmittersWithPendingPresentationsFalse()
    {
        $member  = self::$em->find(Member::class, self::$defaultMember->getId());
        $member2 = self::$em->find(Member::class, self::$defaultMember2->getId());

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        $completePres = new Presentation();
        self::$summit->addEvent($completePres);
        $completePres->setTitle("Complete Submission Presentation");
        $completePres->setAbstract("Abstract");
        $completePres->setCategory(self::$defaultTrack);
        $completePres->setType(self::$defaultPresentationType);
        $completePres->setProgress(Presentation::PHASE_COMPLETE);
        $completePres->setStatus(Presentation::STATUS_RECEIVED);
        $completePres->setStartDate($start);
        $completePres->setEndDate($end);
        $completePres->setCreatedBy($member2);
        // Complete/received, but deliberately NOT published and NOT added to any
        // SummitSelectedPresentation group list

        $pendingPres = new Presentation();
        self::$summit->addEvent($pendingPres);
        $pendingPres->setTitle("Pending Submission Presentation");
        $pendingPres->setAbstract("Abstract");
        $pendingPres->setCategory(self::$defaultTrack);
        $pendingPres->setType(self::$defaultPresentationType);
        $pendingPres->setStartDate($start);
        $pendingPres->setEndDate($end);
        $pendingPres->setCreatedBy($member);
        // Deliberately unfinished (default progress/status), NOT published and NOT
        // added to any SummitSelectedPresentation group list

        self::$em->flush();

        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 100,
            'filter'   => [
                'has_pending_presentations==false',
            ],
            'order'    => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSubmittersApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(200);
        $ids = array_map(fn($s) => $s->id, json_decode($response->getContent())->data);

        $this->assertContains($member2->getId(), $ids,
            'submitter whose only submission is complete/received must be returned for ==false');
        $this->assertNotContains($member->getId(), $ids,
            'submitter with a genuinely unfinished submission must not be returned for ==false');
    }

    public function testExportCurrentSummitSubmittersWhoAreSpeakers()
    {
        $params = [
            'id'        => self::$summit->getId(),
            'page'      => 1,
            'per_page'  => 10,
            'filter'    => [
                'is_speaker==false'
            ],
            'order'     => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSubmittersApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(200);
    }

    public function testSendSpeakersBulkEmail() {
        $params = [
            'id' => self::$summit->getId(),
            'filter'    => [
                'first_name=@b||a,last_name=@b,email=@b',
            ],
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'email_flow_event'  => 'SUMMIT_SUBMISSIONS_PRESENTATION_SUBMITTER_ACCEPTED_ALTERNATE',
//            'submitter_ids'       => [
//                9161
//            ],
            'test_email_recipient'      => 'test_recip@nomail.com',
            'outcome_email_recipient'   => 'outcome_recip@nomail.com',
        ];

        $response = $this->action
        (
            "PUT",
            "OAuth2SummitSubmittersApiController@send",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(200);
    }

    /**
     * Regression test: the ticket's stated goal is the reminder email through
     * PUT .../submitters/all/send, yet nothing previously asserted
     * has_pending_presentations survives validation and reaches
     * ProcessSubmittersEmailRequestJob. Unlike SpeakerService::triggerSendEmails,
     * SubmitterService::triggerSendEmails does not resolve the filter to ids
     * before dispatching - it forwards the raw filter to the job, which resolves
     * ids itself when it runs. So the end-to-end assertion here is that the exact
     * filter requested is the one handed to the job, not a pre-narrowed id chunk.
     */
    public function testSendSubmittersBulkEmailFilteredByHasPendingPresentations() {
        Queue::fake();

        $params = [
            'id' => self::$summit->getId(),
            'filter'    => [
                'has_pending_presentations==true',
            ],
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $data = [
            'email_flow_event'  => 'SUMMIT_SUBMISSIONS_PRESENTATION_SUBMITTER_ACCEPTED_ALTERNATE',
            'test_email_recipient'      => 'test_recip@nomail.com',
            'outcome_email_recipient'   => 'outcome_recip@nomail.com',
        ];

        $response = $this->action
        (
            "PUT",
            "OAuth2SummitSubmittersApiController@send",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(200);

        Queue::assertPushed(\App\Jobs\Emails\ProcessSubmittersEmailRequestJob::class, 1);
        Queue::assertPushed(\App\Jobs\Emails\ProcessSubmittersEmailRequestJob::class, function ($job) {
            $ref = new \ReflectionObject($job);
            $summit_prop = $ref->getProperty('summit_id');
            $summit_prop->setAccessible(true);
            $filter_prop = $ref->getProperty('filter');
            $filter_prop->setAccessible(true);

            $this->assertEquals(self::$summit->getId(), $summit_prop->getValue($job));
            $this->assertEquals(
                ['has_pending_presentations==true'],
                $filter_prop->getValue($job),
                'the has_pending_presentations filter must reach the job unmodified so it can narrow the ids it resolves at run time'
            );
            return true;
        });
    }

    public function testGetSubmittersWithSubmittedMediaUploadsWithType()
    {
        $media_upload_ids = array_map(function($v){
            return $v->getId();
        }, self::$media_uploads_types);

        $params = [
            'id'        => self::$summit->getId(),
            'page'      => 1,
            'per_page'  => 10,
            'filter'    => [
                'has_accepted_presentations==true',
                'has_alternate_presentations==false',
                'has_rejected_presentations==false',
                sprintf('has_media_upload_with_type==%s', implode("||", $media_upload_ids) ),
            ],
            'expand' => 'presentations,accepted_presentations',
            'order'     => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSubmittersApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $submitters = json_decode($content);
        $this->assertTrue(!is_null($submitters));
    }

    public function testGetCurrentSummitSubmittersActivitiesCount()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSubmittersApiController@getSubmittersActivitiesCount",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertNotNull($data);
        $this->assertTrue(isset($data->count));
        $this->assertGreaterThanOrEqual(0, $data->count);
    }

    public function testGetCurrentSummitSubmittersActivitiesCountWithAcceptedPresentations()
    {
        $params = [
            'id'     => self::$summit->getId(),
            'filter' => [
                'has_accepted_presentations==true',
            ],
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSubmittersApiController@getSubmittersActivitiesCount",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertNotNull($data);
        $this->assertTrue(isset($data->count));
        $this->assertGreaterThanOrEqual(0, $data->count);
    }

    /**
     * Regression test: mirrors
     * testGetCurrentSummitSpeakersActivitiesCountWithPendingPresentations but
     * against OAuth2SummitSubmittersApiController::getSubmittersActivitiesCount,
     * which had no coverage of has_pending_presentations at all. The baseline is
     * taken via a direct repository call (like the speakers test does) rather
     * than through an HTTP round-trip, since $this->action() closes the shared
     * EntityManager the fixtures below rely on. The assertion is exact: a
     * broken filter that returns every submitter would produce a count far
     * greater than baseline + 1.
     */
    public function testGetCurrentSummitSubmittersActivitiesCountWithPendingPresentations()
    {
        $baseline = EntityManager::getRepository(Member::class)
            ->getUniqueActivitiesCountBySummit(
                self::$summit,
                FilterParser::parse(
                    ['filter' => 'has_pending_presentations==true'],
                    ['has_pending_presentations' => ['==']]
                )
            );

        $member  = self::$em->find(Member::class, self::$defaultMember->getId());
        $member2 = self::$em->find(Member::class, self::$defaultMember2->getId());

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        $pres = new Presentation();
        self::$summit->addEvent($pres);
        $pres->setTitle("Pending Count Presentation");
        $pres->setAbstract("Abstract");
        $pres->setCategory(self::$defaultTrack);
        $pres->setType(self::$defaultPresentationType);
        $pres->setStartDate($start);
        $pres->setEndDate($end);
        $pres->setCreatedBy($member);
        // Deliberately unfinished (default progress/status), NOT published and NOT
        // added to any SummitSelectedPresentation group list

        // Negative control: a published presentation must NOT count as pending.
        $publishedPres = new Presentation();
        self::$summit->addEvent($publishedPres);
        $publishedPres->setTitle("Published Count Control Presentation");
        $publishedPres->setAbstract("Abstract");
        $publishedPres->setCategory(self::$defaultTrack);
        $publishedPres->setType(self::$defaultPresentationType);
        $publishedPres->setProgress(Presentation::PHASE_COMPLETE);
        $publishedPres->setStatus(Presentation::STATUS_RECEIVED);
        $publishedPres->setStartDate($start);
        $publishedPres->setEndDate($end);
        $publishedPres->setCreatedBy($member2);
        $publishedPres->publish();

        self::$em->flush();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSubmittersApiController@getSubmittersActivitiesCount",
            ['id' => self::$summit->getId(), 'filter' => ['has_pending_presentations==true']],
            [], [], [], $headers
        );

        $this->assertResponseStatus(200);
        $data = json_decode($response->getContent());
        $this->assertNotNull($data);
        $this->assertTrue(isset($data->count));
        $this->assertEquals($baseline + 1, $data->count);
    }

    public function testGetSubmittersFilterByTrackGroupId()
    {
        // Smoke test: filter[]=presentations_track_group_id==N must return HTTP 200, not 422.
        // Before Task 1 the controller rejects this field with "Filter by field ... is not allowed."
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'filter'   => [
                sprintf('presentations_track_group_id==%s', self::$defaultTrackGroup->getId()),
            ],
            'order'    => '+id',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json",
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSubmittersApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $submitters = json_decode($content);
        $this->assertNotNull($submitters);
    }

    public function testGetCurrentSummitSubmittersWithPublishedPresentations()
    {
        $member  = self::$em->find(Member::class, self::$member->getId());
        $member2 = self::$em->find(Member::class, self::$member2->getId());

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        // member2: published presentation — must appear.
        $p1 = new Presentation();
        self::$summit->addEvent($p1);
        $p1->setTitle('Submitter Api Published');
        $p1->setAbstract('Abstract');
        $p1->setCategory(self::$defaultTrack);
        $p1->setType(self::$defaultPresentationType);
        $p1->setProgress(Presentation::PHASE_COMPLETE);
        $p1->setStatus(Presentation::STATUS_RECEIVED);
        $p1->setStartDate($start);
        $p1->setEndDate($end);
        $p1->setCreatedBy($member2);
        $p1->publish();

        // member: unpublished presentation only — must NOT appear.
        $p2 = new Presentation();
        self::$summit->addEvent($p2);
        $p2->setTitle('Submitter Api Unpublished');
        $p2->setAbstract('Abstract');
        $p2->setCategory(self::$defaultTrack);
        $p2->setType(self::$defaultPresentationType);
        $p2->setProgress(Presentation::PHASE_COMPLETE);
        $p2->setStatus(Presentation::STATUS_RECEIVED);
        $p2->setStartDate($start);
        $p2->setEndDate($end);
        $p2->setCreatedBy($member);
        // deliberately not published

        self::$em->flush();

        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 100,
            'filter'   => ['has_published_presentations==true'],
            'order'    => '+id',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json",
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSubmittersApiController@getAllBySummit",
            $params, [], [], [], $headers
        );

        $this->assertResponseStatus(200);
        $ids = array_map(fn($s) => $s->id, json_decode($response->getContent())->data);

        $this->assertContains($member2->getId(), $ids,
            'submitter with a published presentation must be returned');
        $this->assertNotContains($member->getId(), $ids,
            'submitter with only unpublished presentations must be filtered out');
    }

    public function testGetCurrentSummitSubmittersActivitiesCountWithPublishedPresentations()
    {
        $member2 = self::$em->find(Member::class, self::$member2->getId());

        // The fixture sets no created_by on presentations, so the baseline is 0.
        // Seed exactly one published presentation; the count must equal exactly 1.
        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $p = new Presentation();
        self::$summit->addEvent($p);
        $p->setTitle('Count Submitter Published Api');
        $p->setAbstract('Abstract');
        $p->setCategory(self::$defaultTrack);
        $p->setType(self::$defaultPresentationType);
        $p->setProgress(Presentation::PHASE_COMPLETE);
        $p->setStatus(Presentation::STATUS_RECEIVED);
        $p->setStartDate($start);
        $p->setEndDate((clone $start)->add(new \DateInterval('PT2H')));
        $p->setCreatedBy($member2);
        $p->publish();
        self::$em->flush();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json",
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSubmittersApiController@getSubmittersActivitiesCount",
            ['id' => self::$summit->getId(), 'filter' => ['has_published_presentations==true']],
            [], [], [], $headers
        );

        $this->assertResponseStatus(200);
        $data = json_decode($response->getContent());
        $this->assertNotNull($data);
        $this->assertTrue(isset($data->count));
        $this->assertEquals(1, $data->count,
            'exactly one published presentation was seeded; count must be 1');
    }
    // -----------------------------------------------------------------
    // GET /api/v1/summits/{id}/submitters/all/events/count
    // The count must describe the presentations that satisfy the request
    // filter, not every presentation of the matched submitters.
    // -----------------------------------------------------------------

    /**
     * P1 - defaultTrack,   defaultPresentationType,    published,   media upload of type M
     * P2 - secondaryTrack, defaultPresentationType,    published,   no media upload
     * P3 - defaultTrack,   allow2VotePresentationType, unpublished, no media upload
     *
     * InsertSummitTestData never sets created_by, so these are the only presentations
     * of this submitter in the summit.
     */
    private function seedActivitiesCountScenario(): Member
    {
        $submitter = self::$em->find(Member::class, self::$member2->getId());
        $start = new \DateTime('now', new \DateTimeZone('UTC'));

        $p1 = new Presentation();
        self::$summit->addEvent($p1);
        $p1->setTitle('Api Count P1 Published Default Track');
        $p1->setAbstract('Abstract');
        $p1->setCategory(self::$defaultTrack);
        $p1->setType(self::$defaultPresentationType);
        $p1->setProgress(Presentation::PHASE_COMPLETE);
        $p1->setStatus(Presentation::STATUS_RECEIVED);
        $p1->setStartDate($start);
        $p1->setEndDate((clone $start)->add(new \DateInterval('PT2H')));
        $p1->setCreatedBy($submitter);
        $p1->publish();

        $media_upload = new PresentationMediaUpload();
        $media_upload->setName('Api Count P1 Media Upload');
        $media_upload->setDescription('Api Count P1 Media Upload Description');
        $media_upload->setFilename('p1.pdf');
        $media_upload->setMediaUploadType(self::$media_uploads_types[0]);
        $p1->addMediaUpload($media_upload);

        $p2 = new Presentation();
        self::$summit->addEvent($p2);
        $p2->setTitle('Api Count P2 Published Secondary Track');
        $p2->setAbstract('Abstract');
        $p2->setCategory(self::$secondaryTrack);
        $p2->setType(self::$defaultPresentationType);
        $p2->setProgress(Presentation::PHASE_COMPLETE);
        $p2->setStatus(Presentation::STATUS_RECEIVED);
        $p2->setStartDate($start);
        $p2->setEndDate((clone $start)->add(new \DateInterval('PT2H')));
        $p2->setCreatedBy($submitter);
        $p2->publish();

        // allow2VotePresentationType does not allow a publishing period: no start/end dates.
        $p3 = new Presentation();
        self::$summit->addEvent($p3);
        $p3->setTitle('Api Count P3 Unpublished Default Track Other Type');
        $p3->setAbstract('Abstract');
        $p3->setCategory(self::$defaultTrack);
        $p3->setType(self::$allow2VotePresentationType);
        $p3->setProgress(Presentation::PHASE_COMPLETE);
        $p3->setStatus(Presentation::STATUS_RECEIVED);
        $p3->setCreatedBy($submitter);

        self::$em->flush();

        return $submitter;
    }

    private function getActivitiesCount(array $filter): int
    {
        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json",
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSubmittersApiController@getSubmittersActivitiesCount",
            ['id' => self::$summit->getId(), 'filter' => $filter],
            [], [], [], $headers
        );

        $this->assertResponseStatus(200);
        $data = json_decode($response->getContent());
        $this->assertNotNull($data);
        $this->assertTrue(isset($data->count));

        return (int) $data->count;
    }

    public function testGetSubmittersActivitiesCountIsScopedByThePresentationFilters()
    {
        $submitter = $this->seedActivitiesCountScenario();
        $id = 'id==' . $submitter->getId();

        // no presentation-level filter: every presentation of the submitter
        $this->assertEquals(3, $this->getActivitiesCount([$id]));

        // P1 and P3 are in defaultTrack
        $this->assertEquals(2, $this->getActivitiesCount([
            $id, 'presentations_track_id==' . self::$defaultTrack->getId(),
        ]));

        // only P3 uses allow2VotePresentationType
        $this->assertEquals(1, $this->getActivitiesCount([
            $id, 'presentations_type_id==' . self::$allow2VotePresentationType->getId(),
        ]));

        // P1 and P2 are published
        $this->assertEquals(2, $this->getActivitiesCount([
            $id, 'has_published_presentations==true',
        ]));

        // only P1 carries a media upload of that type
        $this->assertEquals(1, $this->getActivitiesCount([
            $id, 'has_media_upload_with_type==' . self::$media_uploads_types[0]->getId(),
        ]));

        // only P1 is both published and in defaultTrack
        $this->assertEquals(1, $this->getActivitiesCount([
            $id,
            'has_published_presentations==true',
            'presentations_track_id==' . self::$defaultTrack->getId(),
        ]));
    }
}
