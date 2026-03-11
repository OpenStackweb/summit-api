<?php namespace App\ModelSerializers\Marketplace;
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
use App\Models\Foundation\Marketplace\TrainingCourseSchedule;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class TrainingCourseScheduleSerializer
 * @package App\ModelSerializers\Marketplace
 */
final class TrainingCourseScheduleSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'City'    => 'city:json_string',
        'State'   => 'state:json_string',
        'Country' => 'country:json_string',
    ];

    protected static $allowed_relations = [
        'times',
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
        $schedule = $this->object;
        if (!$schedule instanceof TrainingCourseSchedule) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if (in_array('times', $relations) && !isset($values['times'])) {
            $times = [];
            foreach ($schedule->getTimes() as $t) {
                $times[] = $t->getId();
            }
            $values['times'] = $times;
        }

        return $values;
    }

    protected static $expand_mappings = [
        'times' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getTimes',
        ],
    ];
}
