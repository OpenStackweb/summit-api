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

use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use models\summit\PresentationCategoryGroup;
/**
 * Class PresentationCategoryGroupSerializer
 * @package ModelSerializers
 */
class PresentationCategoryGroupSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name'        => 'name:json_string',
        'Color'       => 'color:json_color',
        'Description' => 'description:json_string',
        'ClassName'   => 'class_name:json_string',
        'SummitId'    => 'summit_id:json_int',
        'BeginAttendeeVotingPeriodDate' => 'begin_attendee_voting_period_date:datetime_epoch',
        'EndAttendeeVotingPeriodDate' => 'end_attendee_voting_period_date:datetime_epoch',
        'MaxAttendeeVotes' => 'max_attendee_votes:json_int'
    ];

    protected static $allowed_relations = [
        'tracks',
    ];

    protected static $expand_mappings = [
        'tracks' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getCategories',
        ],
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
        $track_group = $this->object;
        if(!$track_group instanceof PresentationCategoryGroup) return $values;
        Log::debug(sprintf("PresentationCategoryGroupSerializer::serialize expand %s fields %s relations %s", $expand, json_encode($fields), json_encode($relations)));
        if(in_array('tracks', $relations) && !isset($values['tracks'])) {
            Log::debug(sprintf("PresentationCategoryGroupSerializer::serialize adding tracks relations %s", json_encode($relations)));
            $tracks = [];
            foreach ($track_group->getCategories() as $track) {
                $tracks[] = $track->getId();
            }
            $values['tracks'] = $tracks;
        }

        return $values;
    }
}