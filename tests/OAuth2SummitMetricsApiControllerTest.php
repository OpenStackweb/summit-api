<?php namespace Tests;
use App\Models\Foundation\Main\IGroup;

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

class OAuth2SummitMetricsApiControllerTest extends ProtectedApiTestCase
{

    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testEnter(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'type' => \models\summit\ISummitMetricType::General,
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json",
            'REMOTE_ADDR'         => '10.1.0.1',
            'HTTP_REFERER'        => 'https://www.test.com'
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitMetricsApiController@enter",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $metric = json_decode($content);
        $this->assertTrue(!is_null($metric));
        return $metric;
    }

    public function testEnterEvent(){

        $params = [
            'id' => self::$summit->getId(),
            'event_id' => self::$presentations[0]->getId(),
            'member_id' => 'me'
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json",
            'REMOTE_ADDR'         => '10.1.0.1',
            'HTTP_REFERER'        => 'https://www.test.com'
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitMetricsApiController@enterToEvent",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $metric = json_decode($content);
        $this->assertTrue(!is_null($metric));
        return $metric;
    }

    public function testEnterOnSite(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json",
            'REMOTE_ADDR'         => '10.1.0.1',
            'HTTP_REFERER'        => 'https://www.test.com'
        ];

        $attendees =  self::$summit->getAttendees();
        $rooms =  self::$venue_rooms;
        $access_levels = self::$summit->getBadgeAccessLevelTypes();
        $data = [
            'attendee_id' => $attendees[0]->getId(),
            'room_id' => $rooms[0]->getId(),
            'required_access_levels' => [$access_levels[0]->getId()]
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitMetricsApiController@onSiteEnter",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $metric = json_decode($content);
        $this->assertTrue(!is_null($metric));
        return $metric;
    }

    public function testEnterOnSiteTwice(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json",
            'REMOTE_ADDR'         => '10.1.0.1',
            'HTTP_REFERER'        => 'https://www.test.com'
        ];

        $attendees =  self::$summit->getAttendees();
        $rooms =  self::$venue_rooms;
        $access_levels = self::$summit->getBadgeAccessLevelTypes();
        $data = [
            'attendee_id' => $attendees[0]->getId(),
            'room_id' => $rooms[0]->getId(),
            'required_access_levels' => [$access_levels[0]->getId()]
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitMetricsApiController@onSiteEnter",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $metric = json_decode($content);
        $this->assertTrue(!is_null($metric));

        $response = $this->action(
            "PUT",
            "OAuth2SummitMetricsApiController@onSiteEnter",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $metric = json_decode($content);
        $this->assertTrue(!is_null($metric));

        return $metric;
    }
}