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
     * @param $entity
     * @param array $values
     * @param string $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @param bool $should_verify_relation
     * @return array
     */
    public function serialize
    (
        $entity,
        array $values,
        string $expand,
        array $fields = [],
        array $relations = [],
        array $params = [],
        bool $should_verify_relation = false
    ): array
     {
        $testRuleRes = is_null($this->test_rule) ? true : call_user_func($this->test_rule, $entity);
        if(!$testRuleRes) return $values;
        $values = $this->unsetOriginalAttribute($values);
        if($should_verify_relation && !in_array($this->attribute, $relations)) return $values;


         $childExpand    = AbstractSerializer::filterExpandByPrefix($expand, $this->attribute);
         $childFields    = AbstractSerializer::filterFieldsByPrefix($fields, $this->attribute);
         $childRelations = AbstractSerializer::filterFieldsByPrefix($relations, $this->attribute);
         $registry       = SerializerRegistry::getInstance();
         $res = [];

         $items = $entity->{$this->getter}();
         if ($items instanceof \Doctrine\ORM\PersistentCollection) {
             Log::debug(sprintf("Many2OneExpandSerializer::serializer items instanceof \Doctrine\ORM\PersistentCollection"));
             if (!$items->isInitialized()) $items->initialize();
             $items = $items->toArray();
         } elseif ($items instanceof \Doctrine\Common\Collections\Collection) {
             Log::debug(sprintf("Many2OneExpandSerializer::serializer items instanceof \Doctrine\Common\Collections\Collection"));
             $items = $items->toArray();
         } elseif ($items instanceof \Illuminate\Support\Collection) {
             Log::debug(sprintf("Many2OneExpandSerializer::serializer items instanceof \Illuminate\Support\Collection"));
             $items = $items->all();
         } elseif ($items instanceof \Traversable) {
             Log::debug(sprintf("Many2OneExpandSerializer::serializer items instanceof \Traversable"));
             $items = iterator_to_array($items, false);
         } elseif (!is_array($items)) {
             Log::debug(sprintf("Many2OneExpandSerializer::serializer items !is_array"));
             $items = (array) $items;
         }

        foreach ($items as $item){
            $shouldSkip = is_null($this->should_skip_rule) ? false : call_user_func($this->should_skip_rule, $item, $params);
            if ($shouldSkip) continue;
            $res[] = $registry->getSerializer($item, $this->serializer_type)
                ->serialize($childExpand, $childFields, $childRelations, $params);
        }
        $values[$this->attribute] = $res;
        return $values;
    }
}