<?php
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
use models\summit\SummitOrderExtraQuestionTypeConstants;
/**
 * Class OAuth2SummitOrderExtraQuestionTypeApiTest
 */
final class OAuth2SummitOrderExtraQuestionTypeApiTest extends ProtectedApiTest
{
    /**
     * @param int $summit_id
     * @param int $company_id
     * @return mixed
     */
    public function testAddExtraOrderQuestion($summit_id=27){

        $params = [
            'id' => $summit_id
        ];

        $name = str_random(16).'_question';

        $data = [
            'name' => $name,
            'type' => SummitOrderExtraQuestionTypeConstants::ComboBoxQuestionType,
            'label' => $name,
            'usage' => SummitOrderExtraQuestionTypeConstants::BothQuestionUsage,
            'mandatory' => true,
            'printable' => true,
            'placeholder' => $name,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitOrderExtraQuestionTypeApiController@add",
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

    public function testAddQuestionValue($summit_id=27){
        $question = $this->testAddExtraOrderQuestion($summit_id);
        $params = [
            'id' => $summit_id,
            'question_id' => $question->id
        ];

        $name = str_random(16).'_question';

        $data = [
            'value' => $name,
            'label' => $name,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitOrderExtraQuestionTypeApiController@addQuestionValue",
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
}