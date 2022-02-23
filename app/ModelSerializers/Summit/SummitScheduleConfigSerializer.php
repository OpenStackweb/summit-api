<?php namespace App\ModelSerializers\Summit;
/*
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

use Libs\ModelSerializers\Many2OneExpandSerializer;
use models\summit\SummitScheduleConfig;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class SummitScheduleConfigSerializer
 * @package App\ModelSerializers\Summit
 */
final class SummitScheduleConfigSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Key' => 'key:json_string',
        'SummitId' => 'summit_id:json_int',
        'MySchedule' => 'is_my_schedule:json_boolean',
        'OnlyEventsWithAttendeeAccess' => 'only_events_with_attendee_access:json_boolean',
        'ColorSource' => 'color_source:json_string',
        'Enabled' => 'is_enabled:json_boolean',
        'Default' => 'is_default:json_boolean',
    ];

    protected static $allowed_relations = [
        'filters',
        'pre_filters',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relation
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $config = $this->object;
        if (!$config instanceof SummitScheduleConfig) return [];

        if (!count($relations)) $relations = $this->getAllowedRelations();
        $values  = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('filters', $relations) && !isset($values['filters'])){
            $filters = [];
            foreach ($config->getFilters() as $filter){
                $filters[] = $filter->getId();
            }
            $values['filters'] = $filters;
        }
        if(in_array('pre_filters', $relations) && !isset($values['pre_filters'])){
            $filters = [];
            foreach ($config->getPreFilters() as $filter){
                $filters[] = $filter->getId();
            }
            $values['pre_filters'] = $filters;
        }
        return $values;
    }

    protected static $expand_mappings = [
        'filters' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getFilters',
        ],
        'pre_filters' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getPreFilters',
        ]
    ];
}