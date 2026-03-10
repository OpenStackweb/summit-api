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
use App\Models\Foundation\Main\IGroup;

/**
 * Class OAuth2SummitTaxTypeApiTest
 */
class OAuth2SummitTaxTypeApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp():void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetAllTaxTypes(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTaxTypeApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertNotNull($data);
    }

    private function createTicketType(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16).'_ticket_type';
        $data = [
            'name' => $name,
            'cost' => 250.25,
            'currency' => 'USD',
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

        $this->assertResponseStatus(201);
        return json_decode($response->getContent());
    }

    public function testAddTaxType(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16).'_iva';
        $data = [
            'name' => $name,
            'rate' => 21.0
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitTaxTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $tax_type = json_decode($content);
        $this->assertNotNull($tax_type);
        $this->assertEquals($name, $tax_type->name);
        return $tax_type;
    }

    public function testGetTaxType(){
        $tax_type = $this->testAddTaxType();

        $params = [
            'id' => self::$summit->getId(),
            'tax_id' => $tax_type->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTaxTypeApiController@get",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $retrieved = json_decode($content);
        $this->assertNotNull($retrieved);
        $this->assertEquals($tax_type->id, $retrieved->id);
    }

    public function testUpdateTaxType(){
        $tax_type = $this->testAddTaxType();

        $params = [
            'id' => self::$summit->getId(),
            'tax_id' => $tax_type->id,
        ];

        $updated_name = str_random(16).'_updated';
        $data = [
            'name' => $updated_name,
            'rate' => 15.0,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitTaxTypeApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $updated = json_decode($content);
        $this->assertNotNull($updated);
        $this->assertEquals($updated_name, $updated->name);
    }

    public function testDeleteTaxType(){
        $tax_type = $this->testAddTaxType();

        $params = [
            'id' => self::$summit->getId(),
            'tax_id' => $tax_type->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitTaxTypeApiController@delete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }

    public function testAddTaxToTicketType(){
        $tax_type = $this->testAddTaxType();
        $ticket_type = $this->createTicketType();

        $params = [
            'id' => self::$summit->getId(),
            'tax_id' => $tax_type->id,
            'ticket_type_id' => $ticket_type->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitTaxTypeApiController@addTaxToTicketType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $result = json_decode($content);
        $this->assertNotNull($result);
        return ['tax_id' => $tax_type->id, 'ticket_type_id' => $ticket_type->id];
    }

    public function testRemoveTaxFromTicketType(){
        $result = $this->testAddTaxToTicketType();

        $params = [
            'id' => self::$summit->getId(),
            'tax_id' => $result['tax_id'],
            'ticket_type_id' => $result['ticket_type_id'],
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitTaxTypeApiController@removeTaxFromTicketType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(201);
    }
}
