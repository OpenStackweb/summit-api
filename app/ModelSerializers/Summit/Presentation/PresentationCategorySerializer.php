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

use Libs\ModelSerializers\AbstractSerializer;
use models\summit\PresentationCategory;

/**
 * Class PresentationCategorySerializer
 * @package ModelSerializers
 */
final class PresentationCategorySerializer extends SilverStripeSerializer
{
    protected static $array_mappings =
        [
            'Title' => 'name:json_string',
            'Description' => 'description:json_string',
            'Code' => 'code:json_string',
            'Slug' => 'slug:json_string',
            'SessionCount' => 'session_count:json_int',
            'AlternateCount' => 'alternate_count:json_int',
            'LightningCount' => 'lightning_count:json_int',
            'LightningAlternateCount' => 'lightning_alternate_count:json_int',
            'VotingVisible' => 'voting_visible:json_boolean',
            'ChairVisible' => 'chair_visible:json_boolean',
            'SummitId' => 'summit_id:json_int',
            'Color' => 'color:json_color',
            'TextColor' => 'text_color:json_color',
            'IconUrl' => 'icon_url:json_url',
            'Order' => 'order:json_int',
            'ProposedScheduleTransitionTime' => 'proposed_schedule_transition_time:json_int',
            'ParentId' => 'parent_id:json_int',
        ];

    protected static $allowed_relations = [
        'track_groups',
        'allowed_tags',
        'extra_questions',
        'selection_lists',
        'allowed_access_levels',
        'proposed_schedule_allowed_locations',
        'subtracks',
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
        $category = $this->object;
        if (!$category instanceof PresentationCategory) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);
        $summit = $category->getSummit();

        if(in_array('track_groups', $relations)) {
            $groups = [];
            foreach ($category->getGroups() as $group) {
                $groups[] = intval($group->getId());
            }
            $values['track_groups'] = $groups;
        }

        if(in_array('allowed_tags', $relations)) {
            $allowed_tag = [];
            foreach ($category->getAllowedTags() as $tag) {
                $allowed_tag[] = $tag->getId();
            }
            $values['allowed_tags'] = $allowed_tag;
        }

        if(in_array('extra_questions', $relations)) {
            $extra_questions = [];
            foreach ($category->getExtraQuestions() as $question) {
                $extra_questions[] = intval($question->getId());
            }
            $values['extra_questions'] = $extra_questions;
        }

        if(in_array('selection_lists', $relations)) {
            $selection_lists = [];
            foreach ($category->getSelectionLists() as $list) {
                $selection_lists[] = intval($list->getId());
            }
            $values['selection_lists'] = $selection_lists;
        }

        if(in_array('allowed_access_levels', $relations)) {
            $allowed_access_levels = [];
            foreach ($category->getAllowedAccessLevels() as $access_level) {
                $allowed_access_levels[] = intval($access_level->getId());
            }

            $values['allowed_access_levels'] = $allowed_access_levels;
        }

        if(in_array('proposed_schedule_allowed_locations', $relations)) {
            $proposed_schedule_allowed_locations = [];
            foreach ($category->getProposedScheduleAllowedLocations() as $allowed_location) {
                $proposed_schedule_allowed_locations[] = intval($allowed_location->getId());
            }
            $values['proposed_schedule_allowed_locations'] = $proposed_schedule_allowed_locations;
        }

        if(in_array('subtracks', $relations)) {
            $subtracks = [];
            foreach ($category->getSubTracks() as $children) {
                $subtracks[] = intval($children->getId());
            }
            $values['subtracks'] = $subtracks;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'track_groups':
                        {
                            $groups = [];
                            unset($values['track_groups']);
                            foreach ($category->getGroups() as $g) {
                                $groups[] = SerializerRegistry::getInstance()->getSerializer($g)->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                            $values['track_groups'] = $groups;
                        }
                        break;

                    case 'allowed_tags':
                        {
                            $allowed_tags = [];
                            unset($values['allowed_tags']);
                            foreach ($category->getAllowedTags() as $tag) {
                                $allowed_tag = SerializerRegistry::getInstance()->getSerializer($tag)->serialize(null, [], ['none']);
                                $track_tag_group = $summit->getTrackTagGroupForTag($tag);
                                if (!is_null($track_tag_group)) {
                                    $allowed_tag['track_tag_group'] = SerializerRegistry::getInstance()->getSerializer($track_tag_group)->serialize(null, [], ['none']);
                                }
                                $allowed_tags[] = $allowed_tag;
                            }
                            $values['allowed_tags'] = $allowed_tags;
                        }
                        break;
                    case 'allowed_access_levels':
                        {
                            $allowed_access_levels = [];
                            unset($values['allowed_access_levels']);
                            foreach ($category->getAllowedAccessLevels() as $access_level) {
                                $allowed_access_levels[] = SerializerRegistry::getInstance()
                                    ->getSerializer($access_level)->serialize
                                    (
                                        AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                        $params
                                    );
                            }
                            $values['allowed_access_levels'] = $allowed_access_levels;
                        }
                        break;
                    case 'extra_questions':
                        {
                            $extra_questions = [];
                            unset($values['extra_questions']);
                            foreach ($category->getExtraQuestions() as $question) {
                                $extra_questions[] = SerializerRegistry::getInstance()
                                    ->getSerializer($question)->serialize
                                    (
                                        AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                        $params
                                    );
                            }
                            $values['extra_questions'] = $extra_questions;
                        }
                        break;
                    case 'proposed_schedule_allowed_locations':
                        {
                            $proposed_schedule_allowed_locations = [];
                            unset($values['proposed_schedule_allowed_locations']);
                            foreach ($category->getProposedScheduleAllowedLocations() as $allowed_location) {
                                $proposed_schedule_allowed_locations[] = SerializerRegistry::getInstance()->getSerializer($allowed_location)->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                            $values['proposed_schedule_allowed_locations'] = $proposed_schedule_allowed_locations;
                        }
                        break;
                    case 'parent':{
                        if($category->hasParent()) {
                            unset($values['parent_id']);
                            $values['parent'] = SerializerRegistry::getInstance()->getSerializer($category->getParent())->serialize
                            (
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                $params
                            );
                        }
                    }
                    break;
                    case 'subtracks':
                        {
                            $subtracks = [];
                            unset($values['subtracks']);
                            foreach ($category->getSubTracks() as $children) {
                                $subtracks[] = SerializerRegistry::getInstance()->getSerializer($children)->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                            $values['subtracks'] = $subtracks;
                        }
                        break;
                }

            }
        }

        return $values;
    }
}