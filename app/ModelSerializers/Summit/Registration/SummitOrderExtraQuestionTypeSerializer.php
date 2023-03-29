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

use Libs\ModelSerializers\Many2OneExpandSerializer;
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
        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('allowed_ticket_types', $relations) && !isset($values['allowed_ticket_types']))
            $values['allowed_ticket_types'] = $question->getAllowedTicketTypeIds();

        if(in_array('allowed_badge_features_types', $relations) && !isset($values['allowed_badge_features_types']))
            $values['allowed_badge_feature_types'] = $question->getAllowedBadgeFeatureTypeIds();

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
        ]
    ];
}