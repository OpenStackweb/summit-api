<?php
/**
 * Copyright 2020 OpenStack Foundation
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

class OAuth2SummitMediaFileTypeApiControllerTest
    extends ProtectedApiTest
{

    public function testAdd(){
        $payload = [
            'name' => str_random(16).'summit_media_file_type',
            'allowed_extensions' => ['PDF', 'SVG']
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitMediaFileTypeApiController@add",
            [],
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $response = json_decode($content, true);
        $this->assertResponseStatus(201);
        $this->assertTrue(isset($response['id']));
    }

    public function testGetAll(){
        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitMediaFileTypeApiController@getAll",
            [],
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $response = json_decode($content, true);
        $this->assertTrue(isset($response['total']));
        $this->assertTrue(intval($response['total']) > 0);
    }

}