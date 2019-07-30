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


/**
 * Class OAuth2SummitAccessLevelTypeTest
 */
final class OAuth2SummitAccessLevelTypeTest extends ProtectedApiTest
{
    /**
     * @param int $summit_id
     * @return mixed
     */
    public function testAddAccessLevel($summit_id = 27){
        $params = [
            'id' => $summit_id,
        ];

        $name        = str_random(16).'_access_level';
        $template = <<<HTML
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 105">
  <g fill="#97C024" stroke="#97C024" stroke-linejoin="round" stroke-linecap="round">
    <path d="M14,40v24M81,40v24M38,68v24M57,68v24M28,42v31h39v-31z" stroke-width="12"/>
    <path d="M32,5l5,10M64,5l-6,10 " stroke-width="2"/>
  </g>
  <path d="M22,35h51v10h-51zM22,33c0-31,51-31,51,0" fill="#97C024"/>
  <g fill="#FFF">
    <circle cx="36" cy="22" r="2"/>
    <circle cx="59" cy="22" r="2"/>
  </g>
</svg>
HTML;

        $data = [
            'name'             => $name,
            'description'      => "this is a description",
            'template_content' => $template,
            'tag_name'         => "droid",
            'is_default'       => true,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitAccessLevelTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $access_level = json_decode($content);
        $this->assertTrue(!is_null($access_level));
        $this->assertTrue($access_level->name == $name);
        return $access_level;
    }

    public function testUpdateAccessLevel($summit_id = 27){

        $access_level_old = $this->testAddAccessLevel();
        $params = [
            'id' => $summit_id,
            "level_id" => $access_level_old->id
        ];

        $data = [
            'description'      => "this is a description update",
            'is_default'       => false,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitAccessLevelTypeApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $access_level = json_decode($content);
        $this->assertTrue(!is_null($access_level));
        $this->assertTrue($access_level->name == $access_level_old->name);
        return $access_level;
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
            "OAuth2SummitAccessLevelTypeApiController@getAllBySummit",
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
    public function testDeleteAccessLevel($summit_id=27){
        $access_level_old = $this->testAddAccessLevel();
        $params = [
            'id' => $summit_id,
            "level_id" => $access_level_old->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitAccessLevelTypeApiController@delete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }
}