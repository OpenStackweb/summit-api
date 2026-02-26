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
use App\Models\Foundation\Marketplace\TrainingCourse;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class TrainingCourseSerializer
 * @package App\ModelSerializers\Marketplace
 */
final class TrainingCourseSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name'               => 'name:json_string',
        'Link'               => 'link:json_string',
        'Description'        => 'description:json_string',
        'Paid'               => 'is_paid:json_boolean',
        'Online'             => 'is_online:json_boolean',
        'TypeId'             => 'type_id:json_int',
        'LevelId'            => 'level_id:json_int',
        'TrainingServiceId'  => 'training_service_id:json_int',
    ];

    protected static $allowed_relations = [
        'type',
        'level',
        'training_service',
        'schedules',
        'projects',
        'prerequisites',
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
        $course = $this->object;
        if (!$course instanceof TrainingCourse) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if (in_array('schedules', $relations) && !isset($values['schedules'])) {
            $schedules = [];
            foreach ($course->getSchedules() as $s) {
                $schedules[] = $s->getId();
            }
            $values['schedules'] = $schedules;
        }

        if (in_array('projects', $relations) && !isset($values['projects'])) {
            $projects = [];
            foreach ($course->getProjects() as $p) {
                $projects[] = $p->getId();
            }
            $values['projects'] = $projects;
        }

        if (in_array('prerequisites', $relations) && !isset($values['prerequisites'])) {
            $prerequisites = [];
            foreach ($course->getPrerequisites() as $p) {
                $prerequisites[] = $p->getId();
            }
            $values['prerequisites'] = $prerequisites;
        }

        return $values;
    }

    protected static $expand_mappings = [
        'type' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'type_id',
            'getter' => 'getType',
            'has' => 'hasType',
        ],
        'level' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'level_id',
            'getter' => 'getLevel',
            'has' => 'hasLevel',
        ],
        'training_service' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'training_service_id',
            'getter' => 'getTrainingService',
            'has' => 'hasTrainingService',
        ],
        'schedules' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getSchedules',
        ],
        'projects' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getProjects',
        ],
        'prerequisites' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getPrerequisites',
        ],
    ];
}
