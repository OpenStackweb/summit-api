<?php namespace Tests;
use App\Models\Foundation\Main\IGroup;
use models\main\Member;
use models\summit\Presentation;

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
}