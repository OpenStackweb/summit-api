<?php namespace Tests;
/**
 * Copyright 2021 OpenStack Foundation
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
 * Class OAuth2SummitTrackChairsApiTest
 * @package Tests
 */
class OAuth2SummitTrackChairsApiTest extends ProtectedApiTestCase {
  use InsertSummitTestData;

  use InsertMemberTestData;

  protected function setUp(): void {
    $this->setCurrentGroup(IGroup::TrackChairs);
    parent::setUp();
    self::insertSummitTestData();
    self::$summit_permission_group->addMember(self::$member);
    self::$em->persist(self::$summit);
    self::$em->persist(self::$summit_permission_group);
    self::$em->flush();
    $track_chair = self::$summit->addTrackChair(self::$member, [self::$defaultTrack]);
    self::$em->persist(self::$summit);
    self::$em->flush();
  }

  protected function tearDown(): void {
    self::clearSummitTestData();
    parent::tearDown();
  }

  public function testGetAllTrackChairsPerSummit() {
    $params = [
      "id" => self::$summit->getId(),
      "filter" => "track_id==" . self::$defaultTrack->getId(),
      "page" => 1,
      "per_page" => 10,
      "order" => "+id",
      "expand" => "member,categories",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitTrackChairsApiController@getAllBySummit",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);
    $track_chairs = json_decode($content);
    $this->assertTrue(!is_null($track_chairs));
    $this->assertTrue($track_chairs->total == 1);
    return $track_chairs;
  }

  public function testGetAllTrackChairsPerSummitAndLastName() {
    $params = [
      "id" => self::$summit->getId(),
      "filter" => sprintf(
        "member_first_name=@%s,member_last_name=@%s,member_email=@%s",
        self::$member->getLastName(),
        self::$member->getLastName(),
        self::$member->getLastName(),
      ),
      "page" => 1,
      "per_page" => 10,
      "order" => "+id",
      "expand" => "member,categories",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitTrackChairsApiController@getAllBySummit",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);
    $track_chairs = json_decode($content);
    $this->assertTrue(!is_null($track_chairs));
    $this->assertTrue($track_chairs->total == 1);
    return $track_chairs;
  }

  public function testAddTrackChair() {
    $params = [
      "id" => self::$summit->getId(),
      "expand" => "member,categories",
    ];

    $data = [
      "member_id" => self::$member2->getId(),
      "categories" => [self::$defaultTrack->getId()],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitTrackChairsApiController@add",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $track_chair = json_decode($content);
    $this->assertTrue(!is_null($track_chair));
  }

  public function testUpdateTrackChair() {
    $params = [
      "id" => self::$summit->getId(),
      "expand" => "member,categories,categories.selection_lists",
    ];

    $data = [
      "member_id" => self::$member2->getId(),
      "categories" => [self::$defaultTrack->getId()],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitTrackChairsApiController@add",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $track_chair = json_decode($content);
    $this->assertTrue(!is_null($track_chair));

    $params = [
      "id" => self::$summit->getId(),
      "track_chair_id" => $track_chair->id,
      "expand" => "member,categories,categories.selection_lists",
    ];

    $data = [
      "member_id" => self::$member2->getId(),
      "categories" => [self::$secondaryTrack->getId()],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitTrackChairsApiController@update",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $track_chair = json_decode($content);
    $this->assertTrue(!is_null($track_chair));
  }

  public function testAddTrackChairAndAddCategory() {
    $params = [
      "id" => self::$summit->getId(),
      "expand" => "member,categories",
    ];

    $data = [
      "member_id" => self::$member2->getId(),
      "categories" => [self::$defaultTrack->getId()],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitTrackChairsApiController@add",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $track_chair = json_decode($content);
    $this->assertTrue(!is_null($track_chair));

    // add to collection

    $params = [
      "id" => self::$summit->getId(),
      "track_chair_id" => $track_chair->id,
      "track_id" => self::$secondaryTrack->getId(),
      "expand" => "member,categories",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitTrackChairsApiController@addTrack2TrackChair",
      $params,
      [],
      [],
      [],
      $headers,
      "",
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $track_chair = json_decode($content);
    $this->assertTrue(!is_null($track_chair));
    $this->assertTrue(count($track_chair->categories) == 2);
  }

  public function testAddTrackChairAndDeleteCategory() {
    $params = [
      "id" => self::$summit->getId(),
      "expand" => "member,categories",
    ];

    $data = [
      "member_id" => self::$member2->getId(),
      "categories" => [self::$defaultTrack->getId(), self::$secondaryTrack->getId()],
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitTrackChairsApiController@add",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $track_chair = json_decode($content);
    $this->assertTrue(!is_null($track_chair));
    $this->assertTrue(count($track_chair->categories) == 2);
    // add to collection

    $params = [
      "id" => self::$summit->getId(),
      "track_chair_id" => $track_chair->id,
      "track_id" => self::$defaultTrack->getId(),
      "expand" => "member,categories",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "DELETE",
      "OAuth2SummitTrackChairsApiController@removeFromTrackChair",
      $params,
      [],
      [],
      [],
      $headers,
      "",
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $track_chair = json_decode($content);
    $this->assertTrue(!is_null($track_chair));
    $this->assertTrue(count($track_chair->categories) == 1);
  }
}
