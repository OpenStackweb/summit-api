<?php namespace Tests;
/**
 * Copyright 2018 OpenStack Foundation
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
 * Class OAuth2TicketTypesApiTest
 * @package Tests
 */
final class OAuth2TicketTypesApiTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertTestData();
    }

    protected function tearDown(): void
    {
        self::clearTestData();
        parent::tearDown();
    }

    public function testGetTicketTypes(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+name'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitsTicketTypesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket_types = json_decode($content);
        $this->assertTrue(!is_null($ticket_types));
        return $ticket_types;
    }

    public function testGetTicketTypesById(){
        $ticket_types_response = $this->testGetTicketTypes();

        $params = [
            'id'       => self::$summit->getId(),
            'ticket_type_id' => $ticket_types_response->data[0]->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitsTicketTypesApiController@getTicketTypeBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket_type = json_decode($content);
        $this->assertTrue(!is_null($ticket_type));
        return $ticket_type;
    }

    public function testAddTicketType(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name        = str_random(16).'_ticket_type';
        $external_id = str_random(16).'_external_id';

        $data = [
            'name'        => $name,
            'external_id' => $external_id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitsTicketTypesApiController@addTicketTypeBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket_type = json_decode($content);
        $this->assertTrue(!is_null($ticket_type));
        $this->assertTrue($ticket_type->name == $name);
        $this->assertTrue($ticket_type->external_id == $external_id);
        return $ticket_type;
    }

    public function testUpdateTicketType(){

        $ticket_type = $this->testAddTicketType();

        $params = [
            'id'             => self::$summit->getId(),
            'ticket_type_id' => $ticket_type->id
        ];

        $data = [
            'description' => 'test description',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitsTicketTypesApiController@updateTicketTypeBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket_type = json_decode($content);
        $this->assertTrue(!is_null($ticket_type));
        $this->assertTrue($ticket_type->description == 'test description');
        return $ticket_type;
    }


    public function testSeedDefaultTicketTypes(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitsTicketTypesApiController@seedDefaultTicketTypesBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket_types = json_decode($content);
        $this->assertTrue(!is_null($ticket_types));
        return $ticket_types;
    }
}