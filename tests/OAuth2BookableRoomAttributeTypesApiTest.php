<?php namespace Tests;
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
 * Class OAuth2BookableRoomAttributeTypesApiTest
 * @package Tests
 */
final class OAuth2BookableRoomAttributeTypesApiTest extends \ProtectedApiTest
{
    public function testGetBookableAttributeTypesBySummit($summit_id = 27){
        $params  = [
            'id'  => $summit_id,
            'page'     => 1 ,
            'per_page' => 10,
            'expand' => 'values'
        ];

        $headers = array("HTTP_Authorization" => " Bearer " .$this->access_token);
        $response = $this->action(
            "GET",
            "OAuth2SummitBookableRoomsAttributeTypeApiController@getAllBookableRoomAttributeTypes",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content     = $response->getContent();
        $attributes  = json_decode($content);

        $this->assertResponseStatus(200);
        $this->assertTrue(count($attributes->data) > 0);
    }


    public function testAddAttributeType($summit_id = 27){
        $params = [
            'id'  => $summit_id,
        ];

        $type  = str_random(16).'_attribute_type';

        $data = [
            'type' => $type
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBookableRoomsAttributeTypeApiController@addBookableRoomAttributeType",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $attribute_type = json_decode($content);
        $this->assertTrue(!is_null($attribute_type));
        return $attribute_type;
    }

    public function testAddAttributeValue($summit_id = 27){
        $type = $this->testAddAttributeType($summit_id);
        $params = [
            'id'  => $summit_id,
            'type_id' => $type->id,
        ];

        $val  = str_random(16).'_attribute_value';

        $data = [
            'value' => $val
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBookableRoomsAttributeTypeApiController@addBookableRoomAttributeValue",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $attribute_value = json_decode($content);
        $this->assertTrue(!is_null($attribute_value));
        return $attribute_value;
    }
}