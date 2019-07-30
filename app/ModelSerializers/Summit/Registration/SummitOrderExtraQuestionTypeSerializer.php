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
use models\summit\SummitOrderExtraQuestionType;
/**
 * Class SummitOrderExtraQuestionTypeSerializer
 * @package ModelSerializers
 */
final class SummitOrderExtraQuestionTypeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name'        => 'name:json_string',
        'Type'        => 'type:json_string',
        'Label'       => 'label:json_string',
        'Usage'       => 'usage:json_string',
        'Placeholder' => 'placeholder:json_string',
        'Printable'   => 'printable:json_boolean',
        'Order'       => 'order:json_int',
        'Mandatory'   => 'mandatory:json_boolean',
        'SummitId'    => 'summit_id:json_int',
    ];

    protected static $allowed_relations = [
        'values',
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
        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('values', $relations) && $question->allowsValues()) {
            $question_values = [];
            foreach ($question->getValues() as $value) {
                $question_values[] = $value->getId();
            }
            $values['values'] = $question_values;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {
                    case 'values':
                        {
                            if (!$question->allowsValues())
                                break;
                            unset($values['values']);
                            $question_values = [];
                            foreach ($question->getValues() as $value) {
                                $question_values[] = SerializerRegistry::getInstance()->getSerializer($value)->serialize();
                            }
                            $values['values'] = $question_values;
                        }
                        break;


                }
            }
        }
        return $values;
    }
}