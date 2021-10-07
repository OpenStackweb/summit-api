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
 * Class One2ManyExpandSerializer
 * @package Libs\ModelSerializers
 */
class One2ManyExpandSerializer implements IExpandSerializer
{
    /**
     * @var string
     */
    protected $original_attribute;

    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var string
     */
    protected $getter;

    /**
     * @var string
     */
    protected $has;

    /**
     * One2ManyExpandSerializer constructor.
     * @param string $original_attribute
     * @param string $attribute
     * @param string $getter
     * @param string|null $has
     */
    public function __construct(string $original_attribute, string $attribute, string $getter, ?string $has = null)
    {
        $this->original_attribute = $original_attribute;
        $this->attribute = $attribute;
        $this->getter = $getter;
        $this->has = $has;
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
        $res = $entity->{$this->has}();
        if(boolval($res)){
            $values = $this->unsetOriginalAttribute($values);
            $values[$this->attribute] = SerializerRegistry::getInstance()->getSerializer
            (
                $entity->{$this->getter}()
            )->serialize
            (
                AbstractSerializer::filterExpandByPrefix($expand, $this->attribute),
                AbstractSerializer::filterFieldsByPrefix($fields, $this->attribute),
                AbstractSerializer::filterFieldsByPrefix($relations, $this->attribute),
                $params
            );
        }
        return $values;
    }

}