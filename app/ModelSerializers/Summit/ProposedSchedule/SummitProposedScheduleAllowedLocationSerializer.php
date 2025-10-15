<?php namespace App\ModelSerializers\Summit\ProposedSchedule;
/*
 * Copyright 2023 OpenStack Foundation
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

use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleAllowedLocation;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
use ModelSerializers\SilverStripeSerializer;

/**
 * Class SummitProposedScheduleAllowedLocationSerializer
 * @package App\ModelSerializers\Summit\ProposedSchedule
 */
final class SummitProposedScheduleAllowedLocationSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'LocationId' => 'location_id:json_int',
        'TrackId' => 'track_id:json_int',
    ];

    protected static $allowed_relations = [
        'allowed_timeframes',
    ];

    protected static $expand_mappings = [
        'allowed_timeframes' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getAllowedTimeframes',
        ],
        'location' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'location_id',
            'getter' => 'getLocation',
            'has' => 'hasLocation'
        ],
        'track' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'track_id',
            'getter' => 'getTrack',
            'has' => 'hasTrack'
        ],
    ];

    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $allowed_location  = $this->object;
        if(!$allowed_location instanceof SummitProposedScheduleAllowedLocation) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('allowed_timeframes', $relations) && !isset($values['allowed_timeframes'])){
            $res = [];
            foreach ($allowed_location->getAllowedTimeframes() as $time_frame){
                $res[] = $time_frame->getId();
            }
            $values['allowed_timeframes'] = $res;
        }

        return $values;
    }
}
