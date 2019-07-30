<?php
/**
 * Copyright 2019 OpenStack Foundation
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

final class OAuth2SummitBadgeTypeApiTest extends ProtectedApiTest
{

    /**
     * @param int $summit_id
     * @param bool $is_default
     * @return mixed
     */
    public function testAddBadgeType($summit_id = 27, $is_default = false){
        $params = [
            'id' => $summit_id,
        ];

        $name        = str_random(16).'_badge_type';
        $template = <<<HTML
<html>
<body>
<div>
<h1>this is a badge</h1>
<features-container></features-container>
</div>
</body>
</html>
HTML;

        $data = [
            'name'             => $name,
            'description'      => "this is a description",
            'template_content' => $template,
            'is_default'       => $is_default
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge_type = json_decode($content);
        $this->assertTrue(!is_null($badge_type));
        $this->assertTrue($badge_type->name == $name);
        return $badge_type;
    }


    public function testUpdateBadgeFeatureType($summit_id = 27){

        $badge_type_old = $this->testAddBadgeType($summit_id, false);
        $params = [
            'id' => $summit_id,
            "badge_type_id" => $badge_type_old->id
        ];

        $data = [
            'description'      => "this is a description update",
            'is_default'       => true,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeTypeApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge_type = json_decode($content);
        $this->assertTrue(!is_null($badge_type));
        $this->assertTrue($badge_type->name == $badge_type_old->name);
        return $badge_type;
    }


    public function testGetAllBySummit($summit_id=27){
        $params = [
            'id' => $summit_id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeTypeApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertTrue(!is_null($data));
        return $data;
    }

    /**
     * @param int $summit_id
     */
    public function testDeleteBadgeFeatureType($summit_id=27){
        $badge_type_old = $this->testAddBadgeType();
        $params = [
            'id' => $summit_id,
            "feature_id" => $badge_type_old->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitBadgeTypeApiController@delete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testAssignAccessLevelToBadgeType($summit_id=27){
        $badge_type_old = $this->testAddBadgeType();
        $params = [
            'id' => $summit_id,
            "feature_id" => $badge_type_old->id,
            'access_level_id' => 1
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeTypeApiController@addAccessLevelToBadgeType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }

    public function testRemoveAccessLevelToBadgeType($summit_id=27){
        $badge_type_old = $this->testAddBadgeType();
        $params = [
            'id' => $summit_id,
            "feature_id" => $badge_type_old->id,
            'access_level_id' => 1
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeTypeApiController@addAccessLevelToBadgeType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $response = $this->action(
            "DELETE",
            "OAuth2SummitBadgeTypeApiController@removeAccessLevelFromBadgeType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }
}