<?php namespace ModelSerializers;
/**
 * Copyright 2016 OpenStack Foundation
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
use models\summit\PresentationSlide;
/**
 * Class PresentationSlideSerializer
 * @package ModelSerializers
 */
final class PresentationSlideSerializer extends PresentationMaterialSerializer
{
    protected static $array_mappings = array
    (
        'Link' => 'link:json_text',
    );

    protected static $allowed_fields = [
        'link',
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
        $values = parent::serialize($expand, $fields, $relations, $params);
        $slide  = $this->object;
        if(!$slide instanceof PresentationSlide) return [];
        $values['has_file'] = false;
        if(empty($values['link'])){
            $values['link']     = $slide->hasSlide() ?  $slide->getSlide()->getUrl(): null;
            $values['has_file'] = !empty($values['link']);
        }
        return $values;
    }
}