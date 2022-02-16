<?php namespace Libs\ModelSerializers;
/**
 * Copyright 2020 OpenStack Foundation
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

use Illuminate\Support\Facades\Log;
use ModelSerializers\SerializerRegistry;
/**
 * Class Many2OneExpandSerializer
 * @package Libs\ModelSerializers
 */
class Many2OneExpandSerializer extends One2ManyExpandSerializer
{
    /**
     * @param mixed $entity
     * @param array $values
     * @param string $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($entity, array $values, string $expand, array $fields = [], array $relations = [], array $params = []): array
    {
        $values = $this->unsetOriginalAttribute($values);
        $res = [];
        foreach ($entity->{$this->getter}() as $item){
            $res[] = SerializerRegistry::getInstance()->getSerializer($item, $this->serializer_type)
                ->serialize
                (
                    AbstractSerializer::filterExpandByPrefix($expand, $this->attribute),
                    AbstractSerializer::filterFieldsByPrefix($fields, $this->attribute),
                    AbstractSerializer::filterFieldsByPrefix($relations, $this->attribute),
                    $params
                );
        }
        $values[$this->attribute] = $res;
        return $values;
    }
}