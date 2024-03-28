<?php namespace ModelSerializers;
/**
 * Copyright 2021 OpenStack Foundation
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
use models\summit\SummitTrackChair;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class SummitTrackChairSerializer
 * @package ModelSerializers
 */
class SummitTrackChairSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'SummitId' => 'summit_id:json_int',
        'MemberId' => 'member_id:json_int',
    ];

    protected static $allowed_relations = [
        'categories',
    ];

    protected function getMemberSerializerType():string{
        return SerializerRegistry::SerializerType_Public;
    }

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $track_chair = $this->object;

        if (!$track_chair instanceof SummitTrackChair) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);

        if (in_array('categories', $relations)) {
            $categories = [];
            foreach ($track_chair->getCategories() as $t) {
                $categories[] = $t->getId();
            }
            $values['categories'] = $categories;
        }

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'categories':
                    {
                        $categories = [];
                        foreach ($track_chair->getCategories() as $t) {
                            $categories[] = SerializerRegistry::getInstance()->getSerializer($t)->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                $params
                            );
                        }
                        $values['categories'] = $categories;
                    }
                    break;
                    case 'member':
                        {

                            if ($track_chair->getMemberId() > 0) {
                                unset($values['member_id']);
                                $values['member'] = SerializerRegistry::getInstance()->getSerializer
                                (
                                    $track_chair->getMember(),
                                    $this->getMemberSerializerType()
                                )->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                        }
                        break;
                    case 'summit':
                        {
                            if ($track_chair->getSummitId() > 0) {
                                unset($values['summit_id']);
                                $values['summit'] = SerializerRegistry::getInstance()->getSerializer($track_chair->getSummit())->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                        }
                        break;
                }
            }
        }
        return $values;
    }
}