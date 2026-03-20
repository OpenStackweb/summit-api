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

final class OAuth2SummitRSVPTemplateApiTest extends ProtectedApiTestCase
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

    private function summitId()
    {
        return self::$summit->getId();
    }

    public function testGetSummitRSVPTemplates()
    {
        $params = [
            'id'       => $this->summitId(),
            'page'     => 1,
            'per_page' => 5,
            'order'    => '-id'
        ];

        $headers =
            [
                "HTTP_Authorization" => " Bearer " . $this->access_token,
                "CONTENT_TYPE"       => "application/json"
            ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitRSVPTemplatesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $rsvp_templates = json_decode($content);
        $this->assertTrue(!is_null($rsvp_templates));
        return $rsvp_templates;
    }

    public function testGetSummitRSVPTemplateQuestionsMetadata()
    {
        $params = [
            'id'       => $this->summitId(),
        ];

        $headers =
            [
                "HTTP_Authorization" => " Bearer " . $this->access_token,
                "CONTENT_TYPE"       => "application/json"
            ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitRSVPTemplatesApiController@getRSVPTemplateQuestionsMetadata",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $metadata = json_decode($content);
        $this->assertTrue(!is_null($metadata));
        return $metadata;
    }

    public function testAddRSVPTemplate(){

        $params = [
            'id' => $this->summitId(),
        ];

        $title       = str_random(16).'_rsvp_template_title';

        $data        = [
            'title'      => $title,
            'is_enabled' => false,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitRSVPTemplatesApiController@addRSVPTemplate",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $template = json_decode($content);
        $this->assertTrue(!is_null($template));
        $this->assertTrue($template->title == $title);
        return $template;
    }

    public function testGetRSVPTemplateById(){

        $template = $this->testAddRSVPTemplate();

        $params = [
            'id'          => $this->summitId(),
            'template_id' => $template->id,
        ];

        $headers =
            [
                "HTTP_Authorization" => " Bearer " . $this->access_token,
                "CONTENT_TYPE"       => "application/json"
            ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitRSVPTemplatesApiController@getRSVPTemplate",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $rsvp_template = json_decode($content);
        $this->assertTrue(!is_null($rsvp_template));
        return $rsvp_template;
    }

    public function testUpdateRSVPTemplate(){

        $template = $this->testAddRSVPTemplate();

        $params = [
            'id' => $this->summitId(),
            'template_id' => $template->id
        ];

        $data        = [
            'is_enabled' => true,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitRSVPTemplatesApiController@updateRSVPTemplate",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $template = json_decode($content);
        $this->assertTrue(!is_null($template));
        $this->assertTrue($template->is_enabled == true);
        return $template;
    }

    public function testDeleteRSVPTemplate(){

        $template = $this->testAddRSVPTemplate();

        $params = [
            'id'          => $this->summitId(),
            'template_id' => $template->id
        ];

        $headers =
            [
                "HTTP_Authorization" => " Bearer " . $this->access_token,
                "CONTENT_TYPE"       => "application/json"
            ];

        $response = $this->action
        (
            "DELETE",
            "OAuth2SummitRSVPTemplatesApiController@deleteRSVPTemplate",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    /**
     * Helper to create a template with a question already added
     */
    private function createTemplateWithTextBoxQuestion(){

        $template = $this->testAddRSVPTemplate();

        $params = [
            'id'          => $this->summitId(),
            'template_id' => $template->id
        ];

        $name       = str_random(16).'_rsvp_question';
        $data       = [
            'name'          => $name,
            'label'         => 'test label',
            'initial_value' => 'test initial value',
            'is_mandatory'  => true,
            'class_name'    => \App\Models\Foundation\Summit\Events\RSVP\RSVPTextBoxQuestionTemplate::ClassName,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitRSVPTemplatesApiController@addRSVPTemplateQuestion",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $question = json_decode($content);
        $this->assertTrue(!is_null($question));
        $this->assertTrue($question->initial_value == 'test initial value');

        return ['template' => $template, 'question' => $question];
    }

    public function testAddRSVPTemplateQuestionRSVPTextBoxQuestionTemplate(){
        $result = $this->createTemplateWithTextBoxQuestion();
        $this->assertTrue(!is_null($result['question']));
        return $result['question'];
    }

    /**
     * Helper to create a template with a dropdown question
     */
    private function createTemplateWithDropDownQuestion(){

        $template = $this->testAddRSVPTemplate();

        $params = [
            'id'          => $this->summitId(),
            'template_id' => $template->id
        ];

        $name       = str_random(16).'_rsvp_question';
        $data       = [
            'name'                => $name,
            'label'               => 'test dropdown',
            'is_mandatory'        => true,
            'is_country_selector' => true,
            'empty_string'        => '--select a value',
            'class_name'          => \App\Models\Foundation\Summit\Events\RSVP\RSVPDropDownQuestionTemplate::ClassName,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitRSVPTemplatesApiController@addRSVPTemplateQuestion",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $question = json_decode($content);
        $this->assertTrue(!is_null($question));

        return ['template' => $template, 'question' => $question];
    }

    public function testAddRSVPTemplateQuestionRSVPDropDownQuestionTemplate(){
        $result = $this->createTemplateWithDropDownQuestion();
        $this->assertTrue(!is_null($result['question']));
        return $result['question'];
    }

    public function testUpdateRSVPTemplateQuestion(){

        $result   = $this->createTemplateWithTextBoxQuestion();
        $template = $result['template'];
        $question = $result['question'];

        $params = [
            'id'          => $this->summitId(),
            'template_id' => $template->id,
            'question_id' => $question->id
        ];

        $data       = [
            'name'       => $question->name,
            'label'      => $question->label.' update!',
            'class_name' => $question->class_name
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitRSVPTemplatesApiController@updateRSVPTemplateQuestion",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);

        $question = json_decode($content);
        $this->assertTrue(!is_null($question));
        return $question;
    }

    public function testDeleteRSVPTemplateQuestion(){

        $result   = $this->createTemplateWithTextBoxQuestion();
        $template = $result['template'];
        $question = $result['question'];

        $params = [
            'id'          => $this->summitId(),
            'template_id' => $template->id,
            'question_id' => $question->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitRSVPTemplatesApiController@deleteRSVPTemplateQuestion",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testAddRSVPQuestionValue(){

        $result   = $this->createTemplateWithDropDownQuestion();
        $template = $result['template'];
        $question = $result['question'];

        $params = [
            'id'          => $this->summitId(),
            'template_id' => $template->id,
            'question_id' => $question->id
        ];

        $value      = str_random(16).'_value';
        $label      = str_random(16).'_label';

        $data       = [
            'value'      => $value,
            'label'      => $label,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitRSVPTemplatesApiController@addRSVPTemplateQuestionValue",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $value   = json_decode($content);
        $this->assertTrue(!is_null($value));
        return ['template' => $template, 'question' => $question, 'value' => $value];
    }

    public function testUpdateRSVPQuestionValue(){

        $result   = $this->testAddRSVPQuestionValue();
        $template = $result['template'];
        $question = $result['question'];
        $value    = $result['value'];

        $params = [
            'id'          => $this->summitId(),
            'template_id' => $template->id,
            'question_id' => $question->id,
            'value_id'    => $value->id
        ];

        $data       = [
            'order' => 1
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitRSVPTemplatesApiController@updateRSVPTemplateQuestionValue",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $value   = json_decode($content);
        $this->assertTrue(!is_null($value));
        $this->assertTrue($value->order == 1);
        return $value;
    }

    public function testDeleteRSVPQuestionValue(){

        $result   = $this->testAddRSVPQuestionValue();
        $template = $result['template'];
        $question = $result['question'];
        $value    = $result['value'];

        $params = [
            'id'          => $this->summitId(),
            'template_id' => $template->id,
            'question_id' => $question->id,
            'value_id'    => $value->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitRSVPTemplatesApiController@deleteRSVPTemplateQuestionValue",
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