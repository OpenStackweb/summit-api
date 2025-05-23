<?php namespace ModelSerializers;
/**
 * Copyright 2020 OpenStack Foundation
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
use models\summit\SummitMetric;
/**
 * Class SummitMetricSerializer
 * @package ModelSerializers
 */
class SummitMetricSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'MemberFirstName' => 'member_first_name:json_string',
        'MemberLastName' => 'member_last_name:json_string',
        'MemberProfilePhotoUrl' => 'member_pic:json_url',
        'Type' => 'type:json_string',
        'Ip' => 'ip:json_string',
        'Origin' => 'origin:json_string',
        'Browser' => 'browser:json_string',
        'OutgressDate' => 'outgress_date:datetime_epoch',
        'IngressDate' => 'ingress_date:datetime_epoch'
    ];

    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $metric  = $this->object;
        if(!$metric instanceof SummitMetric) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        return $this->checkDataPermissions($metric, $values);
    }
    /**
     * @param SummitMetric $metric
     * @param array $values
     * @return array
     */
    protected function checkDataPermissions(SummitMetric $metric, array $values):array{

        $member = $metric->hasMember() ? $metric->getMember() : null;
        if(is_null($member)) return $values;

        if(!$member->isPublicProfileShowPhoto())
        {
            if(isset($values['member_pic'])) $values['member_pic'] = Config::get("app.default_profile_image", null);
        }

        if(!$member->isPublicProfileShowFullname())
        {
            if(isset($values['member_last_name'])) $values['member_last_name'] = '';
        }
        return $values;
    }
}