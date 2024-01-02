<?php namespace Tests;
use App\Models\Foundation\Main\IGroup;

/**
 * Copyright 2017 OpenStack Foundation
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

class OAuth2AttendeesApiTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::$defaultMember2 = self::$member2;
        self::insertSummitTestData();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testGetAttendees(){

        $params = [

            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+tickets_count',
            'filter'   => 'tags==tools',
            'expand'   => 'member,schedule,rsvp,tickets,tags,tickets.ticket_type'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitAttendeesApiController@getAttendeesBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $attendees = json_decode($content);
        $this->assertTrue(!is_null($attendees));
    }

    public function testGetAttendeesCSV(){

        $params = [

            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitAttendeesApiController@getAttendeesBySummitCSV",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertTrue(!is_null($content));
    }

    public function testGetOwnAttendee(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitAttendeesApiController@getOwnAttendee",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $attendee = json_decode($content);
        $this->assertTrue(!is_null($attendee));
    }

    public function testGetAttendeeByID($attendee_id = 1){

        $params = [
            'id'          => self::$summit->getId(),
            'attendee_id' => $attendee_id,
            'expand'      => 'member,schedule,tickets,groups,rsvp,all_affiliations'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitAttendeesApiController@getAttendee",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $attendee = json_decode($content);
        $this->assertTrue(!is_null($attendee));

        return $attendee;
    }

    public function testGetAttendeeByOrderID(){

        $params = [

            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+external_order_id',
            'filter'   => 'external_order_id==615528547',
            'expand'   => 'member,schedule,tickets,ticket_type,all_affiliations'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitAttendeesApiController@getAttendeesBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $attendees = json_decode($content);
        $this->assertTrue(!is_null($attendees));
    }

    public function testAddAttendee(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'member_id' => self::$defaultMember->getId(),
            'tags' => ['tag#1', 'tag#2']
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitAttendeesApiController@addAttendee",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $attendee = json_decode($content);
        $this->assertTrue(!is_null($attendee));
        return $attendee;
    }

    public function testDeleteAttendee(){
        $attendee = $this->testAddAttendee(3);

        $params = [
            'id' => self::$summit->getId(),
            'attendee_id' => $attendee->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitAttendeesApiController@deleteAttendee",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testUpdateAttendee(){
        $attendee = $this->testAddAttendee(3);

        $params = [
            'id' => self::$summit->getId(),
            'attendee_id' => $attendee->id
        ];

        $data = [
            'share_contact_info' => true,
            'first_name' => 'Sebastian',
            'surname' => 'Marcet',
            'email' => 'smarcet@gmail.com',
            'extra_questions' => [
                ['question_id' => 3, 'answer' => 'XL'],
                ['question_id' => 4, 'answer' => 'None'],
                ['question_id' => 5, 'answer' => 'None'],
            ],
            'tags' => ['tag#2','tag#3', 'tag#4']
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitAttendeesApiController@updateAttendee",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $attendee = json_decode($content);
        $this->assertTrue(!is_null($attendee));
        return $attendee;
    }

    public function testUpdateAttendeeNotesUnicode($attendee_id = 1){
        $attendee = $this->testAddAttendee(3);

        $admin_notes = '嘗試特殊字符';

        $params = [
            'id' => self::$summit->getId(),
            'attendee_id' => $attendee->id,
            'expand'   => 'admin_notes'
        ];

        $data = [
            'first_name' => 'Clint',
            'surname' => 'Espinoza',
            'email' => 'clint@gmail.com',
            'admin_notes' => $admin_notes,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitAttendeesApiController@updateAttendee",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $attendee = json_decode($content);
        $this->assertTrue(!is_null($attendee));
        $this->assertEquals($attendee->admin_notes, $admin_notes);
        return $attendee;
    }

    public function testAddAttendeeTicket(){
        $attendee = $this->testAddAttendee(3);

        $params = [
            'id'          => self::$summit->getId(),
            'attendee_id' => $attendee->id,
        ];

        $data = [
            'ticket_type_id'       => 50,
            'external_order_id'    => '617372932',
            'external_attendee_id' => '774078887',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitAttendeesApiController@addAttendeeTicket",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        return $ticket;
    }

    public function testDeleteAttendeeTicket(){

        $params = [
            'id'          => self::$summit->getId(),
            'attendee_id' => 12642,
            'ticket_id'   => 14161
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitAttendeesApiController@deleteAttendeeTicket",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }

    public function testReassignAttendeeTicket(){
        $params = [
            'id'          => self::$summit->getId(),
            'attendee_id' => 14938,
            'ticket_id'   => 15070,
            'other_member_id' => 13867,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitAttendeesApiController@reassignAttendeeTicket",
            $params,
            [],
            [],
            [],
            $headers,
           ''
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        return $ticket;
    }
}