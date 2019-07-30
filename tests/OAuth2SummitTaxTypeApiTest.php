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
 * Class OAuth2SummitTaxTypeApiTest
 */
class OAuth2SummitTaxTypeApiTest extends ProtectedApiTest
{
    private $ticket_type;

    /**
     * @param int $summit_id
     * @return mixed
     */
    public function addTicketType($summit_id = 27){
        $params = [
            'id' => $summit_id,
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

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket_type = json_decode($content);
        $this->assertTrue(!is_null($ticket_type));
        $this->assertTrue($ticket_type->name == $name);
        return $ticket_type;
    }

    /**
     * @param int $summit_id
     * @return mixed
     */
    public function testAddTaxType($summit_id = 27){
        $this->ticket_type = $this->addTicketType($summit_id);

        $params = [
            'id' => $summit_id,
        ];

        $name        = str_random(16).'_iva';
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
        $this->assertTrue(!is_null($tax_type));
        $this->assertTrue($tax_type->name == $name);

        $params = [
            'id' => $summit_id,
            'tax_id' => $tax_type->id,
            'ticket_type_id' =>    $this->ticket_type->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
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
        $tax_type = json_decode($content);

        return $tax_type;
    }
}