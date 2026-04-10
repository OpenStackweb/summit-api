<?php namespace Tests;
use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\Presentation;
use models\utils\SilverstripeBaseModel;

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
    public function testGetTrackGroupsPrivate()
    {

        $params = [
            'id'     => self::$summit->getId(),
            'expand' => 'tracks',
            'filter' => ['class_name=='.\models\summit\PrivatePresentationCategoryGroup::ClassName],
            'order'  => '+name',
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

    public function testAssociateTrack2TrackGroup412(){

        $track_group = $this->testAddTrackGroup();

        $params = [
            'id'             => self::$summit->getId(),
            'track_group_id' => $track_group->id,
            'track_id'       => 999999
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
        $this->assertResponseStatus(404);

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


    public function testGetTrackGroupsCSV(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2PresentationCategoryGroupController@getAllBySummitCSV",
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

    public function testDisassociateTrack2TrackGroup(){
        // first associate
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

        $this->assertResponseStatus(201);

        // verify presentations of defaultTrack have a selection plan before disassociation
        $presentations = self::$defaultTrack->getPresentationsBySelectionPlanIds(
            self::$defaultTrackGroup->getSelectionPlanIds()
        );
        $this->assertNotEmpty($presentations);
        foreach ($presentations as $presentation) {
            $this->assertTrue($presentation->getSelectionPlanId() > 0);
        }
        $presentation_ids = array_map(fn($p) => $p->getId(), $presentations);

        // now disassociate
        $response = $this->action(
            "DELETE",
            "OAuth2PresentationCategoryGroupController@disassociateTrack2TrackGroup",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);

        // reset EM (closed after the API transaction) and re-fetch presentations to verify selection plan was cleared
        self::$em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        foreach ($presentation_ids as $id) {
            $presentation = self::$em->find(Presentation::class, $id);
            $this->assertNotNull($presentation);
            $this->assertEquals(0, $presentation->getSelectionPlanId());
        }
    }

    public function testDisassociateTrackFromTrackGroupPreservesSelectionPlanWhenTrackReachableViaAnotherGroup()
    {
        // Set up a second category group (TrackGroupB) that also contains $defaultTrack
        // and belongs to the same $default_selection_plan as $defaultTrackGroup (TrackGroupA).
        // Removing $defaultTrack from TrackGroupA should NOT clear the selection plan on
        // presentations because TrackGroupB still covers the track within the same plan.
        $track_group_b = new \models\summit\PresentationCategoryGroup();
        $track_group_b->setName("TRACK GROUP B");
        $track_group_b->addCategory(self::$defaultTrack);
        self::$summit->addCategoryGroup($track_group_b);
        self::$default_selection_plan->addTrackGroup($track_group_b);
        self::$em->persist($track_group_b);
        self::$em->flush();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $params = [
            'id'             => self::$summit->getId(),
            'track_group_id' => self::$defaultTrackGroup->getId(),
            'track_id'       => self::$defaultTrack->getId()
        ];

        // Only collect presentations that belong to $default_selection_plan — the plan
        // that has TrackGroupB as a second group covering $defaultTrack.
        // Presentations of $default_selection_plan2 are intentionally excluded: that plan
        // only has $defaultTrackGroup, so their selection plan will be correctly cleared.
        $presentations = self::$defaultTrack->getPresentationsBySelectionPlanIds(
            [self::$default_selection_plan->getId()]
        );
        $this->assertNotEmpty($presentations);
        foreach ($presentations as $presentation) {
            $this->assertTrue($presentation->getSelectionPlanId() > 0);
        }
        $presentation_ids = array_map(fn($p) => $p->getId(), $presentations);

        // disassociate $defaultTrack from TrackGroupA
        $response = $this->action(
            "DELETE",
            "OAuth2PresentationCategoryGroupController@disassociateTrack2TrackGroup",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);

        // reset EM and re-fetch presentations — selection plan must be preserved
        // because TrackGroupB still contains $defaultTrack within the same plan
        self::$em = \LaravelDoctrine\ORM\Facades\Registry::resetManager(\models\utils\SilverstripeBaseModel::EntityManager);
        foreach ($presentation_ids as $id) {
            $presentation = self::$em->find(\models\summit\Presentation::class, $id);
            $this->assertNotNull($presentation);
            $this->assertGreaterThan(0, $presentation->getSelectionPlanId());
        }
    }

    public function testAddPrivateTrackGroup(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $start_date  = clone(self::$summit->getBeginDate());
        $end_date    = clone($start_date);
        $end_date    = $end_date->add(new \DateInterval("P1D"));

        $name = str_random(16).'_private_track_group';
        $data = [
            'name'        => $name,
            'description' => 'test private track group',
            'class_name'  => \models\summit\PrivatePresentationCategoryGroup::ClassName,
            'submission_begin_date' => $start_date->getTimestamp(),
            'submission_end_date'   => $end_date->getTimestamp(),
            'max_submission_allowed_per_user' => 5,
            'begin_attendee_voting_period_date' => $start_date->getTimestamp(),
            'end_attendee_voting_period_date' => $end_date->getTimestamp(),
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
        $this->assertEquals(\models\summit\PrivatePresentationCategoryGroup::ClassName, $track_group->class_name);
        return $track_group;
    }

    public function testAssociateAllowedGroup2TrackGroup(){
        $private_track_group = $this->testAddPrivateTrackGroup();

        $params = [
            'id'             => self::$summit->getId(),
            'track_group_id' => $private_track_group->id,
            'group_id'       => self::$group->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2PresentationCategoryGroupController@associateAllowedGroup2TrackGroup",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(201);
        return $private_track_group;
    }

    public function testDisassociateAllowedGroup2TrackGroup(){
        $private_track_group = $this->testAssociateAllowedGroup2TrackGroup();

        $params = [
            'id'             => self::$summit->getId(),
            'track_group_id' => $private_track_group->id,
            'group_id'       => self::$group->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2PresentationCategoryGroupController@disassociateAllowedGroup2TrackGroup",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
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
