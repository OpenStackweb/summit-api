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
use Closure;
/**
 * Class One2ManyExpandSerializer
 * @package Libs\ModelSerializers
 */
class One2ManyExpandSerializer
{
    /**
     * @var string
     */
    protected $original_attribute;

    /**
     * @var string
     */
    protected $attribute_name;

    /**
     * @var Closure
     */
    protected $getRelationFn;

    /**
     * One2ManyExpandSerializer constructor.
     * @param string $attribute_name
     * @param Closure $getRelationFn
     * @param string|null $original_attribute
     */
    public function __construct(
        string $attribute_name,
        Closure $getRelationFn,
        string $original_attribute = null
    )
    {
        $this->attribute_name = $attribute_name;
        $this->getRelationFn = $getRelationFn;
        $this->original_attribute = $original_attribute;
    }

    /**
     * @param array $values
     * @return array
     */
    protected function unsetOriginalAttribute(array $values)
    {
        if (isset($values[$this->original_attribute]))
            unset($values[$this->original_attribute]);
        return $values;
    }

    /**
     * @param array $values
     * @param string $expand
     * @return array
     */
    public function serialize(array $values, string $expand): array
    {
        $values = $this->unsetOriginalAttribute($values);
        $callback = $this->getRelationFn;
        $values[$this->attribute_name] = SerializerRegistry::getInstance()->getSerializer($callback($this))->serialize(AbstractSerializer::filterExpandByPrefix($expand, $this->attribute_name));
        return $values;
    }

}