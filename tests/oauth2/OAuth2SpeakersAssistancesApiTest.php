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
 * Class OAuth2SpeakersAssistancesApiTest
 */
final class OAuth2SpeakersAssistancesApiTest extends ProtectedApiTestCase
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

    public function testAddSummitAssistance(){
        // Get the speaker that is actually associated with the summit presentations
        $speaker = self::$presentations[0]->getSpeakers()->first();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'speaker_id'   => $speaker->getId(),
            'checked_in'   => false,
            'registered'   => true,
            'is_confirmed' => false,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSpeakersAssistanceApiController@addSpeakerSummitAssistance",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $assistance = json_decode($content);
        $this->assertTrue(!is_null($assistance));

        return $assistance;
    }

    public function testGetAllBySummit(){

        $this->testAddSummitAssistance();

        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+id',
            'expand'   => 'speaker'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersAssistanceApiController@getBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $assistances = json_decode($content);
        $this->assertTrue(!is_null($assistances));
        return $assistances;
    }

    public function testGetAllBySummitCSV(){

        $this->testAddSummitAssistance();

        $params = [
            'id'       => self::$summit->getId(),
            'order'    => '+id',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersAssistanceApiController@getBySummitCSV",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertTrue(!empty($content));
    }

    public function testGetBySummitAndId(){

        $assistance = $this->testAddSummitAssistance();

        $params = [
            'id'            => self::$summit->getId(),
            'assistance_id' => $assistance->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSpeakersAssistanceApiController@getSpeakerSummitAssistanceBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $fetched = json_decode($content);
        $this->assertTrue(!is_null($fetched));
        $this->assertEquals($assistance->id, $fetched->id);
    }

    public function testUpdateSummitAssistance(){

        $assistance = $this->testAddSummitAssistance();

        $params = [
            'id'            => self::$summit->getId(),
            'assistance_id' => $assistance->id
        ];

        $data = [
           'is_confirmed'  => true,
           'on_site_phone' => '+5491133943659'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSpeakersAssistanceApiController@updateSpeakerSummitAssistance",
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
        $this->assertTrue(!is_null($updated));
    }

    public function testDeleteSummitAssistance(){

        $assistance = $this->testAddSummitAssistance();

        $params = [
            'id'            => self::$summit->getId(),
            'assistance_id' => $assistance->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSpeakersAssistanceApiController@deleteSpeakerSummitAssistance",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }
}
