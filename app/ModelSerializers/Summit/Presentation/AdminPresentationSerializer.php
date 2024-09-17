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
        'StreamingUrl' => 'streaming_url:json_url',
        'StreamingType' => 'streaming_type:json_string',
        'EtherpadLink' => 'etherpad_link:json_url',
        'OverflowStreamingUrl' => 'overflow_streaming_url:json_url',
        'OverflowStreamIsSecure' => 'overflow_stream_is_secure:json_boolean',
        'OverflowStreamKey' => 'overflow_stream_key:json_string',
        'TrackChairAvgScoresPerRakingType' => 'track_chair_scores_avg:json_string_array',
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
        'overflow_streaming_url',
        'overflow_stream_is_secure',
        'overflow_stream_key'
    ];

    /**
     * @param string|null $relation
     * @return string
     */
    protected function getSerializerType(?string $relation = null):string{
        return SerializerRegistry::SerializerType_Private;
    }
}