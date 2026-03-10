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
use App\Models\Foundation\Summit\Events\Presentations\TrackQuestions\TrackTextBoxQuestionTemplate;
use App\Models\Foundation\Summit\Events\Presentations\TrackQuestions\TrackRadioButtonListQuestionTemplate;
use App\Models\Foundation\Summit\Events\Presentations\TrackQuestions\TrackDropDownQuestionTemplate;
/**
 * Class OAuth2TrackQuestionsTemplateTest
 */
final class OAuth2TrackQuestionsTemplateTest
    extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetTrackQuestionTemplateByClassName()
    {
        $params = [
            'expand' => 'tracks',
            'filter' => 'class_name==TrackTextBoxQuestionTemplate',
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2TrackQuestionsTemplateApiController@getTrackQuestionTemplates",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $track_question_templates = json_decode($content);
        $this->assertNotNull($track_question_templates);
        $this->assertResponseStatus(200);
        return $track_question_templates;
    }

    public function testAddTrackQuestionTemplate(
        $class_name = TrackTextBoxQuestionTemplate::ClassName, $extra_data = []){
        $params = [
            'expand' => 'tracks'
        ];

        $name  = str_random(16).'_track_question_template_name';
        $label = str_random(16).'_track_question_template_label';
        $initial_value = str_random(16).'_initial_value';

        $data = [
            'name' => $name,
            'label' => $label,
            'class_name' => $class_name,
            'initial_value' => $initial_value,
            'is_mandatory' => true,
            'is_read_only' => true,
            'tracks' => [self::$defaultTrack->getId()]
        ];

        if(count($extra_data) > 0){
            $data = array_merge($data, $extra_data);
        }

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2TrackQuestionsTemplateApiController@addTrackQuestionTemplate",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $track_question_template = json_decode($content);
        $this->assertNotNull($track_question_template);
        $this->assertEquals($name, $track_question_template->name);
        $this->assertEquals($label, $track_question_template->label);

        return $track_question_template;
    }

    public function testUpdateTrackQuestionTemplate(){

        $new_track_question_template = $this->testAddTrackQuestionTemplate();

        $params = [
            'track_question_template_id' => $new_track_question_template->id,
            'expand' => 'tracks'
        ];

        $name       = str_random(16).'_track_question_template_name_update';
        $label       = str_random(16).'_track_question_template_label_update';
        $initial_value = str_random(16).'_initial_value_update';

        $data        = [
            'name' => $name,
            'label' => $label,
            'class_name' => TrackTextBoxQuestionTemplate::ClassName,
            'initial_value' => $initial_value,
            'is_mandatory' => false,
            'is_read_only' => false,
            'tracks' => [self::$defaultTrack->getId()]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2TrackQuestionsTemplateApiController@updateTrackQuestionTemplate",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $track_question_template = json_decode($content);
        $this->assertNotNull($track_question_template);
        $this->assertEquals($name, $track_question_template->name);
        $this->assertEquals($label, $track_question_template->label);

        return $track_question_template;
    }

    public function testAddTrackExtraQuestion(){
        $new_track_extra_question = $this->testAddTrackQuestionTemplate();

        $params = [
            'id' => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
            'question_id' => $new_track_extra_question->id,
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "PUT",
            "OAuth2SummitTracksApiController@addTrackExtraQuestion",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        return $new_track_extra_question;
    }

    public function testRemoveTrackExtraQuestion(){
        $new_track_extra_question = $this->testAddTrackExtraQuestion();

        $params = [
            'id' => self::$summit->getId(),
            'track_id' => self::$defaultTrack->getId(),
            'question_id' => $new_track_extra_question->id,
        ];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "DELETE",
            "OAuth2SummitTracksApiController@removeTrackExtraQuestion",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testDeleteTrackQuestionTemplate(){
        $new_track_question_template = $this->testAddTrackQuestionTemplate();

        $params = [
            'track_question_template_id' => $new_track_question_template->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2TrackQuestionsTemplateApiController@deleteTrackQuestionTemplate",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }

    public function testGetTrackQuestionTemplateMetadata(){
        $params = [];

        $headers = ["HTTP_Authorization" => " Bearer " . $this->access_token];
        $response = $this->action(
            "GET",
            "OAuth2TrackQuestionsTemplateApiController@getTrackQuestionTemplateMetadata",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $metadata = json_decode($content);
        $this->assertNotNull($metadata);
        $this->assertResponseStatus(200);
        return $metadata;
    }

    public function testAddTrackQuestionTemplateValue(){

        $new_track_question_template = $this->testAddTrackQuestionTemplate(
            TrackRadioButtonListQuestionTemplate::ClassName,
            [
                'empty_string' => '-- select a value --'
            ]
        );
        $params = [
            'expand' => 'tracks',
            'track_question_template_id' => $new_track_question_template->id
        ];

        $value = str_random(16).'_track_question_template_value_value';
        $label = str_random(16).'_track_question_template_value_label';
        $data = [
            'value' => $value,
            'label' => $label,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2TrackQuestionsTemplateApiController@addTrackQuestionTemplateValue",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $track_question_template_value = json_decode($content);
        $this->assertNotNull($track_question_template_value);
        $this->assertEquals($value, $track_question_template_value->value);
        $this->assertEquals($label, $track_question_template_value->label);

        return $track_question_template_value;
    }

    public function testUpdateTrackQuestionTemplateValue(){

        $new_track_question_template_value = $this->testAddTrackQuestionTemplateValue();
        $params = [
            'expand' => 'tracks',
            'track_question_template_id' => $new_track_question_template_value->owner_id,
            'track_question_template_value_id' => $new_track_question_template_value->id
        ];

        $value = str_random(16).'_track_question_template_value_value_update';
        $label = str_random(16).'_track_question_template_value_label_update';
        $data = [
            'value' => $value,
            'label' => $label,
            'order' => 1
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2TrackQuestionsTemplateApiController@updateTrackQuestionTemplateValue",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $track_question_template_value = json_decode($content);
        $this->assertNotNull($track_question_template_value);
        $this->assertEquals($value, $track_question_template_value->value);
        $this->assertEquals($label, $track_question_template_value->label);

        return $track_question_template_value;
    }

    public function testDeleteTrackQuestionTemplateValue(){
        $new_track_question_template_value = $this->testAddTrackQuestionTemplateValue();

        $params = [
            'track_question_template_id' => $new_track_question_template_value->owner_id,
            'track_question_template_value_id' => $new_track_question_template_value->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2TrackQuestionsTemplateApiController@deleteTrackQuestionTemplateValue",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }
}
