<?php namespace ModelSerializers;
/**
 * Copyright 2018 OpenStack Foundation
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
use Libs\ModelSerializers\One2ManyExpandSerializer;
use models\summit\SummitRegistrationPromoCode;
/**
 * Class SummitRegistrationPromoCodeSerializer
 * @package ModelSerializers
 */
class SummitRegistrationPromoCodeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Code'              => 'code:json_string',
        'Redeemed'          => 'redeemed:json_boolean',
        'EmailSent'         => 'email_sent:json_boolean',
        'Source'            => 'source:json_string',
        'SummitId'          => 'summit_id:json_int',
        'CreatorId'         => 'creator_id:json_int',
        'QuantityAvailable' => 'quantity_available:json_int',
        'QuantityUsed'      => 'quantity_used:json_int',
        'QuantityRemaining' => 'quantity_remaining:json_int',
        'ValidSinceDate'    => 'valid_since_date:datetime_epoch',
        'ValidUntilDate'    => 'valid_until_date:datetime_epoch',
        'ClassName'         => 'class_name:json_string',
        'Description'       => 'description:json_string',
        'Notes'             => 'notes:json_string',
        'AllowsToDelegate'  => 'allows_to_delegate:json_boolean',
        'AllowsToReassignRelatedTickets'  => 'allows_to_reassign:json_boolean',
    ];

    protected static $allowed_relations = [
        'badge_features',
        'allowed_ticket_types',
        'tags'
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
        $code            = $this->object;
        if(!$code instanceof SummitRegistrationPromoCode) return [];
        $values          = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('badge_features', $relations) && !isset($values['badge_features'])) {
            $features = [];
            foreach ($code->getBadgeFeatures() as $feature) {
                $features[] = $feature->getId();
            }
            $values['badge_features'] = $features;
        }

        if(in_array('allowed_ticket_types', $relations) && !isset($values['allowed_ticket_types'])) {
            $ticket_types = [];
            foreach ($code->getAllowedTicketTypes() as $ticket_type) {
                $ticket_types[] = $ticket_type->getId();
            }
            $values['allowed_ticket_types'] = $ticket_types;
        }

        if(in_array('tags', $relations) && !isset($values['tags'])) {
            $tags = [];
            foreach ($code->getTags() as $tag) {
                $tags[] = $tag->getId();
            }
            $values['tags'] = $tags;
        }

        return $values;
    }

    protected static $expand_mappings = [
        'creator' => [
            'type' => One2ManyExpandSerializer::class,
            'getter' => 'getCreator',
            'has' => 'hasCreator',
        ],
        'badge_features' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getBadgeFeatures',
        ],
        'allowed_ticket_types' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getAllowedTicketTypes',
        ],
        'tags' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getTags',
        ],
    ];
}