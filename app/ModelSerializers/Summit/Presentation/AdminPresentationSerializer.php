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
use Libs\ModelSerializers\One2ManyExpandSerializer;
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
        'SubmissionReopenedUntil' => 'submission_reopened_until:datetime_epoch',
        'SubmissionReopenedById' => 'submission_reopened_by_id:json_int',
    ];

    /**
     * Declared HERE and not on the base PresentationSerializer on purpose. getExpandsMappings()
     * merges parent into child only, and SubmissionPresentationSerializer is a SIBLING of this
     * class (both extend PresentationSerializer), so this relation is unreachable from the
     * Submission and Public variants. A case in the base expand switch would not be -- that is
     * the leak the Admin-only design exists to prevent, and why the SDS rejected id+expand when
     * a base-class switch was the only mechanism considered.
     *
     * serializer_type is explicit because One2ManyExpandSerializer defaults to Public, which
     * blanks the actor's email.
     */
    protected static $expand_mappings = [
        'submission_reopened_by' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'submission_reopened_by_id',
            'getter' => 'getSubmissionReopenedBy',
            'has' => 'hasSubmissionReopenedBy',
            'serializer_type' => SerializerRegistry::SerializerType_Private,
        ],
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
        'overflow_stream_key',
        'submission_reopened_until',
        'submission_reopened_by_id',
    ];

    protected static $allowed_relations = [
        'submission_reopened_by',
    ];

    /**
     * @param string|null $relation
     * @return string
     */
    protected function getSerializerType(?string $relation = null):string{
        return SerializerRegistry::SerializerType_Private;
    }
}