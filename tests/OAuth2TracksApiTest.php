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
use App\Models\Foundation\Main\IGroup;
use Illuminate\Http\UploadedFile;
/**
 * Class OAuth2TracksApiTest
 */
final class OAuth2TracksApiTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp():void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::insertTestData();
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
        self::clearTestData();
        parent::tearDown();
    }

    public function testGetTracksByTitle(){

        $params = [

            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'filter'   => sprintf('name=@%s', self::$defaultTrack->getTitle()),
            'order'    => '+code',
            'expand'   => 'track_groups,allowed_tags'
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
        $this->assertTrue(!is_null($tracks));
        $this->assertTrue($tracks->total >= 1);
        $this->assertTrue($tracks->data[0]->name == self::$defaultTrack->getTitle());
        return $tracks;
    }

    /**
     * @param int $summit_id
     * @param int $track_id
     * @return mixed
     */
    public function testGetTracksById(){
        $params = [

            'id'       => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
            'expand'   =>'extra_questions'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
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
        $this->assertTrue(!is_null($track));
        return $track;
    }

    /**
     * @return mixed
     */
    public function testGetTracksExtraQuestionById(){
        $params = [

            'id'       => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
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
        $extra_questions = json_decode($content);
        $this->assertTrue(!is_null($extra_questions));
        return $extra_questions;
    }

    /**
     * @return mixed
     */
    public function testGetTracksAllowedTagsById(){
        $params = [

            'id'       => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
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
        $allowes_tags = json_decode($content);
        $this->assertTrue(!is_null($allowes_tags));
        return $allowes_tags;
    }


    public function testGetTracksByTitleCSV(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'filter'   => 'title=@con',
            'order'    => '+code'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
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

        $csv = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertTrue(!empty($csv));
    }

    /**
     * @return mixed
     */
    public function testAddTrack(){
        $params = [
            'id'       => self::$summit->getId(),
        ];

        $name       = str_random(16).'_track';
        $data = [
            'name'       => $name,
            'description' => 'test desc',
            'code'        => 'CDB',
            'allowed_tags' => ['101','Case Study', 'Demo'],
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
        $this->assertTrue(!is_null($track));
        return $track;
    }

    /**
     * @return mixed
     */
    public function testAdd3Track(){
        $params = [
            'id'       => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $name       = str_random(16).'_track';
        $data = [
            'name'       => $name,
            'description' => 'test desc',
            'code'        => 'CDB1',
            'allowed_tags' => ['101','Case Study', 'Demo'],
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
        $track1 = json_decode($content);
        $this->assertTrue(!is_null($track1));
        $this->assertTrue($track1->order > 0);

        $name       = str_random(16).'_track';
        $data = [
            'name'       => $name,
            'description' => 'test desc',
            'code'        => 'CDB2',
            'allowed_tags' => ['101','Case Study', 'Demo'],
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
        $track2 = json_decode($content);
        $this->assertTrue(!is_null($track2));
        $this->assertTrue($track2->order > 0 && $track1->order < $track2->order);

        $name       = str_random(16).'_track';
        $data = [
            'name'       => $name,
            'description' => 'test desc',
            'code'        => 'CDB3',
            'allowed_tags' => ['101','Case Study', 'Demo'],
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
        $track3 = json_decode($content);
        $this->assertTrue(!is_null($track3));
        $this->assertTrue($track3->order > 0 && $track2->order < $track3->order);
    }


    /**
     * @return mixed
     */
    public function testUpdateTrack(){

        $params = [
            'id'       => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $name       = str_random(16).'_track';
        $data = [
            'name'       => $name,
            'description' => 'test desc',
            'code'        => 'CDB1',
            'allowed_tags' => ['101','Case Study', 'Demo'],
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
        $track1 = json_decode($content);
        $this->assertTrue(!is_null($track1));
        $this->assertTrue($track1->order > 0);

        $name       = str_random(16).'_track';
        $data = [
            'name'       => $name,
            'description' => 'test desc',
            'code'        => 'CDB2',
            'allowed_tags' => ['101','Case Study', 'Demo'],
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
        $track2 = json_decode($content);
        $this->assertTrue(!is_null($track2));
        $this->assertTrue($track2->order > 0 && $track1->order < $track2->order);

        $name       = str_random(16).'_track';
        $data = [
            'name'       => $name,
            'description' => 'test desc',
            'code'        => 'CDB3',
            'allowed_tags' => ['101','Case Study', 'Demo'],
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
        $track3 = json_decode($content);
        $this->assertTrue(!is_null($track3));
        $this->assertTrue($track3->order > 0 && $track2->order < $track3->order);

        $params = [
            'id'       => self::$summit->getId(),
            'track_id' => $track3->id
        ];

        $data = [
            'description' => 'test desc updated',
            'code'        => 'SMX' ,
            'order' => 1
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
        $track3 = json_decode($content);
        $this->assertTrue(!is_null($track3));
        $this->assertTrue(!empty($track3->description) && $track3->description == 'test desc updated');
        $this->assertTrue(!empty($track3->code) && $track3->code == 'SMX');
        $this->assertTrue($track3->order == 1);
    }

    public function testDeleteNewTrack(){

        $params = [
            'id'       => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
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

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testCopyTracks(){

        $params = [
            'id'            => self::$summit->getId(),
            'to_summit_id' => self::$summit2->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitTracksApiController@copyTracksToSummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $added_tracks = json_decode($content);
        $this->assertTrue(!is_null($added_tracks));
    }

    public function testAddTrackIcon(){
        $params = array
        (
            'id' => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
         //   "CONTENT_TYPE" => "multipart/form-data; boundary=----WebKitFormBoundaryBkSYnzBIiFtZu4pb"
        );

        $response = $this->action
        (
            "POST",
            "OAuth2SummitTracksApiController@addTrackIcon",
            $params,
            array(),
            array(),
            [
                'file' => UploadedFile::fake()->image('icon.jpg')
            ],
            $headers,
           []
        );

        $video_id = $response->getContent();
        $this->assertResponseStatus(201);
        return intval($video_id);
    }

    public function testRemoveTrackIcon(){

        $params = array
        (
            'id' => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            //   "CONTENT_TYPE" => "multipart/form-data; boundary=----WebKitFormBoundaryBkSYnzBIiFtZu4pb"
        );

        $response = $this->action
        (
            "POST",
            "OAuth2SummitTracksApiController@addTrackIcon",
            $params,
            array(),
            array(),
            [
                'file' => UploadedFile::fake()->image('icon.jpg')
            ],
            $headers,
            []
        );

        $params = array
        (
            'id' => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            //   "CONTENT_TYPE" => "multipart/form-data; boundary=----WebKitFormBoundaryBkSYnzBIiFtZu4pb"
        );


        $response = $this->action
        (
            "DELETE",
            "OAuth2SummitTracksApiController@deleteTrackIcon",
            $params,
            array(),
            array(),
            [

            ],
            $headers,
            []
        );

        $video_id = $response->getContent();
        $this->assertResponseStatus(204);
        return intval($video_id);
    }
}