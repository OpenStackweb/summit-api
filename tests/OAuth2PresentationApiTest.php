<?php namespace Tests;

/**
 * Copyright 2022 OpenStack Foundation
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
use LaravelDoctrine\ORM\Facades\EntityManager;
/**
 * Class OAuth2PresentationApiTest
 */
final class OAuth2PresentationApiTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    static $current_track_chair = null;

    protected function setUp(): void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::insertSummitTestData();
        self::$summit_permission_group->addMember(self::$member);
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
        self::$current_track_chair = self::$summit->addTrackChair(self::$member, [ self::$defaultTrack ] );
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testAddTrackChairScore() {

        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'presentation_id'   => self::$default_selection_plan->getPresentations()[0]->getId(),
            'score_type_id'     => self::$default_selection_plan->getTrackChairRatingTypes()[0]->getScoreTypes()[0]->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2PresentationApiController@addTrackChairScore",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(201);

        $content = $response->getContent();

        $score = json_decode($content);

        $this->assertTrue(!is_null($score));
        $this->assertTrue($score->presentation_id === self::$default_selection_plan->getPresentations()[0]->getId());
        $this->assertTrue($score->reviewer_id === self::$current_track_chair->getId());

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'presentation_id' => $score->presentation_id,
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
        $this->assertTrue($presentation->track_chair_avg_score > 0.0);
        $this->assertTrue(count($presentation->track_chair_scores) > 0);
    }

    public function testAddTwiceTrackChairScore() {

        // 1st
        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'presentation_id'   => self::$default_selection_plan->getPresentations()[0]->getId(),
            'score_type_id'     => self::$default_selection_plan->getTrackChairRatingTypes()[0]->getScoreTypes()[0]->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2PresentationApiController@addTrackChairScore",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(201);

        $content = $response->getContent();

        $score = json_decode($content);

        $this->assertTrue(!is_null($score));
        $this->assertTrue($score->presentation_id === self::$default_selection_plan->getPresentations()[0]->getId());
        $this->assertTrue($score->reviewer_id === self::$current_track_chair->getId());
        $this->assertTrue($score->type_id === self::$default_selection_plan->getTrackChairRatingTypes()[0]->getScoreTypes()[0]->getId());

        // 2nd
        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'presentation_id'   => self::$default_selection_plan->getPresentations()[0]->getId(),
            'score_type_id'     => self::$default_selection_plan->getTrackChairRatingTypes()[0]->getScoreTypes()[1]->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2PresentationApiController@addTrackChairScore",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(201);

        $content = $response->getContent();

        $score = json_decode($content);

        $this->assertTrue(!is_null($score));
        $this->assertTrue($score->presentation_id === self::$default_selection_plan->getPresentations()[0]->getId());
        $this->assertTrue($score->reviewer_id === self::$current_track_chair->getId());
        $this->assertTrue($score->type_id === self::$default_selection_plan->getTrackChairRatingTypes()[0]->getScoreTypes()[1]->getId());


        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'presentation_id' => $score->presentation_id,
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
        $this->assertTrue($presentation->track_chair_avg_score > 0.0);
        $this->assertTrue(count($presentation->track_chair_scores) > 0);
    }

    public function testAddPresentationComment(){
        // 1st
        $params = [
            'id'                => self::$summit->getId(),
            'presentation_id'   => self::$default_selection_plan->getPresentations()[0]->getId(),
        ];

        $payload = [
            'body' => 'this is a body',
            'is_public' => true,
        ];

        $response = $this->action(
            "POST",
            "OAuth2PresentationApiController@addComment",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($payload)
        );

        $this->assertResponseStatus(201);

        $content = $response->getContent();

        $comment = json_decode($content);

        $this->assertTrue(!is_null($comment));
        $this->assertEquals('this is a body', $comment->body);
    }

    public function testAddAndGetPresentationComment(){
        // 1st
        $params = [
            'id'                => self::$summit->getId(),
            'presentation_id'   => self::$default_selection_plan->getPresentations()[0]->getId(),
        ];

        $payload = [
            'body' => 'this is a body',
            'is_public' => true,
        ];

        $response = $this->action(
            "POST",
            "OAuth2PresentationApiController@addComment",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($payload)
        );

        $this->assertResponseStatus(201);

        $content = $response->getContent();

        $comment = json_decode($content);

        $this->assertTrue(!is_null($comment));
        $this->assertEquals('this is a body', $comment->body);

        $params = [
            'id'                => self::$summit->getId(),
            'presentation_id'   => self::$default_selection_plan->getPresentations()[0]->getId(),
            'filter' => 'is_public==1'
        ];

        $response = $this->action(
            "GET",
            "OAuth2PresentationApiController@getComments",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $this->assertResponseStatus(200);

        $content = $response->getContent();

        $page = json_decode($content);

        $this->assertTrue(!is_null($page));
        $this->assertNotEmpty($page->data);
    }

    public function testAddAndUpdatePresentationComment(){
        // 1st
        $params = [
            'id'                => self::$summit->getId(),
            'presentation_id'   => self::$default_selection_plan->getPresentations()[0]->getId(),
        ];

        $payload = [
            'body' => 'this is a body',
            'is_public' => true,
        ];

        $response = $this->action(
            "POST",
            "OAuth2PresentationApiController@addComment",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($payload)
        );

        $this->assertResponseStatus(201);

        $content = $response->getContent();

        $comment = json_decode($content);

        $this->assertTrue(!is_null($comment));
        $this->assertEquals('this is a body', $comment->body);

        $payload = [
            'body' => 'this is a body update',
        ];

        $params['comment_id'] = $comment->id;

        $response = $this->action(
            "PUT",
            "OAuth2PresentationApiController@updateComment",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($payload)
        );

        $this->assertResponseStatus(201);

        $content = $response->getContent();

        $comment = json_decode($content);

        $this->assertTrue(!is_null($comment));
        $this->assertEquals('this is a body update', $comment->body);
    }

    public function testAddAndDeletePresentationComment(){
        // 1st
        $params = [
            'id'                => self::$summit->getId(),
            'presentation_id'   => self::$default_selection_plan->getPresentations()[0]->getId(),
        ];

        $payload = [
            'body' => 'this is a body',
            'is_public' => true,
        ];

        $response = $this->action(
            "POST",
            "OAuth2PresentationApiController@addComment",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($payload)
        );

        $this->assertResponseStatus(201);

        $content = $response->getContent();

        $comment = json_decode($content);

        $this->assertTrue(!is_null($comment));
        $this->assertEquals('this is a body', $comment->body);

        $params = [
            'id'                => self::$summit->getId(),
            'presentation_id'   => self::$default_selection_plan->getPresentations()[0]->getId(),
            'comment_id' => $comment->id
        ];

        $this->action(
            "DELETE",
            "OAuth2PresentationApiController@deleteComment",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
        );

        $this->assertResponseStatus(204);

    }

    public function testAddSpeaker2Presentation() {

        $params = [
            'id'              => self::$summit->getId(),
            'presentation_id' => self::$default_selection_plan->getPresentations()[0]->getId(),
            'speaker_id'      => self::$speaker->getId(),
        ];

        $payload = [
            'order' => 1,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2PresentationApiController@addSpeaker2Presentation",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $presentation = json_decode($content);
        $this->assertTrue(!is_null($presentation));
        $this->assertTrue(count($presentation->speakers) > 1);
    }

     public function testRemoveSpeakerFromPresentation() {

         $params = [
             'id'              => self::$summit->getId(),
             'presentation_id' => self::$default_selection_plan->getPresentations()[0]->getId(),
             'speaker_id'      => self::$speaker->getId(),
         ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2PresentationApiController@removeSpeakerFromPresentation",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }

    public function testAddPresentationVideo($summit_id = 25)
    {
        $repo   =  EntityManager::getRepository(\models\summit\Summit::class);
        $summit = $repo->getById($summit_id);
        $presentation = $summit->getPublishedPresentations()[0];
        $params = array
        (
            'id' => $summit_id,
            'presentation_id' => $presentation->getId()
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $video_data = array
        (
            'youtube_id' => 'cpHa7kSOur0',
            'name' => 'test video',
            'description' => 'test video',
            'display_on_site' => true,
        );

        $response = $this->action
        (
            "POST",
            "OAuth2PresentationApiController@addVideo",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($video_data)
        );

        $video_id = $response->getContent();
        $this->assertResponseStatus(201);
        return intval($video_id);
    }

    public function testUpdatePresentationVideo()
    {
        $video_id = $this->testAddPresentationVideo($summit_id = 25);

        $params = array
        (
            'id' => 7,
            'presentation_id' => 15404,
            'video_id' => $video_id
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $video_data = array
        (
            'youtube_id' => 'cpHa7kSOur0',
            'name' => 'test video update',
        );

        $response = $this->action
        (
            "PUT",
            "OAuth2PresentationApiController@updateVideo",
            $params,
            array(),
            array(),
            array(),
            $headers,
            json_encode($video_data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);

    }

    public function testGetPresentationVideos()
    {

        //$video_id = $this->testAddPresentationVideo(7, 15404);

        $params = array
        (
            'id' => 7,
            'presentation_id' => 15404,
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "GET",
            "OAuth2PresentationApiController@getPresentationVideos",
            $params,
            array(),
            array(),
            array(),
            $headers

        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

    }

    public function testDeletePresentationVideo()
    {
        $video_id = $this->testAddPresentationVideo($summit_id = 25);

        $params = array
        (
            'id' => 7,
            'presentation_id' => 15404,
            'video_id' => $video_id
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $response = $this->action
        (
            "DELETE",
            "OAuth2PresentationApiController@deleteVideo",
            $params,
            array(),
            array(),
            array(),
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);

    }

    public function testAddPresentationSlide($summit_id=25){

        $repo   =  EntityManager::getRepository(\models\summit\Summit::class);
        $summit = $repo->getById($summit_id);
        $presentation = $summit->getPublishedPresentations()[0];
        $params = array
        (
            'id' => $summit_id,
            'presentation_id' => $presentation->getId(),
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "multipart/form-data; boundary=----WebKitFormBoundaryBkSYnzBIiFtZu4pb"
        );

        $video_data = array
        (
            'name' => 'test slide',
            'description' => 'test slide',
            'display_on_site' => true,
        );

        $response = $this->action
        (
            "POST",
            "OAuth2PresentationApiController@addPresentationSlide",
            $params,
            array(),
            array(),
            [
                'file' => UploadedFile::fake()->image('slide.pdf')
            ],
            $headers,
            json_encode($video_data)
        );

        $video_id = $response->getContent();
        $this->assertResponseStatus(201);
        return intval($video_id);
    }

    public function testAddPresentationSlideInvalidName($summit_id=25){

        $repo   =  EntityManager::getRepository(\models\summit\Summit::class);
        $summit = $repo->getById($summit_id);
        $presentation = $summit->getPublishedPresentations()[0];
        $params = array
        (
            'id' => $summit_id,
            'presentation_id' => $presentation->getId(),
        );

        $headers = array
        (
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        );

        $video_data = array
        (
            'name' => 'test slide',
            'description' => 'test slide',
            'display_on_site' => true,
        );

        $response = $this->action
        (
            "POST",
            "OAuth2PresentationApiController@addPresentationSlide",
            $params,
            array(),
            array(),
            [
                'file' => UploadedFile::fake()->image('IMG 0008 副本 白底.jpg')
            ],
            $headers,
            json_encode($video_data)
        );

        $video_id = $response->getContent();
        $this->assertResponseStatus(201);
        return intval($video_id);
    }
}