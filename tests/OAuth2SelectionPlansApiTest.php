<?php
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
use models\summit\SummitSelectedPresentation;

/**
 * Class OAuth2SelectionPlansApiTest
 * @package Tests
 */
final class OAuth2SelectionPlansApiTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    protected function setUp():void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::insertTestData();
        self::$summit_permission_group->addMember(self::$member);
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
        $track_chair = self::$summit->addTrackChair(self::$member, [ self::$defaultTrack ]);
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearTestData();
        parent::tearDown();
    }

    /**
     * @param int $summit_id
     * @return mixed
     */
    public function testAddSelectionPlan(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name       = str_random(16).'_selection_plan';
        $data = [
            'name'       => $name,
            'is_enabled'  => false,
            'allow_new_presentations' => false,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addSelectionPlan",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selection_plan = json_decode($content);
        $this->assertTrue(!is_null($selection_plan));
        $this->assertEquals($name, $selection_plan->name);
        return $selection_plan;
    }

    /**
     * @param int $summit_id
     * @return mixed
     */
    public function testUpdateSelectionPlan(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name       = str_random(16).'_selection_plan';
        $data = [
            'name'       => $name,
            'is_enabled'  => false,
            'allow_new_presentations' => false,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addSelectionPlan",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selection_plan = json_decode($content);

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => $selection_plan->id
        ];

        $start = new DateTime('now');
        $end   = new DateTime('now');
        $end->add(new DateInterval('P15D'));

        $data = [
            'is_enabled'  => false,
            'submission_begin_date' => $start->getTimestamp(),
            'submission_end_date' => $end->getTimestamp(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSelectionPlansApiController@updateSelectionPlan",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selection_plan = json_decode($content);
        $this->assertTrue(!is_null($selection_plan));
        $this->assertEquals(false, $selection_plan->is_enabled);
        return $selection_plan;
    }


    public function testAddTrackGroupToSelectionPlan(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $name       = str_random(16).'_selection_plan';
        $data = [
            'name'       => $name,
            'is_enabled'  => false,
            'allow_new_presentations' => false,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addSelectionPlan",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selection_plan = json_decode($content);

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => $selection_plan->id,
            'track_group_id'    => 1
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSelectionPlansApiController@addTrackGroupToSelectionPlan",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(404);
    }

    /**
     * @param string $status
     */
    public function testGetCurrentSelectionPlanByStatus($status = 'submission'){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $name       = str_random(16).'_selection_plan';
        $data = [
            'name'       => $name,
            'is_enabled'  => false,
            'allow_new_presentations' => false,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addSelectionPlan",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selection_plan = json_decode($content);

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => $selection_plan->id
        ];

        $start = new DateTime('now');
        $end   = new DateTime('now');
        $end->add(new DateInterval('P15D'));

        $data = [
            'is_enabled'  => false,
            'submission_begin_date' => $start->getTimestamp(),
            'submission_end_date' => $end->getTimestamp(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSelectionPlansApiController@updateSelectionPlan",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $params = [
            'status'  => $status,
            'expand' => 'track_groups,summit'
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getCurrentSelectionPlanByStatus",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $selection_plan = json_decode($content);
        $this->assertTrue(!is_null($selection_plan));
    }

    public function testGetPresentationsBySelectionPlanAndConditions(){

        $params = [
            'id' => self::$summit->getId(),
            'track_id' =>  self::$defaultTrack->getId(),
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectedPresentationListApiController@createIndividualSelectionList",
            $params,
            [],
            [],
            [],
            $headers,
            ""
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selection_list = json_decode($content);
        $this->assertTrue(!is_null($selection_list));

        $params = [
            'id' => self::$summit->getId(),
            'track_id' =>  self::$defaultTrack->getId(),
            'collection' => SummitSelectedPresentation::CollectionSelected,
            'presentation_id' => self::$presentations[0]->getId(),
            'expand' => 'selected_presentations,interested_presentations,'
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectedPresentationListApiController@assignPresentationToMyIndividualList",
            $params,
            [],
            [],
            [],
            $headers,
            ""
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selection_list = json_decode($content);
        $this->assertTrue(!is_null($selection_list));
        $this->assertTrue(count($selection_list->selected_presentations) > 0);


        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'filter' => [
                'status==Received',
                'is_chair_visible==1',
                'track_chairs_status==voted',
                'actions==type_id==2&&is_completed==1',
            ],
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getSelectionPlanPresentations",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $presentations = json_decode($content);
        $this->assertTrue(!is_null($presentations));
        $this->assertTrue($presentations->total >= 1);
    }

    public function testGetPresentationsBySelectionPlanAndConditionsCSV(){

        $params = [
            'id' => self::$summit->getId(),
            'track_id' =>  self::$defaultTrack->getId(),
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectedPresentationListApiController@createIndividualSelectionList",
            $params,
            [],
            [],
            [],
            $headers,
            ""
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selection_list = json_decode($content);
        $this->assertTrue(!is_null($selection_list));

        $params = [
            'id' => self::$summit->getId(),
            'track_id' =>  self::$defaultTrack->getId(),
            'collection' => SummitSelectedPresentation::CollectionSelected,
            'presentation_id' => self::$presentations[0]->getId(),
            'expand' => 'selected_presentations,interested_presentations,'
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectedPresentationListApiController@assignPresentationToMyIndividualList",
            $params,
            [],
            [],
            [],
            $headers,
            ""
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selection_list = json_decode($content);
        $this->assertTrue(!is_null($selection_list));
        $this->assertTrue(count($selection_list->selected_presentations) > 0);


        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'filter' => [
                'status==Received',
                'is_chair_visible==1',
                'track_chairs_status==voted'
            ],
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getSelectionPlanPresentationsCSV",
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

    public function testGetPresentationsBySelectionPlanAndConditionsMoved(){

        $params = [
            'id' => self::$summit->getId(),
            'track_id' =>  self::$defaultTrack->getId(),
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectedPresentationListApiController@createIndividualSelectionList",
            $params,
            [],
            [],
            [],
            $headers,
            ""
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selection_list = json_decode($content);
        $this->assertTrue(!is_null($selection_list));

        $params = [
            'id' => self::$summit->getId(),
            'track_id' =>  self::$defaultTrack->getId(),
            'collection' => SummitSelectedPresentation::CollectionSelected,
            'presentation_id' => self::$presentations[0]->getId(),
            'expand' => 'selected_presentations,interested_presentations,'
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectedPresentationListApiController@assignPresentationToMyIndividualList",
            $params,
            [],
            [],
            [],
            $headers,
            ""
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selection_list = json_decode($content);
        $this->assertTrue(!is_null($selection_list));
        $this->assertTrue(count($selection_list->selected_presentations) > 0);


        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'filter' => [
                'status==Received',
                'is_chair_visible==1',
                'viewed_status==moved'
            ],
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getSelectionPlanPresentations",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $presentations = json_decode($content);
        $this->assertTrue(!is_null($presentations));
        $this->assertTrue($presentations->total == 0);
    }

    public function testGetPresentationsBySelectionPlanAndConditionsPass(){

        $params = [
            'id' => self::$summit->getId(),
            'track_id' =>  self::$defaultTrack->getId(),
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectedPresentationListApiController@createIndividualSelectionList",
            $params,
            [],
            [],
            [],
            $headers,
            ""
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selection_list = json_decode($content);
        $this->assertTrue(!is_null($selection_list));

        $params = [
            'id' => self::$summit->getId(),
            'track_id' =>  self::$defaultTrack->getId(),
            'collection' => SummitSelectedPresentation::CollectionPass,
            'presentation_id' => self::$presentations[0]->getId(),
            'expand' => 'selected_presentations,interested_presentations,'
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectedPresentationListApiController@assignPresentationToMyIndividualList",
            $params,
            [],
            [],
            [],
            $headers,
            ""
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selection_list = json_decode($content);
        $this->assertTrue(!is_null($selection_list));

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'filter' => [
                'status==Received',
                'is_chair_visible==1',
                'track_chairs_status==pass'
            ],
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getSelectionPlanPresentations",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $presentations = json_decode($content);
        $this->assertTrue(!is_null($presentations));
        $this->assertTrue($presentations->total == 1);
    }

    public function testMarkPresentationAsViewed(){
        $params = [
            'summit' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'presentation_id' => self::$presentations[0]->getId()
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSelectionPlansApiController@markPresentationAsViewed",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $presentation = json_decode($content);
        $this->assertTrue(!is_null($presentation));
        $this->assertTrue($presentation->views_count > 0);
    }

    public function testAddComment(){
        $params = [
            'summit' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'presentation_id' => self::$presentations[0]->getId()
        ];

        $data = [
            'is_public'  => false,
            'body' => 'this is a test comment',
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addCommentToPresentation",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $comment = json_decode($content);
        $this->assertTrue(!is_null($comment));
    }

    public function testAddCategoryChangeRequest(){

        $params = [
            'summit' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'presentation_id' => self::$presentations[0]->getId(),
            'expand' => 'presentation, new_category, old_category',
        ];

        $data = [
            'new_category_id'  => self::$secondaryTrack->getId(),
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@createPresentationCategoryChangeRequest",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $request = json_decode($content);
        $this->assertTrue(!is_null($request));
        $this->assertTrue($request->status === 'Pending');
    }

    public function testGetAllCategoryChangeRequests(){
        $params = [
            'summit' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'presentation_id' => self::$presentations[0]->getId(),
            'expand' => 'presentation, new_category, old_category',
        ];

        $data = [
            'new_category_id'  => self::$secondaryTrack->getId(),
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@createPresentationCategoryChangeRequest",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $request = json_decode($content);
        $this->assertTrue(!is_null($request));
        $this->assertTrue($request->status === 'Pending');

        $params = [
            'summit' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'expand' => 'presentation, new_category, old_category',
            'order' => '-old_category_name'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getAllPresentationCategoryChangeRequest",
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
        $this->assertTrue($page->total == 1);
    }

    public function testRejectRequest(){
        $params = [
            'summit' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'presentation_id' => self::$presentations[0]->getId(),
            'expand' => 'presentation, new_category, old_category',
        ];

        $data = [
            'new_category_id'  => self::$secondaryTrack->getId(),
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@createPresentationCategoryChangeRequest",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $request = json_decode($content);
        $this->assertTrue(!is_null($request));
        $this->assertTrue($request->status === 'Pending');


        $params = [
            'summit' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'presentation_id' => self::$presentations[0]->getId(),
            'category_change_request_id' => $request->id,
            'expand' => 'presentation, new_category, old_category',
        ];

        $data = [
            'approved'  => false,
            'reason' => 'TBD',
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSelectionPlansApiController@resolvePresentationCategoryChangeRequest",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $request = json_decode($content);
        $this->assertTrue(!is_null($request));
    }

    public function testGetPresentationsBySelectionPlan(){

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'filter' => [
                'status==Received',
                'is_chair_visible==1',
                sprintf('track_id==%s', self::$defaultTrack->getId()),
            ],
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getSelectionPlanPresentations",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $presentations = json_decode($content);
        $this->assertTrue(!is_null($presentations));
        $this->assertTrue($presentations->total >= 1);
    }
}