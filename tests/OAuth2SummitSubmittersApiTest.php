<?php namespace Tests;
use App\Models\Foundation\Main\IGroup;

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
final class OAuth2SummitSubmittersApiTest extends ProtectedApiTestCase {
  use InsertSummitTestData;

  use InsertMemberTestData;

  protected function setUp(): void {
    parent::setUp();
    self::insertMemberTestData(IGroup::TrackChairs);
    self::$defaultMember = self::$member;
    self::$defaultMember2 = self::$member2;
    self::insertSummitTestData();
  }

  protected function tearDown(): void {
    self::clearSummitTestData();
    parent::tearDown();
  }

  public function testGetCurrentSummitSubmittersOrderByID() {
    $params = [
      "id" => self::$summit->getId(),
      "page" => 1,
      "per_page" => 10,
      "filter" => ["is_speaker==true"],
      "order" => "+id",
      "expand" => "accepted_presentations,alternate_presentations,rejected_presentations",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitSubmittersApiController@getAllBySummit",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);
    $submitters_response = json_decode($content);
    $this->assertTrue(!is_null($submitters_response));
    $submitters = $submitters_response->data;
    $this->assertNotEmpty($submitters);
  }

  public function testGetCurrentSummitSubmittersByName() {
    $params = [
      "id" => self::$summit->getId(),
      "page" => 1,
      "per_page" => 10,
      "filter" => ["first_name=@b||a,last_name=@b,email=@b"],
      "order" => "+id",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitSubmittersApiController@getAllBySummit",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);
    $submitters = json_decode($content);
    $this->assertTrue(!is_null($submitters));
  }

  public function testGetCurrentSummitSubmittersWithAcceptedPresentations() {
    $params = [
      "id" => self::$summit->getId(),
      "page" => 1,
      "per_page" => 10,
      "filter" => ["has_accepted_presentations==true"],
      "order" => "+id",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitSubmittersApiController@getAllBySummit",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);
    $submitters = json_decode($content);
    $this->assertTrue(!is_null($submitters));
  }

  public function testExportCurrentSummitSubmittersWhoAreSpeakers() {
    $params = [
      "id" => self::$summit->getId(),
      "page" => 1,
      "per_page" => 10,
      "filter" => ["is_speaker==false"],
      "order" => "+id",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitSubmittersApiController@getAllBySummitCSV",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $csv = $response->getContent();
    $this->assertResponseStatus(200);
    $this->assertNotEmpty($csv);
  }

  public function testSendSpeakersBulkEmail() {
    $params = [
      "id" => self::$summit->getId(),
      "filter" => ["first_name=@b||a,last_name=@b,email=@b"],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $data = [
      "email_flow_event" => "SUMMIT_SUBMISSIONS_PRESENTATION_SUBMITTER_ACCEPTED_ALTERNATE",
      //            'submitter_ids'       => [
      //                9161
      //            ],
      "test_email_recipient" => "test_recip@nomail.com",
      "outcome_email_recipient" => "outcome_recip@nomail.com",
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitSubmittersApiController@send",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $this->assertResponseStatus(200);
  }

  public function testGetSubmittersWithSubmittedMediaUploadsWithType() {
    $media_upload_ids = array_map(function ($v) {
      return $v->getId();
    }, self::$media_uploads_types);

    $params = [
      "id" => self::$summit->getId(),
      "page" => 1,
      "per_page" => 10,
      "filter" => [
        "has_accepted_presentations==true",
        "has_alternate_presentations==false",
        "has_rejected_presentations==false",
        sprintf("has_media_upload_with_type==%s", implode("||", $media_upload_ids)),
      ],
      "expand" => "presentations,accepted_presentations",
      "order" => "+id",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitSubmittersApiController@getAllBySummit",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);
    $submitters = json_decode($content);
    $this->assertTrue(!is_null($submitters));
  }
}
