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
use App\Models\Foundation\Summit\SelectionPlan;
use DateInterval;
use DateTime;
use models\summit\PresentationActionType;
use models\summit\SummitSelectedPresentation;
use models\summit\SummitSelectedPresentationList;

/**
 * Class OAuth2SelectionPlansApiTest
 * @package Tests
 */
final class OAuth2SelectionPlansApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    /**
     * Creates an individual selection list and assigns a presentation to it via ORM.
     * Bypasses API because createIndividualSelectionList service uses getById()
     * with the token's user_id (external ID) instead of getByExternalId().
     */
    private function createSelectionListAndAssignPresentation(string $collection = SummitSelectedPresentation::CollectionSelected): SummitSelectedPresentationList
    {
        $selection_list = self::$default_selection_plan->createIndividualSelectionList(
            self::$defaultTrack, self::$member
        );

        $selection = SummitSelectedPresentation::create(
            $selection_list,
            self::$presentations[0],
            $collection,
            self::$member
        );
        $selection->setOrder(1);
        $selection_list->addSelection($selection);

        self::$em->persist(self::$summit);
        self::$em->flush();

        return $selection_list;
    }

    protected function setUp():void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::insertSummitTestData();
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
        self::clearSummitTestData();
        parent::tearDown();
    }

    /**
     * @param int $summit_id
     * @return mixed
     */
    public function testAddSelectionPlan(){

        $start = new DateTime('now');
        $end   = new DateTime('now');
        $end->add(new DateInterval('P15D'));

        $params = [
            'id' => self::$summit->getId(),
        ];

        $name = str_random(16).'_selection_plan';
        $data = [
            'name'       => $name,
            'is_enabled'  => false,
            'is_hidden'   => false,
            'allow_new_presentations' => false,
            'submission_begin_date' => $start->getTimestamp(),
            'submission_end_date' => $end->getTimestamp(),
            'presentation_creator_notification_email_template' => 'CREATOR_EMAIL_TEMPLATE',
            'presentation_moderator_notification_email_template' => 'MODERATOR_EMAIL_TEMPLATE',
            'presentation_speaker_notification_email_template' => 'SPEAKER_EMAIL_TEMPLATE',
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
            'is_hidden'   => false,
            'allow_new_presentations' => false,
            'presentation_creator_notification_email_template' => 'CREATOR_EMAIL_TEMPLATE',
            'presentation_moderator_notification_email_template' => 'MODERATOR_EMAIL_TEMPLATE',
            'presentation_speaker_notification_email_template' => 'SPEAKER_EMAIL_TEMPLATE',
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
            'is_hidden'   => false,
            'allow_new_presentations' => false,
            'presentation_creator_notification_email_template' => 'CREATOR_EMAIL_TEMPLATE',
            'presentation_moderator_notification_email_template' => 'MODERATOR_EMAIL_TEMPLATE',
            'presentation_speaker_notification_email_template' => 'SPEAKER_EMAIL_TEMPLATE',
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
            'is_hidden'   => false,
            'allow_new_presentations' => false,
            'presentation_creator_notification_email_template' => 'CREATOR_EMAIL_TEMPLATE',
            'presentation_moderator_notification_email_template' => 'MODERATOR_EMAIL_TEMPLATE',
            'presentation_speaker_notification_email_template' => 'SPEAKER_EMAIL_TEMPLATE',
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
            'id' => self::$summit->getId(),
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

    /**
     * @param string $status
     */
    public function testGetAllCurrentSummitSelectionPlansByStatus(){

        $params = [
            'id' => self::$summit->getId(),
            'page'      => 1,
            'per_page'  => 10,
            'filter' => [
                'status==submission',       //submission | selection | voting
            ],
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getAll",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $selection_plans = json_decode($content);
        $this->assertNotEmpty($selection_plans);
    }

    public function testGetPresentationsBySelectionPlanAndConditions(){

        $this->createSelectionListAndAssignPresentation();

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'filter' => [
                'status==Received',
                'is_chair_visible==1',
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

        $this->createSelectionListAndAssignPresentation();

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),

            'filter' => sprintf("status==Received,is_chair_visible==1,track_id==%s", self::$defaultTrack->getId())
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

        $this->createSelectionListAndAssignPresentation();

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

        $this->createSelectionListAndAssignPresentation(SummitSelectedPresentation::CollectionPass);

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
            'id' => self::$summit->getId(),
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
            'id' => self::$summit->getId(),
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
            'id' => self::$summit->getId(),
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
            'id' => self::$summit->getId(),
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
            'id' => self::$summit->getId(),
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
            'id' => self::$summit->getId(),
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
            'id' => self::$summit->getId(),
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
               // sprintf('track_id==%s', self::$defaultTrack->getId()),
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

    //Allowed Presentation Action Types

    public function testGetPresentationActionTypesBySelectionPlan(){
        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'page'              => 1,
            'per_page'          => 10,
            'order'             => '+order',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getAllowedPresentationActionTypes",
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
    }

    public function testGetPresentationActionTypeBySelectionPlan(){
        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'type_id'           => self::$default_presentation_action_type->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getAllowedPresentationActionType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $entity = json_decode($content);
        $this->assertTrue(!is_null($entity));
    }

    public function testAddPresentationActionTypeToSelectionPlan(){
        // Create a new PresentationActionType on the summit (not yet on the selection plan)
        $new_action_type = new PresentationActionType();
        $new_action_type->setLabel("TEST_ADD_ACTION_TYPE");
        $new_action_type->setSummit(self::$summit);
        self::$summit->addPresentationActionType($new_action_type);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'type_id'           => $new_action_type->getId(),
        ];

        $data = [
            'order' => 1,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addAllowedPresentationActionType",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $entity = json_decode($content);
        $this->assertTrue(!is_null($entity));
    }

    public function testUpdatePresentationActionTypeOrderInSelectionPlan(){
        // InsertSummitTestData adds 2 action types to default_selection_plan
        // default_presentation_action_type at order 1, second at order 2
        // Update default to order 2 (swap with the second one)
        $new_order = 2;

        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'type_id'           => self::$default_presentation_action_type->getId(),
        ];

        $data = [
            'order' => $new_order,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSelectionPlansApiController@updateAllowedPresentationActionType",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $entity = json_decode($content);
        $this->assertTrue(!is_null($entity));
    }

    public function testRemovePresentationActionTypeFromSelectionPlan(){
        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'type_id'           => self::$default_presentation_action_type->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $this->action(
            "DELETE",
            "OAuth2SummitSelectionPlansApiController@removeAllowedPresentationActionType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }

    // --- Selection Plan CRUD ---

    public function testDeleteSelectionPlan(){
        $selection_plan = $this->testAddSelectionPlan();

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => $selection_plan->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $this->action(
            "DELETE",
            "OAuth2SummitSelectionPlansApiController@deleteSelectionPlan",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }

    public function testGetSelectionPlanPresentation(){
        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'presentation_id' => self::$presentations[0]->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getSelectionPlanPresentation",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $presentation = json_decode($content);
        $this->assertTrue(!is_null($presentation));
        $this->assertEquals(self::$presentations[0]->getId(), $presentation->id);
    }

    public function testGetMySelectionPlans(){
        // First add current member as allowed member on default selection plan
        self::$default_selection_plan->addAllowedMember(self::$member->getEmail());
        self::$em->persist(self::$summit);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getMySelectionPlans",
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
        $this->assertTrue($page->total >= 1);
    }

    // --- Track Groups ---

    public function testAddTrackGroupToSelectionPlanSuccess(){
        // Create a new selection plan without any track group
        $selection_plan = $this->testAddSelectionPlan();

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => $selection_plan->id,
            'track_group_id'    => self::$defaultTrackGroup->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
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

        $this->assertResponseStatus(201);
    }

    public function testDeleteTrackGroupFromSelectionPlan(){
        // default_selection_plan already has defaultTrackGroup attached
        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'track_group_id'    => self::$defaultTrackGroup->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $this->action(
            "DELETE",
            "OAuth2SummitSelectionPlansApiController@deleteTrackGroupToSelectionPlan",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }

    // --- Event Types ---

    public function testAttachEventType(){
        // Create a new selection plan, then attach allow2VotePresentationType
        $selection_plan = $this->testAddSelectionPlan();

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => $selection_plan->id,
            'event_type_id'    => self::$allow2VotePresentationType->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSelectionPlansApiController@attachEventType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(201);
        return $selection_plan;
    }

    public function testDetachEventType(){
        // default_selection_plan already has defaultPresentationType attached
        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'event_type_id'    => self::$defaultPresentationType->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $this->action(
            "DELETE",
            "OAuth2SummitSelectionPlansApiController@detachEventType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }

    // --- Allowed Members ---

    public function testAddAllowedMember(){
        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ];

        $data = [
            'email' => 'allowed_test_' . str_random(8) . '@nomail.com',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addAllowedMember",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $allowed_member = json_decode($content);
        $this->assertTrue(!is_null($allowed_member));
        return $allowed_member;
    }

    public function testGetAllowedMembers(){
        // Add an allowed member first
        $this->testAddAllowedMember();

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'page'      => 1,
            'per_page'  => 10,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getAllowedMembers",
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
        $this->assertTrue($page->total >= 1);
    }

    public function testRemoveAllowedMember(){
        $allowed_member = $this->testAddAllowedMember();

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'allowed_member_id' => $allowed_member->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $this->action(
            "DELETE",
            "OAuth2SummitSelectionPlansApiController@removeAllowedMember",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }

}