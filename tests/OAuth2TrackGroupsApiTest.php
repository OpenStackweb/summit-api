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
final class OAuth2TrackGroupsApiTest extends ProtectedApiTestCase
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

    public function testGetTrackGroups()
    {

        $params = [
            'id'      => self::$summit->getId(),
            'expand' => 'tracks',
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2PresentationCategoryGroupController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $track_groups = json_decode($content);
        $this->assertTrue(!is_null($track_groups));
        $this->assertResponseStatus(200);
        return $track_groups;
    }

    public function testGetTrackGroupsMetadata()
    {

        $params = [
            'id'      => self::$summit->getId(),
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2PresentationCategoryGroupController@getMetadata",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $metadata = json_decode($content);
        $this->assertTrue(!is_null($metadata));
        $this->assertResponseStatus(200);
        return $metadata;
    }

    public function testGetTrackGroupById(){
        $track_groups_response = $this->testGetTrackGroups();

        $track_groups = $track_groups_response->data;

        $track_group  = $track_groups[0];

        $params = [
            'id'             => self::$summit->getId(),
            'track_group_id' => $track_group->id,
            'expand'         => 'tracks,allowed_groups',
        ];

        $headers  = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2PresentationCategoryGroupController@getTrackGroupBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content     = $response->getContent();
        $track_group = json_decode($content);
        $this->assertTrue(!is_null($track_group));
        $this->assertResponseStatus(200);
    }

    /**
     * @param int $summit_id
     */
    public function testGetTrackGroupsPrivate($summit_id = 23)
    {

        $params = [
            'id'     => $summit_id,
            'expand' => 'tracks',
            'filter' => ['class_name=='.\models\summit\PrivatePresentationCategoryGroup::ClassName],
            'order'  => '+title',
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2PresentationCategoryGroupController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $track_groups = json_decode($content);
        $this->assertTrue(!is_null($track_groups));
        $this->assertResponseStatus(200);
    }

    public function testAddTrackGroup(){
        $params = [
            'id' => self::$summit->getId(),
        ];
        $start_date  = clone(self::$summit->getBeginDate());
        $end_date    = clone($start_date);
        $end_date =    $end_date->add(new \DateInterval("P1D"));

        $name       = str_random(16).'_track_group';
        $data = [
            'name'        => $name,
            'description' => 'test desc track group',
            'class_name'  => \models\summit\PresentationCategoryGroup::ClassName,
            "begin_attendee_voting_period_date" => $start_date->getTimestamp(),
            "end_attendee_voting_period_date" => $end_date->getTimestamp(),
            "max_attendee_votes" => 10,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2PresentationCategoryGroupController@addTrackGroupBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $track_group = json_decode($content);
        $this->assertTrue(!is_null($track_group));
        $this->assertTrue($track_group->max_attendee_votes == 10);
        $this->assertTrue($track_group->begin_attendee_voting_period_date == $start_date->getTimestamp());
        $this->assertTrue($track_group->end_attendee_voting_period_date == $end_date->getTimestamp());
        return $track_group;
    }

    public function testUpdateTrackGroup(){

        $track_groups_response = $this->testGetTrackGroups();

        $track_groups = $track_groups_response->data;

        $track_group  = $track_groups[0];

        $params = [
            'id'             => self::$summit->getId(),
            'track_group_id' => $track_group->id
        ];

        $data = [
            'description' => 'test desc track group update',
            'class_name'  => \models\summit\PresentationCategoryGroup::ClassName
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2PresentationCategoryGroupController@updateTrackGroupBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $track_group = json_decode($content);
        $this->assertTrue(!is_null($track_group));
        $this->assertTrue($track_group->description == 'test desc track group update');
        return $track_group;
    }

    public function testAssociateTrack2TrackGroup412($summit_id = 24){

        $track_group = $this->testAddTrackGroup($summit_id);

        $params = [
            'id'             => $summit_id,
            'track_group_id' => $track_group->id,
            'track_id'       => 1
        ];


        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2PresentationCategoryGroupController@associateTrack2TrackGroup",
            $params,
            [],
            [],
            [],
            $headers

        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);

    }

    public function testAssociateTrack2TrackGroup(){

        $track_groups_response = $this->testGetTrackGroups();

        $track_groups = $track_groups_response->data;

        $track_group  = $track_groups[0];

        $params = [
            'id'             => self::$summit->getId(),
            'track_group_id' => $track_group->id,
            'track_id'       => self::$defaultTrack->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2PresentationCategoryGroupController@associateTrack2TrackGroup",
            $params,
            [],
            [],
            [],
            $headers

        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }


    public function testDeleteExistentTrackGroup(){

        $params = [
            'id'             => self::$summit->getId(),
            'track_group_id' => self::$defaultTrackGroup->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2PresentationCategoryGroupController@deleteTrackGroupBySummit",
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