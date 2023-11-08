<?php namespace Tests;

use App\Models\Foundation\Main\IGroup;
use models\summit\SummitAttendee;

/**
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

class OAuth2SummitAttendeeNotesApiTest extends ProtectedApiTest
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

    public function testGetAllAttendeeNotes(){

        $params = [
            'id'     => self::$summit->getId(),
            'expand' => 'owner,author',
            'order'  => '+owner_fullname'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitAttendeeNotesApiController@getAllAttendeeNotes",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $attendee_notes = json_decode($content);
        $this->assertTrue(!is_null($attendee_notes));
    }

    public function testGetAttendeeNotes(){

        $attendee = self::$summit->getAttendees()[0];
        $notes_count = count($attendee->getNotes());

        $params = [
            'id'          => self::$summit->getId(),
            'attendee_id' => $attendee->getId(),
            'expand'      => 'owner,author',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitAttendeeNotesApiController@getAttendeeNotes",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $attendee_notes = json_decode($content);
        $this->assertTrue(!is_null($attendee_notes));
        $this->assertTrue(count($attendee_notes->data) == $notes_count);
    }

    public function testGetAttendeeNote() {
        $attendee = self::$summit->getAttendees()[0];
        $attendee_note = $attendee->getNotes()[0];

        $params = [
            'id'          => self::$summit->getId(),
            'attendee_id' => $attendee->getId(),
            'note_id'     => $attendee_note->getId(),
            'expand'      => 'owner,author'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitAttendeeNotesApiController@getAttendeeNote",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $attendee_note = json_decode($content);
        $this->assertTrue(!is_null($attendee_note));
    }

    public function testAddAttendeeNote() {
        $attendee = self::$summit->getAttendees()[0];
        $attendee_ticket = $attendee->getTickets()[0];

        $params = [
            'id'          => self::$summit->getId(),
            'attendee_id' => $attendee->getId(),
        ];

        $data = [
            'content'   => 'Test attendee note 2',
            'ticket_id' => $attendee_ticket->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitAttendeeNotesApiController@addAttendeeNote",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $attendee_note = json_decode($content);
        $this->assertTrue(!is_null($attendee_note));
    }

    public function testUpdateAttendeeNote() {
        $attendee = self::$summit->getAttendees()[0];
        $attendee_note = $attendee->getNotes()[0];
        $new_content = 'Test attendee note update';

        $params = [
            'id'          => self::$summit->getId(),
            'attendee_id' => self::$summit->getAttendees()[0]->getId(),
            'note_id'     => $attendee_note->getId(),
        ];

        $data = [
            'content' => $new_content,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitAttendeeNotesApiController@updateAttendeeNote",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $attendee_note = json_decode($content);
        $this->assertTrue(!is_null($attendee_note));
        $this->assertEquals($attendee_note->content, $new_content);
    }

    public function testDeleteAttendeeNote() {
        $attendee = self::$summit->getAttendees()[0];
        $attendee_note = $attendee->getNotes()[0];

        $params = [
            'id'          => self::$summit->getId(),
            'attendee_id' => self::$summit->getAttendees()[0]->getId(),
            'note_id'     => $attendee_note->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitAttendeeNotesApiController@deleteAttendeeNote",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }
}