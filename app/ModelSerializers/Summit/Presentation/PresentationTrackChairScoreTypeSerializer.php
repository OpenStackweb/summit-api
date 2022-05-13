<?php namespace ModelSerializers;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType;
use Libs\ModelSerializers\AbstractSerializer;

/**
 * Copyright 2022 OpenStack Foundation
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
 * Class PresentationTrackChairScoreTypeSerializer
 * @package ModelSerializers
 */
final class PresentationTrackChairScoreTypeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Score'         => 'score:json_int',
        'Name'          => 'name:json_string',
        'Description'   => 'description:json_string',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $values = parent::serialize($expand, $fields, $relations, $params);
        $track_chairs_score_type  = $this->object;
        if(!$track_chairs_score_type instanceof PresentationTrackChairScoreType) return [];

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                if ($relation == 'type') {
                    $track_chair_rating_type = $track_chairs_score_type->getType();
                    $values['type'] = SerializerRegistry::getInstance()
                        ->getSerializer($track_chair_rating_type)
                        ->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                }
            }
        }
        return $values;
    }
}