<?php namespace App\Models\Foundation\Summit\Factories;
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
use models\summit\SummitMediaUploadType;
/**
 * Class SummitMediaUploadTypeFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitMediaUploadTypeFactory
{
    /**
     * @param array $data
     * @return SummitMediaUploadType
     */
    public static function build(array $data):SummitMediaUploadType {
        return self::populate(new SummitMediaUploadType, $data);
    }

    /**
     * @param SummitMediaUploadType $type
     * @param array $data
     * @return SummitMediaUploadType
     */
    public static function populate(SummitMediaUploadType $type, array $data):SummitMediaUploadType {

        if(isset($data['name']))
            $type->setName(trim($data['name']));

        if(isset($data['description']))
            $type->setDescription(trim($data['description']));

        if(isset($data['max_size'])) // in KB
            $type->setMaxSize(intval($data['max_size']));

        if(isset($data['private_storage_type']))
            $type->setPrivateStorageType(trim($data['private_storage_type']));

        if(isset($data['temporary_links_public_storage_ttl']))
            $type->setTemporaryLinksPublicStorageTtl(intval($data['temporary_links_public_storage_ttl']));

        if(isset($data['use_temporary_links_on_public_storage']))
            $type->setUseTemporaryLinksOnPublicStorage(boolval($data['use_temporary_links_on_public_storage']));

        if(isset($data['public_storage_type']))
            $type->setPublicStorageType(trim($data['public_storage_type']));

        if(isset($data['is_mandatory']))
            if(boolval($data['is_mandatory']))
                $type->markAsMandatory();
            else
                $type->markAsOptional();

        return $type;
    }
}