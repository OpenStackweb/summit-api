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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeValue;
use App\Models\Foundation\Main\ExtraQuestions\SubQuestionRule;
use App\Models\Foundation\Main\IGroup;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitOrderExtraQuestionType;
use models\summit\SummitOrderExtraQuestionTypeConstants;
use models\summit\SummitTicketType;

/**
 * Class OAuth2SummitOrderExtraQuestionTypeApiTest
 */
final class OAuth2SummitOrderExtraQuestionTypeApiTest extends ProtectedApiTest
{

    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::$defaultMember = self::$member;
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

    public function testAddExtraOrderQuestion(){

        $params = [
            'id' => self::$summit->getId()
        ];

        $name = str_random(16).'_question';

        $data = [
            'name' => $name,
            'type' => SummitOrderExtraQuestionTypeConstants::ComboBoxQuestionType,
            'label' => $name,
            'usage' => SummitOrderExtraQuestionTypeConstants::BothQuestionUsage,
            'mandatory' => true,
            'printable' => true,
            'allowed_ticket_types' => [self::$default_ticket_type->getId(), self::$default_ticket_type_2->getId()],
            'allowed_badge_features_types' => [],
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

    public function testUpdateExtraOrderQuestion(){

        $params = [
            'id' => self::$summit->getId(),
            'question_id' => self::$summit->getOrderExtraQuestions()->first()
        ];

        $name = str_random(16).'_question';

        $data = [
            'name' => $name,
            'type' => SummitOrderExtraQuestionTypeConstants::ComboBoxQuestionType,
            'label' => $name,
            'usage' => SummitOrderExtraQuestionTypeConstants::BothQuestionUsage,
            'mandatory' => true,
            'printable' => true,
            'allowed_ticket_types' => [self::$default_ticket_type->getId(), self::$default_ticket_type_2->getId()],
            'allowed_badge_features_types' => [],
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrderExtraQuestionTypeApiController@update",
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
            'id' => self::$summit->getId()
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

        $params = [
            'id' => self::$summit->getId(),
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

    public function testAddSubQuestionRule412():void{

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $params = [
            'id' => self::$summit->getId()
        ];

        $name = str_random(16).'_question';

        $data = [
            'name' => $name,
            'type' => SummitOrderExtraQuestionTypeConstants::ComboBoxQuestionType,
            'label' => $name,
            'usage' => SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage,
            'mandatory' => true,
            'printable' => true,
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
        $question1 = json_decode($content);
        $this->assertTrue(!is_null($question1));

        // add values to parent question
        $params = [
            'id' => self::$summit->getId(),
            'question_id' => $question1->id,
            'expand' => 'question'
        ];

        for($i = 1 ; $i <= 10; $i++){

            $data = [
                'value' => str_random(16).'_value_'.$i,
                'label' => str_random(16).'_label_'.$i,
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
            $value = json_decode($content);
            $this->assertTrue(!is_null($value));
            $question1 = $value->question;
        }

        $params = [
            'id' => self::$summit->getId()
        ];

        $name = str_random(16).'_sub_question';

        $data = [
            'name' => $name,
            'type' => SummitOrderExtraQuestionTypeConstants::TextQuestionType,
            'label' => $name,
            'usage' => SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage,
            'mandatory' => true,
            'printable' => true,
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
        $sub_question = json_decode($content);
        $this->assertTrue(!is_null($sub_question));

        $params = [
            'id' => self::$summit->getId(),
            'question_id' => $question1->id,
            'expand' => 'parent_question,sub_question',
        ];

        $data = [
            'visibility' => ExtraQuestionTypeConstants::SubQuestionRuleVisibility_Visible,
            'visibility_condition' => 'Invalid',
            'answer_values_operator' => ExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_Or,
            'answer_values' => $question1->values,
            'sub_question_id' => $sub_question->id
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitOrderExtraQuestionTypeApiController@addSubQuestionRule",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }

    public function testAddSubQuestionRule():void{

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $params = [
            'id' => self::$summit->getId()
        ];

        $name = str_random(16).'_question';

        $data = [
            'name' => $name,
            'type' => SummitOrderExtraQuestionTypeConstants::ComboBoxQuestionType,
            'label' => $name,
            'usage' => SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage,
            'mandatory' => true,
            'printable' => true,
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
        $question1 = json_decode($content);
        $this->assertTrue(!is_null($question1));

        // add values to parent question
        $params = [
            'id' => self::$summit->getId(),
            'question_id' => $question1->id,
            'expand' => 'question'
        ];

        for($i = 1 ; $i <= 10; $i++){

            $data = [
                'value' => str_random(16).'_value_'.$i,
                'label' => str_random(16).'_label_'.$i,
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
            $value = json_decode($content);
            $this->assertTrue(!is_null($value));
            $question1 = $value->question;
        }

        $params = [
            'id' => self::$summit->getId()
        ];

        $name = str_random(16).'_sub_question';

        $data = [
            'name' => $name,
            'type' => SummitOrderExtraQuestionTypeConstants::TextQuestionType,
            'label' => $name,
            'usage' => SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage,
            'mandatory' => true,
            'printable' => true,
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
        $sub_question = json_decode($content);
        $this->assertTrue(!is_null($sub_question));

        $params = [
            'id' => self::$summit->getId(),
            'question_id' => $question1->id,
            'expand' => 'parent_question,sub_question',
        ];

        $data = [
            'visibility' => ExtraQuestionTypeConstants::SubQuestionRuleVisibility_Visible,
            'visibility_condition' => ExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_Equal,
            'answer_values_operator' => ExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_Or,
            'answer_values' => $question1->values,
            'sub_question_id' => $sub_question->id
        ];

        $response = $this->action(
               "POST",
               "OAuth2SummitOrderExtraQuestionTypeApiController@addSubQuestionRule",
               $params,
               [],
               [],
               [],
               $headers,
               json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $rule = json_decode($content);
        $this->assertTrue(!is_null($rule));
        $this->assertTrue($rule->sub_question->id === $sub_question->id);
        $this->assertTrue($rule->parent_question->id === $question1->id);
    }

    public function testUpdateSubQuestionRule():void{

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $params = [
            'id' => self::$summit->getId()
        ];

        $name = str_random(16).'_question';

        $data = [
            'name' => $name,
            'type' => SummitOrderExtraQuestionTypeConstants::ComboBoxQuestionType,
            'label' => $name,
            'usage' => SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage,
            'mandatory' => true,
            'printable' => true,
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
        $question1 = json_decode($content);
        $this->assertTrue(!is_null($question1));

        // add values to parent question
        $params = [
            'id' => self::$summit->getId(),
            'question_id' => $question1->id,
            'expand' => 'question'
        ];

        for($i = 1 ; $i <= 10; $i++){

            $data = [
                'value' => str_random(16).'_value_'.$i,
                'label' => str_random(16).'_label_'.$i,
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
            $value = json_decode($content);
            $this->assertTrue(!is_null($value));
            $question1 = $value->question;
        }

        $params = [
            'id' => self::$summit->getId()
        ];

        $name = str_random(16).'_sub_question';

        $data = [
            'name' => $name,
            'type' => SummitOrderExtraQuestionTypeConstants::TextQuestionType,
            'label' => $name,
            'usage' => SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage,
            'mandatory' => true,
            'printable' => true,
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
        $sub_question = json_decode($content);
        $this->assertTrue(!is_null($sub_question));

        $params = [
            'id' => self::$summit->getId(),
            'question_id' => $question1->id,
            'expand' => 'parent_question,sub_question',
        ];

        $data = [
            'visibility' => ExtraQuestionTypeConstants::SubQuestionRuleVisibility_Visible,
            'visibility_condition' => ExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_Equal,
            'answer_values_operator' => ExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_Or,
            'answer_values' => $question1->values,
            'sub_question_id' => $sub_question->id
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitOrderExtraQuestionTypeApiController@addSubQuestionRule",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $rule = json_decode($content);
        $this->assertTrue(!is_null($rule));
        $this->assertTrue($rule->sub_question->id === $sub_question->id);
        $this->assertTrue($rule->parent_question->id === $question1->id);

        $params = [
            'id' => self::$summit->getId(),
            'question_id' => $question1->id,
            'expand' => 'parent_question,sub_question',
            'rule_id' => $rule->id
        ];

        $data = [
            'visibility_condition' => ExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_NotEqual,
            'answer_values_operator' => ExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_And,
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrderExtraQuestionTypeApiController@updateSubQuestionRule",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $rule = json_decode($content);
        $this->assertTrue(!is_null($rule));
        $this->assertTrue($rule->visibility_condition === ExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_NotEqual);
        $this->assertTrue($rule->answer_values_operator === ExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_And);
    }

    static $questions = [];
    static $values = [];

    public function testGetAllMainBySummit(){

        $question1 = new SummitOrderExtraQuestionType();
        $question1->setUsage(SummitOrderExtraQuestionTypeConstants::BothQuestionUsage);
        $question1->setLabel('QUESTION1');
        $question1->setName('QUESTION1');
        $question1->setType(SummitOrderExtraQuestionTypeConstants::CheckBoxListQuestionType);
        $question1->setMaxSelectedValues(2);
        $question1->setPrintable(true);
        self::$questions[] = $question1;

        $val1 = new ExtraQuestionTypeValue();
        $val1->setLabel('VAL1');
        $val1->setValue('VAL1');
        $val1->setOrder(1);
        $question1->addValue($val1);
        self::$values[] = $val1;

        $val2 = new ExtraQuestionTypeValue();
        $val2->setLabel('VAL2');
        $val2->setValue('VAL2');
        $val2->setOrder(2);
        $question1->addValue($val2);
        self::$values[] = $val2;

        $val3 = new ExtraQuestionTypeValue();
        $val3->setLabel('VAL3');
        $val3->setValue('VAL3');
        $val3->setOrder(3);
        $question1->addValue($val3);
        self::$values[] = $val3;

        $question2 = new SummitOrderExtraQuestionType();
        $question2->setUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
        $question2->setLabel('QUESTION2');
        $question2->setName('QUESTION2');
        $question2->setType(SummitOrderExtraQuestionTypeConstants::ComboBoxQuestionType);
        $question2->setPrintable(true);
        $question2->setMandatory(true);
        self::$questions[] = $question2;

        $val4 = new ExtraQuestionTypeValue();
        $val4->setLabel('VAL1');
        $val4->setValue('VAL1');
        $val4->setOrder(1);
        $question2->addValue($val4);
        self::$values[] = $val4;

        $val5 = new ExtraQuestionTypeValue();
        $val5->setLabel('VAL2');
        $val5->setValue('VAL2');
        $val5->setOrder(2);
        $question2->addValue($val5);
        self::$values[] = $val5;

        $val6 = new ExtraQuestionTypeValue();
        $val6->setLabel('VAL3');
        $val6->setValue('VAL3');
        $val6->setOrder(3);
        $question2->addValue($val6);
        self::$values[] = $val6;

        $question3 = new SummitOrderExtraQuestionType();
        $question3->setUsage(SummitOrderExtraQuestionTypeConstants::BothQuestionUsage);
        $question3->setLabel('QUESTION3');
        $question3->setName('QUESTION3');
        $question3->setType(SummitOrderExtraQuestionTypeConstants::TextQuestionType);
        $question3->setPrintable(true);
        $question3->setMandatory(true);

        self::$questions[] = $question3;

        self::$summit->addOrderExtraQuestion($question1);
        self::$summit->addOrderExtraQuestion($question2);
        self::$summit->addOrderExtraQuestion($question3);

        self::$em->persist(self::$summit);
        self::$em->flush();

        $rule1 = new SubQuestionRule();
        $question1->addSubQuestionRule($rule1);
        $question2->addParentRule($rule1);

        $rule1->setAnswerValues([$val1->getId(), $val3->getId()]);
        $rule1->setVisibility(SummitOrderExtraQuestionTypeConstants::SubQuestionRuleVisibility_Visible);
        $rule1->setVisibilityCondition(SummitOrderExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_Equal);
        $rule1->setAnswerValuesOperator(SummitOrderExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_Or);

        $rule2 = new SubQuestionRule();
        $question2->addSubQuestionRule($rule2);
        $question3->addParentRule($rule2);

        $rule2->setAnswerValues([$val4->getId(), $val5->getId(), $val6->getId()]);
        $rule2->setVisibility(SummitOrderExtraQuestionTypeConstants::SubQuestionRuleVisibility_Visible);
        $rule2->setVisibilityCondition(SummitOrderExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_Equal);
        $rule2->setAnswerValuesOperator(SummitOrderExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_Or);

        self::$em->persist(self::$summit);
        self::$em->flush();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $params = [
            'id' => self::$summit->getId(),
            'filter' => 'class=='.ExtraQuestionTypeConstants::QuestionClassMain,
             // recursive expand
            'expand' => '*sub_question_rules,*sub_question,values,values.question'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitOrderExtraQuestionTypeApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $mainQuestions = json_decode($content);
        $this->assertTrue(!is_null($mainQuestions));
        $this->assertResponseStatus(200);
    }

    public function testGetAttendeeAllowedExtraQuestions(){
        $ticket_type_repository = EntityManager::getRepository(SummitTicketType::class);
        $attendee_ticket_type = $ticket_type_repository->findOneBy(['name' => 'Invited Attendee']);

        $attendee = self::$summit->getAttendees()->first();
        if (!$attendee instanceof SummitAttendee) $this->fail('Not a valid attendee');

        $ticket = $attendee->getTickets()->first();
        $ticket->setTicketType($attendee_ticket_type);

        self::$em->persist($attendee);
        self::$em->persist(self::$summit);
        self::$em->flush();

        // Main question with allowed ticket type (Radio button list)

        $question1 = new SummitOrderExtraQuestionType();
        $question1->setUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
        $question1->setLabel('Attendee Type');
        $question1->setName('ATTENDEE_TYPE_QUESTION');
        $question1->setType(ExtraQuestionTypeConstants::RadioButtonListQuestionType);
        $question1->setMaxSelectedValues(1);
        $question1->setPrintable(true);
        $question1->setMandatory(true);
        $question1->addAllowedTicketType($attendee_ticket_type);

        self::$questions[] = $question1;

        // main question values

        $answers = ['Developer', 'Video Creator', 'Partner', 'Press', 'Speaker', 'Roblox Staff', 'Other'];
        $order = 1;
        foreach ($answers as $answer) {
            $val = new ExtraQuestionTypeValue();
            $val->setLabel($answer);
            $val->setValue($answer);
            $val->setOrder($order++);
            $question1->addValue($val);
            self::$values[] = $val;
        }

        self::$summit->addOrderExtraQuestion($question1);

        // Sub Questions with allowed ticket type ( parent: with allowed ticket type (Radio button list))

        $allowed_subquestion_labels = ['Company/Studio Name', 'Business Phone', 'City', 'State/Province'];

        foreach ($allowed_subquestion_labels as $subquestion_label) {
            $sq = new SummitOrderExtraQuestionType();
            $sq->setUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
            $sq->setLabel($subquestion_label);
            $sq->setName($subquestion_label);
            $sq->setType(ExtraQuestionTypeConstants::TextQuestionType);
            $sq->setPrintable(true);
            $sq->setMandatory(true);

            $sq->addAllowedTicketType($attendee_ticket_type);

            self::$questions[] = $sq;
            self::$summit->addOrderExtraQuestion($sq);

            $rule1 = new SubQuestionRule();
            $rule1->setAnswerValues([self::$values[0]->getId()]);
            $rule1->setVisibility(ExtraQuestionTypeConstants::SubQuestionRuleVisibility_Visible);
            $rule1->setVisibilityCondition(ExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_Equal);
            $rule1->setAnswerValuesOperator(ExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_Or);

            $question1->addSubQuestionRule($rule1);
            $sq->addParentRule($rule1);
        }

        // SubQuestion without allowed ticket type ( this should be included no matter what)

        $no_ticket_type_sq = new SummitOrderExtraQuestionType();
        $no_ticket_type_sq->setUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
        $no_ticket_type_sq->setLabel('Country');
        $no_ticket_type_sq->setName('Country');
        $no_ticket_type_sq->setType(ExtraQuestionTypeConstants::TextQuestionType);
        $no_ticket_type_sq->setPrintable(true);
        $no_ticket_type_sq->setMandatory(true);

        self::$questions[] = $no_ticket_type_sq;
        self::$summit->addOrderExtraQuestion($no_ticket_type_sq);

        $rule1 = new SubQuestionRule();
        $rule1->setAnswerValues([self::$values[0]->getId()]);
        $rule1->setVisibility(ExtraQuestionTypeConstants::SubQuestionRuleVisibility_Visible);
        $rule1->setVisibilityCondition(ExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_Equal);
        $rule1->setAnswerValuesOperator(ExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_Or);

        $question1->addSubQuestionRule($rule1);
        $no_ticket_type_sq->addParentRule($rule1);

        self::$em->persist(self::$summit);
        self::$em->flush();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

         $params = [
            'id' => self::$summit->getId(),
            'attendee_id' => $attendee->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+id',
            // recursive expand
            'expand' => '*sub_question_rules,*sub_question,values,values.question'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitOrderExtraQuestionTypeApiController@getAttendeeExtraQuestions",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $questions = json_decode($content);
        $this->assertTrue(!is_null($questions));
        $this->assertEquals(1, $questions->total); //allowed main question
        $this->assertCount(5, $questions->data[0]->sub_question_rules);
        $this->assertResponseStatus(200);
    }

    public function testGetAttendeeAllowedExtraQuestionsWith2SubquestionLevels(){
        $ticket_type_repository = EntityManager::getRepository(SummitTicketType::class);
        $attendee_ticket_type = $ticket_type_repository->findOneBy(['name' => 'Invited Attendee']);
        $attendee_ticket_type2 = $ticket_type_repository->findOneBy(['name' => 'Roblox Staff']);

        $attendee = self::$summit->getAttendees()->first();
        if (!$attendee instanceof SummitAttendee) $this->fail('Not a valid attendee');

        $ticket = $attendee->getTickets()->first();
        $ticket->setTicketType($attendee_ticket_type);

        self::$em->persist($attendee);
        self::$em->persist(self::$summit);
        self::$em->flush();

        // Main question with allowed ticket type

        $question1 = new SummitOrderExtraQuestionType();
        $question1->setUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
        $question1->setLabel('Attendee Type');
        $question1->setName('ATTENDEE_TYPE_QUESTION');
        $question1->setType(ExtraQuestionTypeConstants::RadioButtonListQuestionType);
        $question1->setMaxSelectedValues(1);
        $question1->setPrintable(true);
        $question1->setMandatory(true);

        $question1->addAllowedTicketType($attendee_ticket_type);

        $answers = ['Developer', 'Video Creator', 'Partner', 'Press', 'Speaker', 'Roblox Staff', 'Other'];
        $order = 1;
        foreach ($answers as $answer) {
            $val = new ExtraQuestionTypeValue();
            $val->setLabel($answer);
            $val->setValue($answer);
            $val->setOrder($order++);
            $question1->addValue($val);
            self::$values[] = $val;
        }

        self::$summit->addOrderExtraQuestion($question1);

        // Level 1 subquestion with allowed ticket type

        $sq1 = new SummitOrderExtraQuestionType();
        $sq1->setUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
        $sq1->setLabel('Level 1 subquestion');
        $sq1->setName('LEVEL_1_SUBQUESTION');
        $sq1->setType(ExtraQuestionTypeConstants::RadioButtonListQuestionType);
        $sq1->setMaxSelectedValues(1);
        $sq1->setPrintable(true);
        $sq1->setMandatory(true);

        $val = new ExtraQuestionTypeValue();
        $val->setLabel('Level 1 subquestion value');
        $val->setValue('Level 1 subquestion value');
        $val->setOrder(1);
        $sq1->addValue($val);

        $sq1->addAllowedTicketType($attendee_ticket_type);
        self::$summit->addOrderExtraQuestion($sq1);

        $rule1 = new SubQuestionRule();
        $rule1->setAnswerValues([self::$values[0]->getId()]);
        $rule1->setVisibility(ExtraQuestionTypeConstants::SubQuestionRuleVisibility_Visible);
        $rule1->setVisibilityCondition(ExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_Equal);
        $rule1->setAnswerValuesOperator(ExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_Or);

        $question1->addSubQuestionRule($rule1);
        $sq1->addParentRule($rule1);

        // Level 2 subquestion without allowed ticket type

        $sq2 = new SummitOrderExtraQuestionType();
        $sq2->setUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
        $sq2->setLabel('Level 2 subquestion');
        $sq2->setName('LEVEL_2_SUBQUESTION_WITH_ALLOWED_TICKET_TYPE2');
        $sq2->setType(ExtraQuestionTypeConstants::TextQuestionType);
        $sq2->setPrintable(true);
        $sq2->setMandatory(true);
        $sq2->addAllowedTicketType($attendee_ticket_type2);

        self::$summit->addOrderExtraQuestion($sq2);

        $rule2 = new SubQuestionRule();
        $rule2->setAnswerValues([self::$values[1]->getId()]);
        $rule2->setVisibility(ExtraQuestionTypeConstants::SubQuestionRuleVisibility_Visible);
        $rule2->setVisibilityCondition(ExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_Equal);
        $rule2->setAnswerValuesOperator(ExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_Or);

        $sq1->addSubQuestionRule($rule2);
        $sq2->addParentRule($rule2);

        // Level 2 subquestion with allowed ticket type

        $sq3 = new SummitOrderExtraQuestionType();
        $sq3->setUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
        $sq3->setLabel('Level 2 subquestion with allowed ticket type');
        $sq3->setName('LEVEL_2_SUBQUESTION_WITH_ALLOWED_TICKET_TYPE');
        $sq3->setType(ExtraQuestionTypeConstants::TextQuestionType);
        $sq3->setPrintable(true);
        $sq3->setMandatory(true);

        $sq3->addAllowedTicketType($attendee_ticket_type);
        self::$summit->addOrderExtraQuestion($sq3);

        $rule3 = new SubQuestionRule();
        $rule3->setAnswerValues([self::$values[1]->getId()]);
        $rule3->setVisibility(ExtraQuestionTypeConstants::SubQuestionRuleVisibility_Visible);
        $rule3->setVisibilityCondition(ExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_Equal);
        $rule3->setAnswerValuesOperator(ExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_Or);

        $sq1->addSubQuestionRule($rule3);
        $sq3->addParentRule($rule3);

        self::$em->persist(self::$summit);
        self::$em->flush();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $params = [
            'id' => self::$summit->getId(),
            'attendee_id' => $attendee->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+id',
            // recursive expand
            'expand' => '*sub_question_rules,*sub_question,values,values.question'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitOrderExtraQuestionTypeApiController@getAttendeeExtraQuestions",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $questions = json_decode($content);
        $this->assertTrue(!is_null($questions));
        $this->assertEquals(1, $questions->total);              //allowed main question
        $this->assertCount(1, $questions->data[0]->sub_question_rules);
        $this->assertCount(1, $questions->data[0]->sub_question_rules[0]->sub_question->sub_question_rules);     //1 allowed and 1 not allowed
        $this->assertResponseStatus(200);
    }
}