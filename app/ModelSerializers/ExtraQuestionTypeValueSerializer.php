<?php namespace ModelSerializers;
use Libs\ModelSerializers\One2ManyExpandSerializer;

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

/**
 * Class ExtraQuestionTypeValueSerializer
 * @package ModelSerializers
 */
class ExtraQuestionTypeValueSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Label'      => 'label:json_string',
        'Value'      => 'value:json_string',
        'Order'      => 'order:json_int',
        'QuestionId' => 'question_id:json_int',
        'Default'    => 'is_default:json_boolean',
    ];

    protected static $expand_mappings = [
        'question' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'question_id',
            'getter' => 'getQuestion',
            'has' => 'hasQuestion'
        ],
    ];
}