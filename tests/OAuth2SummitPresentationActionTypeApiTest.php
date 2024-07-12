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
use models\summit\PresentationActionType;

/**
 * Class OAuth2SummitPresentationActionTypeApiTest
 * @package Tests
 */
final class OAuth2SummitPresentationActionTypeApiTest extends ProtectedApiTestCase {
  use InsertSummitTestData;

  use InsertMemberTestData;

  static $action1 = null;
  static $action2 = null;

  protected function setUp(): void {
    $this->setCurrentGroup(IGroup::TrackChairs);
    parent::setUp();
    self::insertSummitTestData();
    self::$summit_permission_group->addMember(self::$member);
    self::$em->persist(self::$summit);
    self::$em->persist(self::$summit_permission_group);
    self::$em->flush();
    $track_chair = self::$summit->addTrackChair(self::$member, [self::$defaultTrack]);

    self::$action1 = new PresentationActionType();
    self::$action1->setLabel("ACTION1");
    self::$action1->setOrder(1);
    self::$summit->addPresentationActionType(self::$action1);

    self::$action2 = new PresentationActionType();
    self::$action2->setLabel("ACTION2");
    self::$action2->setOrder(2);
    self::$summit->addPresentationActionType(self::$action2);

    self::$em->persist(self::$summit);
    self::$em->flush();
  }

  protected function tearDown(): void {
    self::clearSummitTestData();
    parent::tearDown();
  }

  public function testGetAllPerSummit() {
    $params = [
      "summit_id" => self::$summit->getId(),
      "page" => 1,
      "per_page" => 10,
      "order" => "+order",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitPresentationActionTypeApiController@getAllBySummit",
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
    $this->assertTrue($page->total == 2);
  }

  public function testGetAllPerSummitWithFiltering() {
    $params = [
      "summit_id" => self::$summit->getId(),
      "filter" => "label==ACTION1",
      "page" => 1,
      "per_page" => 10,
      "order" => "+order",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitPresentationActionTypeApiController@getAllBySummit",
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
    $this->assertTrue($page->total == 1);
  }

  public function testGetActionTypeById() {
    $params = [
      "summit_id" => self::$summit->getId(),
      "action_id" => self::$action1->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitPresentationActionTypeApiController@get",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);
    $action = json_decode($content);
    $this->assertTrue(!is_null($action));
    $this->assertTrue($action->id == self::$action1->getId());
  }

  public function testReorderAction() {
    $params = [
      "summit_id" => self::$summit->getId(),
      "action_id" => self::$action2->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $payload = [
      "order" => 1,
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitPresentationActionTypeApiController@update",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($payload),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $action = json_decode($content);
    $this->assertTrue(!is_null($action));
    $this->assertTrue($action->id == self::$action2->getId());
    $this->assertTrue($action->order == 1);
  }

  public function testAddAction() {
    $params = [
      "summit_id" => self::$summit->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $payload = [
      "label" => "ACTION3",
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitPresentationActionTypeApiController@add",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($payload),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $action = json_decode($content);
    $this->assertTrue(!is_null($action));
    $this->assertTrue($action->label == "ACTION3");
    $this->assertTrue($action->order == 3);
  }

  public function testUpdateAction() {
    $params = [
      "summit_id" => self::$summit->getId(),
      "action_id" => self::$action2->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $payload = [
      "label" => self::$action2->getLabel() . " UPDATE",
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitPresentationActionTypeApiController@update",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($payload),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $action = json_decode($content);
    $this->assertTrue(!is_null($action));
    $this->assertTrue($action->id == self::$action2->getId());
    $this->assertTrue($action->label == self::$action2->getLabel());
  }

  public function testDeleteAction() {
    $params = [
      "summit_id" => self::$summit->getId(),
      "action_id" => self::$action2->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "DELETE",
      "OAuth2SummitPresentationActionTypeApiController@delete",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(204);
    $this->assertTrue(empty($content));
  }
}
