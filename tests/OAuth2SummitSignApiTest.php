<?php namespace Tests;
/*
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
 * Class OAuth2SummitSignApiTest
 * @package Tests
 */
final class OAuth2SummitSignApiTest extends ProtectedApiTestCase {
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

  public function testAddSign() {
    $params = [
      "id" => self::$summit->getId(),
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $data = [
      "template" => "template1.html",
      "location_id" => self::$summit->getLocations()[0]->getId(),
    ];

    $response = $this->action(
      "POST",
      "OAuth2SummitSignApiController@add",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $sign = json_decode($content);
    $this->assertTrue($sign->id > 0);
    return $sign;
  }

  public function testGetAllSignsByLocationAndSummit() {
    $this->testAddSign();
    $location_id = self::$summit->getLocations()[0]->getId();
    $params = [
      "id" => self::$summit->getId(),
      "page" => 1,
      "per_page" => 10,
      "filter" => ["location_id==" . $location_id],
      "order" => "+id",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2SummitSignApiController@getAllBySummit",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $this->assertResponseStatus(200);
    $res = json_decode($content);
    $this->assertTrue(!is_null($res));
    $this->assertTrue($res->total > 0);
    $this->assertTrue($res->data[0]->location_id == $location_id);
    $this->assertTrue($res->data[0]->summit_id == self::$summit->getId());
  }

  public function testUpdateSign() {
    $sign = $this->testAddSign();

    $params = [
      "id" => self::$summit->getId(),
      "sign_id" => $sign->id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $data = [
      "template" => "template2.html",
    ];

    $response = $this->action(
      "PUT",
      "OAuth2SummitSignApiController@update",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $updatedSign = json_decode($content);
    $this->assertTrue($updatedSign->id == $sign->id);
    $this->assertTrue($updatedSign->template != $sign->template);
    $this->assertTrue($updatedSign->template == "template2.html");
    return $sign;
  }
}
