<?php namespace App\Models\Foundation\Main\ExtraQuestions;
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
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;
use App\Models\Foundation\Main\IOrderable;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping as ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="SubQuestionRule")
 * Class SubQuestionRule
 * @package App\Models\Foundation\Main\ExtraQuestions
 */
class SubQuestionRule extends SilverstripeBaseModel implements IOrderable {
  use One2ManyPropertyTrait;

  protected $getIdMappings = [
    "getParentQuestionId" => "parent_question",
    "getSubQuestionId" => "sub_question",
  ];

  protected $hasPropertyMappings = [
    "hasParentQuestion" => "parent_question",
    "hasSubQuestion" => "sub_question",
  ];
  /**
   * @ORM\Column(name="Visibility", type="string")
   * @var string
   */
  private $visibility;

  /**
   * @ORM\Column(name="VisibilityCondition", type="string")
   * @var string
   */
  private $visibility_condition;

  /**
   * @ORM\Column(name="AnswerValues", type="string")
   * @var string
   */
  private $answer_values;

  /**
   * @ORM\Column(name="AnswerValuesOperator", type="string")
   * @var string
   */
  private $answer_values_operator;

  /**
   * @ORM\Column(name="`CustomOrder`", type="integer")
   * @var int
   */
  private $order;

  /**
   * @ORM\ManyToOne(targetEntity="App\Models\Foundation\ExtraQuestions\ExtraQuestionType", fetch="EXTRA_LAZY", cascade={"persist"}, inversedBy="sub_question_rules")
   * @ORM\JoinColumn(name="ParentQuestionID", referencedColumnName="ID")
   * @var ExtraQuestionType
   */
  private $parent_question;

  /**
   * @ORM\ManyToOne(targetEntity="App\Models\Foundation\ExtraQuestions\ExtraQuestionType", fetch="EXTRA_LAZY", cascade={"persist"}, inversedBy="parent_rules")
   * @ORM\JoinColumn(name="SubQuestionID", referencedColumnName="ID")
   * @var ExtraQuestionType
   */
  private $sub_question;

  public function __construct() {
    parent::__construct();
    $this->order = 0;
  }

  /**
   * @return string
   */
  public function getVisibility(): string {
    return $this->visibility;
  }

  /**
   * @param string $visibility
   * @throws ValidationException
   */
  public function setVisibility(string $visibility): void {
    if (!in_array($visibility, ExtraQuestionTypeConstants::AllowedSubQuestionRuleVisibility)) {
      throw new ValidationException(sprintf("Visibility %s is not valid value.", $visibility));
    }
    $this->visibility = $visibility;
  }

  /**
   * @return string
   */
  public function getVisibilityCondition(): string {
    return $this->visibility_condition;
  }

  /**
   * @param string $visibility_condition
   * @throws ValidationException
   */
  public function setVisibilityCondition(string $visibility_condition): void {
    if (
      !in_array(
        $visibility_condition,
        ExtraQuestionTypeConstants::AllowedSubQuestionRuleVisibilityCondition,
      )
    ) {
      throw new ValidationException(
        sprintf("Visibility Condition  %s is not valid value.", $visibility_condition),
      );
    }

    $this->visibility_condition = $visibility_condition;
  }

  /**
   * @return array|string[]
   */
  public function getAnswerValues(): array {
    if (empty($this->answer_values)) {
      return [];
    }
    return explode(",", $this->answer_values);
  }

  /**
   * @param array|string[] $answer_values
   * @throws ValidationException
   */
  public function setAnswerValues(array $answer_values): void {
    if (count($answer_values) === 0) {
      throw new ValidationException("answer_values is mandatory.");
    }
    $this->answer_values = implode(",", $answer_values);
  }
  /**
   * @return string
   */
  public function getAnswerValuesOperator(): string {
    return $this->answer_values_operator;
  }

  /**
   * @param string $answer_values_operator
   * @throws ValidationException
   */
  public function setAnswerValuesOperator(string $answer_values_operator): void {
    if (
      !in_array(
        $answer_values_operator,
        ExtraQuestionTypeConstants::AllowedSubQuestionRuleAnswerValuesOperator,
      )
    ) {
      throw new ValidationException(
        sprintf("Answer Values Operator %s is not valid value.", $answer_values_operator),
      );
    }

    $this->answer_values_operator = $answer_values_operator;
  }

  /**
   * @return ExtraQuestionType
   */
  public function getParentQuestion(): ExtraQuestionType {
    return $this->parent_question;
  }

  /**
   * @param ExtraQuestionType $parent_question
   */
  public function setParentQuestion(ExtraQuestionType $parent_question): void {
    $this->parent_question = $parent_question;
  }

  /**
   * @return ExtraQuestionType
   */
  public function getSubQuestion(): ExtraQuestionType {
    return $this->sub_question;
  }

  /**
   * @param ExtraQuestionType $sub_question
   */
  public function setSubQuestion(ExtraQuestionType $sub_question): void {
    $this->sub_question = $sub_question;
  }

  public function clearParentQuestion(): void {
    $this->parent_question = null;
  }

  public function clearSubQuestion(): void {
    $this->sub_question = null;
  }

  /**
   * @param ExtraQuestionAnswer|null $answer
   * @return bool
   */
  public function isSubQuestionVisible(?ExtraQuestionAnswer $answer): bool {
    $initial_condition =
      $this->answer_values_operator ===
      ExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_And
        ? true
        : false;
    if (is_null($answer)) {
      if (
        $this->visibility_condition ===
        ExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_Equal
      ) {
        $initial_condition = false;
      } else {
        $initial_condition = true;
      }
    } else {
      // check condition
      switch ($this->visibility_condition) {
        case ExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_Equal:
          foreach ($this->getAnswerValues() as $rule_val) {
            if (
              $this->answer_values_operator ===
              ExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_And
            ) {
              $initial_condition &= $answer->contains($rule_val);
            } else {
              $initial_condition |= $answer->contains($rule_val);
            }
          }
          break;
        case ExtraQuestionTypeConstants::SubQuestionRuleVisibilityCondition_NotEqual:
          foreach ($this->getAnswerValues() as $rule_val) {
            if (
              $this->answer_values_operator ===
              ExtraQuestionTypeConstants::SubQuestionRuleAnswerValuesOperator_And
            ) {
              $initial_condition &= !$answer->contains($rule_val);
            } else {
              $initial_condition |= !$answer->contains($rule_val);
            }
          }
          break;
      }
    }
    // final visibility check
    if ($this->visibility === ExtraQuestionTypeConstants::SubQuestionRuleVisibility_Visible) {
      return $initial_condition;
    }
    // not visible
    return !$initial_condition;
  }

  /**
   * @return int
   */
  public function getOrder(): int {
    return $this->order;
  }

  /**
   * @param int $order
   */
  public function setOrder($order): void {
    Log::debug(sprintf("SubQuestionRule::setOrder id %s order %s", $this->id, $order));
    $this->order = $order;
  }
}
