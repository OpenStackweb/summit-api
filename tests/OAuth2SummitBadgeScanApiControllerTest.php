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
 * Class OAuth2SummitBadgeScanApiControllerTest
 */
class OAuth2SummitBadgeScanApiControllerTest extends ProtectedApiTest
{
    /**
     * @param int $summit_id
     * @return mixed
     */
    public function testAddBadgeScan($summit_id = 1){
        $params = [
            'id' => $summit_id,
        ];

        $data = [
            'qr_code' => "BADGE_REGISTRATIONDEVSUMMIT2019|REGISTRATIONDEVSUMMIT2019_TICKET_5D7BE0E518E8C161661586|santipalenque@gmail.com|Santiago, Palenque",
            "scan_date" => 1572019200,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $scan = json_decode($content);
        $this->assertTrue(!is_null($scan));
        return $scan;
    }

    /**
     * @param int $summit_id
     * @return mixed
     */
    public function testGetAllMyBadgeScans($summit_id = 1){

        $params = [
            'id' => $summit_id,
            'filter'=> 'attendee_email=@santi',
            'expand' => 'sponsor,badge,badge.ticket,badge.ticket.owner'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@getAllMyBadgeScans",
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

}