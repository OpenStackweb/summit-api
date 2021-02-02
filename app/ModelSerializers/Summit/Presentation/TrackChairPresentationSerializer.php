<?php namespace ModelSerializers;
/**
 * Copyright 2021 OpenStack Foundation
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
use models\summit\Presentation;
/**
 * Class TrackChairPresentationSerializer
 * @package ModelSerializers
 */
class TrackChairPresentationSerializer extends AdminPresentationSerializer
{

    protected static $array_mappings = [
        'GroupSelected' => 'is_group_selected:json_bool',
        'ViewsCount'  => 'views_count:json_int',
        'CommentsCount' => 'comments_count:json_int',
        'PopularityScore' => 'popularity_score:json_float',
        'VotesCount'      => 'votes_count:json_int',
        'VotesAverage' => 'votes_average:json_float',
        'VotesTotalPoints' => 'votes_total_points:json_int',
    ];

    protected static $allowed_fields = [
        'is_group_selected',
        'views_count',
        'comments_count',
        'popularity_score',
        'votes_count',
        'votes_average',
        'votes_total_points',
    ];

    protected static $allowed_relations = [
        'slides',
        'media_uploads',
        'videos',
        'speakers',
        'links',
        'extra_questions',
        'public_comments',
        'selectors',
        'likers',
        'passers',
        'comments',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array() )
    {
        if(!count($relations)) $relations = $this->getAllowedRelations();

        $presentation = $this->object;

        if(!$presentation instanceof Presentation) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);


        if(in_array('selectors', $relations))
        {
            $selectors = [];
            foreach ($presentation->getSelectors() as $m) {
                $selectors[] = $m->getId();
            }
            $values['selectors'] = $selectors;
        }

        if(in_array('likers', $relations))
        {
            $likers = [];
            foreach ($presentation->getLikers() as $m) {
                $likers[] = $m->getId();
            }
            $values['likers'] = $likers;
        }

        if(in_array('passers', $relations))
        {
            $passers = [];
            foreach ($presentation->getPassers() as $m) {
                $passers[] = $m->getId();
            }
            $values['passers'] = $passers;
        }

        if(in_array('comments', $relations))
        {
            $comments = [];
            foreach ($presentation->getComments() as $comment) {
                $comments[] = $comment->getId();
            }
            $values['comments'] = $comments;
        }

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'selectors': {
                        $selectors = [];
                        foreach ($presentation->getSelectors() as $m) {
                            $selectors[] = SerializerRegistry::getInstance()->getSerializer($m)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['selectors'] = $selectors;
                    }
                        break;
                    case 'likers': {
                        $likers = [];
                        foreach ($presentation->getLikers() as $m) {
                            $likers[] = SerializerRegistry::getInstance()->getSerializer($m)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['likers'] = $likers;
                    }
                        break;
                    case 'passers': {
                        $passers = [];
                        foreach ($presentation->getPassers() as $m) {
                            $passers[] = SerializerRegistry::getInstance()->getSerializer($m)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['passers'] = $passers;
                    }
                        break;
                    case 'comments':{
                        $comments = [];
                        foreach ($presentation->getComments() as $comment) {
                            $comments[] = SerializerRegistry::getInstance()->getSerializer($comment)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['comments'] = $comments;
                    }
                        break;
                }
            }
        }
        return $values;
    }
}