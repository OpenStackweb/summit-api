<?php namespace ModelSerializers;
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
/**
 * Class SummitAccessLevelTypeSerializer
 * @package ModelSerializers
 */
final class SummitAccessLevelTypeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name'              => 'name:json_string',
        'Description'       => 'description:json_string',
        'TemplateContent'   => 'template_content:json_string',
        'Default'           => 'is_default:json_boolean',
        'SummitId'          => 'summit_id:json_int',
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
            "SummitAccessLevelTypeSerializer",
            $this->object->getIdentifier(),
            $expand,
            $fields,
            $relations
        ), function () use ($expand, $fields, $relations, $params) {
            return parent::serialize($expand, $fields, $relations, $params);
        });
    }
}