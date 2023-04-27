<?php namespace ModelSerializers;
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

use App\Models\Foundation\Main\ExtraQuestions\SubQuestionRule;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use models\summit\SummitAttendee;
use models\summit\SummitOrderExtraQuestionType;

/**
 * Class SummitOrderExtraQuestionTypeSerializer
 * @package ModelSerializers
 */
final class SummitOrderExtraQuestionTypeSerializer extends ExtraQuestionTypeSerializer
{
    protected static $array_mappings = [
        'Usage'       => 'usage:json_string',
        'Printable'   => 'printable:json_boolean',
        'SummitId'    => 'summit_id:json_int',
    ];

    public static function shouldSkip($e, $params): bool
    {
        if (array_key_exists('attendee', $params)) {
            $attendee = $params['attendee'];
            if(!$attendee instanceof SummitAttendee) return false;
            if (!$e instanceof SubQuestionRule) return false;
            return !$attendee->isAllowedQuestion($e->getSubQuestion());
        }
        return false;
    }

    protected static $allowed_relations = [
        'allowed_ticket_types',
        'allowed_badge_features_types',
    ];

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
        if (!$question instanceof SummitOrderExtraQuestionType) return [];

        if(!count($relations)) $relations = $this->getAllowedRelations();

        // we do here before calling parent to overload and applyt the should skip rule
        if(in_array('sub_question_rules', $relations) && !isset($values['sub_question_rules']) && $question->allowsValues()) {
            $sub_question_rules = [];
            foreach ($question->getOrderedSubQuestionRules() as $rule) {
                if (self::shouldSkip($rule, $params)) continue;
                $sub_question_rules[] = $rule->getId();
            }
            $values['sub_question_rules'] = $sub_question_rules;
        }

        if(in_array('parent_rules', $relations) && !isset($values['parent_rules'])) {
            $parent_rules = [];
            foreach ($question->getParentRules() as $rule) {
                if (self::shouldSkip($rule, $params)) continue;
                $parent_rules[] = $rule->getId();
            }
            $values['parent_rules'] = $parent_rules;
        }

        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('allowed_ticket_types', $relations) && !isset($values['allowed_ticket_types']))
            $values['allowed_ticket_types'] = $question->getAllowedTicketTypeIds();

        if(in_array('allowed_badge_features_types', $relations) && !isset($values['allowed_badge_features_types']))
            $values['allowed_badge_features_types'] = $question->getAllowedBadgeFeatureTypeIds();

        return $values;
    }


    protected static $expand_mappings = [
        'allowed_ticket_types' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getAllowedTicketTypes',
        ],
        'allowed_badge_features_types' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getAllowedBadgeFeatureTypes',
        ],
        // overload to apply the should skip rule
        'sub_question_rules' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getOrderedSubQuestionRules',
            'test_rule' => 'ModelSerializers\\ExtraQuestionTypeSerializer::testRule',
            'should_skip_rule' => 'ModelSerializers\\SummitOrderExtraQuestionTypeSerializer::shouldSkip',

        ],
        // overload to apply the should skip rule
        'parent_rules' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getParentRules',
            'should_skip_rule' => 'ModelSerializers\\SummitOrderExtraQuestionTypeSerializer::shouldSkip',
        ]
    ];
}