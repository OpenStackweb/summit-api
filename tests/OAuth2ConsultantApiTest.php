<?php namespace Tests;
/**
 * Copyright 2015 OpenStack Foundation
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

/**
 * Class OAuth2ConsultantApiTest
 */
class OAuth2ConsultantApiTest extends ProtectedApiTestCase {
  public function testGetConsultants() {
    $params = [
      "page" => 1,
      "per_page" => 10,
      "status" => "active",
    ];

    $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
    $response = $this->action(
      "GET",
      "OAuth2ConsultantsApiController@getConsultants",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $consultants = json_decode($content);

    $this->assertResponseStatus(200);
  }

  public function testGetConsultantsCORS() {
    $params = [
      "page" => 1,
      "per_page" => 10,
      "status" => "active",
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "HTTP_Origin" => ["www.test.com"],
      "HTTP_Access-Control-Request-Method" => "GET",
    ];

    $response = $this->action(
      "OPTIONS",
      "OAuth2ConsultantsApiController@getConsultants",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $consultants = json_decode($content);

    $this->assertResponseStatus(403);
  }

  public function testGetConsultantNotFound() {
    $params = [
      "id" => 0,
    ];

    $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
    $response = $this->action(
      "GET",
      "OAuth2ConsultantsApiController@getConsultant",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $res = json_decode($content);

    $this->assertResponseStatus(404);
  }

  public function testGetConsultantFound() {
    $params = [
      "id" => 18,
    ];

    $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
    $response = $this->action(
      "GET",
      "OAuth2ConsultantsApiController@getConsultant",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $res = json_decode($content);

    $this->assertResponseStatus(200);
  }

  public function testGetOffices() {
    $params = [
      "id" => 19,
    ];

    $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
    $response = $this->action(
      "GET",
      "OAuth2ConsultantsApiController@getOffices",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $res = json_decode($content);

    $this->assertResponseStatus(200);
  }
}
