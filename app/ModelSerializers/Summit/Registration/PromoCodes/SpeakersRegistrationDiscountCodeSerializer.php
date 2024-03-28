<?php namespace ModelSerializers;
/**
 * Copyright 2023 OpenStack Foundation
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

use Libs\ModelSerializers\Many2OneExpandSerializer;
use models\summit\SpeakersRegistrationDiscountCode;
/**
 * Class SpeakersRegistrationDiscountCodeSerializer
 * @package ModelSerializers
 */
class SpeakersRegistrationDiscountCodeSerializer
    extends SummitRegistrationDiscountCodeSerializer
{
    protected static $array_mappings = [
        'Type' => 'type:json_string',
    ];

    protected static $allowed_relations = [
        'owners',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $code = $this->object;
        if(!$code instanceof SpeakersRegistrationDiscountCode) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('owners', $relations) && !isset($values['owners'])){
            $owners = [];
            foreach ($code->getOwners() as $owner){
                $owners[] = $owner->getId();
            }
            $values['owners'] = $owners;
        }

        return $values;
    }

    protected static $expand_mappings = [
        'owners' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getOwners',
        ]
    ];
}