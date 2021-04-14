<?php namespace ModelSerializers;
/**
 * Copyright 2021 OpenStack Foundation
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
use models\summit\Presentation;
/**
 * Class TrackChairPresentationCSVSerializer
 * @package ModelSerializers
 */
final class TrackChairPresentationCSVSerializer extends TrackChairPresentationSerializer
{

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [] )
    {
        $values = parent::serialize($expand, $fields, $relations, $params);
        $presentation = $this->object;
        if(!$presentation instanceof Presentation) return $values;

        if(isset($values['description'])){
            $values['description'] = strip_tags($values['description']);
        }
        if(isset($values['attendees_expected_learnt'])){
            $values['attendees_expected_learnt'] = strip_tags($values['attendees_expected_learnt']);
        }

        return $values;
    }
}