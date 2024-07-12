<?php namespace Tests;
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

use App\Models\Foundation\Main\IGroup;

/**
 * Class OAuth2SummitProposedScheduleApiTest
 * @package Tests
 */
final class OAuth2SummitProposedScheduleApiTest extends ProtectedApiTestCase {
  use InsertSummitTestData;

  use InsertMemberTestData;

  protected function setUp(): void {
    $this->setCurrentGroup(IGroup::TrackChairs);
    parent::setUp();
    self::insertMemberTestData(IGroup::TrackChairs);
    self::$defaultMember = self::$member;
    self::$defaultMember2 = self::$member2;
    self::insertSummitTestData();
    self::$summit_permission_group->addMember(self::$member);
    self::$em->persist(self::$summit);
    self::$em->persist(self::$summit_permission_group);
    self::$em->flush();
  }

  protected function tearDown(): void {
    self::clearSummitTestData();
    self::clearMemberTestData();
    parent::tearDown();
  }

  public function testGetProposedScheduleEvents() {
    $params = [
      "id" => self::$summit->getId(),
      "source" => "track-chairs",
      "page" => 1,
      "per_page" => 10,
      "filter" => "presentation_title=@Presentation Title",
      "order" => "-track_id",
      "expand" => "created_by,location",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitProposedScheduleApiController@getProposedScheduleEvents",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);
    $page = json_decode($content);
    $this->assertTrue(!is_null($page));
  }

  public function testPublishScheduleEvent() {
    $start_date = new \DateTime("now", new \DateTimeZone("UTC"));
    $end_date = (clone $start_date)->add(new \DateInterval("P10D"));

    $presentation = self::$presentations[22];

    $params = [
      "id" => self::$summit->getId(),
      "source" => "track-chairs",
      "presentation_id" => $presentation->getId(),
      "expand" => "schedule",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $payload = [
      "location_id" => $presentation->getLocation()->getId(),
      "start_date" => $start_date->getTimestamp(),
      "end_date" => $end_date->getTimestamp(),
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitProposedScheduleApiController@publish",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($payload),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $scheduled_event = json_decode($content);
    $this->assertTrue(!is_null($scheduled_event));

    return $scheduled_event;
  }

  public function testUnPublishScheduleEvent() {
    $scheduled_event = $this->testPublishScheduleEvent();

    $params = [
      "id" => $scheduled_event->schedule->summit_id,
      "source" => $scheduled_event->schedule->source,
      "presentation_id" => $scheduled_event->summit_event_id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "DELETE",
      "OAuth2SummitProposedScheduleApiController@unpublish",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $this->assertResponseStatus(204);
  }

  public function testPublishAll() {
    $scheduled_event = $this->testPublishScheduleEvent();

    $params = [
      "id" => $scheduled_event->schedule->summit_id,
      "source" => "track-chairs",
      "filter" => ["track_id==" . self::$defaultTrack->getId()],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitProposedScheduleApiController@publishAll",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $scheduled_event = json_decode($content);
    $this->assertTrue(!is_null($scheduled_event));
  }

  public function testAddScheduleReview() {
    $params = [
      "id" => self::$summit->getId(),
      "source" => "track-chairs",
      "track_id" => self::$summit->getPresentationCategories()[0]->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $payload = [
      "message" => "TEST REVIEW",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitProposedScheduleApiController@send2Review",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($payload),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $scheduled_event = json_decode($content);
    $this->assertTrue(!is_null($scheduled_event));

    return $scheduled_event;
  }

  public function testRemoveScheduleReview() {
    $params = [
      "id" => self::$summit->getId(),
      "source" => "track-chairs",
      "track_id" => self::$summit->getPresentationCategories()[0]->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $payload = [
      "message" => "NOT APPROVED",
    ];

    $response = $this->action(
      "DELETE",
      "OAuth2SummitProposedScheduleApiController@removeReview",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($payload),
    );

    $this->assertResponseStatus(204);
  }

  public function testGetProposedScheduleReviewSubmissions() {
    $params = [
      "id" => self::$summit->getId(),
      "source" => "track-chairs",
      "page" => 1,
      "per_page" => 10,
      "filter" => "track_id==" . self::$summit->getPresentationCategories()[0]->getId(),
      "expand" => "created_by,track",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitProposedScheduleApiController@getProposedScheduleReviewSubmissions",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);
    $page = json_decode($content);
    $this->assertTrue(!is_null($page));
  }
}
