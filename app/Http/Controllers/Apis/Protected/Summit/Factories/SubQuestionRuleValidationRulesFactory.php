<?php namespace App\Http\Controllers;
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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;

/**
 * Class SubQuestionRuleValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SubQuestionRuleValidationRulesFactory
{
    /**
     * @param array $data
     * @param bool $update
     * @return array
     */
    public static function build(array $data, $update = false){

        if($update){
            return [
                'visibility' => 'sometimes|string|in:'.implode(',', ExtraQuestionTypeConstants::AllowedSubQuestionRuleVisibility),
                'visibility_condition' => 'sometimes|string|in:'.implode(',', ExtraQuestionTypeConstants::AllowedSubQuestionRuleVisibilityCondition),
                'answer_values_operator' => 'sometimes|string|in:'.implode(',', ExtraQuestionTypeConstants::AllowedSubQuestionRuleAnswerValuesOperator),
                'answer_values' => 'sometimes|int_array',
                'sub_question_id'=> 'sometimes|integer'
            ];
        }

        return [
            'visibility' => 'required|string|in:'.implode(',', ExtraQuestionTypeConstants::AllowedSubQuestionRuleVisibility),
            'visibility_condition' => 'required|string|in:'.implode(',', ExtraQuestionTypeConstants::AllowedSubQuestionRuleVisibilityCondition),
            'answer_values_operator' => 'required|string|in:'.implode(',', ExtraQuestionTypeConstants::AllowedSubQuestionRuleAnswerValuesOperator),
            'answer_values' => 'required|int_array',
            'sub_question_id'=> 'required|integer'
        ];
    }
}