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

use models\summit\SummitMediaFileType;
/**
 * Class SummitMediaFileTypeFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitMediaFileTypeFactory
{
    public static function build(array $data):SummitMediaFileType{
        return self::populate(new SummitMediaFileType(), $data);
    }

    public static function populate(SummitMediaFileType $type, array $data):SummitMediaFileType{
        if(isset($data['name'])){
            $type->setName(trim($data['name']));
        }
        if(isset($data['description'])){
            $type->setDescription(trim($data['description']));
        }
        if(isset($data['allowed_extensions'])){
            $allowed_extensions = implode('|', $data['allowed_extensions']);
            $type->setAllowedExtensions($allowed_extensions);
        }
        $type->markAsUserDefined();
        return $type;
    }
}