<?php namespace App\ModelSerializers\Marketplace;
/**
 * Copyright 2017 OpenStack Foundation
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
use App\Models\Foundation\Marketplace\CloudServiceOffered;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SilverStripeSerializer;

/**
 * Class CloudServiceOfferedSerializer
 * @package App\ModelSerializers\Marketplace
 */
final class CloudServiceOfferedSerializer extends SilverStripeSerializer
{

    protected static $allowed_relations = [
        'pricing_schemas',
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

        $service  = $this->object;
        if(!$service instanceof CloudServiceOffered) return [];
        $values           = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('pricing_schemas', $relations)){
            $res = [];
            foreach ($service->getPricingSchemas() as $schema){
                $res[] = SerializerRegistry::getInstance()
                    ->getSerializer($schema)
                    ->serialize($expand);
            }
            $values['pricing_schemas'] = $res;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {

                }
            }
        }
        return $values;
    }
}