<?php namespace ModelSerializers;
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

use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitMediaUploadType;
/**
 * Class SummitMediaUploadTypeSerializer
 * @package ModelSerializers
 */
final class SummitMediaUploadTypeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name' => 'name:json_string',
        'Description' => 'description:json_string',
        'MaxSize' => 'max_size:json_int',
        'Mandatory' => 'is_mandatory:json_boolean',
        'PrivateStorageType' => 'private_storage_type:json_string',
        'PublicStorageType' => 'public_storage_type:json_string',
        'SummitId' => 'summit_id:json_int',
        'TypeId' => 'type_id:json_int',
        'UseTemporaryLinksOnPublicStorage' => 'use_temporary_links_on_public_storage:json_boolean',
        'TemporaryLinksPublicStorageTtl' => 'temporary_links_public_storage_ttl:json_int',
        'MinUploadsQty' => 'min_uploads_qty:json_int',
        'MaxUploadsQty' => 'max_uploads_qty:json_int',
        'Editable' => 'is_editable:json_boolean',
    ];

    protected static $allowed_relations = [
        'presentation_types',
        'summit',
        'type'
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
        $type = $this->object;
        if (!$type instanceof SummitMediaUploadType) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if (!count($relations)) $relations = $this->getAllowedRelations();
        if(!count($fields)) $fields = $this->getAllowedFields();
        if (in_array('presentation_types', $relations)) {
            $presentation_types = [];
            foreach ($type->getPresentationTypes() as $presentation_type) {
                $presentation_types[] = $presentation_type->getId();
            }
            $values['presentation_types'] = $presentation_types;
        }

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                if (!in_array('presentation_types', $relations)) continue;
                switch ($relation) {
                    case 'type': {
                        unset($values['type_id']);
                        $values['type'] = SerializerRegistry::getInstance()->getSerializer(
                        $type->getType())->serialize
                        (
                            AbstractSerializer::filterExpandByPrefix($expand, $relation),
                            AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                            AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                        );
                    }
                        break;
                    case 'summit': {
                        unset($values['summit_id']);
                        $values['summit'] = SerializerRegistry::getInstance()->getSerializer($type->getSummit())->serialize
                        (
                            AbstractSerializer::filterExpandByPrefix($expand, $relation),
                            AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                            AbstractSerializer::filterFieldsByPrefix($relations, $relation)
                        );
                    }
                        break;
                    case 'presentation_types': {
                        unset($values['presentation_types']);
                        $presentation_types = [];

                        foreach ($type->getPresentationTypes() as $presentation_type){
                            $presentation_types[] = SerializerRegistry::getInstance()->getSerializer($presentation_type)->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                            );
                        }

                        $values['presentation_types'] = $presentation_types;
                    }
                        break;
                }
            }
        }
        return $values;
    }
}