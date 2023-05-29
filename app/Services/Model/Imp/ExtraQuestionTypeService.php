<?php namespace App\Services\Model\Imp;
/**
 * Copyright 2021 OpenStack Foundation
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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeValue;
use App\Models\Foundation\ExtraQuestions\IExtraQuestionTypeRepository;
use App\Models\Foundation\Factories\ExtraQuestionTypeValueFactory;
use App\Models\Foundation\Main\ExtraQuestions\Factories\SubQuestionRuleFactory;
use App\Models\Foundation\Main\ExtraQuestions\SubQuestionRule;
use App\Services\Model\AbstractService;
use App\Services\Model\IExtraQuestionTypeService;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;

/**
 * Class ExtraQuestionTypeService
 * @package App\Services\Model\Imp
 */
abstract class ExtraQuestionTypeService
    extends AbstractService
    implements IExtraQuestionTypeService
{
    /**
     * @var IExtraQuestionTypeRepository
     */
    protected $repository;

    /**
     * @param ExtraQuestionType $question
     * @param array $payload
     * @return ExtraQuestionTypeValue
     * @throws \Exception
     */
    protected function _addOrderExtraQuestionValue(ExtraQuestionType $question, array $payload): ExtraQuestionTypeValue
    {
        return $this->tx_service->transaction(function () use ($question, $payload) {

            $name   = trim($payload['value']);
            $former_value = $question->getValueByName($name);
            if(!is_null($former_value))
                throw new ValidationException("Value already exists.");

            if(isset($payload['label'])) {
                $label = trim($payload['label']);
                $former_value = $question->getValueByLabel($label);
                if (!is_null($former_value))
                    throw new ValidationException("Value already exists.");
            }

            $value = ExtraQuestionTypeValueFactory::build($payload);

            $question->addValue($value);

            return $value;

        });
    }


    /**
     * @param ExtraQuestionType $question
     * @param int $value_id
     * @param array $payload
     * @return ExtraQuestionTypeValue
     * @throws \Exception
     */
    protected function _updateOrderExtraQuestionValue(ExtraQuestionType $question, int $value_id, array $payload): ExtraQuestionTypeValue
    {
        return $this->tx_service->transaction(function () use ($question, $value_id, $payload) {

            $value = $question->getValueById($value_id);
            if(is_null($value))
                throw new EntityNotFoundException("Value not found.");

            if(isset($payload['value'])) {
                $name = trim($payload['value']);
                $former_value = $question->getValueByName($name);
                if (!is_null($former_value) && $former_value->getId() != $value_id)
                    throw new ValidationException("Value already exists.");
            }

            if(isset($payload['label'])) {
                $label = trim($payload['label']);
                $former_value = $question->getValueByLabel($label);
                if (!is_null($former_value) && $former_value->getId() != $value_id)
                    throw new ValidationException("Label already exists.");
            }

            if (isset($payload['order']) && intval($payload['order']) != $value->getOrder()) {
                // request to update order
                $question->recalculateValueOrder($value,  intval($payload['order']) );
            }

            return ExtraQuestionTypeValueFactory::populate($value, $payload);
        });
    }


    /**
     * @param ExtraQuestionType $question
     * @param int $value_id
     * @throws \Exception
     */
    protected function _deleteOrderExtraQuestionValue(ExtraQuestionType $question, int $value_id): void
    {
        $this->tx_service->transaction(function () use ($question, $value_id) {

            $value = $question->getValueById($value_id);

            if(is_null($value))
                throw new EntityNotFoundException("value not found");

            // check if question has answers

            if($this->repository->hasAnswers($question)){
                throw new ValidationException(sprintf("You can not delete question value %s bc already has answers from attendees.", $value_id));
            }

            $question->removeValue($value);
        });
    }

    /**
     * @param Summit $summit
     * @param int $parent_id
     * @param array $payload
     * @return SubQuestionRule
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSubQuestionRule(Summit $summit, int $parent_id, array $payload):SubQuestionRule{
        return $this->tx_service->transaction(function () use ($summit, $parent_id, $payload) {
            $sub_question_id = intval($payload['sub_question_id']);

            if($parent_id === $sub_question_id)
                throw new ValidationException("Parent Question can not be the same as the Sub Question.");

            $parent = $summit->getOrderExtraQuestionById($parent_id);
            if(is_null($parent))
                throw new EntityNotFoundException(sprintf("Parent Question %s not found.", $parent_id));

            $subQuestion = $summit->getOrderExtraQuestionById($sub_question_id);
            if(is_null($subQuestion))
                throw new EntityNotFoundException(sprintf("Sub Question %s not found.", $parent_id));

            if($subQuestion->getClass() !== ExtraQuestionTypeConstants::QuestionClassMain)
                throw new ValidationException(sprintf("Question %s is already a Sub Question of another main Question.", $sub_question_id));

            if(!$parent->allowsValues()){
                throw new ValidationException(sprintf("Parent Question %s does not allows MultiValues.", $parent_id));
            }

            return SubQuestionRuleFactory::build($parent, $subQuestion, $payload);
        });
    }

    /**
     * @param Summit $summit
     * @param int $parent_id
     * @param int $rule_id
     * @param array $payload
     * @return SubQuestionRule
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateSubQuestionRule(Summit $summit, int $parent_id, int $rule_id, array $payload):SubQuestionRule{
        return $this->tx_service->transaction(function () use ($summit, $parent_id, $rule_id, $payload) {

            Log::debug
            (
                sprintf
                (
                    "ExtraQuestionTypeService::updateSubQuestionRule summit %s parent %s rule %s payload %s",
                    $summit->getId(),
                    $parent_id,
                    $rule_id,
                    json_encode($payload)
                )
            );

            $parent = $summit->getOrderExtraQuestionById($parent_id);
            if(is_null($parent))
                throw new EntityNotFoundException(sprintf("Parent Question %s not found.", $parent_id));

            $rule = $parent->getSubQuestionRulesById($rule_id);

            if(is_null($rule))
                throw new EntityNotFoundException
                (
                    sprintf
                    (
                        "Rule %s does not belongs to question %s.",
                        $rule_id,
                        $parent_id
                    )
                );

            $subQuestion = $rule->getSubQuestion();
            if(isset($payload['sub_question_id'])){
                $sub_question_id = intval($payload['sub_question_id']);
                if($subQuestion->getId() !== $sub_question_id){
                    $subQuestion = $summit->getOrderExtraQuestionById($sub_question_id);
                    if(is_null($subQuestion))
                        throw new EntityNotFoundException(sprintf("Sub Question %s not found.", $sub_question_id));
                }
            }

            $rule =  SubQuestionRuleFactory::populate($rule, $parent, $subQuestion, $payload);

            Log::debug
            (
                sprintf
                (
                    "ExtraQuestionTypeService::updateSubQuestionRule rule %s currentOrder %s",
                    $rule_id,
                    $rule->getOrder(),
                )
            );

            if (isset($payload['order']) && intval($payload['order']) != $rule->getOrder()) {
                // request to update order
                Log::debug
                (
                    sprintf
                    (
                        "ExtraQuestionTypeService::updateSubQuestionRule rule %s currentOrder %s newOrder %s",
                        $rule_id,
                        $rule->getOrder(),
                        $payload['order']
                    )
                );
                $parent->recalculateSubQuestionRuleOrder($rule, intval($payload['order']));
            }

            return $rule;
        });
    }

    /**
     * @param Summit $summit
     * @param int $parent_id
     * @param int $rule_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSubQuestionRule(Summit $summit, int $parent_id, int $rule_id):void{
         $this->tx_service->transaction(function () use ($summit, $parent_id, $rule_id) {
             $parent = $summit->getOrderExtraQuestionById($parent_id);
             if(is_null($parent))
                 throw new EntityNotFoundException(sprintf("Parent Question %s not found.", $parent_id));

             $rule = $parent->getSubQuestionRulesById($rule_id);

             if(is_null($rule))
                 throw new EntityNotFoundException
                 (
                     sprintf
                     (
                         "Rule %s does not belongs to question %s",
                         $rule_id,
                         $parent_id
                     )
                 );

             $parent->removeSubQuestionRule($rule);
             $subQuestion = $rule->getSubQuestion();
             $subQuestion->removeParentRule($rule);
        });
    }


}