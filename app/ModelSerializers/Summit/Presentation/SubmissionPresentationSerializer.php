<?php namespace ModelSerializers;
/**
 * Copyright 2024 OpenStack Foundation
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

use ModelSerializers\PresentationSerializer;
use ModelSerializers\SerializerRegistry;

/**
 * Class SubmissionPresentationSerializer
 * @package ModelSerializers
 */
class SubmissionPresentationSerializer extends PresentationSerializer
{
    /**
     * @param string|null $relation
     * @return string
     */
    protected function getSerializerType(?string $relation=null):string{
        $relation = trim($relation);
        if($relation == 'created_by')
            return SerializerRegistry::SerializerType_Admin;
        if($relation == 'updated_by')
            return SerializerRegistry::SerializerType_Admin;
        if($relation == 'speakers')
            return SerializerRegistry::SerializerType_Admin;
        if($relation == 'moderator')
            return SerializerRegistry::SerializerType_Admin;
        // deprecated
        if($relation == 'creator')
            return SerializerRegistry::SerializerType_Admin;
        return SerializerRegistry::SerializerType_Private;
    }
}