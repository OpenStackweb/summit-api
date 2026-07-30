<?php namespace ModelSerializers;
/**
 * Copyright 2026 OpenStack Foundation
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
use Libs\ModelSerializers\One2ManyExpandSerializer;

/**
 * Class SummitAttendeeMemberExpandSerializer
 * @package ModelSerializers
 */
class SummitAttendeeMemberExpandSerializer extends One2ManyExpandSerializer
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
        if (!$entity->{$this->has}()) return $values;

        $values = $this->unsetOriginalAttribute($values);
        $serializer_type = $params['serializer_type'] ?? $this->serializer_type;

        $values[$this->attribute] = SerializerRegistry::getInstance()->getSerializer(
            $entity->{$this->getter}(),
            $serializer_type
        )->serialize(
            AbstractSerializer::filterExpandByPrefix($expand, $this->attribute),
            AbstractSerializer::filterFieldsByPrefix($fields, $this->attribute),
            AbstractSerializer::filterFieldsByPrefix($relations, $this->attribute),
            ['summit' => $entity->getSummit()]
        );

        return $values;
    }
}
