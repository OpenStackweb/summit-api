<?php namespace Tests;
/**
 * Copyright 2019 OpenStack Foundation
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
use models\summit\SummitOrderExtraQuestionTypeConstants;
/**
 * Class OAuth2SummitSelectionPlanExtraQuestionTypeApiTest
 */
final class OAuth2SummitSelectionPlanExtraQuestionTypeApiTest extends ProtectedApiTest
{

    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$summit_permission_group->addMember(self::$member);
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearMemberTestData();
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testAddExtraQuestion(){

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ];

        $name = str_random(16).'_question';

        $data = [
            'name' => $name,
            'type' => SummitOrderExtraQuestionTypeConstants::ComboBoxQuestionType,
            'label' => $name,
            'mandatory' => true,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addExtraQuestion",
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

    public function testAddQuestionValue(){
        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ];

        $name = str_random(16).'_question';

        $data = [
            'name' => $name,
            'type' => SummitOrderExtraQuestionTypeConstants::ComboBoxQuestionType,
            'label' => $name,
            'usage' => SummitOrderExtraQuestionTypeConstants::BothQuestionUsage,
            'mandatory' => true,
            'printable' => true,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addExtraQuestion",
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

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'question_id' => $question->id
        ];

        $name = str_random(16).'_question';

        $data = [
            'value' => $name,
            'label' => $name,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addExtraQuestionValue",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $value = json_decode($content);
        $this->assertTrue(!is_null($value));

        // get all values

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'question_id' => $question->id,
            'expand' => 'values',
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getExtraQuestions",
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
        return $value;
    }

    public function testGetMetadata(){

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
         ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getExtraQuestionsMetadata",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $metadata = json_decode($content);
        $this->assertTrue(!empty($metadata));
    }
}