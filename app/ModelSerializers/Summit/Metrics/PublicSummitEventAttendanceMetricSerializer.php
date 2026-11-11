<?php namespace ModelSerializers;
/**
 * Copyright 2026 OpenStack Foundation
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
use Illuminate\Support\Facades\Config;
use models\summit\SummitEventAttendanceMetric;
/**
 * Class PublicSummitEventAttendanceMetricSerializer
 * Serializer used for the "current_attendance" collection on the public API.
 * It deliberately omits technical/identifying data (ip, browser, origin, created_by_id).
 * @package ModelSerializers
 */
final class PublicSummitEventAttendanceMetricSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'MemberFirstName' => 'member_first_name:json_string',
        'MemberLastName' => 'member_last_name:json_string',
        'MemberProfilePhotoUrl' => 'member_pic:json_url',
        'Type' => 'type:json_string',
        'OutgressDate' => 'outgress_date:datetime_epoch',
        'IngressDate' => 'ingress_date:datetime_epoch',
        'EventId' => 'event_id:json_int',
        'RoomId' => 'room_id:json_int',
        'SubType' => 'sub_type:json_string',
        'AttendeeId' => 'attendee_id:json_int',
    ];

    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $metric = $this->object;
        if (!$metric instanceof SummitEventAttendanceMetric) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        $member = $metric->hasMember() ? $metric->getMember() : null;
        if (is_null($member)) return $values;

        if (!$member->isPublicProfileShowPhoto() && isset($values['member_pic']))
            $values['member_pic'] = Config::get("app.default_profile_image", null);

        if (!$member->isPublicProfileShowFullname() && isset($values['member_last_name']))
            $values['member_last_name'] = '';

        return $values;
    }
}
