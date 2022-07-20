<?php namespace ModelSerializers;
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
use Libs\ModelSerializers\Many2OneExpandSerializer;


/**
 * Class ExtraQuestionTypeSerializer
 * @package ModelSerializers
 */
class ExtraQuestionTypeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name'        => 'name:json_string',
        'Type'        => 'type:json_string',
        'Label'       => 'label:json_string',
        'Placeholder' => 'placeholder:json_string',
        'Order'       => 'order:json_int',
        'Mandatory'   => 'mandatory:json_boolean',
        'MaxSelectedValues' => 'max_selected_values:json_int',
        'Class' => 'class:json_string',
    ];

    protected static $allowed_relations = [
        'values',
        'sub_question_rules',
        'parent_rules',
    ];

    public static function testRule($e){
        return $e->allowsValues();
    }

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array() )
    {
        $question = $this->object;
        if (!$question instanceof ExtraQuestionType) return [];
        if(!count($relations)) $relations = $this->getAllowedRelations();
        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('values', $relations) && !isset($values['values']) && $question->allowsValues()) {
            $question_values = [];
            foreach ($question->getValues() as $value) {
                $question_values[] = $value->getId();
            }
            $values['values'] = $question_values;
        }

        if(in_array('sub_question_rules', $relations) && !isset($values['sub_question_rules']) && $question->allowsValues()) {
            $sub_question_rules = [];
            foreach ($question->getOrderedSubQuestionRules() as $rule) {
                $sub_question_rules[] = $rule->getId();
            }
            $values['sub_question_rules'] = $sub_question_rules;
        }

        if(in_array('parent_rules', $relations) && !isset($values['parent_rules'])) {
            $parent_rules = [];
            foreach ($question->getParentRules() as $rule) {
                $parent_rules[] = $rule->getId();
            }
            $values['parent_rules'] = $parent_rules;
        }

        return $values;
    }


    protected static $expand_mappings = [
        'values' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getValues',
            'test_rule' => 'ModelSerializers\\ExtraQuestionTypeSerializer::testRule'
        ],
        'sub_question_rules' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getOrderedSubQuestionRules',
            'test_rule' => 'ModelSerializers\\ExtraQuestionTypeSerializer::testRule',
        ],
        'parent_rules' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getParentRules',
        ]
    ];
}