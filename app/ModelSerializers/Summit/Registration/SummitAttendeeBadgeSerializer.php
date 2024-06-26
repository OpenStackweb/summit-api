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

use App\ModelSerializers\Traits\RequestScopedCache;
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

    use RequestScopedCache;

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {

        return $this->cache($this->getRequestKey
        (
            "SummitAttendeeBadgeSerializer",
            $this->object->getIdentifier(),
            $expand,
            $fields,
            $relations
        ), function () use ($expand, $fields, $relations, $params) {

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

            $values['print_excerpt'] = $badge->getPrintExcerpt();

            if (!empty($expand)) {
                $exp_expand = explode(',', $expand);
                foreach ($exp_expand as $relation) {
                    $relation = trim($relation);
                    switch ($relation) {

                        case 'ticket': {
                            if ($badge->hasTicket())
                            {
                                unset($values['ticket_id']);
                                $values['ticket'] = SerializerRegistry::getInstance()->getSerializer($badge->getTicket())->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                        }
                            break;
                        case 'type': {
                            if ($badge->hasType())
                            {
                                unset($values['type_id']);
                                $values['type'] = SerializerRegistry::getInstance()->getSerializer($badge->getType())->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                        }
                            break;
                        case 'features': {
                            if (in_array('features', $relations)) {
                                unset( $values['features']);
                                $features = [];

                                foreach ($badge->getAllFeatures() as $feature) {
                                    $features[] = SerializerRegistry::getInstance()->getSerializer($feature)->serialize
                                    (
                                        AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                        $params
                                    );
                                }
                                $values['features'] = $features;
                            }
                        }
                            break;

                    }
                }
            }

            return $values;
        });
    }
}