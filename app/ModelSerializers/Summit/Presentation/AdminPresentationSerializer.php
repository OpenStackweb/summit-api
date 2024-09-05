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
use models\summit\Presentation;

/**
 * Class AdminPresentationSerializer
 * @package ModelSerializers
 */
class AdminPresentationSerializer extends PresentationSerializer
{

    protected static $array_mappings = [
        'Rank'              => 'rank:json_int',
        'SelectionStatus'   => 'selection_status:json_string',
        'ViewsCount'  => 'views_count:json_int',
        'CommentsCount' => 'comments_count:json_int',
        'PopularityScore' => 'popularity_score:json_float',
        'VotesCount'      => 'votes_count:json_int',
        'VotesAverage' => 'votes_average:json_float',
        'VotesTotalPoints' => 'votes_total_points:json_int',
        'TrackChairAvgScore' => 'track_chair_avg_score:json_float',
        'PassersCount' => 'passers_count:json_int',
        'LikersCount' => 'likers_count:json_int',
        'SelectorsCount' => 'selectors_count:json_int',
        'Occupancy' => 'occupancy:json_string',
    ];

    protected static $allowed_fields = [
        'rank',
        'selection_status',
        'views_count',
        'comments_count',
        'popularity_score',
        'votes_count',
        'votes_average',
        'votes_total_points',
        'track_chair_avg_score',
        'remaining_selections',
        'passers_count',
        'likers_count',
        'selectors_count',
        'track_chair_scores_avg',
        'occupancy',
        'streaming_url',
        'streaming_type',
        'etherpad_link',
    ];

    /**
     * @return string
     */
    protected function getSerializerType(?string $relation = null):string{
        return SerializerRegistry::SerializerType_Private;
    }

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize
    (
        $expand = null, array $fields = [], array $relations = [], array $params = []
    )
    {
        $presentation = $this->object;
        if (!$presentation instanceof Presentation) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);
        // alway set
        if (in_array('streaming_url', $fields))
            $values['streaming_url'] = $presentation->getStreamingUrl();
        if (in_array('streaming_type', $fields))
            $values['streaming_type'] = $presentation->getStreamingType();
        if (in_array('etherpad_link', $fields))
            $values['etherpad_link'] = $presentation->getEtherpadLink();
        if (in_array('track_chair_scores_avg', $fields))
            $values['track_chair_scores_avg'] = $presentation->getTrackChairAvgScoresPerRakingType();

        return $values;
    }
}