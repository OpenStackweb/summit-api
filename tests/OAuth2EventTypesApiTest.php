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
 * Class OAuth2EventTypesApiTest
 */
final class OAuth2EventTypesApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetEventTypesByClassName(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'filter'   => 'class_name==EVENT_TYPE',
            'order'    => '+name'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitsEventTypesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $event_types = json_decode($content);
        $this->assertTrue(!is_null($event_types));
        return $event_types;
    }

    public function testGetEventTypesByClassNameCSV(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'filter'   => 'class_name==EVENT_TYPE',
            'order'    => '+name'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitsEventTypesApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $headers
        );

        $csv = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertTrue(!empty($csv));
    }

    public function testGetEventTypesDefaultOnes(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'filter'   => 'is_default==1',
            'order'    => '+name'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitsEventTypesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $event_types = json_decode($content);
        $this->assertTrue(!is_null($event_types));
        return $event_types;
    }

    public function testGetEventTypesNonDefaultOnes(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'filter'   => 'is_default==0',
            'order'    => '+name'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitsEventTypesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $event_types = json_decode($content);
        $this->assertTrue(!is_null($event_types));
        return $event_types;
    }

    public function testGetEventTypeAll(){
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
            "OAuth2SummitsEventTypesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $event_types = json_decode($content);
        $this->assertTrue(!is_null($event_types));
        return $event_types;
    }

    public function testGetEventTypesByClassNamePresentationType(){
        $params = [

            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'filter'   => 'class_name==PRESENTATION_TYPE',
            'order'    => '+name'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitsEventTypesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $event_types = json_decode($content);
        $this->assertTrue(!is_null($event_types));
    }

    public function testAddEventType(){
        $params = [
            'id'       => self::$summit->getId(),
        ];

        $name       = str_random(16).'_eventtype';
        $data = [
            'name'       => $name,
            'class_name' => \models\summit\SummitEventType::ClassName,
            'allows_publishing_dates' => false,
            'allows_location' => false,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitsEventTypesApiController@addEventTypeBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $event_type = json_decode($content);
        $this->assertTrue(!is_null($event_type));
        $this->assertTrue($event_type->allows_location == false);
        $this->assertTrue($event_type->allows_location == false);
        return $event_type;
    }

    public function testUpdateEventType(){

        $new_event_type = $this->testAddEventType();

        $params = [
            'id'            => self::$summit->getId(),
            'event_type_id' => $new_event_type->id,
            'expand' => 'allowed_ticket_types'
        ];

        $data = [
            'color'       => "FFAAFF",
            'class_name' => \models\summit\SummitEventType::ClassName,
            'allowed_ticket_types' => [
                self::$summit->getTicketTypes()[0]->getId(),
                self::$summit->getTicketTypes()[1]->getId(),
            ]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitsEventTypesApiController@updateEventTypeBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $event_type = json_decode($content);
        $this->assertTrue(!is_null($event_type));
        $this->assertTrue($event_type->color == '#FFAAFF');
        $this->assertTrue(count($event_type->allowed_ticket_types) == 2);
        return $event_type;
    }

    public function testDeleteDefaultOne(){

        $event_types = $this->testGetEventTypesDefaultOnes();

        $params = [
            'id'            =>  self::$summit->getId(),
            'event_type_id' => $event_types->data[0]->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitsEventTypesApiController@deleteEventTypeBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertTrue(!empty($content));
        $this->assertResponseStatus(412);
    }

    public function testDeleteNonDefaultOne(){

        $event_types = $this->testGetEventTypesNonDefaultOnes();

        $params = [
            'id'            =>  self::$summit->getId(),
            'event_type_id' => $event_types->data[0]->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitsEventTypesApiController@deleteEventTypeBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertTrue(empty($content));
        $this->assertResponseStatus(204);
    }

    public function testSeedDefaultEventTypes(){
        $params = [
            'id' =>  self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitsEventTypesApiController@seedDefaultEventTypesBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $event_types = json_decode($content);
        $this->assertTrue(!is_null($event_types));
        return $event_types;
    }


}