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

/**
 * Class OAuth2SummitTrackChairsRankingApiTest
 */
final class OAuth2SummitTrackChairsRankingApiTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertTestData();
    }

    protected function tearDown(): void
    {
        self::clearTestData();
        parent::tearDown();
    }

    // Track Chair Rating Types

    public function testGetTrackChairRatingTypes() {

        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'order'             => '+order',
            'page'              => 1,
            'per_page'          => 10,
            'expand'            => 'score_types,selection_plan'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTrackChairRatingTypesApiController@getTrackChairRatingTypes",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $rating_types = json_decode($content);
        $this->assertTrue(!is_null($rating_types));
        $this->assertTrue($rating_types->total > 0);
        $this->assertTrue(count($rating_types->data[0]->score_types) > 0);
    }

    public function testGetTrackChairRatingType() {

        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'type_id'           => self::$default_selection_plan->getTrackChairRatingTypes()[0]->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTrackChairRatingTypesApiController@getTrackChairRatingType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $rating_type = json_decode($content);
        $this->assertTrue(!is_null($rating_type));
    }

    public function testAddTrackChairRatingType() {

        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ];

        $data = [
            'weight' => 1.5,
            'name'   => 'Rating Type Test',
            'order'  => 1,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitTrackChairRatingTypesApiController@addTrackChairRatingType",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $rating_type = json_decode($content);
        $this->assertTrue(!is_null($rating_type));
    }

    public function testUpdateTrackChairRatingType() {

        $rating_type_name = 'Rating Type Test Updated';
        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'type_id'           => self::$default_selection_plan->getTrackChairRatingTypes()[0]->getId(),
        ];

        $data = [
            'weight' => 1.8,
            'name'   => $rating_type_name,
            'order'  => 2,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitTrackChairRatingTypesApiController@updateTrackChairRatingType",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $rating_type = json_decode($content);
        $this->assertTrue(!is_null($rating_type));
        $this->assertTrue($rating_type->name == $rating_type_name);
    }

    public function testDeleteTrackChairRatingType() {

        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'type_id'           => self::$default_selection_plan->getTrackChairRatingTypes()[0]->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $this->action(
            "DELETE",
            "OAuth2SummitTrackChairRatingTypesApiController@deleteTrackChairRatingType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }

    // Track Chair Score Types

    public function testGetTrackChairScoreTypes() {

        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'type_id'           => self::$default_selection_plan->getTrackChairRatingTypes()[0]->getId(),
            'order'             => '-score',
            'page'              => 1,
            'per_page'          => 10,
            'expand'            => 'type'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTrackChairScoreTypesApiController@getTrackChairScoreTypes",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $score_types = json_decode($content);
        $this->assertTrue(!is_null($score_types));
    }

    public function testGetTrackChairScoreType() {

        $rating_type = self::$default_selection_plan->getTrackChairRatingTypes()[0];

        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'type_id'           => $rating_type->getId(),
            'score_type_id'     => $rating_type->getScoreTypes()[0]->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTrackChairScoreTypesApiController@getTrackChairScoreType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $score_type = json_decode($content);
        $this->assertTrue(!is_null($score_type));
    }

    public function testAddTrackChairScoreType() {

        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'type_id'           => self::$default_selection_plan->getTrackChairRatingTypes()[0]->getId(),
        ];

        $data = [
            'score'         => 1,
            'name'          => 'Score Type Name Test',
            'description'   => 'Score Type Description Test',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitTrackChairScoreTypesApiController@addTrackChairScoreType",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $score_type = json_decode($content);
        $this->assertTrue(!is_null($score_type));
    }

    public function testUpdateTrackChairScoreType() {

        $name = 'Score Type Name Updated Test';
        $rating_type = self::$default_selection_plan->getTrackChairRatingTypes()[0];

        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'type_id'           => $rating_type->getId(),
            'score_type_id'     => $rating_type->getScoreTypes()[0]->getId(),
        ];

        $data = [
            'score'         => 1,
            'name'          => $name,
            'description'   => 'Score Type Description Updated Test',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitTrackChairScoreTypesApiController@updateTrackChairScoreType",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $score_type = json_decode($content);
        $this->assertTrue(!is_null($score_type));
        $this->assertTrue($score_type->name == $name);
    }

    public function testDeleteTrackChairScoreType() {

        $rating_type = self::$default_selection_plan->getTrackChairRatingTypes()[0];

        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'type_id'           => $rating_type->getId(),
            'score_type_id'     => $rating_type->getScoreTypes()[0]->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $this->action(
            "DELETE",
            "OAuth2SummitTrackChairScoreTypesApiController@deleteTrackChairScoreType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }
}