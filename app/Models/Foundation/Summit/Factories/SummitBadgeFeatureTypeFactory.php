<?php namespace App\Models\Foundation\Summit\Factories;
use models\summit\SummitBadgeFeatureType;

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

/**
 * Class SummitBadgeFeatureTypeFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitBadgeFeatureTypeFactory
{
    /**
     * @param array $data
     * @return SummitBadgeFeatureType
     */
    public static function build(array $data):SummitBadgeFeatureType{
        return self::populate(new SummitBadgeFeatureType, $data);
    }

    /**
     * @param SummitBadgeFeatureType $feature
     * @param array $data
     * @return SummitBadgeFeatureType
     */
    public static function populate(SummitBadgeFeatureType $feature, array $data){

        if(isset($data['name']))
            $feature->setName(trim($data['name']));

        if(isset($data['description']))
            $feature->setDescription(trim($data['description']));

        if(isset($data['template_content']))
            $feature->setTemplateContent(trim($data['template_content']));

        return $feature;
    }
}