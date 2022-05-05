<?php namespace App\Models\Foundation\Main\ExtraQuestions\Factories;
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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\Main\ExtraQuestions\SubQuestionRule;
use models\exceptions\ValidationException;

/**
 * Class SubQuestionRuleFactory
 * @package App\Models\Foundation\Main\ExtraQuestions\Factories
 */
final class SubQuestionRuleFactory
{
    /**
     * @param ExtraQuestionType $parent
     * @param ExtraQuestionType $subQuestion
     * @param array $payload
     * @return SubQuestionRule
     * @throws ValidationException
     */
    public static function build(ExtraQuestionType $parent, ExtraQuestionType $subQuestion, array $payload):SubQuestionRule{
        return self::populate(new SubQuestionRule, $parent, $subQuestion, $payload);
    }

    /**
     * @param SubQuestionRule $rule
     * @param ExtraQuestionType $parent
     * @param ExtraQuestionType $subQuestion
     * @param array $payload
     * @return SubQuestionRule
     * @throws ValidationException
     */
    public static function populate(SubQuestionRule $rule, ExtraQuestionType $parent, ExtraQuestionType $subQuestion, array $payload):SubQuestionRule{

        $parent->addSubQuestionRule($rule);
        $subQuestion->addParentRule($rule);

        if(isset($payload['visibility']))
            $rule->setVisibility(trim($payload['visibility']));

        if(isset($payload['visibility_condition']))
            $rule->setVisibilityCondition(trim($payload['visibility_condition']));

        if(isset($payload['answer_values_operator']))
            $rule->setAnswerValuesOperator(trim($payload['answer_values_operator']));

        if(isset($payload['answer_values']) && is_array($payload['answer_values'])) {
            $values = $payload['answer_values'];
            foreach ($values as $v){
                if(!$parent->allowValue(intval($v))){
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "Parent Question %s does not allows value %s.",
                            $parent->getId(),
                            $v
                        )
                    );
                }
            }
            $rule->setAnswerValues($values);
        }

        return $rule;
    }
}