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
use ModelSerializers\SerializerRegistry;
/**
 * Class Many2OneExpandSerializer
 * @package Libs\ModelSerializers
 */
class Many2OneExpandSerializer extends One2ManyExpandSerializer
{
    /**
     * @param array $values
     * @param string $expand
     * @return array
     */
    public function serialize(array $values, string $expand): array
    {
        $values = $this->unsetOriginalAttribute($values);
        $callback = $this->getRelationFn;
        $res = [];
        foreach ($callback($this) as $item){
            $res[] = SerializerRegistry::getInstance()->getSerializer($item)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $this->attribute_name));
        }
        $values[$this->attribute_name] = $res;
        return $values;
    }
}