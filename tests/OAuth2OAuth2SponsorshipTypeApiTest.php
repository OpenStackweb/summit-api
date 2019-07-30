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
 * Class OAuth2OAuth2SponsorshipTypeApiTest
 */
final class OAuth2OAuth2SponsorshipTypeApiTest extends ProtectedApiTest
{

    /**
     * @return mixed
     */
    public function testAddSponsorShipType(){
        $params = [

        ];

        $name = str_random(16).'_sponsorship';

        $data = [
            'name'  => $name,
            'label' => $name,
            'size'  => \models\summit\ISponsorshipTypeConstants::BigSize,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SponsorshipTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsorship_type = json_decode($content);
        $this->assertTrue(!is_null($sponsorship_type));
        $this->assertTrue($sponsorship_type->name == $name);
        return $sponsorship_type;
    }

    public function testGetAllSponsorShipTypes(){
        $params = [
            'filter' => 'size==Big'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SponsorshipTypeApiController@getAll",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        return $page;
    }

    /**
     * @return mixed
     */
    public function testUpdateSponsorShipType(){
        $sponsorship_type = $this->testAddSponsorShipType();
        $params = [
            'id' => $sponsorship_type->id
        ];

        $data = [
            'order' => 1
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SponsorshipTypeApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $sponsorship_type = json_decode($content);
        $this->assertTrue(!is_null($sponsorship_type));
        return $sponsorship_type;
    }

    /**
     * @return mixed
     */
    public function testDeleteSponsorShipType(){
        $sponsorship_type = $this->testAddSponsorShipType();
        $params = [
            'id' => $sponsorship_type->id
        ];


        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SponsorshipTypeApiController@delete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
        $this->assertTrue(empty($content));

    }
}