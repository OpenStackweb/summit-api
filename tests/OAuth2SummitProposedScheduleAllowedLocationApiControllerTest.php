<?php namespace Tests;
/*
 * Copyright 2023 OpenStack Foundation
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
 * Class OAuth2SummitProposedScheduleAllowedLocationApiControllerTest
 * @package Tests
 */
final class OAuth2SummitProposedScheduleAllowedLocationApiControllerTest
    extends ProtectedApiTest
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp(): void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::$defaultMember2 = self::$member2;
        self::insertSummitTestData();
        self::$summit_permission_group->addMember(self::$member);
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testAddAllowedLocation2Track()
    {
        $params = [
            'id' => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $payload = [
            'location_id' => self::$mainVenue->getId()
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitProposedScheduleAllowedLocationApiController@addAllowedLocationToTrack",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $allowed_location = json_decode($content);
        $this->assertTrue(!is_null($allowed_location));
        return $allowed_location;
    }


    public function testAddTimeFrame()
    {
        $allowed_location = $this->testAddAllowedLocation2Track();

        $params = [
            'id' => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
            'location_id' => $allowed_location->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $payload = [
            'day' => \DateTime::createFromFormat('Ymd H:i:s', '20230518 00:00:00')->getTimestamp(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitProposedScheduleAllowedLocationApiController@addTimeFrame2AllowedLocation",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $time_frame = json_decode($content);
        $this->assertTrue(!is_null($time_frame));
        $this->assertTrue($time_frame->day == $payload['day']);
        return $time_frame;
    }

    public function testAddTimeFrameTwice()
    {
        $allowed_location = $this->testAddAllowedLocation2Track();

        $params = [
            'id' => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
            'location_id' => $allowed_location->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $payload = [
            'day' => \DateTime::createFromFormat('Ymd H:i:s', '20230518 00:00:00')->getTimestamp(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitProposedScheduleAllowedLocationApiController@addTimeFrame2AllowedLocation",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $time_frame = json_decode($content);
        $this->assertTrue(!is_null($time_frame));
        $this->assertTrue($time_frame->day == $payload['day']);

        $response = $this->action(
            "POST",
            "OAuth2SummitProposedScheduleAllowedLocationApiController@addTimeFrame2AllowedLocation",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }

    public function testGetAllByTrack()
    {
        $this->testAddAllowedLocation2Track();
        $params = [
            'id' => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitProposedScheduleAllowedLocationApiController@getAllAllowedLocationByTrack",
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
        $this->assertTrue($page->total > 1);
    }

    public function testAddTimeFrameAndThenGetIt()
    {
        $time_frame = $this->testAddTimeFrame();
        $params = [
            'id' => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
            'location_id' => $time_frame->allowed_location_id,
            'time_frame_id' => $time_frame->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitProposedScheduleAllowedLocationApiController@getTimeFrameFromAllowedLocation",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $time_frame_new = json_decode($content);
        $this->assertTrue(!is_null($time_frame_new));
        $this->assertTrue($time_frame_new->id == $time_frame->id);
    }

    public function testGetAllTimeFramesByAllowedLocation(){
        $time_frame = $this->testAddTimeFrame();
        $params = [
            'id' => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
            'location_id' => $time_frame->allowed_location_id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitProposedScheduleAllowedLocationApiController@getAllTimeFrameFromAllowedLocation",
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
        $this->assertTrue($page->total > 1);
    }

}