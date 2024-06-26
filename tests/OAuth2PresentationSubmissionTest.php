<?php namespace Tests;
use models\summit\SummitEvent;

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

class OAuth2PresentationSubmissionTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    protected function setUp():void
    {
        parent::setUp();

        self::insertSummitTestData();
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testSubmitPresentation(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $title       = str_random(16).'_presentation';
        $data = [
            'title'       => $title,
            'description' => 'this is a description',
            'social_description'  => 'this is a social description',
            'level'  => 'N/A',
            'attendees_expected_learnt'  => 'super duper',
            'type_id'  => self::$defaultPresentationType->getId(),
            'track_id'  => self::$defaultTrack->getId(),
            'attending_media' => true,
            'links' => ['https://www.google.com'],
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'submission_source' => SummitEvent::SOURCE_ADMIN,
            //'tags' => ['Upstream Development']
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2PresentationApiController@submitPresentation",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $presentation = json_decode($content);
        $this->assertTrue(!is_null($presentation));
        $this->assertEquals($title, $presentation->title);
        $this->assertEquals(SummitEvent::SOURCE_ADMIN, $presentation->submission_source);

        $params = [
            'id' => self::$summit->getId(),
            'presentation_id' => $presentation->id
        ];

        $response = $this->action(
            "PUT",
            "OAuth2PresentationApiController@completePresentationSubmission",
            $params,
            [],
            [],
            [],
            $headers
        );

        return $presentation;
    }

    /**
     * @param int $summit_id
     */
    public function testDeletePresentation($summit_id = 25){
        $new_presentation = $this->testSubmitPresentation($summit_id);
        $params = [
            'id' => $summit_id,
            'presentation_id' => $new_presentation->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2PresentationApiController@deletePresentation",
            $params,
            [],
            [],
            [],
            $headers,
            ''
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testImportAssetsFromMUX(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'mux_token_id'       => "TOKEN",
            'mux_token_secret' => "SECRET",
            "email_to" => "test@test.com"
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2PresentationApiController@importAssetsFromMUX",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
    }
}