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

use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitOrderExtraQuestionAnswer;
/**
 * Class SummitOrderExtraQuestionAnswerSerializer
 * @package ModelSerializers
 */
final class SummitOrderExtraQuestionAnswerSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Value'      => 'value:json_string',
        'OrderId'    => 'order_id:json_int',
        'AttendeeId' => 'attendee_id:json_int',
        'QuestionId' => 'question_id:json_int',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $answer = $this->object;
        if (!$answer instanceof SummitOrderExtraQuestionAnswer) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if (!count($relations)) $relations = $this->getAllowedRelations();


        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {

                    case 'order':
                        {

                            if ($answer->hasOrder()) {
                                unset($values['order_id']);
                                $values['order'] = SerializerRegistry::getInstance()->getSerializer($answer->getOrder())->serialize(AbstractSerializer::getExpandForPrefix('order', $expand));
                            }
                        }
                        break;

                    case 'attendee':
                        {

                            if ($answer->hasAttendee()) {
                                unset($values['attendee_id']);
                                $values['attendee'] = SerializerRegistry::getInstance()->getSerializer($answer->getAttendee())->serialize(AbstractSerializer::getExpandForPrefix('attendee', $expand));
                            }
                        }
                        break;

                    case 'question':
                        {

                            if ($answer->hasQuestion()) {
                                unset($values['question_id']);
                                $values['question'] = SerializerRegistry::getInstance()->getSerializer($answer->getQuestion())->serialize(AbstractSerializer::getExpandForPrefix('question', $expand));
                            }
                        }
                        break;


                }
            }
        }


        return $values;
    }
}