<?php namespace App\ModelSerializers;
/*
 * Copyright 2024 OpenStack Foundation
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

use App\Models\Foundation\UserStories\UserStory;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
use ModelSerializers\SilverStripeSerializer;

/**
 * Class UserStorySerializer
 * @package App\ModelSerializers
 */
class UserStorySerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name'              => 'name:json_string',
        'Description'       => 'description:json_string',
        'ShortDescription'  => 'short_description:json_string',
        'Link'              => 'link:json_string',
        'Active'            => 'active:json_boolean',
        'MillionCoreClub'   => 'is_million_core_club:json_boolean',
        'OrganizationId'    => 'organization_id:json_int',
        'IndustryId'        => 'industry_id:json_int',
        'LocationId'        => 'location_id:json_int',
        'ImageId'           => 'image_id:json_int',
    ];

    protected static $allowed_relations = [
        'tags'
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
        $user_story = $this->object;
        if(!$user_story instanceof UserStory) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('tags', $relations) && !isset($values['tags'])) {
            $tags = [];
            foreach ($user_story->getTags() as $tag) {
                $tags[] = $tag->getId();
            }
            $values['tags'] = $tags;
        }

        return $values;
    }

    protected static $expand_mappings = [
        'organization' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'organization_id',
            'getter' => 'getOrganization',
            'has' => 'hasOrganization'
        ],
        'industry' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'industry_id',
            'getter' => 'getIndustry',
            'has' => 'hasIndustry'
        ],
        'location' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'location_id',
            'getter' => 'getLocation',
            'has' => 'hasLocation'
        ],
        'image' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'image_id',
            'getter' => 'getImage',
            'has' => 'hasImage'
        ],
        'tags' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getTags',
        ],
    ];
}