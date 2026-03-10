<?php namespace Tests;
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
use App\Models\Foundation\Main\IGroup;
/**
 * Class OAuth2SummitTracksApiTest
 * @package Tests
 */
class OAuth2SummitTracksApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp():void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
        self::$summit_permission_group->addMember(self::$member);
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
        self::$summit->addTrackChair(self::$member, [ self::$defaultTrack ] );
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetAllTracksPerSummit(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+id',
            'expand'   => 'subtracks'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTracksApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $tracks = json_decode($content);
        $this->assertNotNull($tracks);
        $this->assertGreaterThan(0, $tracks->total);
        return $tracks;
    }

    public function testGetAllTracksPerSummitCSV(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTracksApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(200);
        $content = $response->getContent();
        $this->assertNotEmpty($content);
    }

    public function testGetAllTracksPerSummitWithFilter(){
        $params = [
            'id' => self::$summit->getId(),
            'filter' => sprintf('name=@%s', substr(self::$defaultTrack->getTitle(), 0, 5)),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTracksApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $tracks = json_decode($content);
        $this->assertNotNull($tracks);
    }

    public function testAddTrack(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16).'_track';
        $data = [
            'name' => $name,
            'description' => 'test track description',
            'code' => str_random(4),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitTracksApiController@addTrackBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $track = json_decode($content);
        $this->assertNotNull($track);
        $this->assertEquals($name, $track->name);
        return $track;
    }

    public function testGetTrackById(){
        $params = [
            'id'       => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
            'expand'   => 'track_groups,allowed_tags',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTracksApiController@getTrackBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $track = json_decode($content);
        $this->assertNotNull($track);
        $this->assertEquals(self::$defaultTrack->getId(), $track->id);
    }

    public function testUpdateProposedScheduleTransitionTime(){

        $params = [
            'id'       => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId()
        ];

        $data = [
            'proposed_schedule_transition_time' => 5,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitTracksApiController@updateTrackBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $track = json_decode($content);
        $this->assertNotNull($track);
    }

    public function testDeleteNewTrack(){
        $new_track = $this->testAddTrack();

        $params = [
            'id'       => self::$summit->getId(),
            'track_id' => $new_track->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitTracksApiController@deleteTrackBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }

    public function testGetTrackAllowedTags(){
        $params = [
            'id'       => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTracksApiController@getTrackAllowedTagsBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertNotNull($data);
    }

    public function testGetTrackExtraQuestions(){
        $params = [
            'id'       => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTracksApiController@getTrackExtraQuestionsBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertNotNull($data);
    }

    public function testAddTrackIconMissingFile(){
        $params = [
            'id'       => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitTracksApiController@addTrackIcon",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(412);
    }

    public function testDeleteTrackIcon(){
        $params = [
            'id'       => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitTracksApiController@deleteTrackIcon",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }

    private function createTrackWithCode($code){
        $params = ['id' => self::$summit->getId()];

        $data = [
            'name' => str_random(16).'_track',
            'description' => 'test desc',
            'code' => $code,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitTracksApiController@addTrackBySummit",
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

    public function testAddSubTrack(){
        $parent = $this->createTrackWithCode('P' . str_random(4));
        $child  = $this->createTrackWithCode('C' . str_random(4));

        $params = [
            'id'             => self::$summit->getId(),
            'track_id'       => $parent->id,
            'child_track_id' => $child->id,
        ];

        $data = [
            'order' => 1,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitTracksApiController@addSubTrack",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $track = json_decode($content);
        $this->assertNotNull($track);
        return ['parent_id' => $parent->id, 'child_id' => $child->id];
    }

    public function testRemoveSubTrack(){
        $result = $this->testAddSubTrack();

        $params = [
            'id'             => self::$summit->getId(),
            'track_id'       => $result['parent_id'],
            'child_track_id' => $result['child_id'],
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitTracksApiController@removeSubTrack",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }
}
