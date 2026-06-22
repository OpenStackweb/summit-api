<?php namespace Tests;
use App\Models\Foundation\Main\IGroup;
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

    public function testGetCurrentSummitSubmittersWithPendingPresentations()
    {
        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        $pres = new Presentation();
        self::$summit->addEvent($pres);
        $pres->setTitle("Pending Test Presentation");
        $pres->setAbstract("Abstract");
        $pres->setCategory(self::$defaultTrack);
        $pres->setType(self::$defaultPresentationType);
        $pres->setProgress(Presentation::PHASE_COMPLETE);
        $pres->setStatus(Presentation::STATUS_RECEIVED);
        $pres->setStartDate($start);
        $pres->setEndDate($end);
        $pres->setCreatedBy(self::$defaultMember);
        // Deliberately NOT published and NOT added to any SummitSelectedPresentation group list
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

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $submitters = json_decode($content);
        $this->assertTrue(!is_null($submitters));
        $this->assertTrue(count($submitters->data) > 0);
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
}