<?php namespace ModelSerializers;
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
use Libs\ModelSerializers\One2ManyExpandSerializer;
/**
 * Class SubQuestionRuleSerializer
 * @package ModelSerializers
 */
final class SubQuestionRuleSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Visibility' => 'visibility:json_string',
        'VisibilityCondition' => 'visibility_condition:json_string',
        'AnswerValues' => 'answer_values:json_string_array',
        'AnswerValuesOperator' => 'answer_values_operator:json_string',
        'SubQuestionId' => 'sub_question_id:json_int',
        'ParentQuestionId' => 'parent_question_id:json_int',
    ];

    protected static $expand_mappings = [
        'parent_question' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'parent_question_id',
            'getter' => 'getParentQuestion',
            'has' => 'hasParentQuestion'
        ],
        'sub_question' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'sub_question_id',
            'getter' => 'getSubQuestion',
            'has' => 'hasSubQuestion'
        ],
    ];
}