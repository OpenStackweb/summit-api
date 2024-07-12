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
 * Class OAuth2PublicCloudApiTest
 */
class OAuth2PublicCloudApiTest extends ProtectedApiTestCase {
  public function testGetPublicClouds() {
    $params = [
      "page" => 1,
      "per_page" => 10,
      "status" => "active",
    ];

    $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];

    $response = $this->action(
      "GET",
      "PublicCloudsApiController@getAll",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $clouds = json_decode($content);

    $this->assertResponseStatus(200);
  }

  public function testGetPublicCloudNotFound() {
    $params = [
      "id" => 0,
    ];

    $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
    $response = $this->action(
      "GET",
      "PublicCloudsApiController@get",
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

  public function testGetPublicCloudFound() {
    $params = [
      "id" => 17,
    ];

    $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
    $response = $this->action(
      "GET",
      "PublicCloudsApiController@getCloud",
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

  public function testGetDataCenterRegions() {
    $params = [
      "id" => 53,
    ];

    $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
    $response = $this->action(
      "GET",
      "PublicCloudsApiController@getCloudDataCenters",
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
