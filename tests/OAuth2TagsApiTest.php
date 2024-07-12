<?php namespace Tests;
/**
 * Copyright 2017 OpenStack Foundation
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

class OAuth2TagsApiTest extends ProtectedApiTestCase {
  public function testGetTags() {
    $params = [
      //AND FILTER
      "filter" => ["tag=@test"],
      "order" => "+id",
    ];

    $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
    $response = $this->action(
      "GET",
      "OAuth2TagsApiController@getAll",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $tags = json_decode($content);
    $this->assertTrue(!is_null($tags));
    $this->assertResponseStatus(200);
  }

  public function testGetTag($tag_id = 1) {
    $params = [
      "id" => $tag_id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "GET",
      "OAuth2TagsApiController@getTag",
      $params,
      [],
      [],
      [],
      $headers,
    );

    $content = $response->getContent();
    $tag = json_decode($content);
    $this->assertTrue(!is_null($tag));
    $this->assertResponseStatus(200);
  }

  public function testAddTag() {
    $params = [];

    $tag = str_random(16) . "_tag";
    $data = [
      "tag" => $tag,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "POST",
      "OAuth2TagsApiController@addTag",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $tag = json_decode($content);
    $this->assertTrue(!is_null($tag));
    return $tag;
  }

  public function testUpdateTag($tag_id = 3) {
    $params = [
      "id" => $tag_id,
    ];

    $tag = "Business";
    $data = [
      "tag" => $tag,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $response = $this->action(
      "PUT",
      "OAuth2TagsApiController@updateTag",
      $params,
      [],
      [],
      [],
      $headers,
      json_encode($data),
    );

    $content = $response->getContent();
    $this->assertResponseStatus(201);
    $updated_tag = json_decode($content);
    $this->assertTrue(!is_null($updated_tag));
    $this->assertTrue($updated_tag->tag == $tag);
    return $tag;
  }

  public function testDeleteTag($tag_id = 503) {
    $params = [
      "id" => $tag_id,
    ];

    $headers = [
      "HTTP_Authorization" => " Bearer " . $this->access_token,
      "CONTENT_TYPE" => "application/json",
    ];

    $this->action("DELETE", "OAuth2TagsApiController@deleteTag", $params, [], [], [], $headers);

    $this->assertResponseStatus(204);
  }
}
