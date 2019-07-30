<?php namespace App\Models\Foundation\Summit\Factories;
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
use models\summit\SummitAccessLevelType;
/**
 * Class SummitAccessLevelTypeFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitAccessLevelTypeFactory
{
    /**
     * @param array $data
     * @return SummitAccessLevelType
     */
    public static function build(array $data){
        return self::populate(new SummitAccessLevelType, $data);
    }

    /**
     * @param SummitAccessLevelType $access_level
     * @param array $data
     * @return SummitAccessLevelType
     */
    public static function populate(SummitAccessLevelType $access_level, array $data){

        if(isset($data['name']))
            $access_level->setName(trim($data['name']));

        if(isset($data['description']))
            $access_level->setDescription(trim($data['description']));

        if(isset($data['template_content']))
            $access_level->setTemplateContent(trim($data['template_content']));

        if(isset($data['is_default']))
            $access_level->setIsDefault(boolval($data['is_default']));

        return $access_level;
    }
}