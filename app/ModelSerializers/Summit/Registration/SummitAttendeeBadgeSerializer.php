<?php namespace App\ModelSerializers\Summit;
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
use models\summit\SummitAttendeeBadge;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class SummitAttendeeBadgeSerializer
 * @package ModelSerializers
 */
class SummitAttendeeBadgeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'PrintDate'    => 'print_date:datetime_epoch',
        'QRCode'       => 'qr_code:json_string',
        'Void'         => 'is_void:json_boolean',
        'TicketId'     => 'ticket_id:json_int',
        'TypeId'       => 'type_id:json_int',
        'PrintedTimes' => 'printed_times:json_int',
    ];

    protected static $allowed_relations = [
        'features',
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
        if(!count($relations)) $relations = $this->getAllowedRelations();
        $badge = $this->object;
        if(!$badge instanceof SummitAttendeeBadge) return [];
        $values  = parent::serialize($expand, $fields, $relations, $params);

        if (in_array('features', $relations)) {
            $features = [];

            foreach ($badge->getAllFeatures() as $feature) {
                $features[] = $feature->getId();
            }
            $values['features'] = $features;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {

                    case 'ticket': {
                        if ($badge->hasTicket())
                        {
                            unset($values['ticket_id']);
                            $values['ticket'] = SerializerRegistry::getInstance()->getSerializer($badge->getTicket())->serialize(AbstractSerializer::getExpandForPrefix('ticket', $expand));
                        }
                    }
                        break;
                    case 'type': {
                        if ($badge->hasType())
                        {
                            unset($values['type_id']);
                            $values['type'] = SerializerRegistry::getInstance()->getSerializer($badge->getType())->serialize(AbstractSerializer::getExpandForPrefix('type', $expand));
                        }
                    }
                        break;
                    case 'features': {
                        if (in_array('features', $relations)) {
                            unset( $values['features']);
                            $features = [];

                            foreach ($badge->getAllFeatures() as $feature) {
                                $features[] = SerializerRegistry::getInstance()->getSerializer($feature)->serialize(AbstractSerializer::getExpandForPrefix('features', $expand));
                            }
                            $values['features'] = $features;
                        }
                    }
                        break;

                }
            }
        }

        return $values;
    }
}