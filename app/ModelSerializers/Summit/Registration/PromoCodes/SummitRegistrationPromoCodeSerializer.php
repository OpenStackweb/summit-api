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
        'BadgeTypeId'       => 'badge_type_id:json_int',
        'QuantityAvailable' => 'quantity_available:json_int',
        'QuantityUsed'      => 'quantity_used:json_int',
        'ValidSinceDate'    => 'valid_since_date:datetime_epoch',
        'ValidUntilDate'    => 'valid_until_date:datetime_epoch',
        'ClassName'         => 'class_name:json_string',
    ];

    protected static $allowed_relations = [
        'badge_features',
        'allowed_ticket_types',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [] )
    {
        if(!count($relations)) $relations = $this->getAllowedRelations();

        $code            = $this->object;
        if(!$code instanceof SummitRegistrationPromoCode) return [];
        $values          = parent::serialize($expand, $fields, $relations, $params);
        $serializer_type = SerializerRegistry::SerializerType_Public;

        if(in_array('badge_features', $relations)) {
            $features = [];
            foreach ($code->getBadgeFeatures() as $feature) {
                $features[] = $feature->getId();
            }
            $values['badge_features'] = $features;
        }

        if(in_array('allowed_ticket_types', $relations)) {
            $ticket_types = [];
            foreach ($code->getAllowedTicketTypes() as $ticket_type) {
                $ticket_types[] = $ticket_type->getId();
            }
            $values['allowed_ticket_types'] = $ticket_types;
        }

        if(isset($params['serializer_type']))
            $serializer_type = $params['serializer_type'];

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                switch (trim($relation)) {
                    case 'creator': {
                        if($code->hasCreator()){
                            unset($values['creator_id']);
                            $values['creator'] = SerializerRegistry::getInstance()->getSerializer
                            (
                                $code->getCreator(),
                                $serializer_type
                            )->serialize($expand);
                        }
                    }
                    break;
                    case 'badge_features': {
                        unset($values['badge_features']);
                        $features = [];
                        foreach ($code->getBadgeFeatures() as $feature) {
                            $features[] = SerializerRegistry::getInstance()->getSerializer($feature)->serialize($expand);
                        }
                        $values['badge_features'] = $features;
                    }
                        break;
                    case 'allowed_ticket_types': {
                        unset($values['allowed_ticket_types']);

                        $ticket_types = [];
                        foreach ($code->getAllowedTicketTypes() as $ticket_type) {
                            $ticket_types[] = SerializerRegistry::getInstance()->getSerializer($ticket_type)->serialize($expand);
                        }
                        $values['allowed_ticket_types'] = $ticket_types;
                    }
                        break;
                    case 'badge_type': {
                        if($code->hasBadgeType()){
                            unset($values['badge_type_id']);
                            $values['badge_type'] = SerializerRegistry::getInstance()->getSerializer
                            (
                                $code->getBadgeType()
                            )->serialize($expand);
                        }
                    }
                        break;
                }
            }
        }

        return $values;
    }
}