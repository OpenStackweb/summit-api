<?php namespace Tests;
/*
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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionAnswer;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeValue;
use App\Models\Foundation\Main\ExtraQuestions\ExtraQuestionAnswerHolder;
use App\Models\Foundation\Main\ExtraQuestions\SubQuestionRule;
use Doctrine\Common\Collections\ArrayCollection;
use models\summit\Summit;
use models\summit\SummitOrderExtraQuestionAnswer;
use models\summit\SummitOrderExtraQuestionType;
use models\summit\SummitOrderExtraQuestionTypeConstants;

/**
 * Class MockExtraQuestionAnswerHolder
 * @package Tests
 */
class MockExtraQuestionAnswerHolder {
  use ExtraQuestionAnswerHolder;

  private $current_summit;

  private $answers = [];
  /**
   * @param Summit $current_summit
   */
  public function __construct(Summit $current_summit) {
    $this->current_summit = $current_summit;

    $questions = $this->current_summit->getMainOrderExtraQuestionsByUsage(
      SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage,
    );
    $answer1 = new SummitOrderExtraQuestionAnswer();
    $values1 = $questions[0]->getValues();
    $answer1->setQuestion($questions[0]);
    $answer1->setValue(strval($values1[1]->getId()));
    $this->answers[] = $answer1;
  }

  /**
   * @return SummitOrderExtraQuestionAnswer[] | ArrayCollection
   */
  public function getExtraQuestionAnswers() {
    return $this->answers;
  }

  /**
   * @return ExtraQuestionType[] | ArrayCollection
   */
  public function getExtraQuestions(): array {
    return $this->current_summit->getMainOrderExtraQuestionsByUsage(
      SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage,
    );
  }

  /**
   * @param int $questionId
   * @return ExtraQuestionType|null
   */
  public function getQuestionById(int $questionId): ?ExtraQuestionType {
    return $this->current_summit->getOrderExtraQuestionById($questionId);
  }

  /**
   * @param ExtraQuestionType $q
   * @return bool
   */
  public function isAllowedQuestion(ExtraQuestionType $q): bool {
    if (!$q instanceof SummitOrderExtraQuestionType) {
      return false;
    }
    return true;
  }

  /**
   * @param ExtraQuestionType $q
   * @return bool
   */
  public function canChangeAnswerValue(ExtraQuestionType $q): bool {
    return true;
  }

  public function clearExtraQuestionAnswers(): void {
    $this->answers = [];
  }

  /**
   * @param SummitOrderExtraQuestionAnswer $answer
   */
  public function addExtraQuestionAnswer(ExtraQuestionAnswer $answer): void {
    $this->answers[] = $answer;
  }

  public function buildExtraQuestionAnswer(): ExtraQuestionAnswer {
    return new SummitOrderExtraQuestionAnswer();
  }
}

/*
 * Class ExtraQuestionTypeModelTest
 * @package Tests
 */
class ExtraQuestionTypeModelTest extends BrowserKitTestCase {
  use InsertSummitTestData;
  static $questions = [];
  static $values = [];

  protected function setUp(): void {
    parent::setUp();

    self::insertSummitTestData();

    $question1 = new SummitOrderExtraQuestionType();
    $question1->setUsage(SummitOrderExtraQuestionTypeConstants::BothQuestionUsage);
    $question1->setLabel("QUESTION1");
    $question1->setName("QUESTION1");
    $question1->setType(SummitOrderExtraQuestionTypeConstants::CheckBoxListQuestionType);
    $question1->setMaxSelectedValues(2);
    $question1->setPrintable(true);
    self::$questions[] = $question1;

    $val1 = new ExtraQuestionTypeValue();
    $val1->setLabel("VAL1");
    $val1->setValue("VAL1");
    $val1->setOrder(1);
    $question1->addValue($val1);
    self::$values[] = $val1;

    $val2 = new ExtraQuestionTypeValue();
    $val2->setLabel("VAL2");
    $val2->setValue("VAL2");
    $val2->setOrder(2);
    $question1->addValue($val2);
    self::$values[] = $val2;

    $val3 = new ExtraQuestionTypeValue();
    $val3->setLabel("VAL3");
    $val3->setValue("VAL3");
    $val3->setOrder(3);
    $question1->addValue($val3);
    self::$values[] = $val3;

    $question2 = new SummitOrderExtraQuestionType();
    $question2->setUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
    $question2->setLabel("QUESTION2");
    $question2->setName("QUESTION2");
    $question2->setType(SummitOrderExtraQuestionTypeConstants::ComboBoxQuestionType);
    $question2->setPrintable(true);
    $question2->setMandatory(true);
    self::$questions[] = $question2;

    $val4 = new ExtraQuestionTypeValue();
    $val4->setLabel("VAL1");
    $val4->setValue("VAL1");
    $val4->setOrder(1);
    $question2->addValue($val4);
    self::$values[] = $val4;

    $val5 = new ExtraQuestionTypeValue();
    $val5->setLabel("VAL2");
    $val5->setValue("VAL2");
    $val5->setOrder(2);
    $question2->addValue($val5);
    self::$values[] = $val5;

    $val6 = new ExtraQuestionTypeValue();
    $val6->setLabel("VAL3");
    $val6->setValue("VAL3");
    $val6->setOrder(3);
    $question2->addValue($val6);
    self::$values[] = $val6;

    $question3 = new SummitOrderExtraQuestionType();
    $question3->setUsage(SummitOrderExtraQuestionTypeConstants::BothQuestionUsage);
    $question3->setLabel("QUESTION3");
    $question3->setName("QUESTION3");
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
    $rule1->setVisibilityCondition(
      SummitOrderExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_Equal,
    );
    $rule1->setAnswerValuesOperator(
      SummitOrderExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_Or,
    );

    $rule2 = new SubQuestionRule();
    $question2->addSubQuestionRule($rule2);
    $question3->addParentRule($rule2);

    $rule2->setAnswerValues([$val4->getId(), $val5->getId(), $val6->getId()]);
    $rule2->setVisibility(SummitOrderExtraQuestionTypeConstants::SubQuestionRuleVisibility_Visible);
    $rule2->setVisibilityCondition(
      SummitOrderExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_Equal,
    );
    $rule2->setAnswerValuesOperator(
      SummitOrderExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_Or,
    );

    self::$em->persist(self::$summit);
    self::$em->flush();
  }

  protected function tearDown(): void {
    self::clearSummitTestData();
    parent::tearDown();
  }

  public function testSummitGetMainQuestions() {
    $main_questions = self::$summit->getMainOrderExtraQuestionsByUsage(
      SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage,
    );
    $this->assertTrue(count($main_questions) > 0);
  }

  public function testValidateAnswers() {
    $mock = new MockExtraQuestionAnswerHolder(self::$summit);
    $res = $mock->hadCompletedExtraQuestions([
      [
        "question_id" => self::$questions[0]->getId(),
        "answer" => self::$values[0]->getId(),
      ],
      [
        "question_id" => self::$questions[1]->getId(),
        "answer" => implode(",", [self::$values[3]->getId(), self::$values[5]->getId()]),
      ],
      [
        "question_id" => self::$questions[2]->getId(),
        "answer" => "test",
      ],
    ]);

    $this->assertTrue($res);
  }

  public function testSameValidateAnswers() {
    $mock = new MockExtraQuestionAnswerHolder(self::$summit);
    $res = $mock->hadCompletedExtraQuestions();
    $this->assertTrue($res);
  }
}
