<?php namespace Tests;
use App\Models\Foundation\Main\IGroup;

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
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertTestData();
    }

    protected function tearDown():void
    {
        self::clearTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testAddBadgeScan(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());

        $data = [
            'qr_code' => sprintf(
            "%s|%s|%s|%s",
                self::$summit->getBadgeQRPrefix(),
                $attendee->getTickets()[0]->getNumber(),
                $attendee->getEmail(),
                $attendee->getFullName(),
            ),
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

    public function testGetAllMyBadgeScans(){

        $params = [
            'id'    =>  self::$summit->getId(),
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

    public function testCheckInBadgeScan(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());

        $data = [
            'qr_code' => sprintf
            (
                "%s|%s|%s|%s",
                self::$summit->getBadgeQRPrefix(),
                $attendee->getTickets()[0]->getNumber(),
                $attendee->getEmail(),
                $attendee->getFullName(),
            ),
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeScanApiController@checkIn",
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
}