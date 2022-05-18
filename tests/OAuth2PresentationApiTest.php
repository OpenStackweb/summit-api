<?php namespace Tests;
use App\Models\Foundation\Main\IGroup;

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
        self::insertTestData();
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
        self::clearTestData();
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
}